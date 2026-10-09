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
            ->with(['brand', 'category', 'variants.supplierVariants', 'variants.images', 'variants.attributeValues.attribute', 'variants.attributeValues.value'])
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
            ->with(['brand', 'category', 'variants.supplierVariants', 'variants.images', 'variants.attributeValues.attribute', 'variants.attributeValues.value'])
            ->withCount(['reviews' => fn ($query) => $query->approved()])
            ->withAvg(['reviews' => fn ($query) => $query->approved()], 'rating')
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', function ($q) {
                        $q->active()->whereColumn('stock', '>', 'reserved_stock');
                    });
            })
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
            'tech' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=82',
            'fashion' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=800&q=82',
            'home' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=800&q=82',
            'kitchen' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=800&q=82',
            'beauty' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=800&q=82',
            'kids' => 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?auto=format&fit=crop&w=800&q=82',
            'sports' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=82',
            'general' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&w=800&q=82',
        ];

        $categories->each(function (Category $category) use ($categoryFallbacks) {
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
        });

        $brands = Brand::query()
            ->active()
            ->orderBy('sort_order')
            ->limit(10)
            ->get();

        $savedSettings = HomepageSetting::query()->first();
        $heroSlides = $savedSettings?->hero_slides ?: config('homepage.slides', []);
        $homeTexts = array_merge(config('homepage.texts', []), $savedSettings?->section_texts ?? []);
        $offerPhrases = $savedSettings?->rotating_phrases ?: config('homepage.rotating_phrases', []);
        $rotatingProducts = $featured->take(6)->values()->map(fn (Product $product, int $index) => [
            'phrase' => $offerPhrases[$index % max(1, count($offerPhrases))] ?? 'Encuentra algo especial',
            'name' => $product->name,
            'image' => $product->coverImage(),
            'url' => route('store.product', ['slug' => $product->slug]),
        ])->values();

        return view('livewire.store.home', compact('featured', 'latest', 'primaryCategories', 'categories', 'brands', 'heroSlides', 'rotatingProducts', 'homeTexts'));
    }
}
