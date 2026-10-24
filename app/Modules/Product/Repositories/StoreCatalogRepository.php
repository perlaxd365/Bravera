<?php

namespace App\Modules\Product\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class StoreCatalogRepository
{
    /**
     * Paginado de productos visibles para la tienda.
     */
    public function paginate(
        string $search = '',
        bool $offers = false,
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
            ->with(['brand', 'category', 'variants.supplierVariants.supplier', 'variants.images', 'variants.attributeValues.attribute', 'variants.attributeValues.value'])
            ->withCount(['reviews' => fn ($query) => $query->approved()])
            ->withAvg(['reviews' => fn ($query) => $query->approved()], 'rating')
            ->withCount([
                'pageViews as web_visits_30d' => fn ($query) => $query
                    ->where('viewed_at', '>=', now()->subDays(30)),
            ])
            ->withSum([
                'metaAds as meta_visits_30d' => fn ($query) => $query->where('status', 'active'),
            ], 'link_clicks_30d')
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', function ($q) {
                        $q->active()->whereColumn('stock', '>', 'reserved_stock');
                    });
            })
            ->when($categoryPath, function ($query, $path) {
                // Las categorías principales Hombre/Mujer agrupan temporalmente
                // las antiguas subcategorías Moda/Hombre y Moda/Mujer sin
                // cambiar la categoría guardada de los productos existentes.
                $legacyPaths = match ($path) {
                    'hombre' => ['moda/hombre'],
                    'mujer' => ['moda/mujer'],
                    default => [],
                };

                $query->whereHas('category', function ($q) use ($path, $legacyPaths) {
                    $q->where('path', $path)
                        ->orWhere('path', 'like', $path.'/%');

                    if ($legacyPaths !== []) {
                        $q->orWhereIn('path', $legacyPaths);
                    }
                });
            })
            ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
            ->when($offers, fn ($query) => $query->whereHas('variants', fn ($variants) => $variants
                ->whereNotNull('compare_price')
                ->whereColumn('compare_price', '>', 'sale_price')))
            ->when($search, fn (Builder $query) => $this->applySearch($query, $search))
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
            ->when($sort === 'web_visits_desc', fn ($q) => $q->orderByDesc('web_visits_30d')->latest())
            ->when($sort === 'meta_visits_desc', fn ($q) => $q->orderByDesc('meta_visits_30d')->latest())
            ->when($sort === 'price_asc'
                || $sort === 'price_desc'
                || $sort === 'name'
                || $sort === 'web_visits_desc'
                || $sort === 'meta_visits_desc',
                fn ($q) => $q,
                fn ($q) => $q->latest())
            ->paginate($perPage);
    }

    /**
     * Productos alternativos cuando la búsqueda no tiene coincidencias exactas
     * o para complementar los resultados.
     */
    public function similar(string $search, array $excludeIds = [], int $limit = 8): Collection
    {
        $terms = $this->searchTerms($search);

        if ($terms === []) {
            return new Collection;
        }

        $candidates = Product::query()
            ->active()
            ->visible()
            ->with(['brand', 'category', 'variants.supplierVariants.supplier', 'variants.images'])
            ->withCount(['reviews' => fn ($query) => $query->approved()])
            ->withAvg(['reviews' => fn ($query) => $query->approved()], 'rating')
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', fn ($supplierQuery) => $supplierQuery
                        ->active()
                        ->whereColumn('stock', '>', 'reserved_stock'));
            })
            ->when($excludeIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludeIds))
            ->latest()
            ->limit(100)
            ->get();

        $ranked = $candidates
            ->map(function (Product $product) use ($terms): array {
                $name = $this->normalize($product->name);
                $category = $this->normalize(
                    ($product->category?->name ?? '').' '.($product->category?->path ?? '')
                );
                $description = $this->normalize(
                    ($product->short_description ?? '').' '.($product->description ?? '')
                );
                $score = 0;

                foreach ($terms as $term) {
                    $score += str_contains($name, $term) ? 8 : 0;
                    $score += str_contains($category, $term) ? 6 : 0;
                    $score += str_contains($description, $term) ? 3 : 0;
                }

                if ($product->is_featured) {
                    $score += 1;
                }

                return ['product' => $product, 'score' => $score];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('product')
            ->values()
            ->all();

        return new Collection($ranked);
    }

    /**
     * Productos comprables para complementar el carrito. Prioriza la misma
     * categoría de los artículos que el comprador ya eligió.
     */
    public function cartRecommendations(
        array $excludeProductIds,
        array $categoryIds,
        int $limit = 4,
    ): Collection {
        $categoryIds = collect($categoryIds)
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return Product::query()
            ->active()
            ->visible()
            ->with(['brand', 'category', 'variants.images', 'variants.supplierVariants.supplier'])
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', fn ($supplierQuery) => $supplierQuery
                        ->active()
                        ->whereColumn('stock', '>', 'reserved_stock'));
            })
            ->when($excludeProductIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludeProductIds))
            ->when($categoryIds !== [], function (Builder $query) use ($categoryIds): void {
                $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
                $query->orderByRaw(
                    "CASE WHEN category_id IN ({$placeholders}) THEN 0 ELSE 1 END",
                    $categoryIds,
                );
            })
            ->orderByDesc('is_featured')
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function applySearch(Builder $query, string $search): void
    {
        $terms = $this->searchTerms($search);

        if ($terms === []) {
            return;
        }

        $query->where(function (Builder $query) use ($terms): void {
            foreach ($terms as $term) {
                $query->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('category', fn (Builder $category) => $category
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('path', 'like', "%{$term}%"));
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function searchTerms(string $search): array
    {
        $normalized = $this->normalize($search);
        $stopWords = ['producto', 'productos', 'buscar', 'comprar', 'quiero', 'para', 'con', 'del', 'las', 'los', 'una', 'uno', 'por'];
        $synonyms = [
            'navideno' => 'navidad',
            'navidena' => 'navidad',
            'navidenos' => 'navidad',
            'navidenas' => 'navidad',
            'navideño' => 'navidad',
            'navideña' => 'navidad',
            'navideños' => 'navidad',
            'navideñas' => 'navidad',
            'decoracion' => 'decoracion',
            'decorativo' => 'decoracion',
            'decorativa' => 'decoracion',
        ];

        return collect(preg_split('/\s+/', $normalized) ?: [])
            ->map(fn (string $term): string => $synonyms[$term] ?? $term)
            ->reject(fn (string $term): bool => $term === '' || in_array($term, $stopWords, true))
            ->filter(fn (string $term): bool => mb_strlen($term) >= 3)
            ->unique()
            ->take(6)
            ->values()
            ->all();
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace(
            '/\s+/',
            ' ',
            Str::lower(Str::ascii($value)),
        ) ?? '');
    }
}
