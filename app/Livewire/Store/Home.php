<?php

namespace App\Livewire\Store;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageSetting;
use App\Models\Product;
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

        $primaryCategories = Category::query()
            ->where('is_visible', true)
            ->whereNull('parent_id')
            ->whereIn('slug', ['hombre', 'mujer', 'nino', 'nina'])
            ->orderBy('position')
            ->get();

        $categories = Category::query()
            ->where('is_visible', true)
            ->whereNull('parent_id')
            ->whereNotIn('slug', ['hombre', 'mujer', 'nino', 'nina'])
            ->orderBy('position')
            ->limit(8)
            ->get();

        $categoryFallbacks = [
            'tech' => 'https://images.pexels.com/photos/7989741/pexels-photo-7989741.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'fashion' => 'https://images.pexels.com/photos/34373606/pexels-photo-34373606.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'home' => 'https://images.pexels.com/photos/14977714/pexels-photo-14977714.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'kitchen' => 'https://images.pexels.com/photos/16443132/pexels-photo-16443132.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'beauty' => 'https://images.pexels.com/photos/3388335/pexels-photo-3388335.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'kids' => 'https://images.pexels.com/photos/28609810/pexels-photo-28609810.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'sports' => 'https://images.pexels.com/photos/14059510/pexels-photo-14059510.jpeg?auto=compress&cs=tinysrgb&w=1000',
            'general' => 'https://images.pexels.com/photos/1477767/pexels-photo-1477767.jpeg?auto=compress&cs=tinysrgb&w=1000',
        ];

        $allCategories = $primaryCategories->concat($categories)->unique('id')->values();
        $discoveryCategory = $categories->first() ?? $allCategories->first();

        $allCategories->each(function (Category $category) use ($categoryFallbacks) {
            $label = mb_strtolower($category->name.' '.$category->path);
            $key = match (true) {
                str_contains($label, 'tecnolog') || str_contains($label, 'electr') || str_contains($label, 'celular') || str_contains($label, 'comput') => 'tech',
                str_contains($label, 'moda') || str_contains($label, 'ropa') || str_contains($label, 'calzado') => 'fashion',
                str_contains($label, 'cocina') => 'kitchen',
                str_contains($label, 'hogar') || str_contains($label, 'dormitorio') || str_contains($label, 'mueble') => 'home',
                str_contains($label, 'belleza') || str_contains($label, 'cuidado') || str_contains($label, 'salud') => 'beauty',
                str_contains($label, 'niño') || str_contains($label, 'bebe') || str_contains($label, 'juguete') => 'kids',
                str_contains($label, 'deporte') || str_contains($label, 'fitness') => 'sports',
                default => 'general',
            };

            $category->store_image = $category->image ?: $categoryFallbacks[$key];
            $category->store_image_fallback = $categoryFallbacks[$key];
        });

        $brands = Brand::query()
            ->active()
            ->orderBy('sort_order')
            ->limit(10)
            ->get();

        $savedSettings = HomepageSetting::query()->first();
        $heroSlides = $savedSettings?->hero_slides ?: config('homepage.slides', []);
        $heroFallbackImage = config('homepage.slides.0.image');
        $homeTexts = array_merge(config('homepage.texts', []), $savedSettings?->section_texts ?? []);
        $offerPhrases = $savedSettings?->rotating_phrases ?: config('homepage.rotating_phrases', []);
        $rotatingProducts = $featured->take(6)->values()->map(fn (Product $product, int $index) => [
            'phrase' => $offerPhrases[$index % max(1, count($offerPhrases))] ?? 'Encuentra algo especial',
            'name' => $product->name,
            'image' => $product->coverImageModel()?->sizedUrl(180),
            'url' => route('store.product', ['slug' => $product->slug]),
        ])->values();

        return view('livewire.store.home', compact('featured', 'latest', 'allCategories', 'discoveryCategory', 'brands', 'heroSlides', 'heroFallbackImage', 'rotatingProducts', 'homeTexts'));
    }
}
