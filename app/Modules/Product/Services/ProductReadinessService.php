<?php

namespace App\Modules\Product\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductReadinessService
{
    /**
     * Evaluar si un producto tiene todo lo necesario para publicarse bien.
     *
     * @param  array<string, mixed>  $draft  Valores sin guardar del formulario.
     * @return array{
     *     percent: int,
     *     completed: int,
     *     total: int,
     *     ready: bool,
     *     missing: array<int, string>,
     *     checks: array<string, array{label: string, description: string, step: int, complete: bool}>
     * }
     */
    public function assess(Product $product, array $draft = []): array
    {
        $attributes = [
            'name' => $draft['name'] ?? $product->name,
            'category_id' => $draft['category_id'] ?? $product->category_id,
            'slug' => $draft['slug'] ?? $product->slug,
            'short_description' => $draft['short_description'] ?? $product->short_description,
            'description' => $draft['description'] ?? $product->description,
            'seo_title' => $draft['seo_title'] ?? $product->seo_title,
            'seo_description' => $draft['seo_description'] ?? $product->seo_description,
            'status' => $draft['status'] ?? $product->status,
            'is_visible' => $draft['is_visible'] ?? $product->is_visible,
        ];

        $variants = $this->variants($product);
        $activeVariants = $variants->where('is_active', true);
        $activeSupplierVariants = $activeVariants
            ->flatMap(fn ($variant) => $variant->supplierVariants)
            ->filter(fn ($supplierVariant) => $supplierVariant->is_active
                && $supplierVariant->supplier?->status === 'active');

        $checks = [
            'basic' => [
                'label' => 'Datos base',
                'description' => 'Nombre, categoría y URL del producto.',
                'step' => 1,
                'complete' => filled($attributes['name'])
                    && filled($attributes['category_id'])
                    && filled($attributes['slug']),
            ],
            'description' => [
                'label' => 'Descripción completa',
                'description' => 'Resumen corto y detalle completo.',
                'step' => 1,
                'complete' => filled(strip_tags((string) $attributes['short_description']))
                    && filled(strip_tags((string) $attributes['description'])),
            ],
            'seo' => [
                'label' => 'SEO preparado',
                'description' => 'Título y descripción para buscadores.',
                'step' => 2,
                'complete' => (filled($attributes['seo_title']) || filled($attributes['name']))
                    && (filled($attributes['seo_description'])
                        || filled($attributes['short_description'])
                        || filled($attributes['description'])
                        || filled($attributes['name'])),
            ],
            'variant' => [
                'label' => 'Variante activa',
                'description' => 'Al menos una variante activa con precio de venta.',
                'step' => 3,
                'complete' => $activeVariants->contains(
                    fn ($variant) => (float) $variant->sale_price > 0
                ),
            ],
            'image' => [
                'label' => 'Imagen publicada',
                'description' => 'Una foto activa en una variante activa.',
                'step' => 3,
                'complete' => $activeVariants->contains(
                    fn ($variant) => $variant->images->contains('is_active', true)
                ),
            ],
            'supplier' => [
                'label' => 'Proveedor activo',
                'description' => 'Una fuente de inventario activa y vigente.',
                'step' => 4,
                'complete' => $activeSupplierVariants->isNotEmpty(),
            ],
            'stock' => [
                'label' => 'Stock disponible',
                'description' => 'Unidades libres para vender.',
                'step' => 4,
                'complete' => $activeSupplierVariants->contains(
                    fn ($supplierVariant) => $supplierVariant->availableStock() > 0
                ),
            ],
            'published' => [
                'label' => 'Publicado en tienda',
                'description' => 'Producto activo y visible para clientes.',
                'step' => 1,
                'complete' => (bool) $attributes['status'] && (bool) $attributes['is_visible'],
            ],
        ];

        if ($product->isCourse()) {
            $checks['course_assets'] = [
                'label' => 'Materiales del curso',
                'description' => 'Al menos un archivo o enlace privado.',
                'step' => 2,
                'complete' => $product->exists && $product->courseAssets()->exists(),
            ];
        }

        $completed = count(array_filter($checks, fn (array $check): bool => $check['complete']));
        $total = count($checks);

        return [
            'percent' => (int) round(($completed / $total) * 100),
            'completed' => $completed,
            'total' => $total,
            'ready' => $completed === $total,
            'missing' => collect($checks)
                ->reject(fn (array $check): bool => $check['complete'])
                ->pluck('label')
                ->values()
                ->all(),
            'checks' => $checks,
        ];
    }

    /**
     * Productos que todavía no cumplen todas las condiciones de publicación.
     */
    public function pendingPublicationCount(): int
    {
        $readyProducts = Product::query()
            ->select('id')
            ->where('status', true)
            ->where('is_visible', true)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereNotNull('category_id')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereNotNull('short_description')
            ->where('short_description', '!=', '')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->where(function (Builder $query): void {
                $query->where(fn (Builder $query) => $query
                    ->whereNotNull('seo_title')
                    ->where('seo_title', '!=', ''))
                    ->orWhere(fn (Builder $query) => $query
                        ->whereNotNull('name')
                        ->where('name', '!=', ''));
            })
            ->where(function (Builder $query): void {
                $query->where(fn (Builder $query) => $query
                    ->whereNotNull('seo_description')
                    ->where('seo_description', '!=', ''))
                    ->orWhere(fn (Builder $query) => $query
                        ->whereNotNull('short_description')
                        ->where('short_description', '!=', ''))
                    ->orWhere(fn (Builder $query) => $query
                        ->whereNotNull('description')
                        ->where('description', '!=', ''))
                    ->orWhere(fn (Builder $query) => $query
                        ->whereNotNull('name')
                        ->where('name', '!=', ''));
            })
            ->whereHas('variants', fn (Builder $query) => $query
                ->where('is_active', true)
                ->where('sale_price', '>', 0))
            ->whereHas('variants', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereHas('images', fn (Builder $query) => $query->where('is_active', true)))
            ->whereHas('variants', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereHas('supplierVariants', fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->whereHas('supplier', fn (Builder $query) => $query->where('status', 'active'))))
            ->whereHas('variants', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereHas('supplierVariants', fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->whereColumn('stock', '>', 'reserved_stock')
                    ->whereHas('supplier', fn (Builder $query) => $query->where('status', 'active'))));

        return Product::query()
            ->whereNotIn('id', $readyProducts)
            ->count();
    }

    private function variants(Product $product): Collection
    {
        if (! $product->exists) {
            return collect();
        }

        $product->loadMissing([
            'variants.images',
            'variants.supplierVariants.supplier',
        ]);

        return $product->variants;
    }
}
