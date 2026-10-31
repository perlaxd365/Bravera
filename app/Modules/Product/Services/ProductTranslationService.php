<?php

namespace App\Modules\Product\Services;

use App\Models\Product;
use Illuminate\Support\Arr;

class ProductTranslationService
{
    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function sync(Product $product, array $translations): void
    {
        foreach ((array) config('storefront.supported_locales', ['es', 'en']) as $locale) {
            if ($locale === 'es') {
                continue;
            }

            $values = collect(Arr::get($translations, $locale, []))
                ->only(['name', 'short_description', 'description', 'seo_title', 'seo_description'])
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->all();

            if (collect($values)->filter(fn ($value) => filled($value))->isEmpty()) {
                $product->translations()->where('locale', $locale)->delete();

                continue;
            }

            $product->translations()->updateOrCreate(
                ['locale' => $locale],
                $values,
            );
        }

        $product->unsetRelation('translations');
    }
}
