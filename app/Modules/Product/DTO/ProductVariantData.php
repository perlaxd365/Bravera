<?php

namespace App\Modules\Product\DTO;

use App\Models\ProductVariant;

readonly class ProductVariantData
{
    public function __construct(
        public ?int $id,
        public int $product_id,
        public string $sku,
        public ?string $barcode,
        public float $cost_price,
        public float $sale_price,
        public ?float $compare_price,
        public ?float $weight,
        public ?float $length,
        public ?float $width,
        public ?float $height,
        public bool $sync_enabled,
        public bool $is_default,
        public bool $is_active,
        public array $attribute_values = [],
    ) {}

    /**
     * Crear DTO desde un array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,

            product_id: (int) $data['product_id'],

            sku: trim($data['sku']),

            barcode: isset($data['barcode'])
                ? trim($data['barcode'])
                : null,

            cost_price: (float) ($data['cost_price'] ?? 0),

            sale_price: (float) ($data['sale_price'] ?? 0),

            compare_price: isset($data['compare_price'])
                ? (float) $data['compare_price']
                : null,

            weight: isset($data['weight'])
                ? (float) $data['weight']
                : null,

            length: isset($data['length'])
                ? (float) $data['length']
                : null,

            width: isset($data['width'])
                ? (float) $data['width']
                : null,

            height: isset($data['height'])
                ? (float) $data['height']
                : null,

            sync_enabled: (bool) ($data['sync_enabled'] ?? true),

            is_default: (bool) ($data['is_default'] ?? false),

            is_active: (bool) ($data['is_active'] ?? true),

            attribute_values: $data['attribute_values'] ?? [],
        );
    }

    /**
     * Crear DTO desde un modelo.
     */
    public static function fromModel(ProductVariant $variant): self
    {
        $attributeValues = [];

        if ($variant->relationLoaded('attributeValues')) {
            $attributeValues = $variant->attributeValues
                ->mapWithKeys(function ($item) {
                    return [
                        $item->attribute_id => $item->attribute_value_id,
                    ];
                })
                ->toArray();
        }

        return new self(
            id: $variant->id,

            product_id: $variant->product_id,

            sku: $variant->sku,

            barcode: $variant->barcode,

            cost_price: (float) $variant->cost_price,

            sale_price: (float) $variant->sale_price,

            compare_price: $variant->compare_price !== null
                ? (float) $variant->compare_price
                : null,

            weight: $variant->weight !== null
                ? (float) $variant->weight
                : null,

            length: $variant->length !== null
                ? (float) $variant->length
                : null,

            width: $variant->width !== null
                ? (float) $variant->width
                : null,

            height: $variant->height !== null
                ? (float) $variant->height
                : null,

            sync_enabled: $variant->sync_enabled,

            is_default: $variant->is_default,

            is_active: $variant->is_active,

            attribute_values: $attributeValues,
        );
    }

    /**
     * Convertir el DTO a array para product_variants.
     *
     * Los atributos NO se incluyen aquí porque
     * pertenecen a product_variant_attribute_values.
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'cost_price' => $this->cost_price,
            'sale_price' => $this->sale_price,
            'compare_price' => $this->compare_price,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'sync_enabled' => $this->sync_enabled,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
        ];
    }
}
