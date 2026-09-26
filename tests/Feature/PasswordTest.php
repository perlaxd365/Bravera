<?php

namespace Tests\Feature;

use App\Livewire\Account\PasswordPanel;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_panel_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/mi-cuenta/contrasena')
            ->assertOk()
            ->assertSee('Cambiar contraseña')
            ->assertSee('Enviar código');

        $this->actingAs($user)
            ->get('/mi-cuenta/contrasena')
            ->assertOk()
            ->assertSee('Mi cuenta');
    }

    public function test_password_panel_requires_authentication(): void
    {
        $this->get('/mi-cuenta/contrasena')->assertRedirect(route('login'));
    }

    public function test_password_can_only_be_changed_after_valid_code(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Notification::fake();

        $component = Livewire::test(PasswordPanel::class);

        $component->call('requestCode')
            ->assertSet('step', 'code');

        Notification::assertSentTo($user, VerificationCodeNotification::class);
        $code = Notification::sent($user, VerificationCodeNotification::class)->first()->code;

        $component->set('code', '111111')
            ->call('verifyCode')
            ->assertHasErrors('code')
            ->assertSet('step', 'code');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $component->set('code', $code)
            ->call('verifyCode')
            ->assertHasNoErrors()
            ->assertSet('step', 'new');

        $component->set('password', 'brand-new-password')
            ->set('password_confirmation', 'brand-new-password')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('step', 'done');

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_password_cannot_be_set_without_verifying_a_code(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(PasswordPanel::class)
            ->set('password', 'brand-new-password')
            ->set('password_confirmation', 'brand-new-password')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_sending_code_respects_the_resend_cooldown(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Notification::fake();

        $component = Livewire::test(PasswordPanel::class);
        $component->call('requestCode')->assertSet('step', 'code');

        $component->call('requestCode')
            ->assertHasErrors('code')
            ->assertSet('step', 'code');

        Notification::assertSentTo($user, VerificationCodeNotification::class);
        $this->assertSame(1, Notification::sent($user, VerificationCodeNotification::class)->count());
    }

    public function test_google_user_can_create_a_password_after_code_verification(): void
    {
        $user = User::factory()->create([
            'provider' => 'google',
            'password' => null,
        ]);
        $this->actingAs($user);

        Notification::fake();

        $component = Livewire::test(PasswordPanel::class)
            ->call('requestCode')
            ->assertSet('step', 'code');

        $code = Notification::sent($user, VerificationCodeNotification::class)->first()->code;

        $component->set('code', $code)
            ->call('verifyCode')
            ->assertSet('step', 'new');

        $component->set('password', 'created-password')
            ->set('password_confirmation', 'created-password')
            ->call('updatePassword')
            ->assertSet('step', 'done');

        $this->assertTrue(Hash::check('created-password', $user->fresh()->password));
    }
}
