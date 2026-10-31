<?php

namespace Tests\Feature\Store;

use App\Mail\AbandonedCartReminder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\EmailRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class AbandonedCartRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EmailRateLimiter::reset();
    }

    public function test_command_sends_reminder_and_respects_the_three_day_window(): void
    {
        Mail::fake();

        $user = $this->customer();
        [$cart] = $this->abandonedCart($user, now()->subDays(4));

        $this->artisan('carts:send-recovery', ['--days' => 3])
            ->assertExitCode(0);

        Mail::assertSent(AbandonedCartReminder::class, fn (AbandonedCartReminder $mail) => $mail->hasTo($user->email));

        $cart->refresh();
        $this->assertNotNull($cart->recovery_token);
        $this->assertNotNull($cart->recovery_email_sent_at);
        $this->assertSame(1, $cart->recovery_email_count);

        $this->artisan('carts:send-recovery', ['--days' => 3])
            ->assertExitCode(0);
        Mail::assertSentCount(1);

        $cart->forceFill(['recovery_email_sent_at' => now()->subDays(4)])->save();

        $this->artisan('carts:send-recovery', ['--days' => 3])
            ->assertExitCode(0);

        Mail::assertSentCount(2);
        $this->assertSame(2, $cart->fresh()->recovery_email_count);
    }

    public function test_converted_cart_and_pending_order_are_not_emailed(): void
    {
        Mail::fake();

        $convertedUser = $this->customer('convertido@example.test');
        $this->abandonedCart($convertedUser, now()->subDays(4), 'converted');

        $pendingUser = $this->customer('pendiente@example.test');
        [$pendingCart] = $this->abandonedCart($pendingUser, now()->subDays(4));
        $this->pendingOrderFor($pendingCart, $pendingUser);

        $this->artisan('carts:send-recovery', ['--days' => 3])
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_user_opt_out_stops_reminders(): void
    {
        Mail::fake();

        $user = $this->customer('baja@example.test');
        $user->forceFill(['marketing_opt_out_at' => now()])->save();
        $this->abandonedCart($user, now()->subDays(4));

        $this->artisan('carts:send-recovery', ['--days' => 3])
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_recovery_link_binds_the_cart_and_requires_the_owner(): void
    {
        $user = $this->customer();
        [$cart] = $this->abandonedCart($user, now()->subDays(4));
        $cart->forceFill(['recovery_token' => Str::random(48)])->save();

        $this->actingAs($user)
            ->get(route('cart.recover', ['token' => $cart->recovery_token]))
            ->assertRedirect(route('checkout'));

        $this->assertSame($cart->id, session(CartService::SESSION_KEY));

        $other = $this->customer('otro@example.test');
        $this->actingAs($other)
            ->get(route('cart.recover', ['token' => $cart->recovery_token]))
            ->assertForbidden();
    }

    public function test_recovery_link_redirects_guests_to_login_and_preserves_destination(): void
    {
        $user = $this->customer();
        [$cart] = $this->abandonedCart($user, now()->subDays(4));
        $cart->forceFill(['recovery_token' => Str::random(48)])->save();

        $this->get(route('cart.recover', ['token' => $cart->recovery_token]))
            ->assertRedirect(route('login'));

        $this->assertSame(
            route('cart.recover', ['token' => $cart->recovery_token]),
            session('url.intended'),
        );
    }

    public function test_guest_user_recovery_link_does_not_require_a_password(): void
    {
        $guest = User::create([
            'name' => 'Invitado Recuperable',
            'email' => 'invitado-recuperable@example.test',
            'password' => bcrypt('random-password'),
            'email_verified_at' => now(),
            'is_guest' => true,
        ]);

        [$cart] = $this->abandonedCart($guest, now()->subDays(4));
        $cart->forceFill(['recovery_token' => Str::random(48)])->save();

        $this->assertGuest();
        $this->get(route('cart.recover', ['token' => $cart->recovery_token]))
            ->assertRedirect(route('checkout'));

        $this->assertSame($cart->id, session(CartService::SESSION_KEY));
    }

    public function test_signed_opt_out_link_updates_the_user(): void
    {
        $user = $this->customer();
        $url = URL::signedRoute('cart.recovery.opt-out', ['user' => $user->id]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Preferencia guardada');

        $this->assertNotNull($user->fresh()->marketing_opt_out_at);
    }

    public function test_reminder_email_renders_products_and_recovery_link(): void
    {
        $user = $this->customer('render@example.test');
        [$cart] = $this->abandonedCart($user, now()->subDays(4));
        $cart->forceFill(['recovery_token' => Str::random(48)])->save();

        $html = (new AbandonedCartReminder($cart->fresh('items')))->render();

        $this->assertStringContainsString('Producto abandonado', $html);
        $this->assertStringContainsString(
            route('cart.recover', ['token' => $cart->recovery_token]),
            $html,
        );
        $this->assertStringContainsString('No quiero recibir recordatorios', $html);
    }

    private function customer(string $email = 'cliente@example.test'): User
    {
        return User::create([
            'name' => 'Cliente Carrito',
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array{0: Cart, 1: ProductVariant}
     */
    private function abandonedCart(User $user, mixed $lastActivity, string $status = 'active'): array
    {
        $category = Category::create([
            'name' => 'Recuperación',
            'slug' => 'recuperacion-'.Str::lower(Str::random(6)),
            'path' => 'recuperacion-'.Str::lower(Str::random(6)),
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto abandonado',
            'slug' => 'producto-abandonado-'.Str::lower(Str::random(6)),
            'status' => true,
            'is_visible' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CART-'.Str::upper(Str::random(8)),
            'sale_price' => 89.90,
            'is_default' => true,
            'is_active' => true,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => $status,
            'last_activity_at' => $lastActivity,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 89.90,
            'unit_cost' => 40,
            'supplier_shipping_cost' => 0,
        ]);

        return [$cart, $variant];
    }

    private function pendingOrderFor(Cart $cart, User $user): Order
    {
        return Order::create([
            'order_number' => 'BVR-REC-'.uniqid(),
            'user_id' => $user->id,
            'cart_id' => $cart->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 179.80,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => 179.80,
            'cost_total' => 80,
            'currency' => 'PEN',
        ]);
    }
}
