<?php

namespace App\Modules\Product\DTO;

use App\Models\SupplierVariant;

readonly class SupplierVariantData
{
    public function __construct(
        public ?int $id,
        public int $supplier_id,
        public int $product_variant_id,
        public ?string $supplier_sku,
        public ?string $supplier_product_url,
        public float $cost_price,
        public float $shipping_cost,
        public ?float $supplier_sale_price,
        public int $stock,
        public int $reserved_stock,
        public int $minimum_stock,
        public ?array $extra_data,
        public int $estimated_dispatch_days,
        public ?string $last_sync_at,
        public bool $is_default,
        public bool $is_active,
        public ?string $internal_notes,
    ) {}

    /**
     * Crear DTO desde un array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,

            supplier_id: (int) $data['supplier_id'],

            product_variant_id: (int) $data['product_variant_id'],

            supplier_sku: isset($data['supplier_sku'])
                ? trim($data['supplier_sku'])
                : null,

            supplier_product_url: isset($data['supplier_product_url'])
                ? trim($data['supplier_product_url'])
                : null,

            cost_price: (float) ($data['cost_price'] ?? 0),

            shipping_cost: (float) ($data['shipping_cost'] ?? 0),

            supplier_sale_price: isset($data['supplier_sale_price'])
                ? (float) $data['supplier_sale_price']
                : null,

            stock: (int) ($data['stock'] ?? 0),

            reserved_stock: (int) ($data['reserved_stock'] ?? 0),

            minimum_stock: (int) ($data['minimum_stock'] ?? 0),

            extra_data: $data['extra_data'] ?? null,

            estimated_dispatch_days: (int) (
                $data['estimated_dispatch_days'] ?? 1
            ),

            last_sync_at: $data['last_sync_at'] ?? null,

            is_default: (bool) ($data['is_default'] ?? false),

            is_active: (bool) ($data['is_active'] ?? true),

            internal_notes: $data['internal_notes'] ?? null,
        );
    }

    /**
     * Crear DTO desde un modelo.
     */
    public static function fromModel(SupplierVariant $supplierVariant): self
    {
        return new self(
            id: $supplierVariant->id,
            supplier_id: $supplierVariant->supplier_id,
            product_variant_id: $supplierVariant->product_variant_id,
            supplier_sku: $supplierVariant->supplier_sku,
            supplier_product_url: $supplierVariant->supplier_product_url,
            cost_price: (float) $supplierVariant->cost_price,
            shipping_cost: (float) $supplierVariant->shipping_cost,
            supplier_sale_price: $supplierVariant->supplier_sale_price !== null
                ? (float) $supplierVariant->supplier_sale_price
                : null,
            stock: (int) $supplierVariant->stock,
            reserved_stock: (int) $supplierVariant->reserved_stock,
            minimum_stock: (int) $supplierVariant->minimum_stock,
            extra_data: $supplierVariant->extra_data,
            estimated_dispatch_days: (int) $supplierVariant->estimated_dispatch_days,
            last_sync_at: $supplierVariant->last_sync_at?->toDateTimeString(),
            is_default: (bool) $supplierVariant->is_default,
            is_active: (bool) $supplierVariant->is_active,
            internal_notes: $supplierVariant->internal_notes,
        );
    }

    /**
     * Convertir el DTO a array.
     */
    public function toArray(): array
    {
        return [
            'supplier_id' => $this->supplier_id,
            'product_variant_id' => $this->product_variant_id,
            'supplier_sku' => $this->supplier_sku,
            'supplier_product_url' => $this->supplier_product_url,
            'cost_price' => $this->cost_price,
            'shipping_cost' => $this->shipping_cost,
            'supplier_sale_price' => $this->supplier_sale_price,
            'stock' => $this->stock,
            'reserved_stock' => $this->reserved_stock,
            'minimum_stock' => $this->minimum_stock,
            'extra_data' => $this->extra_data,
            'estimated_dispatch_days' => $this->estimated_dispatch_days,
            'last_sync_at' => $this->last_sync_at,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'internal_notes' => $this->internal_notes,
        ];
    }
}
