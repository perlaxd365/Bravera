<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-brevare.auth-card
        title="Confirma tu contraseña"
        subtitle="Esta es un área segura. Ingresa tu contraseña para continuar."
        icon="shield-check"
    >
        <form wire:submit="confirmPassword" class="space-y-5" novalidate>
            <x-brevare.password-input
                wire:model="password"
                id="password"
                label="Contraseña"
                autocomplete="current-password"
                required
            />

            <x-brevare.button class="w-full">
                Confirmar contraseña
            </x-brevare.button>
        </form>

        <x-slot:footer>
            <p class="text-center text-sm text-gray-500">
                <a href="{{ route('home') }}" wire:navigate class="font-semibold text-gray-900 underline-offset-4 transition hover:underline">
                    Ir a la tienda
                </a>
            </p>
        </x-slot:footer>
    </x-brevare.auth-card>
</div>