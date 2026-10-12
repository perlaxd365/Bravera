<?php

namespace App\Enums;

/**
 * Ciclo de vida de una devolución.
 *
 * Una devolución nace `pending`: es una solicitud que todavía no toca la
 * pasarela. Solo cuando un administrador la valida en el panel se envía a
 * Culqi y pasa a `processing` (la pasarela la dejó en trámite) o `completed`
 * (devolución confirmada). `rejected` es la decisión del administrador y
 * `failed` el rechazo o error de la pasarela.
 */
enum RefundStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente de validación',
            self::PROCESSING => 'En proceso',
            self::COMPLETED => 'Devuelto',
            self::REJECTED => 'Rechazada',
            self::FAILED => 'Falló en la pasarela',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::REJECTED => 'neutral',
            self::FAILED => 'danger',
        };
    }

    /**
     * Devuelve si la devolución ya consumió saldo del pago, aunque la
     * pasarela todavía no la haya confirmado.
     */
    public function holdsFunds(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING, self::COMPLETED], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
