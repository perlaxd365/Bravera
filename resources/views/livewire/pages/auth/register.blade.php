<?php

use App\Events\CustomerRegistered;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\PointsService;
use App\Support\Auth\LoginRedirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
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

    public function mount(): void
    {
        LoginRedirect::remember(request());
    }

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

        app(PointsService::class)->awardRegistration($user);

        // Evento propio: evita la verificación por enlace síncrona del framework,
        // que rompía el registro cuando el SMTP fallaba. Aquí el correo de
        // bienvenida y el de verificación viajan por la cola de forma segura.
        event(new CustomerRegistered($user));

        $verification->sendCode($user);

        Session::regenerate();
        Session::put('pending_email_verification_user_id', $user->id);

        $this->redirectRoute('verification.notice', navigate: true);
    }
}; ?>

<div x-data="{ legalModal: null }">
    <x-brevare.auth-card
        :title="app()->getLocale() === 'en' ? 'Create your account' : 'Crea tu cuenta'"
        :subtitle="app()->getLocale() === 'en' ? 'Join Brevare and shop with confidence.' : 'Únete a Brevare y compra con los mejores precios del mercado.'"
    >
        <x-brevare.google-button :label="app()->getLocale() === 'en' ? 'Sign up with Google' : 'Regístrate con Google'" />

        <x-brevare.auth-divider />

        <form wire:submit="register" class="space-y-5" novalidate>
            <x-brevare.floating-input
                wire:model="name"
                id="name"
                :label="app()->getLocale() === 'en' ? 'Full name' : 'Nombre completo'"
                type="text"
                icon="user"
                autocomplete="name"
                required
            />

            <x-brevare.floating-input
                wire:model="email"
                id="email"
                :label="app()->getLocale() === 'en' ? 'Email address' : 'Correo electrónico'"
                type="email"
                icon="mail"
                autocomplete="username"
                required
            />

            <x-brevare.password-input
                wire:model="password"
                id="password"
                :label="app()->getLocale() === 'en' ? 'Password' : 'Contraseña'"
                strength
                autocomplete="new-password"
                required
            />

            <x-brevare.password-input
                wire:model="password_confirmation"
                id="password_confirmation"
                :label="app()->getLocale() === 'en' ? 'Confirm password' : 'Confirmar contraseña'"
                autocomplete="new-password"
                required
            />

            <label for="terms" class="flex cursor-pointer items-start gap-2.5 text-sm text-gray-600">
                <input
                    wire:model="terms"
                    id="terms"
                    type="checkbox"
                    @error('terms') aria-describedby="terms-error" @enderror
                    aria-invalid="@error('terms') true @else false @enderror"
                    class="mt-0.5 size-4 rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900/20 @error('terms') border-red-500 focus:ring-red-500 @enderror"
                    required
                >
                <span>
                    {{ app()->getLocale() === 'en' ? 'I accept the' : 'Acepto los' }}
                    <button type="button" @click="legalModal = 'terms'" class="font-semibold text-gray-900 underline-offset-4 hover:underline">{{ __('storefront.footer.terms') }}</button>
                    {{ app()->getLocale() === 'en' ? 'and' : 'y la' }}
                    <button type="button" @click="legalModal = 'privacy'" class="font-semibold text-gray-900 underline-offset-4 hover:underline">{{ __('storefront.footer.privacy') }}</button>
                    {{ app()->getLocale() === 'en' ? 'for Brevare.' : 'de Brevare.' }}
                </span>
            </label>
            @error('terms')
                <p id="terms-error" class="-mt-3 text-sm font-medium text-red-700" role="alert">Marca la casilla para aceptar los Términos y Condiciones y la Política de Privacidad.</p>
            @enderror

            <x-brevare.button class="w-full">
                {{ __('storefront.common.create_account') }}
            </x-brevare.button>
        </form>

        <x-slot:footer>
            <p class="text-center text-sm text-gray-500">
                {{ app()->getLocale() === 'en' ? 'Already have an account?' : '¿Ya tienes cuenta?' }}
                <a href="{{ route('login') }}" wire:navigate class="font-semibold text-gray-900 underline-offset-4 transition hover:underline">
                    {{ __('storefront.common.sign_in') }}
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
                <h2 class="text-base font-semibold text-gray-900" x-text="legalModal === 'terms' ? @js(__('storefront.footer.terms')) : @js(__('storefront.footer.privacy'))"></h2>
                <button type="button" @click="legalModal = null" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-900" aria-label="{{ __('storefront.common.close') }}">
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
                    {{ app()->getLocale() === 'en' ? 'Got it' : 'Entendido' }}
                </x-brevare.button>
            </div>
        </div>
    </div>
</div>
