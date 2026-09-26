<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
        };
    }

    public function badgeClass(): string
    {
        return $this->badgeColor();
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'dark',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
