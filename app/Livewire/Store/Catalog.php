<?php

namespace App\Livewire\Store;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Modules\Product\Repositories\StoreCatalogRepository;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('store.layouts.app')]
class Catalog extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(history: true)]
    public ?string $categoryPath = null;

    #[Url(history: true)]
    public ?int $brandId = null;

    #[Url(history: true)]
    public array $attributeValues = [];

    #[Url(history: true)]
    public ?float $minPrice = null;

    #[Url(history: true)]
    public ?float $maxPrice = null;

    #[Url(history: true)]
    public string $sort = 'latest';

    protected $queryString = [
        'search' => ['as' => 'q', 'except' => ''],
    ];

    public function mount(?string $path = null, ?string $q = null): void
    {
        if ($path) {
            $this->categoryPath = $path;

            abort_unless(Category::where('path', $path)->exists(), 404);
        }

        if ($q) {
            $this->search = $q;
        }
    }

    public function updatedAttributeValues(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'brandId', 'attributeValues', 'minPrice', 'maxPrice', 'sort']);
        $this->resetPage();
    }

    public function render(StoreCatalogRepository $repository)
    {
        $products = $repository->paginate(
            search: $this->search,
            categoryPath: $this->categoryPath,
            brandId: $this->brandId,
            attributeValueIds: array_values(array_filter($this->attributeValues)),
            minPrice: $this->minPrice,
            maxPrice: $this->maxPrice,
            sort: $this->sort,
        );

        $currentCategory = $this->categoryPath
            ? Category::where('path', $this->categoryPath)->first()
            : null;

        $subcategories = $currentCategory
            ? Category::where('parent_id', $currentCategory->id)
                ->where('is_visible', true)
                ->orderBy('position')
                ->get()
            : collect();

        $categories = Category::where('is_visible', true)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->get();

        $brands = Brand::active()->orderBy('sort_order')->get();

        $attributes = Attribute::query()
            ->active()
            ->filterable()
            ->with('values')
            ->orderBy('sort_order')
            ->get();

        return view('livewire.store.catalog', compact(
            'products',
            'currentCategory',
            'subcategories',
            'categories',
            'brands',
            'attributes',
        ));
    }
}
