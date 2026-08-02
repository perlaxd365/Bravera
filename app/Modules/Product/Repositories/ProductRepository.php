<?php

namespace App\Modules\Product\Repositories;

use App\Models\Product;
use App\Modules\Product\DTO\ProductData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    /**
     * Listado paginado.
     */
    public function paginate(
        string $search = '',
        ?int $categoryId = null,
        ?int $brandId = null,
        int $perPage = 10
    ): LengthAwarePaginator {

        return Product::query()
            ->with(['category', 'brand'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Obtener todos.
     */
    public function all(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Buscar por ID.
     */
    public function find(int $id): Product
    {
        return Product::with([
            'category',
            'brand',
            'variants',
        ])->findOrFail($id);
    }

    /**
     * Buscar por slug.
     */
    public function findBySlug(string $slug): ?Product
    {
        return Product::where('slug', $slug)->first();
    }

    /**
     * Crear producto.
     */
    public function create(ProductData $data): Product
    {
        return Product::create($data->toArray());
    }

    /**
     * Actualizar producto.
     */
    public function update(Product $product, ProductData $data): Product
    {
        $product->update($data->toArray());

        return $product->refresh();
    }

    /**
     * Eliminar.
     */
    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    /**
     * Cambiar estado.
     */
    public function toggleStatus(Product $product): Product
    {
        $product->update([
            'status' => ! $product->status,
        ]);

        return $product->refresh();
    }
}
