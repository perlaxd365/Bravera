<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('home', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_login_returns_to_the_requested_account_page(): void
    {
        $user = User::factory()->create();

        session(['url.intended' => route('account.favorites')]);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('account.favorites'));
    }

    public function test_login_remembers_the_redirect_query_parameter(): void
    {
        $this->get('/login?redirect='.urlencode(route('account.favorites')))
            ->assertOk();

        $this->assertSame(route('account.favorites'), session('url.intended'));
    }

    public function test_login_rejects_external_redirect_parameters(): void
    {
        $this->get('/login?redirect='.urlencode('https://example.com/phishing'))
            ->assertOk();

        $this->assertSame(route('home'), session('url.intended'));
    }

    public function test_dashboard_route_returns_to_store(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get('/dashboard')->assertRedirect('/');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
