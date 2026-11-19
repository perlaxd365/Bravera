<?php

namespace App\Livewire\Store;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageSetting;
use App\Models\Product;
use App\Support\Localization\StorefrontContext;
use App\Support\Seo\CatalogLinks;
use App\Support\Seo\StructuredData;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class Home extends Component
{
    public function render()
    {
        // Escaparate principal: productos llamativos (con foto y disponibles)
        // ordenados por precio más económico primero, y destacados como desempate.
        $featured = Product::query()
            ->active()
            ->visible()
            ->orderByRaw('(select min(pv.sale_price) from product_variants pv where pv.product_id = products.id and pv.is_active = 1 and exists (select 1 from supplier_variants sv where sv.product_variant_id = pv.id and sv.is_active = 1 and sv.stock > sv.reserved_stock)) asc')
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->with(['brand', 'category', 'translations', 'variants.supplierVariants.supplier', 'variants.images', 'variants.attributeValues.attribute', 'variants.attributeValues.value'])
            ->withCount(['reviews' => fn ($query) => $query->approved()])
            ->withAvg(['reviews' => fn ($query) => $query->approved()], 'rating')
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', function ($q) {
                        $q->active()->whereColumn('stock', '>', 'reserved_stock');
                    });
            })
            ->whereHas('variants.images')
            ->limit(8)
            ->get();

        $latest = Product::query()
            ->active()
            ->visible()
            ->with(['brand', 'category', 'translations', 'variants.supplierVariants.supplier', 'variants.images', 'variants.attributeValues.attribute', 'variants.attributeValues.value'])
            ->withCount(['reviews' => fn ($query) => $query->approved()])
            ->withAvg(['reviews' => fn ($query) => $query->approved()], 'rating')
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', function ($q) {
                        $q->active()->whereColumn('stock', '>', 'reserved_stock');
                    });
            })
            ->when($featured->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $featured->modelKeys()))
            ->latest()
            ->limit(12)
            ->get();

        $newestProducts = Product::query()
            ->active()
            ->visible()
            ->with(['category', 'translations', 'variants.images', 'variants.supplierVariants.supplier'])
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', fn ($supplierQuery) => $supplierQuery
                        ->active()
                        ->whereColumn('stock', '>', 'reserved_stock'));
            })
            ->latest('created_at')
            ->latest('id')
            ->limit(8)
            ->get();

        $categoryFallbacks = [
            'tech' => 'https://images.pexels.com/photos/7989741/pexels-photo-7989741.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'fashion' => 'https://images.pexels.com/photos/34373606/pexels-photo-34373606.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'men' => 'https://images.pexels.com/photos/1043474/pexels-photo-1043474.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'shoes' => 'https://images.pexels.com/photos/2529148/pexels-photo-2529148.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'home' => 'https://images.pexels.com/photos/14977714/pexels-photo-14977714.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'kitchen' => 'https://images.pexels.com/photos/16443132/pexels-photo-16443132.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'beauty' => 'https://images.pexels.com/photos/3388335/pexels-photo-3388335.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'kids' => 'https://images.pexels.com/photos/28609810/pexels-photo-28609810.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'sports' => 'https://images.pexels.com/photos/14059510/pexels-photo-14059510.jpeg?auto=compress&cs=tinysrgb&w=1000',
            // This Pexels asset was returning 404 in the live storefront.
            'general' => 'https://images.pexels.com/photos/34373606/pexels-photo-34373606.jpeg?auto=compress&cs=tinysrgb&w=1000',
        ];

        $curatedCategories = CatalogLinks::curated();
        $allCategories = CatalogLinks::home(10);
        $discoveryCategory = $allCategories->first();

        $allCategories->each(function (Category $category) use ($categoryFallbacks) {
            $label = mb_strtolower($category->name.' '.$category->path);
            $key = match (true) {
                str_contains($label, 'tecnolog') || str_contains($label, 'electr') || str_contains($label, 'celular') || str_contains($label, 'comput') => 'tech',
                str_contains($label, 'hombre') => 'men',
                str_contains($label, 'calzado') || str_contains($label, 'zapat') || str_contains($label, 'sneaker') => 'shoes',
                str_contains($label, 'moda') || str_contains($label, 'ropa') || str_contains($label, 'mujer') => 'fashion',
                str_contains($label, 'cocina') => 'kitchen',
                str_contains($label, 'hogar') || str_contains($label, 'dormitorio') || str_contains($label, 'mueble') => 'home',
                str_contains($label, 'belleza') || str_contains($label, 'cuidado') || str_contains($label, 'salud') => 'beauty',
                str_contains($label, 'niño') || str_contains($label, 'bebe') || str_contains($label, 'juguete') => 'kids',
                str_contains($label, 'deporte') || str_contains($label, 'fitness') => 'sports',
                default => 'general',
            };

            $hasBlockedImage = filled($category->image)
                && str_contains((string) $category->image, 'images.unsplash.com');

            $category->store_image = filled($category->image) && ! $hasBlockedImage
                ? $category->image
                : $categoryFallbacks[$key];
            $category->store_image_fallback = $categoryFallbacks[$key];
        });

        $brands = Brand::query()
            ->active()
            ->orderBy('sort_order')
            ->limit(10)
            ->get();

        $savedSettings = HomepageSetting::query()->first();

        // La portada siempre gira alrededor de productos reales elegidos al azar.
        // Al usar inRandomOrder(), cualquier producto recién publicado entra solo
        // en la rotación en cuanto esté activo, visible y con stock.
        $heroProducts = Product::query()
            ->active()
            ->visible()
            ->with(['brand', 'category', 'translations', 'variants.images', 'variants.supplierVariants.supplier'])
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', fn ($supplierQuery) => $supplierQuery
                        ->active()
                        ->whereColumn('stock', '>', 'reserved_stock'));
            })
            ->whereHas('variants.images')
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $storefront = StorefrontContext::current();

        $productHeroSlides = $heroProducts
            ->values()
            ->map(function (Product $product, int $index) use ($storefront): array {
                $variants = $product->availableVariantModels();
                $minVariant = $variants->sortBy('sale_price')->first();
                $price = (float) ($minVariant?->sale_price ?? 0);
                $compare = (float) ($minVariant?->compare_price ?? 0);
                $discount = $compare > $price && $price > 0
                    ? (int) round((1 - ($price / $compare)) * 100)
                    : 0;

                $cover = $product->coverImageModel();
                $thumbnails = $product->variants
                    ->flatMap(fn ($variant) => $variant->images)
                    ->unique('id')
                    ->reject(fn ($image) => $cover && $image->id === $cover->id)
                    ->take(3)
                    ->map(fn ($image) => $image->sizedUrl(320))
                    ->filter()
                    ->values()
                    ->all();

                $name = $product->localized('name') ?? $product->name;
                $description = $product->localized('short_description');

                return [
                    'image' => $cover?->sizedUrl(1600),
                    'thumbs' => $thumbnails,
                    'eyebrow' => $product->brand?->name
                        ?? $product->category?->name
                        ?? (app()->getLocale() === 'en' ? 'Brevare picks' : 'Selección Brevare'),
                    'title' => $name,
                    'subtitle' => filled($description)
                        ? Str::limit(strip_tags((string) $description), 160)
                        : __('storefront.home.hero_subtitle'),
                    'price' => $price > 0 ? $storefront->formatFromPen($price) : null,
                    'compare' => $discount > 0 ? $storefront->formatFromPen($compare) : null,
                    'discount' => $discount,
                    'cta' => __('storefront.home.buy_now'),
                    'url' => route('store.product', ['slug' => $product->slug]),
                    'side' => $index % 2 === 0 ? 'left' : 'right',
                ];
            })
            ->filter(fn (array $slide): bool => filled($slide['image']))
            ->values();

        // Si aún no hay productos comprables, se conservan las portadas guardadas
        // desde el panel para no dejar el espacio vacío.
        $fallbackHeroSlides = collect($savedSettings?->hero_slides ?: config('homepage.slides', []))
            ->values()
            ->map(fn (array $slide, int $index): array => [
                'image' => $slide['image'] ?? null,
                'thumbs' => [],
                'eyebrow' => $slide['eyebrow'] ?? '',
                'title' => trim(($slide['title'] ?? '').' '.($slide['highlight'] ?? '')),
                'subtitle' => $slide['subtitle'] ?? '',
                'price' => null,
                'compare' => null,
                'discount' => 0,
                'cta' => $slide['cta'] ?? __('storefront.home.buy_now'),
                'url' => $slide['url'] ?? route('store.search'),
                'side' => $index % 2 === 0 ? 'left' : 'right',
            ]);

        $heroSlides = $productHeroSlides->isNotEmpty()
            ? $productHeroSlides->all()
            : $fallbackHeroSlides->take(6)->all();

        $heroFallbackImage = config('homepage.slides.0.image');
        $homeTexts = array_merge(config('homepage.texts', []), $savedSettings?->section_texts ?? []);
        $promoTickerItems = $newestProducts
            ->map(function (Product $product): array {
                return [
                    'eyebrow' => $product->category?->name
                        ? (app()->getLocale() === 'en' ? 'New · ' : 'Nuevo · ').$product->category->name
                        : (app()->getLocale() === 'en' ? 'New at Brevare' : 'Nuevo en Brevare'),
                    'name' => $product->localized('name') ?? $product->name,
                    'image' => $product->coverImageModel()?->sizedUrl(220),
                    'url' => route('store.product', ['slug' => $product->slug]),
                ];
            })
            ->filter(fn (array $item): bool => filled($item['image']))
            ->values()
            ->all();

        $seoTitle = app()->getLocale() === 'en'
            ? __('storefront.home.seo_title')
            : 'Brevare | Moda, tecnología, hogar y regalos';
        $seoDescription = app()->getLocale() === 'en'
            ? __('storefront.home.seo_description')
            : 'Compra moda, tecnología, hogar y regalos en Brevare. Entrega garantizada y envíos a todo el Perú.';
        $seoImage = $heroSlides[0]['image'] ?? asset('favicon-192.png');

        return view('livewire.store.home', compact('featured', 'latest', 'allCategories', 'discoveryCategory', 'brands', 'heroSlides', 'heroFallbackImage', 'promoTickerItems', 'homeTexts'))
            ->title($seoTitle)
            ->layoutData([
                'seoDescription' => $seoDescription,
                'seoImage' => $seoImage,
                'canonicalUrl' => route('home'),
                'pageStructuredData' => [
                    StructuredData::homeCategoryList($curatedCategories),
                ],
            ]);
    }
}
