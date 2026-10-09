<?php

namespace App\DTOs;

use App\Modules\Shipping\Enums\ShippingZoneType;

class ShippingZoneDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ShippingZoneType $type,
        public readonly int $location_id,
        public readonly bool $status = true,
    ) {}

    /**
     * Convierte el DTO en un arreglo.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'location_id' => $this->location_id,
            'status' => $this->status,
        ];
    }
}
