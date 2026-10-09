<?php

namespace App\Modules\Product\Services;

use App\Models\ProductVariant;
use App\Modules\Product\DTO\ProductVariantData;
use App\Modules\Product\Repositories\ProductVariantRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductVariantService
{
    public function __construct(
        protected ProductVariantRepository $repository,
        protected ProductVariantAttributeValueService $attributeValueService
    ) {}

    /**
     * Obtener listado paginado de variantes de un producto.
     */
    public function paginate(
        int $productId,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        return $this->repository->paginate(
            productId: $productId,
            search: $search,
            perPage: $perPage
        );
    }

    /**
     * Buscar variante por ID.
     */
    public function find(int $id): ?ProductVariant
    {
        return $this->repository->find($id);
    }

    /**
     * Crear variante.
     */
    public function create(ProductVariantData $data): ProductVariant
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Datos de la variante
            |--------------------------------------------------------------------------
            */

            $payload = $data->toArray();

            /*
            |--------------------------------------------------------------------------
            | Variante principal
            |--------------------------------------------------------------------------
            */

            if ($payload['is_default']) {
                ProductVariant::query()
                    ->where('product_id', $payload['product_id'])
                    ->update([
                        'is_default' => false,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Crear variante
            |--------------------------------------------------------------------------
            */

            $variant = $this->repository->create($data);

            /*
            |--------------------------------------------------------------------------
            | Sincronizar atributos
            |--------------------------------------------------------------------------
            */

            $this->attributeValueService->sync(
                variantId: $variant->id,
                attributeValues: $data->attribute_values
            );

            /*
            |--------------------------------------------------------------------------
            | Retornar variante actualizada
            |--------------------------------------------------------------------------
            */

            return $this->repository->find($variant->id);
        });
    }

    /**
     * Actualizar variante.
     */
    public function update(
        ProductVariant $variant,
        ProductVariantData $data
    ): ProductVariant {
        return DB::transaction(function () use ($variant, $data) {

            /*
            |--------------------------------------------------------------------------
            | Datos de la variante
            |--------------------------------------------------------------------------
            */

            $payload = $data->toArray();

            /*
            |--------------------------------------------------------------------------
            | Variante principal
            |--------------------------------------------------------------------------
            */

            if ($payload['is_default']) {
                ProductVariant::query()
                    ->where('product_id', $variant->product_id)
                    ->whereKeyNot($variant->id)
                    ->update([
                        'is_default' => false,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Actualizar variante
            |--------------------------------------------------------------------------
            */

            $variant = $this->repository->update($variant, $data);

            /*
            |--------------------------------------------------------------------------
            | Sincronizar atributos
            |--------------------------------------------------------------------------
            */

            $this->attributeValueService->sync(
                variantId: $variant->id,
                attributeValues: $data->attribute_values
            );

            /*
            |--------------------------------------------------------------------------
            | Retornar variante actualizada
            |--------------------------------------------------------------------------
            */

            return $this->repository->find($variant->id);
        });
    }

    /**
     * Eliminar variante.
     */
    public function delete(ProductVariant $variant): bool
    {
        return DB::transaction(function () use ($variant) {

            return $this->repository->delete($variant);
        });
    }

    /**
     * Cambiar estado.
     */
    public function toggleStatus(ProductVariant $variant): ProductVariant
    {
        return $this->repository->toggleStatus($variant);
    }
}
