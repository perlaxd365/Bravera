<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <x-bravera.auth-card
        title="Crea una nueva contraseña"
        subtitle="Elige una contraseña segura para tu cuenta de Bravera."
        icon="key"
    >
        <form wire:submit="resetPassword" class="space-y-5" novalidate>
            <x-bravera.floating-input
                wire:model="email"
                id="email"
                label="Correo electrónico"
                type="email"
                icon="mail"
                autocomplete="username"
                required
                readonly
            />

            <x-bravera.password-input
                wire:model="password"
                id="password"
                label="Nueva contraseña"
                strength
                autocomplete="new-password"
                required
            />

            <x-bravera.password-input
                wire:model="password_confirmation"
                id="password_confirmation"
                label="Confirma tu contraseña"
                autocomplete="new-password"
                required
            />

            <x-bravera.button class="w-full">
                Restablecer contraseña
            </x-bravera.button>
        </form>

        <x-slot:footer>
            <p class="text-center text-sm text-gray-500">
                <a href="{{ route('login') }}" wire:navigate class="font-semibold text-gray-900 underline-offset-4 transition hover:underline">
                    Volver a iniciar sesión
                </a>
            </p>
        </x-slot:footer>
    </x-bravera.auth-card>
</div>