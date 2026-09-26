<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as OAuthUser;
use Livewire\Volt\Volt;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(array $attributes = []): OAuthUser
    {
        $user = new OAuthUser;

        $user->map(array_merge([
            'id' => 'google-12345',
            'nickname' => null,
            'name' => 'María Pérez',
            'email' => 'maria.perez@gmail.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ], $attributes));

        $user->token = 'fake-token';

        return $user;
    }

    private function mockGoogleDriver(OAuthUser $fakeUser): void
    {
        $driver = new class($fakeUser)
        {
            public function __construct(public OAuthUser $user) {}

            public function user(): OAuthUser
            {
                return $this->user;
            }
        };

        $socialite = new class($driver)
        {
            public function __construct(public object $driver) {}

            public function driver(string $name): object
            {
                return $this->driver;
            }
        };

        Socialite::swap($socialite);
    }

    public function test_google_callback_creates_user_linked_to_google(): void
    {
        $this->mockGoogleDriver($this->fakeGoogleUser());

        $this->get(route('auth.google.callback'))->assertRedirect(route('set-password'));

        $user = User::where('email', 'maria.perez@gmail.com')->firstOrFail();

        $this->assertSame('google', $user->provider);
        $this->assertSame('google-12345', $user->provider_id);
        $this->assertSame('https://example.com/avatar.jpg', $user->avatar);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
        $this->assertTrue($user->needsPasswordSetup());
        $this->assertTrue($user->hasRole('Cliente'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_callback_links_existing_account_without_duplicate(): void
    {
        User::create([
            'name' => 'María Pérez',
            'email' => 'maria.perez@gmail.com',
            'password' => 'ExistingPass123!',
        ]);

        $this->mockGoogleDriver($this->fakeGoogleUser());

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertSame(1, User::where('email', 'maria.perez@gmail.com')->count());

        $user = User::where('email', 'maria.perez@gmail.com')->firstOrFail();

        $this->assertSame('google', $user->provider);
        $this->assertSame('google-12345', $user->provider_id);
        $this->assertFalse($user->needsPasswordSetup());
        $this->assertAuthenticatedAs($user);
    }

    public function test_set_password_saves_password_for_google_user(): void
    {
        $user = User::create([
            'name' => 'María Pérez',
            'email' => 'maria.perez@gmail.com',
            'provider' => 'google',
            'provider_id' => 'google-12345',
        ]);

        $this->actingAs($user);

        $component = Volt::test('pages.auth.set-password')
            ->set('password', 'NewPassword123!')
            ->set('password_confirmation', 'NewPassword123!');

        $component->call('savePassword');

        $component->assertRedirect(route('home'));

        $user->refresh();

        $this->assertFalse($user->needsPasswordSetup());
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }
}
