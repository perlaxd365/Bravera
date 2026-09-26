<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envía correos reintentando cuando la pasarela SMTP los rechaza por límite
 * de tasa (Mailtrap free, SendGrid, etc.), en lugar de perder el envío.
 */
class ReliableMailer
{
    /**
     * @param  array<int, string>  $recipients
     * @param  array<string, mixed>  $context
     */
    public static function send(
        array $recipients,
        Mailable $mailable,
        string $description = 'correo',
        array $context = [],
        int $attempts = 3,
    ): bool {
        $recipients = array_values(array_filter($recipients));

        if ($recipients === []) {
            return false;
        }

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                EmailRateLimiter::beforeSend();

                Mail::to($recipients)->send($mailable);

                return true;
            } catch (Throwable $e) {
                if (! static::isRateLimited($e) || $attempt === $attempts) {
                    Log::warning("No se pudo enviar el {$description}", $context + [
                        'intento' => $attempt,
                        'error' => $e->getMessage(),
                    ]);

                    return false;
                }

                Log::info("Reintentando el {$description} por límite de tasa", $context + [
                    'intento' => $attempt,
                ]);

                sleep(2 * $attempt);
            }
        }

        return false;
    }

    /**
     * Detecta los rechazos por límite de tasa de las pasarelas SMTP.
     */
    public static function isRateLimited(Throwable $e): bool
    {
        $message = $e->getMessage();

        foreach (['Too many emails', 'Rate limit', '450 4.7.1', '421 4.7'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
