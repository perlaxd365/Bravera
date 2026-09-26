<?php

namespace App\Modules\Product\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StoreCatalogRepository
{
    /**
     * Paginado de productos visibles para la tienda.
     */
    public function paginate(
        string $search = '',
        ?string $categoryPath = null,
        ?int $brandId = null,
        array $attributeValueIds = [],
        ?float $minPrice = null,
        ?float $maxPrice = null,
        string $sort = 'latest',
        int $perPage = 12,
    ): LengthAwarePaginator {
        return Product::query()
            ->active()
            ->visible()
            ->with(['brand', 'category', 'variants.supplierVariants', 'variants.images'])
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', function ($q) {
                        $q->active()->whereColumn('stock', '>', 'reserved_stock');
                    });
            })
            ->when($categoryPath, function ($query, $path) {
                $query->whereHas('category', function ($q) use ($path) {
                    $q->where('path', $path)
                        ->orWhere('path', 'like', $path.'/%');
                });
            })
            ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            })
            ->when($attributeValueIds, function ($query, $ids) {
                $query->whereHas('variants.attributeValues', function ($q) use ($ids) {
                    $q->whereIn('attribute_value_id', $ids);
                });
            })
            ->when(filled($minPrice), function ($query) use ($minPrice) {
                $query->whereHas('variants', fn ($q) => $q->where('sale_price', '>=', $minPrice));
            })
            ->when(filled($maxPrice), function ($query) use ($maxPrice) {
                $query->whereHas('variants', fn ($q) => $q->where('sale_price', '<=', $maxPrice));
            })
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy(
                Product::selectRaw('MIN(v.sale_price)')
                    ->from('product_variants as v')
                    ->whereColumn('v.product_id', 'products.id')
                    ->where('v.is_active', true),
            ))
            ->when($sort === 'price_desc', fn ($q) => $q->orderByDesc(
                Product::selectRaw('MAX(v.sale_price)')
                    ->from('product_variants as v')
                    ->whereColumn('v.product_id', 'products.id')
                    ->where('v.is_active', true),
            ))
            ->when($sort === 'name', fn ($q) => $q->orderBy('name'))
            ->when($sort === 'price_asc' || $sort === 'price_desc' || $sort === 'name',
                fn ($q) => $q,
                fn ($q) => $q->latest())
            ->paginate($perPage);
    }
}
