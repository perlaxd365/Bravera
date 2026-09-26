<?php

namespace App\Http\Controllers\Auth;

use App\Events\CustomerRegistered;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as OAuthUser;

class GoogleController extends Controller
{
    /**
     * Redirige al usuario al consentimiento de Google.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Procesa la respuesta de Google y autentica (o crea) al cliente.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => 'No pudimos iniciar sesión con Google. Inténtalo nuevamente.']);
        }

        if (blank($googleUser->getEmail())) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google no compartió un correo asociado a tu cuenta.']);
        }

        $user = $this->resolveExistingUser($googleUser);

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Usuario de Google',
                'email' => strtolower($googleUser->getEmail()),
                'password' => null,
                'avatar' => $googleUser->getAvatar(),
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                // Google ya validó el correo durante el login OAuth.
                'email_verified_at' => now(),
            ]);

            $user->assignCustomerRole();

            event(new CustomerRegistered($user));
        }

        Auth::login($user);

        $user->registerLogin();

        // Los usuarios nuevos vía Google aún no tienen contraseña en Bravera:
        // se les pide crearla una vez (confirmándola dos veces) y quedan listos.
        if ($user->needsPasswordSetup()) {
            return redirect()->route('set-password');
        }

        return redirect()->route('home');
    }

    /**
     * Busca la cuenta Bravera correspondiente al usuario de Google:
     * primero por proveedor y luego por correo (cuentas ya registradas).
     */
    private function resolveExistingUser(OAuthUser $googleUser): ?User
    {
        $byProvider = User::query()
            ->where('provider', 'google')
            ->where('provider_id', $googleUser->getId())
            ->first();

        if ($byProvider) {
            return $byProvider;
        }

        $byEmail = User::query()
            ->where('email', strtolower($googleUser->getEmail()))
            ->first();

        if (! $byEmail) {
            return null;
        }

        $byEmail->forceFill([
            'avatar' => $googleUser->getAvatar(),
            'email_verified_at' => $byEmail->email_verified_at ?? now(),
            'provider' => 'google',
            'provider_id' => $googleUser->getId(),
        ])->save();

        return $byEmail;
    }
}
