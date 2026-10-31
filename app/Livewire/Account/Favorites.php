<?php

namespace App\Livewire\Account;

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.account')]
class Favorites extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public function render()
    {
        $products = Product::query()
            ->active()
            ->visible()
            ->whereHas('favorites', fn ($query) => $query->where('user_id', auth()->id()))
            ->with([
                'brand',
                'category',
                'variants.images',
                'variants.supplierVariants.supplier',
                'variants.attributeValues.attribute',
                'variants.attributeValues.value',
            ])
            ->withCount(['reviews' => fn ($query) => $query->approved()])
            ->withAvg(['reviews' => fn ($query) => $query->approved()], 'rating')
            ->latest()
            ->paginate(12);

        return view('livewire.account.favorites', compact('products'));
    }
}
