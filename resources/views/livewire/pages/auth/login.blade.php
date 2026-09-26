<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = Auth::user();

        if ($user && ! $user->hasVerifiedEmail()) {
            $this->redirectRoute('verification.notice', navigate: true);

            return;
        }

        $this->redirectIntended(
            default: $this->redirectAfterLogin(),
            navigate: true,
        );
    }

    /**
     * Personal según el rol: administrativo va al panel, cliente a la tienda.
     */
    private function redirectAfterLogin(): string
    {
        $user = Auth::user();

        if ($user && $user->hasAnyRole([
            'Super Admin',
            'Administrador',
            'Operador',
            'Marketing',
            'Atención al Cliente',
        ])) {
            return route('admin.dashboard');
        }

        return route('home');
    }
}; ?>

<div>
    <x-bravera.auth-card
        title="¡Hola, bienvenido de nuevo!"
        subtitle="Ingresa con tu cuenta para seguir comprando en Bravera."
    >
        <x-bravera.google-button />

        <x-bravera.auth-divider />

        <form wire:submit="login" class="space-y-5" novalidate>
            @if (session('status'))
                <div class="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <flux:icon name="check" class="mt-0.5 size-4 shrink-0" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <x-bravera.floating-input
                wire:model="form.email"
                id="email"
                label="Correo electrónico"
                type="email"
                icon="mail"
                autocomplete="username"
                required
            />

            <x-bravera.password-input
                wire:model="form.password"
                id="password"
                label="Contraseña"
                autocomplete="current-password"
                required
            />

            <div class="flex items-center justify-between">
                <label for="remember" class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                    <span class="relative inline-flex">
                        <input
                            wire:model="form.remember"
                            id="remember"
                            type="checkbox"
                            class="size-4 rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900/20"
                        >
                    </span>
                    Recuérdame
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-semibold text-gray-900 underline-offset-4 transition hover:underline">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

            <x-bravera.button class="w-full">
                Iniciar sesión
            </x-bravera.button>
        </form>

        <x-slot:footer>
            <p class="text-center text-sm text-gray-500">
                ¿Aún no tienes cuenta?
                <a href="{{ route('register') }}" wire:navigate class="font-semibold text-gray-900 underline-offset-4 transition hover:underline">
                    Crea una gratis
                </a>
            </p>
        </x-slot:footer>
    </x-bravera.auth-card>
</div>