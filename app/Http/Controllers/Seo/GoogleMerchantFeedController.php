<?php

namespace App\Http\Controllers\Seo;

use App\Models\Product;
use App\Models\ProductVariant;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleMerchantFeedController
{
    public function __invoke(): StreamedResponse
    {
        $baseUrl = rtrim(config('app.url'), '/');
        return response()->stream(function () use ($baseUrl): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>';
            echo '<title>Brevare</title><link>'.$this->escape($baseUrl).'</link><description>Productos disponibles en Brevare.</description>';

            Product::query()
                ->active()
                ->visible()
                ->with([
                    'brand',
                    'category',
                    'variants' => fn ($query) => $query->active()
                        ->with([
                            'supplierVariants' => fn ($suppliers) => $suppliers->available(),
                            'images' => fn ($images) => $images->active()->orderByDesc('is_primary')->orderBy('sort_order'),
                            'attributeValues.attribute',
                            'attributeValues.value',
                        ]),
                ])
                ->orderBy('id')
                ->chunkById(150, function ($products): void {
                    foreach ($products as $product) {
                        foreach ($product->variants as $variant) {
                            $item = $this->feedItem($product, $variant);
                            if ($item !== null) {
                                echo $item;
                            }
                        }
                    }
                });

            echo '</channel></rss>';
        }, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800, s-maxage=1800',
        ]);
    }

    private function feedItem(Product $product, ProductVariant $variant): ?string
    {
        $stock = $variant->supplierVariants->sum(fn ($supplier) => $supplier->availableStock());

        if ($stock < 1) {
            return null;
        }

        $image = $variant->images->first()?->imageUrl() ?: $product->coverImage();
        if (! $image || ! filter_var($image, FILTER_VALIDATE_URL)) {
            return null;
        }

        $variantLabel = $variant->attributeValues
            ->map(fn ($pivot) => trim(($pivot->attribute?->name ?? '').': '.($pivot->value?->value ?? '')))
            ->filter(fn ($value) => $value !== ':')
            ->implode(', ');
        $title = $product->name.($variantLabel ? ' - '.$variantLabel : '');
        $description = $product->seo_description
            ?: ($product->short_description ?: strip_tags((string) $product->description));
        $description = trim(preg_replace('/\s+/u', ' ', $description) ?? '');
        $description = $description ?: $product->name.' disponible en Brevare.';
        $link = route('store.product', ['slug' => $product->slug]).'?variant='.rawurlencode($variant->sku);

        $xml = '<item>';
        $xml .= $this->tag('id', 'brv-v-'.$variant->id);
        $xml .= $this->tag('title', $title);
        $xml .= $this->tag('description', $description);
        $xml .= $this->tag('link', $link);
        $xml .= $this->tag('image_link', $image);
        $xml .= $this->tag('availability', 'in_stock');
        $xml .= $this->tag('price', number_format((float) $variant->sale_price, 2, '.', '').' PEN');
        $xml .= $this->tag('condition', 'new');
        $xml .= $this->tag('item_group_id', 'brv-p-'.$product->id);
        $xml .= $this->tag('product_type', $product->category?->name ?? 'Productos');

        if ($product->brand?->name) {
            $xml .= $this->tag('brand', $product->brand->name);
        }

        if ($this->isValidGtin($variant->barcode)) {
            $xml .= $this->tag('gtin', preg_replace('/\D+/', '', $variant->barcode));
        }

        return $xml.'</item>';
    }

    private function tag(string $name, string $value): string
    {
        return '<g:'.$name.'>'.$this->escape($value).'</g:'.$name.'>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function isValidGtin(?string $value): bool
    {
        $gtin = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (! in_array(strlen($gtin), [8, 12, 13, 14], true)) {
            return false;
        }

        $digits = array_map('intval', str_split($gtin));
        $checkDigit = array_pop($digits);
        $sum = 0;

        foreach (array_reverse($digits) as $index => $digit) {
            $sum += $digit * ($index % 2 === 0 ? 3 : 1);
        }

        return (10 - ($sum % 10)) % 10 === $checkDigit;
    }
}
