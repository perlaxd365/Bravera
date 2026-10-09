<?php

namespace App\Http\Controllers\Seo;

use App\Models\Category;
use App\Models\Product;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitemapController
{
    public function __invoke(): StreamedResponse
    {
        return response()->stream(function (): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
            echo $this->urlEntry(route('home'));

            Category::query()
                ->where('is_visible', true)
                ->orderBy('id')
                ->chunkById(500, function ($categories): void {
                    foreach ($categories as $category) {
                        echo $this->urlEntry(
                            route('store.category', ['path' => $category->path]),
                            $category->updated_at?->toAtomString(),
                            $category->image,
                        );
                    }
                });

            Product::query()
                ->active()
                ->visible()
                ->with('variants.images')
                ->orderBy('id')
                ->chunkById(250, function ($products): void {
                    foreach ($products as $product) {
                        $image = $this->coverImage($product);
                        echo $this->urlEntry(
                            route('store.product', ['slug' => $product->slug]),
                            $product->updated_at?->toAtomString(),
                            $image,
                        );
                    }
                });

            echo '</urlset>';
        }, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600',
        ]);
    }

    private function coverImage(Product $product): ?string
    {
        $variant = $product->variants
            ->sortByDesc('is_default')
            ->first(fn ($variant) => $variant->images->contains('is_active', true));

        if (! $variant) {
            return null;
        }

        $image = $variant->images->where('is_active', true)->firstWhere('is_primary', true)
            ?? $variant->images->first(fn ($item) => $item->is_active);

        return $image?->imageUrl();
    }

    private function urlEntry(string $url, ?string $lastModified = null, ?string $image = null): string
    {
        $xml = '<url><loc>'.$this->escape($url).'</loc>';

        if ($lastModified) {
            $xml .= '<lastmod>'.$this->escape($lastModified).'</lastmod>';
        }

        if ($image && filter_var($image, FILTER_VALIDATE_URL)) {
            $xml .= '<image:image><image:loc>'.$this->escape($image).'</image:loc></image:image>';
        }

        return $xml.'</url>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
