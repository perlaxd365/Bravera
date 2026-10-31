<?php

namespace App\Livewire\Store;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Modules\Product\Repositories\StoreCatalogRepository;
use App\Support\Seo\CatalogLinks;
use App\Support\Seo\StructuredData;
use Illuminate\Database\Eloquent\Collection;
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

    #[Url]
    public bool $offers = false;

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

        $this->offers = $this->offers || request()->routeIs('store.offers');
    }

    public function updatedAttributeValues(): void
    {
        $this->filtersChanged();
    }

    public function updatedBrandId(): void
    {
        $this->filtersChanged();
    }

    public function updatedOffers(): void
    {
        $this->filtersChanged();
    }

    public function updatedMinPrice(): void
    {
        $this->filtersChanged();
    }

    public function updatedMaxPrice(): void
    {
        $this->filtersChanged();
    }

    public function clearBrand(): void
    {
        $this->brandId = null;
        $this->filtersChanged();
    }

    public function clearAttributeValue(int $valueId): void
    {
        $this->attributeValues = array_values(array_filter(
            $this->selectedAttributeValueIds(),
            fn (int $selectedId) => $selectedId !== $valueId,
        ));

        $this->filtersChanged();
    }

    public function clearPriceRange(): void
    {
        $this->reset(['minPrice', 'maxPrice']);
        $this->filtersChanged();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'brandId', 'attributeValues', 'minPrice', 'maxPrice', 'sort']);
        $this->filtersChanged();
    }

    public function render(StoreCatalogRepository $repository)
    {
        $storefront = \App\Support\Localization\StorefrontContext::current();
        $priceFilterRate = $storefront->isInternational() ? $storefront->exchangeRate : 1;
        $products = $repository->paginate(
            search: $this->search,
            offers: $this->offers,
            categoryPath: $this->categoryPath,
            brandId: $this->brandId,
            attributeValueIds: $this->selectedAttributeValueIds(),
            minPrice: $this->minPrice !== null ? $this->minPrice * $priceFilterRate : null,
            maxPrice: $this->maxPrice !== null ? $this->maxPrice * $priceFilterRate : null,
            sort: $this->sort,
        );

        $similarProducts = filled($this->search)
            ? $repository->similar(
                $this->search,
                excludeIds: $products->getCollection()->modelKeys(),
                limit: 8,
            )
            : new Collection;

        $currentCategory = $this->categoryPath
            ? Category::with('parent')->where('path', $this->categoryPath)->first()
            : null;

        $subcategories = $currentCategory
            ? Category::where('parent_id', $currentCategory->id)
                ->where('is_visible', true)
                ->orderBy('position')
                ->get()
            : collect();

        $categories = CatalogLinks::home(12);

        $brands = Brand::active()->orderBy('sort_order')->get();

        $attributes = Attribute::query()
            ->active()
            ->filterable()
            ->with('values')
            ->orderBy('sort_order')
            ->get();

        $hasActiveFilters = filled($this->search)
            || filled($this->brandId)
            || filled($this->minPrice)
            || filled($this->maxPrice)
            || $this->selectedAttributeValueIds() !== []
            || $this->sort !== 'latest';

        $label = $currentCategory
            ? CatalogLinks::label($currentCategory)
            : ($this->offers ? 'Ofertas' : ($this->search ? 'Resultados de búsqueda' : 'Catálogo completo'));

        $seoTitle = $currentCategory?->meta_title
            ?: ($currentCategory
                ? $label.' en Brevare | Compra online'
                : ($this->offers
                    ? 'Ofertas | Brevare'
                    : ($this->search ? 'Resultados de búsqueda | Brevare' : 'Catálogo completo | Brevare')));

        $seoDescription = $currentCategory?->meta_description
            ?: ($currentCategory?->description
                ?: ($currentCategory
                    ? 'Compra '.$label.' en Brevare. Envíos a todo el Perú, pagos seguros y atención cercana.'
                    : ($this->offers
                        ? 'Descubre los productos de Brevare con descuento y compra online con envío a todo el Perú.'
                        : 'Explora moda, tecnología, hogar y regalos en Brevare. Envíos a todo el Perú.')));

        $canonicalUrl = $currentCategory
            ? route('store.category', ['path' => $currentCategory->path])
            : ($this->offers ? route('store.offers') : route('store.search'));

        if ($products->currentPage() > 1 && ! $hasActiveFilters) {
            $canonicalUrl .= '?page='.$products->currentPage();
        }

        $breadcrumbs = [
            ['name' => 'Inicio', 'url' => route('home')],
            ['name' => 'Catálogo', 'url' => route('store.search')],
        ];

        if ($this->offers) {
            $breadcrumbs = [
                ['name' => 'Inicio', 'url' => route('home')],
                ['name' => 'Ofertas', 'url' => route('store.offers')],
            ];
        }

        if ($currentCategory?->parent) {
            $breadcrumbs[] = [
                'name' => CatalogLinks::label($currentCategory->parent),
                'url' => route('store.category', ['path' => $currentCategory->parent->path]),
            ];
        }

        if ($currentCategory) {
            $breadcrumbs[] = [
                'name' => $label,
                'url' => route('store.category', ['path' => $currentCategory->path]),
            ];
        } elseif ($this->search) {
            $breadcrumbs[] = [
                'name' => 'Resultados para "'.$this->search.'"',
                'url' => $canonicalUrl,
            ];
        }

        $breadcrumbStructuredData = StructuredData::breadcrumbs(
            $breadcrumbs,
            $canonicalUrl.'#breadcrumb',
        );

        $itemListEntries = $products->getCollection()
            ->take(24)
            ->values()
            ->map(fn ($product, int $index) => [
                'name' => $product->name,
                'url' => route('store.product', ['slug' => $product->slug]),
                'position' => (($products->currentPage() - 1) * $products->perPage()) + $index + 1,
            ])
            ->all();

        $itemList = StructuredData::itemList(
            $itemListEntries,
            $currentCategory ? 'Productos en '.$label : 'Catálogo de Brevare',
            $canonicalUrl.'#itemlist',
        );

        $pageStructuredData = [
            StructuredData::webPage(
                'CollectionPage',
                $canonicalUrl,
                $seoTitle,
                $seoDescription,
                $canonicalUrl.'#breadcrumb',
                $canonicalUrl.'#itemlist',
            ),
            $itemList,
        ];

        $seoImage = $currentCategory?->image && filter_var($currentCategory->image, FILTER_VALIDATE_URL)
            ? $currentCategory->image
            : asset('favicon-192.png');

        return view('livewire.store.catalog', compact(
            'products',
            'currentCategory',
            'subcategories',
            'categories',
            'brands',
            'attributes',
            'similarProducts',
        ))->title($seoTitle)->layoutData([
            'seoDescription' => $seoDescription,
            'seoImage' => $seoImage,
            'canonicalUrl' => $canonicalUrl,
            'noIndex' => $hasActiveFilters,
            'breadcrumbStructuredData' => $breadcrumbStructuredData,
            'pageStructuredData' => $pageStructuredData,
        ]);
    }

    /**
     * Normaliza los valores seleccionados, incluyendo URLs creadas con el formato anterior.
     *
     * @return array<int, int>
     */
    private function selectedAttributeValueIds(): array
    {
        return collect($this->attributeValues)
            ->flatMap(fn ($value) => is_array($value) ? $value : [$value])
            ->filter(fn ($value) => is_int($value) || (is_string($value) && ctype_digit($value)))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    private function filtersChanged(): void
    {
        $this->resetPage();
        $this->dispatch('catalog-filters-updated');
    }
}
