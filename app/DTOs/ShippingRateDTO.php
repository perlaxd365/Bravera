<?php

namespace App\DTOs;

class ShippingRateDTO
{
    public function __construct(
        public readonly int $shipping_zone_id,
        public readonly int $supplier_id,
        public readonly ?int $product_id,
        public readonly ?int $product_variant_id,
        public readonly float $price,
        public readonly ?int $delivery_days = null,
        public readonly bool $status = true,
    ) {}

    public function toArray(): array
    {
        return [
            'shipping_zone_id' => $this->shipping_zone_id,
            'supplier_id' => $this->supplier_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'price' => $this->price,
            'delivery_days' => $this->delivery_days,
            'status' => $this->status,
        ];
    }
}
