<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::AUTHORIZED => 'Autorizado',
            self::PAID => 'Pagado',
            self::FAILED => 'Fallido',
            self::REFUNDED => 'Reembolsado',
            self::CANCELLED => 'Cancelado',
            self::EXPIRED => 'Expirado',
        };
    }

    public function badgeClass(): string
    {
        return $this->badgeColor();
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::AUTHORIZED => 'info',
            self::PAID => 'success',
            self::FAILED, self::CANCELLED, self::EXPIRED => 'danger',
            self::REFUNDED => 'dark',
        };
    }
}
