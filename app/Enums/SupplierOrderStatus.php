<?php

namespace App\Enums;

enum SupplierOrderStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente de enviar',
            self::SENT => 'Enviada al proveedor',
            self::ACCEPTED => 'Aceptada por el proveedor',
            self::IN_TRANSIT => 'En tránsito',
            self::DELIVERED => 'Entregada',
            self::CANCELLED => 'Cancelada',
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
            self::SENT => 'info',
            self::ACCEPTED => 'primary',
            self::IN_TRANSIT => 'neutral',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
