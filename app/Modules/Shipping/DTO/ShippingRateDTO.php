<?php

namespace App\Modules\Shipping\DTO;

class ShippingRateDTO
{
    public function __construct(
        public readonly int $supplier_id,
        public readonly int $shipping_zone_id,
        public readonly ?int $product_id,
        public readonly ?int $product_variant_id,
        public readonly float $price,
        public readonly bool $status = true,
    ) {}

    /**
     * Convierte el DTO en un arreglo.
     */
    public function toArray(): array
    {
        return [
            'supplier_id' => $this->supplier_id,
            'shipping_zone_id' => $this->shipping_zone_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'price' => $this->price,
            'status' => $this->status,
        ];
    }
}
