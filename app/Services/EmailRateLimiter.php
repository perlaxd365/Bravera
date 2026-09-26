<?php

namespace App\Services;

/**
 * Espacia los envíos de correo dentro de una misma petición para respetar
 * el límite de la pasarela SMTP en desarrollo (p. ej. Mailtrap free permite
 * 1 email por segundo).
 */
class EmailRateLimiter
{
    /**
     * Timestamp (microtime) del último envío en esta petición.
     */
    protected static ?float $lastSendAt = null;

    /**
     * Pausa lo necesario para garantizar al menos 1 segundo entre envíos.
     */
    public static function beforeSend(): void
    {
        $now = microtime(true);

        if (static::$lastSendAt === null) {
            static::$lastSendAt = $now;

            return;
        }

        $elapsed = $now - static::$lastSendAt;

        if ($elapsed < 1) {
            usleep((int) ((1 - $elapsed) * 1_000_000));
        }

        static::$lastSendAt = microtime(true);
    }

    /**
     * Reinicia el marcador (útil en tests).
     */
    public static function reset(): void
    {
        static::$lastSendAt = null;
    }
}
