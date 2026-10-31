<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response
            ->assertSeeVolt('pages.auth.verify-email')
            ->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('home'));
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_el_codigo_verificado_vuelve_al_checkout_pendiente(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $code = app(EmailVerificationService::class)->sendCode($user, force: true);

        session([
            'pending_email_verification_user_id' => $user->id,
            'url.intended' => route('checkout'),
        ]);

        Volt::test('pages.auth.verify-email')
            ->set('code', $code)
            ->call('verify')
            ->assertRedirect(route('checkout', absolute: false));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
    }

    public function test_la_verificacion_ignora_el_endpoint_interno_de_livewire_como_destino(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $code = app(EmailVerificationService::class)->sendCode($user, force: true);

        session([
            'pending_email_verification_user_id' => $user->id,
            'url.intended' => '/livewire/update',
        ]);

        Volt::test('pages.auth.verify-email')
            ->set('code', $code)
            ->call('verify')
            ->assertRedirect(route('home', absolute: false));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
    }
}
