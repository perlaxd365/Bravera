<?php

namespace App\Modules\Product\Repositories;

use App\Models\ProductVariantAttributeValue;
use App\Modules\Product\DTO\ProductVariantAttributeValueData;
use Illuminate\Database\Eloquent\Collection;

class ProductVariantAttributeValueRepository
{
    /**
     * Obtener los atributos asignados a una variante.
     */
    public function getByVariant(int $variantId): Collection
    {
        return ProductVariantAttributeValue::query()
            ->with([
                'attribute',
                'value',
            ])
            ->where('product_variant_id', $variantId)
            ->get();
    }

    /**
     * Buscar una relación por ID.
     */
    public function find(int $id): ?ProductVariantAttributeValue
    {
        return ProductVariantAttributeValue::query()
            ->with([
                'attribute',
                'value',
            ])
            ->find($id);
    }

    /**
     * Buscar un atributo específico dentro de una variante.
     */
    public function findByVariantAndAttribute(
        int $variantId,
        int $attributeId
    ): ?ProductVariantAttributeValue {
        return ProductVariantAttributeValue::query()
            ->where('product_variant_id', $variantId)
            ->where('attribute_id', $attributeId)
            ->first();
    }

    /**
     * Crear relación.
     */
    public function create(
        ProductVariantAttributeValueData $data
    ): ProductVariantAttributeValue {
        return ProductVariantAttributeValue::create(
            $data->toArray()
        );
    }

    /**
     * Actualizar relación.
     */
    public function update(
        ProductVariantAttributeValue $model,
        ProductVariantAttributeValueData $data
    ): ProductVariantAttributeValue {
        $model->update($data->toArray());

        return $model->refresh();
    }

    /**
     * Eliminar relación.
     */
    public function delete(
        ProductVariantAttributeValue $model
    ): bool {
        return (bool) $model->delete();
    }

    /**
     * Eliminar todos los atributos de una variante.
     */
    public function deleteByVariant(int $variantId): int
    {
        return ProductVariantAttributeValue::query()
            ->where('product_variant_id', $variantId)
            ->delete();
    }
}
