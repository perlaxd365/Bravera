<?php

namespace App\Modules\Product\Repositories;

use App\Models\ProductVariant;
use App\Modules\Product\DTO\ProductVariantData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductVariantRepository
{
    /**
     * Listado paginado de variantes de un producto.
     */
    public function paginate(
        int $productId,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator {

        return ProductVariant::query()
            ->where('product_id', $productId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_default')
            ->orderBy('sku')
            ->paginate($perPage);
    }

    /**
     * Buscar por ID.
     */
    public function find(int $id): ?ProductVariant
    {
        return ProductVariant::with([
            'product',
            'attributeValues.attribute',
            'attributeValues.value',
            'supplierVariants.supplier',
            'images',
        ])->find($id);
    }

    /**
     * Crear variante.
     */
    public function create(ProductVariantData $data): ProductVariant
    {
        return ProductVariant::create($data->toArray());
    }

    /**
     * Actualizar variante.
     */
    public function update(
        ProductVariant $variant,
        ProductVariantData $data
    ): ProductVariant {
        $variant->update($data->toArray());

        return $variant->refresh();
    }

    /**
     * Eliminar variante.
     */
    public function delete(ProductVariant $variant): bool
    {
        return (bool) $variant->delete();
    }

    /**
     * Cambiar estado.
     */
    public function toggleStatus(ProductVariant $variant): ProductVariant
    {
        $variant->update([
            'is_active' => ! $variant->is_active,
        ]);

        return $variant->refresh();
    }
}
