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

<div x-data="{ legalModal: null }">
    <x-brevare.auth-card
        title="Crea tu cuenta"
        subtitle="Únete a Brevare y compra con los mejores precios del mercado."
    >
        <x-brevare.google-button label="Regístrate con Google" />

        <x-brevare.auth-divider />

        <form wire:submit="register" class="space-y-5" novalidate>
            <x-brevare.floating-input
                wire:model="name"
                id="name"
                label="Nombre completo"
                type="text"
                icon="user"
                autocomplete="name"
                required
            />

            <x-brevare.floating-input
                wire:model="email"
                id="email"
                label="Correo electrónico"
                type="email"
                icon="mail"
                autocomplete="username"
                required
            />

            <x-brevare.password-input
                wire:model="password"
                id="password"
                label="Contraseña"
                strength
                autocomplete="new-password"
                required
            />

            <x-brevare.password-input
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
                    <button type="button" @click="legalModal = 'terms'" class="font-semibold text-gray-900 underline-offset-4 hover:underline">Términos y Condiciones</button>
                    y la
                    <button type="button" @click="legalModal = 'privacy'" class="font-semibold text-gray-900 underline-offset-4 hover:underline">Política de Privacidad</button>
                    de Brevare.
                </span>
            </label>

            <x-brevare.button class="w-full">
                Crear cuenta
            </x-brevare.button>
        </form>

        <x-slot:footer>
            <p class="text-center text-sm text-gray-500">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" wire:navigate class="font-semibold text-gray-900 underline-offset-4 transition hover:underline">
                    Inicia sesión
                </a>
            </p>
        </x-slot:footer>
    </x-brevare.auth-card>

    {{-- Modal legal: Términos y Privacidad --}}
    <div
        x-show="legalModal"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="legalModal = null"
        class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/50 p-0 sm:items-center sm:p-6"
        role="dialog"
        aria-modal="true"
    >
        <div
            @click.outside="legalModal = null"
            x-transition
            class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl"
        >
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900" x-text="legalModal === 'terms' ? 'Términos y Condiciones' : 'Política de Privacidad'"></h2>
                <button type="button" @click="legalModal = null" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-900" aria-label="Cerrar">
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-6">
                <div x-show="legalModal === 'terms'">
                    @include('partials.legal.terms')
                </div>
                <div x-show="legalModal === 'privacy'">
                    @include('partials.legal.privacy')
                </div>
            </div>

            <div class="border-t border-gray-100 px-6 py-4">
                <x-brevare.button type="button" @click="legalModal = null" class="w-full">
                    Entendido
                </x-brevare.button>
            </div>
        </div>
    </div>
</div>