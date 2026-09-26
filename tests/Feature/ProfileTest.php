<?php

namespace Tests\Feature;

use App\Livewire\Account\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/mi-cuenta/perfil')
            ->assertOk()
            ->assertSee('Datos personales')
            ->assertSee('Contraseña');
    }

    public function test_legacy_profile_url_redirects_to_account_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertRedirect('/mi-cuenta/perfil');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertRedirect(route('verification.notice'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->set('phone', '+51 999 888 777')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertSame('Test User', $user->refresh()->name);
        $this->assertSame('+51 999 888 777', $user->phone);
        $this->assertNotNull($user->email_verified_at);
    }
}
