<?php

namespace Tests\Feature\Store;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Store\Roulette;
use App\Livewire\Store\RouletteOverlay;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RouletteSpin;
use App\Models\User;
use App\Modules\Discounts\Services\CouponService;
use App\Modules\Payments\Services\PaymentService;
use App\Services\RouletteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RouletteTest extends TestCase
{
    use RefreshDatabase;

    public function test_roulette_has_the_three_discounts_and_blocks_a_second_spin_until_payment_is_confirmed(): void
    {
        $service = app(RouletteService::class);
        $segments = collect($service->segments());

        $this->assertCount(8, $segments);
        $this->assertTrue($segments->contains(fn ($segment) => $segment['percent'] === 15 && $segment['min'] === 100.0));
        $this->assertTrue($segments->contains(fn ($segment) => $segment['percent'] === 10 && $segment['min'] === 40.0));
        $this->assertTrue($segments->contains(fn ($segment) => $segment['percent'] === 5 && $segment['min'] === 0.0));

        $user = User::factory()->create();
        $coupon = $this->couponFor($user, 10, 40);
        $spin = RouletteSpin::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'result' => '10',
            'discount_percent' => 10,
            'min_subtotal' => 40,
            'claimed_at' => now(),
        ]);
        $order = Order::create([
            'order_number' => 'BVR-RULETA-001',
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'subtotal' => 100,
            'shipping_total' => 0,
            'discount_total' => 10,
            'total' => 90,
            'cost_total' => 0,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'currency' => 'PEN',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'manual',
            'method' => 'card',
            'amount' => 90,
            'currency' => 'PEN',
            'status' => PaymentStatus::PENDING,
        ]);

        $this->assertFalse($service->eligibility($user)['allowed']);

        app(PaymentService::class)->markAsPaid($payment);

        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);
        $this->assertTrue($service->eligibility($user->fresh())['allowed']);
        $this->assertSame($spin->id, $user->rouletteSpins()->latest('id')->value('id'));
    }

    public function test_roulette_coupon_cannot_be_used_by_another_account(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $coupon = $this->couponFor($owner, 5, 0);

        $this->expectException(ValidationException::class);

        app(CouponService::class)->validate(
            $coupon->code,
            $other,
            collect(),
            100,
        );
    }

    public function test_guest_spin_keeps_the_selected_index_and_prize_in_sync(): void
    {
        $component = Livewire::test(RouletteOverlay::class)->call('spin');
        $prize = $component->get('guestPrize');
        $segments = app(RouletteService::class)->segments();

        $this->assertNotNull($prize);
        $this->assertArrayHasKey($prize['index'], $segments);
        $this->assertSame($segments[$prize['index']]['result'], $prize['result']);
        $this->assertSame($segments[$prize['index']]['percent'], $prize['percent']);
        $component->assertDispatched(
            'roulette-result',
            index: $prize['index'],
            result: $prize['result'],
        );
    }

    public function test_guest_can_spin_again_after_a_result_without_prize(): void
    {
        $component = Livewire::test(RouletteOverlay::class)
            ->set('guestPrize', [
                'index' => 3,
                'result' => 'none',
                'label' => 'Sigue intentando',
                'percent' => 0,
                'min' => 0,
            ]);

        $component->assertSee('Volver a girar')
            ->call('spin')
            ->assertSet('error', null);

        $this->assertNotNull(Session::get('brevare_roulette_guest_spin'));
    }

    public function test_guest_with_a_prize_must_claim_it_before_spinning_again(): void
    {
        $component = Livewire::test(RouletteOverlay::class)
            ->set('guestPrize', [
                'index' => 0,
                'result' => '15',
                'label' => '15%',
                'percent' => 15,
                'min' => 100,
            ])
            ->call('spin');

        $component->assertSet('error', 'Ya giraste en esta sesión. Inicia sesión para reclamar tu resultado.');
    }

    public function test_guest_is_asked_to_sign_in_before_spinning(): void
    {
        Livewire::test(Roulette::class)
            ->assertSee('Necesitas una cuenta')
            ->call('spin')
            ->assertSet('error', 'Inicia sesión para girar y guardar tu cupón.');
    }

    public function test_guest_spin_is_kept_in_session_and_claimed_after_login(): void
    {
        $user = User::factory()->create();

        $guest = Livewire::test(RouletteOverlay::class)
            ->assertSee('GIRA Y GANA DESCUENTO GRATIS')
            ->call('spin')
            ->assertSet('error', null);

        $this->assertNotNull($guest->get('guestPrize'));
        $this->assertSame(0, RouletteSpin::count());

        $this->actingAs($user);

        Livewire::test(RouletteOverlay::class)
            ->call('claimPending')
            ->assertSet('error', null);

        $this->assertDatabaseHas('roulette_spins', ['user_id' => $user->id]);
    }

    private function couponFor(User $user, int $percent, float $minimum): Coupon
    {
        return Coupon::create([
            'code' => 'BV-TEST'.random_int(1000, 9999),
            'name' => 'Ruleta Brevare Perú',
            'type' => CouponType::PERCENTAGE,
            'value' => $percent,
            'min_subtotal' => $minimum,
            'usage_limit' => 1,
            'per_user_limit' => 1,
            'applies_to' => CouponAppliesTo::ALL,
            'is_active' => true,
            'user_id' => $user->id,
        ]);
    }
}
