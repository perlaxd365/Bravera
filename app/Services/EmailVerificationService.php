<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Genera, envía y valida el código de verificación por correo.
 */
class EmailVerificationService
{
    /**
     * Segundos que deben pasar antes de reenviar el código.
     */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Minutos que dura el código antes de expirar.
     */
    public const CODE_TTL_MINUTES = 10;

    /**
     * Segundos restantes para poder reenviar el código.
     */
    public function secondsUntilCanResend(User $user): int
    {
        $sentAt = $user->email_verification_code_sent_at;

        if (! $sentAt) {
            return 0;
        }

        $elapsed = (int) (now()->timestamp - $sentAt->timestamp);

        return max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);
    }

    /**
     * Envía el código al correo del usuario.
     *
     * Un fallo del SMTP (p. ej. límite de envíos por segundo) no debe romper
     * la operación en curso. El error se registra y se continúa.
     */
    private function sendCodeSafely(User $user, string $code): void
    {
        try {
            EmailRateLimiter::beforeSend();

            $user->notify(new VerificationCodeNotification($code));
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el código de verificación', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envía (o reenvía) el código de verificación al correo del usuario.
     *
     * @throws ValidationException si se intenta reenviar antes del cooldown.
     */
    public function sendCode(User $user, bool $force = false): string
    {
        if ($user->hasVerifiedEmail()) {
            return '';
        }

        if (! $force && ($wait = $this->secondsUntilCanResend($user)) > 0) {
            throw ValidationException::withMessages([
                'verification_code' => "Debes esperar {$wait} segundos antes de solicitar otro código.",
            ]);
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'email_verification_code_sent_at' => now(),
        ])->save();

        $this->sendCodeSafely($user, $code);

        return $code;
    }

    /**
     * Valida el código ingresado y marca el correo como verificado.
     */
    public function verify(User $user, string $input): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        $code = $user->email_verification_code;
        $expiresAt = $user->email_verification_code_expires_at;

        if (! $code || ! $expiresAt || now()->gt($expiresAt)) {
            return false;
        }

        if ((string) $input === '' || ! Hash::check($input, $code)) {
            return false;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
            'email_verification_code_sent_at' => null,
        ])->save();

        return true;
    }

    /**
     * Envía un código de 6 dígitos para operaciones sensibles (p. ej. cambiar
     * la contraseña), sin exigir que el correo esté sin verificar.
     *
     * @throws ValidationException si se intenta reenviar antes del cooldown.
     */
    public function sendPasswordCode(User $user, bool $force = false): string
    {
        if (! $force && ($wait = $this->secondsUntilCanResend($user)) > 0) {
            throw ValidationException::withMessages([
                'code' => "Debes esperar {$wait} segundos antes de solicitar otro código.",
            ]);
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'email_verification_code_sent_at' => now(),
        ])->save();

        $this->sendCodeSafely($user, $code);

        return $code;
    }

    /**
     * Valida un código de verificación sin alterar el estado de verificación
     * del correo. En caso de éxito, limpia los campos del código.
     */
    public function verifyPasswordCode(User $user, string $input): bool
    {
        $code = $user->email_verification_code;
        $expiresAt = $user->email_verification_code_expires_at;

        if (! $code || ! $expiresAt || now()->gt($expiresAt)) {
            return false;
        }

        if ((string) $input === '' || ! Hash::check($input, $code)) {
            return false;
        }

        $user->forceFill([
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
            'email_verification_code_sent_at' => null,
        ])->save();

        return true;
    }
}
