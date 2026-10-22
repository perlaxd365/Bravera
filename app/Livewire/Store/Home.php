<?php

namespace App\Livewire\Store;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageSetting;
use App\Models\Product;
use App\Support\Seo\CatalogLinks;
use App\Support\Seo\StructuredData;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class Home extends Component
{
    public function render()
    {
        $featured = Product::query()
            ->active()
            ->visible()
            ->featured()
            ->with(['brand', 'category', 'variants.supplierVariants.supplier', 'variants.images', 'variants.attributeValues.attribute', 'variants.attributeValues.value'])
            ->withCount(['reviews' => fn ($query) => $query->approved()])
            ->withAvg(['reviews' => fn ($query) => $query->approved()], 'rating')
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', function ($q) {
                        $q->active()->whereColumn('stock', '>', 'reserved_stock');
                    });
            })
            ->limit(8)
            ->get();

        $latest = Product::query()
            ->active()
            ->visible()
            ->with(['brand', 'category', 'variants.supplierVariants.supplier', 'variants.images', 'variants.attributeValues.attribute', 'variants.attributeValues.value'])
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

        $christmasProducts = Product::query()
            ->active()
            ->visible()
            ->whereHas('category', fn ($query) => $query
                ->where('path', 'navidad')
                ->orWhere('path', 'like', 'navidad/%'))
            ->with(['category', 'variants.images', 'variants.supplierVariants.supplier'])
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', fn ($supplierQuery) => $supplierQuery
                        ->active()
                        ->whereColumn('stock', '>', 'reserved_stock'));
            })
            ->latest()
            ->limit(6)
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
        $savedHeroSlides = collect($savedSettings?->hero_slides ?: config('homepage.slides', []));
        $christmasSlides = $christmasProducts->take(3)->values()->map(function (Product $product, int $index): array {
            $copy = [
                [
                    'eyebrow' => 'Navidad en casa',
                    'title' => 'Detalles que iluminan',
                    'highlight' => 'tu Navidad',
                    'subtitle' => 'Coronas, ramos y guirnaldas para darle calidez a cada rincón.',
                ],
                [
                    'eyebrow' => 'Momentos para compartir',
                    'title' => 'Tu mesa, lista para',
                    'highlight' => 'celebrar',
                    'subtitle' => 'Pequeños detalles que hacen que diciembre se sienta especial.',
                ],
                [
                    'eyebrow' => 'Decora a tu estilo',
                    'title' => 'Una Navidad con',
                    'highlight' => 'tu personalidad',
                    'subtitle' => 'Encuentra piezas navideñas para transformar tu casa sin complicarte.',
                ],
            ][$index] ?? [];

            return [
                ...$copy,
                'image' => $product->coverImageModel()?->sizedUrl(1600),
                'cta' => 'Ver el producto',
                'url' => route('store.product', ['slug' => $product->slug]),
                'tone' => 'christmas',
            ];
        })->filter(fn (array $slide): bool => filled($slide['image'] ?? null))->values();

        $heroSlides = $savedHeroSlides
            ->take(2)
            ->merge($christmasSlides)
            ->merge($savedHeroSlides->slice(2))
            ->unique(fn (array $slide): string => (string) ($slide['image'] ?? ''))
            ->take(9)
            ->values()
            ->all();
        $heroFallbackImage = config('homepage.slides.0.image');
        $homeTexts = array_merge(config('homepage.texts', []), $savedSettings?->section_texts ?? []);
        $promoTickerPhrases = [
            'Navidad empieza en casa',
            'Detalles que se sienten como regalo',
            'Decora sin complicarte',
            'Momentos para compartir',
            'Ilumina cada rincón',
            'Un detalle especial',
        ];
        $promoTickerItems = $christmasProducts->values()
            ->map(function (Product $product, int $index) use ($promoTickerPhrases): array {
                return [
                    'eyebrow' => $promoTickerPhrases[$index % count($promoTickerPhrases)],
                    'name' => $product->name,
                    'image' => $product->coverImageModel()?->sizedUrl(220),
                    'url' => route('store.product', ['slug' => $product->slug]),
                ];
            })
            ->filter(fn (array $item): bool => filled($item['image']))
            ->values()
            ->all();

        $seoTitle = 'Brevare | Moda, tecnología, hogar y regalos en Perú';
        $seoDescription = 'Compra moda, tecnología, hogar y regalos en Brevare. Envíos a todo el Perú, pagos seguros y atención cercana.';
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
