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

        $user = Auth::user();

        if ($user && ! $user->hasVerifiedEmail()) {
            Session::put('pending_email_verification_user_id', $user->id);
            Auth::logout();
            Session::regenerate();

            $this->redirectRoute('verification.notice', navigate: true);

            return;
        }

        Session::regenerate();

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

<div class="mx-auto w-full min-w-0 max-w-md">
    @php
        $reviewLogin = config('brevare.review_login');
    @endphp

    @if ($reviewLogin['enabled'] ?? false)
        <div class="auth-rise mb-5 w-full rounded-3xl border-2 border-amber-400 bg-amber-50 p-5 text-center shadow-xl shadow-amber-500/10 sm:p-6">
            <p class="text-base font-extrabold uppercase tracking-wide text-amber-900 sm:text-lg">
                ¿Vas a hacer pruebas de compra?
            </p>

            <p class="mt-2 text-sm leading-relaxed text-amber-900 sm:text-base">
                Entra con esta cuenta de prueba para revisar el proceso de compra completo
                <strong>sin restricciones</strong>.
            </p>

            <dl class="mt-4 space-y-2 rounded-2xl bg-white px-4 py-3 text-left shadow-sm sm:px-5 sm:py-4">
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Usuario</dt>
                    <dd class="select-all break-all font-mono text-base font-bold text-gray-900 sm:text-lg">
                        {{ $reviewLogin['email'] }}
                    </dd>
                </div>
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Contraseña</dt>
                    <dd class="select-all break-all font-mono text-base font-bold text-gray-900 sm:text-lg">
                        {{ $reviewLogin['password'] }}
                    </dd>
                </div>
            </dl>
        </div>
    @endif

    <x-brevare.auth-card
        title="¡Hola, bienvenido de nuevo!"
        subtitle="Ingresa con tu cuenta para seguir comprando en Brevare."
    >
        <x-brevare.google-button />

        <x-brevare.auth-divider />

        <form wire:submit="login" class="space-y-5" novalidate>
            @if (session('status'))
                <div class="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <flux:icon name="check" class="mt-0.5 size-4 shrink-0" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <x-brevare.floating-input
                wire:model="form.email"
                id="email"
                label="Correo electrónico"
                type="email"
                icon="mail"
                autocomplete="username"
                required
            />

            <x-brevare.password-input
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

            <x-brevare.button class="w-full">
                Iniciar sesión
            </x-brevare.button>
        </form>

        <x-slot:footer>
            <p class="text-center text-sm text-gray-500">
                ¿Aún no tienes cuenta?
                <a href="{{ route('register') }}" wire:navigate class="font-semibold text-gray-900 underline-offset-4 transition hover:underline">
                    Crea una gratis
                </a>
            </p>
        </x-slot:footer>
    </x-brevare.auth-card>
</div>
