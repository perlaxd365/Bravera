<?php

namespace App\Modules\Product\Repositories;

use App\Models\SupplierVariant;
use App\Modules\Product\DTO\SupplierVariantData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierVariantRepository
{
    /**
     * Listado paginado de proveedores de una variante.
     */
    public function paginate(
        int $productVariantId,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator {

        return SupplierVariant::query()
            ->with('supplier')
            ->where('product_variant_id', $productVariantId)
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->whereHas('supplier', function ($supplierQuery) use ($search) {

                        $supplierQuery
                            ->where('business_name', 'like', "%{$search}%")
                            ->orWhere('trade_name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                        ->orWhere('supplier_sku', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Buscar proveedor de variante por ID.
     */
    public function find(int $id): ?SupplierVariant
    {
        return SupplierVariant::with([
            'supplier',
            'productVariant',
        ])->find($id);
    }

    /**
     * Crear proveedor de variante.
     */
    public function create(SupplierVariantData $data): SupplierVariant
    {
        return SupplierVariant::create(
            $data->toArray()
        );
    }

    /**
     * Actualizar proveedor de variante.
     */
    public function update(
        SupplierVariant $supplierVariant,
        SupplierVariantData $data
    ): SupplierVariant {
        $supplierVariant->update(
            $data->toArray()
        );

        return $supplierVariant->refresh();
    }

    /**
     * Eliminar proveedor de variante.
     */
    public function delete(SupplierVariant $supplierVariant): bool
    {
        return (bool) $supplierVariant->delete();
    }

    /**
     * Cambiar estado.
     */
    public function toggleStatus(
        SupplierVariant $supplierVariant
    ): SupplierVariant {
        $supplierVariant->update([
            'is_active' => ! $supplierVariant->is_active,
        ]);

        return $supplierVariant->refresh();
    }
}
