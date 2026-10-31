<?php

namespace App\Support\Seo;

use App\Models\Category;
use Illuminate\Support\Collection;

class CatalogLinks
{
    /**
     * Categorías principales en el orden editorial solicitado.
     *
     * @return Collection<int, Category>
     */
    public static function curated(): Collection
    {
        $order = collect(config('seo.home_category_order', []));

        if ($order->isEmpty()) {
            return collect();
        }

        $categories = Category::query()
            ->where('is_visible', true)
            ->whereIn('path', $order->pluck('path'))
            ->get()
            ->keyBy('path');

        return $order
            ->map(function (array $item) use ($categories): ?Category {
                $category = $categories->get($item['path']);

                if (! $category) {
                    return null;
                }

                $category->seo_label = $item['label'] ?? self::label($category);

                return $category;
            })
            ->filter()
            ->values();
    }

    /**
     * Categorías para el home y la navegación, manteniendo primero el orden
     * editorial y completando con categorías principales por posición.
     *
     * @return Collection<int, Category>
     */
    public static function home(int $limit = 8): Collection
    {
        $curated = self::curated();
        $curatedPaths = $curated->pluck('path')->all();

        $curatedRoots = collect($curatedPaths)
            ->filter(fn (string $path) => str_contains($path, '/'))
            ->map(fn (string $path) => explode('/', $path)[0])
            ->unique()
            ->all();

        $remaining = Category::query()
            ->where('is_visible', true)
            ->whereNull('parent_id')
            ->whereNotIn('path', array_merge($curatedPaths, $curatedRoots))
            ->orderBy('position')
            ->orderBy('id')
            ->limit(max(0, $limit - $curated->count()))
            ->get();

        return $curated
            ->concat($remaining)
            ->take($limit)
            ->values();
    }

    /**
     * Sitemap: primero las categorías editoriales y después el resto.
     *
     * @return Collection<int, Category>
     */
    public static function orderedForSitemap(): Collection
    {
        $curated = self::curated();

        $remaining = Category::query()
            ->where('is_visible', true)
            ->whereNotIn('path', $curated->pluck('path'))
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return $curated->concat($remaining)->values();
    }

    public static function label(Category $category): string
    {
        return $category->seo_label
            ?? config('seo.category_labels.'.$category->path)
            ?? $category->name;
    }
}
