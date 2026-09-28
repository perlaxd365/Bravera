<?php

use App\Livewire\Actions\Logout;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $code = '';

    /**
     * Al llegar a la página nos aseguramos de que exista un código vigente.
     */
    public function mount(EmailVerificationService $verification): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        if (blank(Auth::user()->email_verification_code)) {
            $verification->sendCode(Auth::user(), force: true);

            Session::flash('status', 'code-sent');
        }
    }

    /**
     * Valida el código ingresado.
     */
    public function verify(EmailVerificationService $verification): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6', 'digits:6'],
        ]);

        $user = Auth::user();

        if ($verification->verify($user, $this->code)) {
            Session::flash('status', 'verified');

            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->addError('code', 'El código es incorrecto o ya expiró. Solicita uno nuevo.');
    }

    /**
     * Reenvía el código respetando la espera de seguridad.
     */
    public function resend(EmailVerificationService $verification): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        try {
            $verification->sendCode(Auth::user());
        } catch (ValidationException $e) {
            $this->reset('code');
            $this->addError('code', $e->validator->errors()->first('verification_code'));

            return;
        }

        $this->reset('code');

        Session::flash('status', 'code-resent');
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Enmascara el correo para mostrarlo con privacidad.
     */
    public function maskedEmail(): string
    {
        $email = Auth::user()->email;
        [$local, $domain] = explode('@', $email);

        $visible = substr($local, 0, 3);

        return $visible.str_repeat('•', max(2, strlen($local) - 3)).'@'.$domain;
    }

    /**
     * Segundos que faltan para poder reenviar el código.
     */
    public function cooldown(): int
    {
        return app(EmailVerificationService::class)
            ->secondsUntilCanResend(Auth::user());
    }
}; ?>

<div>
    <x-brevare.auth-card
        title="Verifica tu correo"
        subtitle="Te enviamos un código de 6 dígitos para confirmar tu cuenta."
    >
        <div class="auth-rise" style="animation-delay: 80ms">
            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-900 text-white">
                    <flux:icon name="mail" class="size-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">Código enviado a</p>
                    <p class="truncate text-sm font-semibold text-gray-900">{{ $this->maskedEmail() }}</p>
                </div>
            </div>
        </div>

        @if (session('status') === 'verified')
            <div class="auth-rise mt-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" style="animation-delay: 120ms">
                <flux:icon name="check" class="size-4 shrink-0" />
                Cuenta verificada. ¡Te estamos llevando a la tienda!
            </div>
        @elseif (in_array(session('status'), ['code-sent', 'code-resent'], true))
            <div class="auth-rise mt-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" style="animation-delay: 120ms">
                <flux:icon name="check" class="size-4 shrink-0" />
                @if (session('status') === 'code-resent')
                    Te enviamos un nuevo código. Revisa tu bandeja de entrada.
                @else
                    Código enviado. Revisa tu bandeja de entrada (y la carpeta de spam).
                @endif
            </div>
        @endif

        <div class="mt-6">
            <p class="mb-2 text-center text-sm font-semibold text-gray-900">Ingresa el código</p>

            <x-brevare.otp-input wire:model="code" :length="6" method="verify" />

            @error('code')
                <div class="auth-shake mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-600">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div
            class="mt-5 flex items-center justify-center gap-2 text-sm text-gray-500"
            x-data="{
                wait: @js($this->cooldown()),
                init() {
                    if (this.wait > 0) this.tick();
                },
                tick() {
                    clearInterval(this.timer);
                    this.timer = setInterval(() => {
                        this.wait = Math.max(0, this.wait - 1);
                        if (this.wait === 0) clearInterval(this.timer);
                    }, 1000);
                },
            }"
        >
            <flux:icon name="clock" class="size-4" />
            <span x-text="wait > 0 ? 'Reenviar código en ' + wait + 's' : '¿No te llegó el código?'"></span>
            <button
                type="button"
                wire:click="resend"
                x-on:click="wait = 60; tick();"
                x-bind:disabled="wait > 0"
                class="font-semibold text-gray-900 underline-offset-4 transition hover:underline disabled:cursor-not-allowed disabled:text-gray-400"
            >
                Reenviar código
            </button>
        </div>

        <x-slot:footer>
            <div class="space-y-3">
                <x-brevare.button type="button" wire:click="verify">
                    Verificar mi correo
                </x-brevare.button>

                <button
                    type="button"
                    wire:click="logout"
                    class="mx-auto flex items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-900"
                >
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
                    Cerrar sesión
                </button>
            </div>
        </x-slot:footer>
    </x-brevare.auth-card>
</div>