<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink($this->only('email'));

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __('password.sent'));
    }
}; ?>

<div>
    <x-bravera.auth-card
        title="¿Olvidaste tu contraseña?"
        subtitle="Sin problema. Escríbenos tu correo y te enviaremos un enlace para crear una nueva."
        icon="lock"
    >
        @if (session('status'))
            <div class="auth-rise flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" style="animation-delay: 60ms">
                <flux:icon name="check" class="mt-0.5 size-4 shrink-0" />
                <span>Si el correo está registrado, recibirás en unos minutos un enlace para restablecer tu contraseña.</span>
            </div>
        @endif

        <form wire:submit="sendPasswordResetLink" class="space-y-5" novalidate>
            <x-bravera.floating-input
                wire:model="email"
                id="email"
                label="Correo electrónico"
                type="email"
                icon="mail"
                autocomplete="username"
                required
            />

            <x-bravera.button class="w-full">
                Enviar enlace de recuperación
            </x-bravera.button>
        </form>

        <x-slot:footer>
            <p class="text-center text-sm text-gray-500">
                ¿Recordaste tu contraseña?
                <a href="{{ route('login') }}" wire:navigate class="font-semibold text-gray-900 underline-offset-4 transition hover:underline">
                    Inicia sesión
                </a>
            </p>
        </x-slot:footer>
    </x-bravera.auth-card>
</div>