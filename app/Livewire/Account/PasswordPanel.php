<?php

namespace App\Livewire\Account;

use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.account')]
class PasswordPanel extends Component
{
    public string $step = 'idle';

    public string $code = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatedCode(string $value): void
    {
        $this->code = preg_replace('/\D/', '', $value);
        $this->code = mb_substr($this->code, 0, 6);
    }

    public function requestCode(EmailVerificationService $verification): void
    {
        if (($wait = $verification->secondsUntilCanResend(Auth::user())) > 0) {
            $this->addError('code', "Debes esperar {$wait} segundos antes de solicitar otro código.");

            return;
        }

        $verification->sendPasswordCode(Auth::user());

        $this->resetValidation('code');
        $this->step = 'code';

        $this->dispatch('password-code-sent');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Enviamos un código de 6 dígitos a tu correo.',
        ]);
    }

    public function verifyCode(EmailVerificationService $verification): void
    {
        $this->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (! $verification->verifyPasswordCode(Auth::user(), $this->code)) {
            $this->addError('code', 'El código es incorrecto o ha expirado. Solicita uno nuevo.');

            return;
        }

        $this->resetValidation();
        $this->code = '';
        $this->step = 'new';

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Código verificado. Crea tu nueva contraseña.',
        ]);
    }

    public function updatePassword(): void
    {
        if ($this->step !== 'new') {
            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->forceFill(['password' => Hash::make($this->password)])->save();

        $this->reset('code', 'password', 'password_confirmation');
        $this->step = 'done';

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Tu contraseña se actualizó correctamente.',
        ]);
    }

    public function cancel(): void
    {
        $this->reset('code', 'password', 'password_confirmation');
        $this->resetValidation();
        $this->step = 'idle';
    }

    public function render(EmailVerificationService $verification)
    {
        return view('livewire.account.password', [
            'resendSeconds' => $verification->secondsUntilCanResend(Auth::user()),
        ]);
    }
}
