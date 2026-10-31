<?php

namespace App\Support\Seo;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class StructuredData
{
    public static function organization(): array
    {
        $company = config('brevare.company', []);
        $seo = config('seo', []);
        $base = self::baseUrl();
        $logo = asset('favicon-192.png');
        $image = $seo['organization_image'] ?: $logo;

        $founders = collect($seo['founders'] ?? [])
            ->filter(fn ($founder) => filled($founder['name'] ?? null))
            ->map(fn ($founder) => [
                '@type' => 'Person',
                'name' => $founder['name'],
                'jobTitle' => $founder['job_title'] ?? null,
            ])
            ->values()
            ->all();

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => self::organizationId(),
            'name' => $company['name'] ?? 'Brevare',
            'legalName' => $company['legal_name'] ?? null,
            'url' => $base,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logo,
                'width' => 192,
                'height' => 192,
            ],
            'image' => $image,
            'description' => $seo['description'] ?? null,
            'slogan' => $seo['slogan'] ?? null,
            'foundingDate' => $seo['founding_date'] ?? null,
            'founder' => $founders,
            'foundingLocation' => [
                '@type' => 'Place',
                'name' => $seo['founding_location'] ?? null,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $company['city'] ?? null,
                    'addressRegion' => $company['region'] ?? null,
                    'addressCountry' => $company['country'] ?? 'PE',
                ],
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $company['address'] ?? null,
                'addressLocality' => $company['city'] ?? null,
                'addressRegion' => $company['region'] ?? null,
                'addressCountry' => $company['country'] ?? 'PE',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'email' => $company['support_email'] ?? $company['email'] ?? null,
                'telephone' => $company['phone_e164'] ?? null,
                'areaServed' => 'PE',
                'availableLanguage' => ['es'],
            ],
            'taxID' => $company['ruc'] ?? null,
            'currenciesAccepted' => 'PEN',
            'paymentAccepted' => 'Tarjeta, Yape, Plin, Efectivo',
            'areaServed' => [
                '@type' => 'Country',
                'name' => $company['country_name'] ?? 'Perú',
            ],
            'sameAs' => $seo['same_as'] ?? [],
        ]);
    }

    public static function website(): array
    {
        $base = self::baseUrl();

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $base.'/#website',
            'url' => $base,
            'name' => config('brevare.company.name', 'Brevare'),
            'inLanguage' => 'es-PE',
            'publisher' => ['@id' => self::organizationId()],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $base.'/buscar?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ]);
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public static function breadcrumbs(array $items, ?string $id = null): array
    {
        $items = collect($items)
            ->filter(fn ($item) => filled($item['name'] ?? null) && filled($item['url'] ?? null))
            ->values();

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            '@id' => $id,
            'itemListElement' => $items
                ->map(fn ($item, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $item['position'] ?? $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ])
                ->all(),
        ]);
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public static function itemList(array $items, string $name, ?string $id = null): array
    {
        $items = collect($items)
            ->filter(fn ($item) => filled($item['name'] ?? null) && filled($item['url'] ?? null))
            ->values();

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            '@id' => $id,
            'name' => $name,
            'numberOfItems' => $items->count(),
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'itemListElement' => $items
                ->map(fn ($item, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'url' => $item['url'],
                ])
                ->all(),
        ]);
    }

    public static function webPage(
        string $type,
        string $url,
        string $name,
        string $description,
        ?string $breadcrumbId = null,
        ?string $mainEntityId = null,
    ): array {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => $type,
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $name,
            'description' => $description,
            'isPartOf' => ['@id' => self::baseUrl().'/#website'],
            'breadcrumb' => $breadcrumbId ? ['@id' => $breadcrumbId] : null,
            'mainEntity' => $mainEntityId ? ['@id' => $mainEntityId] : null,
        ]);
    }

    public static function product(
        Product $product,
        ?ProductVariant $variant,
        string $url,
        array $images,
        int $reviewCount = 0,
        ?float $reviewAverage = null,
    ): array {
        $images = collect($images)
            ->filter(fn ($image) => is_string($image) && filter_var($image, FILTER_VALIDATE_URL))
            ->unique()
            ->values()
            ->all();

        $price = $variant?->sale_price ?? $product->minPrice();
        $stock = $variant?->total_available;

        if ($stock === null && $variant?->relationLoaded('supplierVariants')) {
            $stock = $variant->supplierVariants->sum(
                fn ($supplierVariant) => $supplierVariant->is_active
                    ? $supplierVariant->availableStock()
                    : 0
            );
        }

        $localizedName = $product->localized('name') ?? $product->name;
        $description = $product->localized('seo_description')
            ?: ($product->localized('short_description') ?: strip_tags((string) $product->localized('description')));
        $description = trim(preg_replace('/\s+/u', ' ', (string) $description) ?? '');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $url.'#product',
            'name' => $localizedName,
            'description' => $description ?: $localizedName,
            'url' => $url,
            'sku' => $variant?->sku,
            'mpn' => $variant?->barcode ?: $variant?->sku,
            'image' => $images,
            'brand' => $product->brand?->name
                ? ['@type' => 'Brand', 'name' => $product->brand->name]
                : null,
            'category' => $product->category?->name,
        ];

        if ($price !== null) {
            $data['offers'] = [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => 'PEN',
                'price' => number_format((float) $price, 2, '.', ''),
                'availability' => ((int) ($stock ?? 0)) > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@id' => self::organizationId()],
            ];
        }

        if ($reviewCount > 0 && $reviewAverage !== null) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float) $reviewAverage, 1, '.', ''),
                'reviewCount' => $reviewCount,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return self::clean($data);
    }

    public static function aboutPage(): array
    {
        $url = route('about');

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'AboutPage',
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => 'Sobre Brevare',
            'description' => 'Conoce a Brevare, sus fundadores y su origen en Lima, Perú.',
            'isPartOf' => ['@id' => self::baseUrl().'/#website'],
            'about' => ['@id' => self::organizationId()],
            'mainEntity' => ['@id' => self::organizationId()],
            'breadcrumb' => ['@id' => $url.'#breadcrumb'],
        ]);
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $categories
     */
    public static function homeCategoryList(Collection $categories): array
    {
        return self::itemList(
            $categories
                ->map(fn ($category) => [
                    'name' => CatalogLinks::label($category),
                    'url' => route('store.category', ['path' => $category->path]),
                ])
                ->values()
                ->all(),
            'Categorías principales de Brevare',
            self::baseUrl().'/#categorias',
        );
    }

    public static function organizationId(): string
    {
        return self::baseUrl().'/#organization';
    }

    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    private static function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::clean($value);
            }

            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
