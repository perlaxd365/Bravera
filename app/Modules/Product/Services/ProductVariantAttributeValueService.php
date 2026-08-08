<?php

namespace App\Modules\Product\Services;

use App\Models\ProductVariantAttributeValue;
use App\Modules\Product\DTO\ProductVariantAttributeValueData;
use App\Modules\Product\Repositories\ProductVariantAttributeValueRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductVariantAttributeValueService
{
    public function __construct(
        protected ProductVariantAttributeValueRepository $repository
    ) {}

    /**
     * Obtener todos los atributos asignados a una variante.
     */
    public function getByVariant(int $variantId): Collection
    {
        return $this->repository->getByVariant($variantId);
    }

    /**
     * Buscar una relación por ID.
     */
    public function find(int $id): ?ProductVariantAttributeValue
    {
        return $this->repository->find($id);
    }

    /**
     * Crear una relación atributo/valor.
     */
    public function create(
        ProductVariantAttributeValueData $data
    ): ProductVariantAttributeValue {
        return $this->repository->create($data);
    }

    /**
     * Actualizar una relación atributo/valor.
     */
    public function update(
        ProductVariantAttributeValue $model,
        ProductVariantAttributeValueData $data
    ): ProductVariantAttributeValue {
        return $this->repository->update($model, $data);
    }

    /**
     * Eliminar una relación.
     */
    public function delete(
        ProductVariantAttributeValue $model
    ): bool {
        return $this->repository->delete($model);
    }

    /**
     * Sincronizar todos los atributos de una variante.
     *
     * Formato esperado:
     *
     * [
     *     attribute_id => attribute_value_id,
     * ]
     */
    public function sync(
        int $variantId,
        array $attributeValues
    ): void {
        DB::transaction(function () use (
            $variantId,
            $attributeValues
        ) {
            $this->repository->deleteByVariant($variantId);

            foreach ($attributeValues as $attributeId => $attributeValueId) {

                if (blank($attributeValueId)) {
                    continue;
                }

                $this->repository->create(
                    ProductVariantAttributeValueData::fromArray([
                        'product_variant_id' => $variantId,
                        'attribute_id' => (int) $attributeId,
                        'attribute_value_id' => (int) $attributeValueId,
                    ])
                );
            }
        });
    }
}
