<?php

namespace App\Modules\Product\Services;

use App\Models\SupplierVariant;
use App\Modules\Product\DTO\SupplierVariantData;
use App\Modules\Product\Repositories\SupplierVariantRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupplierVariantService
{
    public function __construct(
        protected SupplierVariantRepository $repository
    ) {}

    /**
     * Obtener listado paginado de proveedores de una variante.
     */
    public function paginate(
        int $productVariantId,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        return $this->repository->paginate(
            productVariantId: $productVariantId,
            search: $search,
            perPage: $perPage
        );
    }

    /**
     * Buscar proveedor de variante por ID.
     */
    public function find(int $id): ?SupplierVariant
    {
        return $this->repository->find($id);
    }

    /**
     * Crear proveedor de variante.
     */
    public function create(
        SupplierVariantData $data
    ): SupplierVariant {
        return DB::transaction(function () use ($data) {

            $payload = $data->toArray();

            /*
            |--------------------------------------------------------------------------
            | Proveedor principal
            |--------------------------------------------------------------------------
            |
            | Una variante solamente puede tener un proveedor principal.
            |
            */

            if ($payload['is_default']) {
                SupplierVariant::query()
                    ->where(
                        'product_variant_id',
                        $payload['product_variant_id']
                    )
                    ->update([
                        'is_default' => false,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Crear proveedor
            |--------------------------------------------------------------------------
            */

            return $this->repository->create(
                SupplierVariantData::fromArray($payload)
            );
        });
    }

    /**
     * Actualizar proveedor de variante.
     */
    public function update(
        SupplierVariant $supplierVariant,
        SupplierVariantData $data
    ): SupplierVariant {
        return DB::transaction(function () use (
            $supplierVariant,
            $data
        ) {

            $payload = $data->toArray();

            /*
            |--------------------------------------------------------------------------
            | Proveedor principal
            |--------------------------------------------------------------------------
            */

            if ($payload['is_default']) {
                SupplierVariant::query()
                    ->where(
                        'product_variant_id',
                        $supplierVariant->product_variant_id
                    )
                    ->whereKeyNot($supplierVariant->id)
                    ->update([
                        'is_default' => false,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Actualizar proveedor
            |--------------------------------------------------------------------------
            */

            return $this->repository->update(
                $supplierVariant,
                SupplierVariantData::fromArray($payload)
            );
        });
    }

    /**
     * Eliminar proveedor de variante.
     */
    public function delete(
        SupplierVariant $supplierVariant
    ): bool {
        return DB::transaction(function () use ($supplierVariant) {

            return $this->repository->delete(
                $supplierVariant
            );
        });
    }

    /**
     * Cambiar estado.
     */
    public function toggleStatus(
        SupplierVariant $supplierVariant
    ): SupplierVariant {
        return $this->repository->toggleStatus(
            $supplierVariant
        );
    }
}
