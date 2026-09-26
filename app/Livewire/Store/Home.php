<?php

namespace App\Livewire\Store;

use App\Models\Brand;
use App\Models\Category;
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
            ->with(['brand', 'category', 'variants.supplierVariants', 'variants.images'])
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
            ->with(['brand', 'category', 'variants.supplierVariants', 'variants.images'])
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', function ($q) {
                        $q->active()->whereColumn('stock', '>', 'reserved_stock');
                    });
            })
            ->latest()
            ->limit(12)
            ->get();

        $categories = Category::query()
            ->where('is_visible', true)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->limit(8)
            ->get();

        $brands = Brand::query()
            ->active()
            ->orderBy('sort_order')
            ->limit(10)
            ->get();

        $heroSlides = [
            [
                'image' => 'https://images.pexels.com/photos/7989741/pexels-photo-7989741.jpeg?auto=compress&cs=tinysrgb&w=1600&h=900&fit=crop',
                'eyebrow' => 'Tecnología',
                'title' => 'Smartphones y laptops',
                'highlight' => 'hasta -20%',
                'subtitle' => 'Los equipos más nuevos de Samsung, Apple y HP con envío rápido en todo el Perú.',
                'cta' => 'Ver ofertas',
                'url' => route('store.category', ['path' => 'tecnologia']),
            ],
            [
                'image' => 'https://images.pexels.com/photos/34373606/pexels-photo-34373606.jpeg?auto=compress&cs=tinysrgb&w=1600&h=900&fit=crop',
                'eyebrow' => 'Moda',
                'title' => 'Nueva colección',
                'highlight' => 'Moda Mujer',
                'subtitle' => 'Vestidos, blazers y accesorios que se roban las miradas. Colección de temporada.',
                'cta' => 'Descubrir',
                'url' => route('store.category', ['path' => 'moda/mujer']),
            ],
            [
                'image' => 'https://images.pexels.com/photos/28609810/pexels-photo-28609810.jpeg?auto=compress&cs=tinysrgb&w=1600&h=900&fit=crop',
                'eyebrow' => 'Hogar',
                'title' => 'Renueva tu descanso',
                'highlight' => 'desde S/ 99',
                'subtitle' => 'Juegos de sábanas, acolchados y más para convertir tu dormitorio en un oasis.',
                'cta' => 'Ver dormitorio',
                'url' => route('store.category', ['path' => 'hogar/dormitorio']),
            ],
            [
                'image' => 'https://images.pexels.com/photos/16443132/pexels-photo-16443132.jpeg?auto=compress&cs=tinysrgb&w=1600&h=900&fit=crop',
                'eyebrow' => 'Cocina',
                'title' => 'Equipa tu cocina',
                'highlight' => 'hasta -25%',
                'subtitle' => 'Sets de cuchillos de chef, batidoras de mano y ollas antiadherentes premium.',
                'cta' => 'Explorar cocina',
                'url' => route('store.category', ['path' => 'hogar/cocina']),
            ],
        ];

        return view('livewire.store.home', compact('featured', 'latest', 'categories', 'brands', 'heroSlides'));
    }
}
