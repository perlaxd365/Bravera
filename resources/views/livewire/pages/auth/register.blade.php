<?php

use App\Events\CustomerRegistered;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $terms = false;

    /**
     * Handle an incoming registration request.
     */
    public function register(EmailVerificationService $verification): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'min:8', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignCustomerRole();

        // Evento propio: evita la verificación por enlace síncrona del framework,
        // que rompía el registro cuando el SMTP fallaba. Aquí el correo de
        // bienvenida y el de verificación viajan por la cola de forma segura.
        event(new CustomerRegistered($user));

        $verification->sendCode($user);

        Auth::login($user);

        $this->redirectRoute('verification.notice', navigate: true);
    }
}; ?>

<div>
    <x-bravera.auth-card
        title="Crea tu cuenta"
        subtitle="Únete a Bravera y compra con los mejores precios del mercado."
    >
        <x-bravera.google-button label="Regístrate con Google" />

        <x-bravera.auth-divider />

        <form wire:submit="register" class="space-y-5" novalidate>
            <x-bravera.floating-input
                wire:model="name"
                id="name"
                label="Nombre completo"
                type="text"
                icon="user"
                autocomplete="name"
                required
            />

            <x-bravera.floating-input
                wire:model="email"
                id="email"
                label="Correo electrónico"
                type="email"
                icon="mail"
                autocomplete="username"
                required
            />

            <x-bravera.password-input
                wire:model="password"
                id="password"
                label="Contraseña"
                strength
                autocomplete="new-password"
                required
            />

            <x-bravera.password-input
                wire:model="password_confirmation"
                id="password_confirmation"
                label="Confirmar contraseña"
                autocomplete="new-password"
                required
            />

            <label for="terms" class="flex cursor-pointer items-start gap-2.5 text-sm text-gray-600">
                <input
                    wire:model="terms"
                    id="terms"
                    type="checkbox"
                    class="mt-0.5 size-4 rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900/20"
                    required
                >
                <span>
                    Acepto los
                    <a href="#" class="font-semibold text-gray-900 underline-offset-4 hover:underline">Términos y condiciones</a>
                    y la
                    <a href="#" class="font-semibold text-gray-900 underline-offset-4 hover:underline">Política de privacidad</a>
                    de Bravera.
                </span>
            </label>

            <x-bravera.button class="w-full">
                Crear cuenta
            </x-bravera.button>
        </form>

        <x-slot:footer>
            <p class="text-center text-sm text-gray-500">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" wire:navigate class="font-semibold text-gray-900 underline-offset-4 transition hover:underline">
                    Inicia sesión
                </a>
            </p>
        </x-slot:footer>
    </x-bravera.auth-card>
</div>