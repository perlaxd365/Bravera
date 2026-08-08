<?php

namespace App\Modules\Product\DTO;

use App\Models\ProductVariantAttributeValue;

readonly class ProductVariantAttributeValueData
{
    public function __construct(
        public ?int $id,
        public int $product_variant_id,
        public int $attribute_id,
        public int $attribute_value_id,
    ) {}

    /**
     * Crear DTO desde un array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            product_variant_id: (int) $data['product_variant_id'],
            attribute_id: (int) $data['attribute_id'],
            attribute_value_id: (int) $data['attribute_value_id'],
        );
    }

    /**
     * Crear DTO desde un modelo.
     */
    public static function fromModel(
        ProductVariantAttributeValue $model
    ): self {
        return new self(
            id: $model->id,
            product_variant_id: $model->product_variant_id,
            attribute_id: $model->attribute_id,
            attribute_value_id: $model->attribute_value_id,
        );
    }

    /**
     * Convertir DTO a array.
     */
    public function toArray(): array
    {
        return [
            'product_variant_id' => $this->product_variant_id,
            'attribute_id' => $this->attribute_id,
            'attribute_value_id' => $this->attribute_value_id,
        ];
    }
}
