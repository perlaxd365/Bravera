<?php

namespace App\Livewire\Admin\Catalog\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Services\ProductReadinessService;
use App\Modules\Product\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    /**
     * Texto de búsqueda.
     */
    public string $search = '';

    /**
     * Registros por página.
     */
    public int $perPage = 10;

    /**
     * Filtro categoría.
     */
    public ?int $categoryId = null;

    /**
     * Filtro marca.
     */
    public ?int $brandId = null;

    /**
     * Orden del listado.
     */
    public string $sort = 'latest';

    /**
     * Repositorio.
     */
    protected ProductRepository $repository;

    /**
     * Servicio.
     */
    protected ProductService $service;

    protected ProductReadinessService $readiness;

    /**
     * Inicializar dependencias.
     */
    public function boot(
        ProductRepository $repository,
        ProductService $service,
        ProductReadinessService $readiness
    ): void {

        $this->repository = $repository;

        $this->service = $service;

        $this->readiness = $readiness;
    }

    /**
     * Reinicia la paginación cuando cambia la búsqueda.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reinicia la paginación cuando cambia la categoría.
     */
    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    /**
     * Reinicia la paginación cuando cambia la marca.
     */
    public function updatingBrandId(): void
    {
        $this->resetPage();
    }

    /**
     * Reinicia la paginación cuando cambia el orden.
     */
    public function updatingSort(): void
    {
        $this->resetPage();
    }

    /**
     * Refresca el listado.
     */
    #[On('product-saved')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    /**
     * Eliminar producto.
     */
    public function delete(Product $product): void
    {
        $this->service->delete($product);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    /**
     * Renderizar componente.
     */
    public function render()
    {
        $products = $this->repository->paginate(
            perPage: $this->perPage,
            search: $this->search,
            categoryId: $this->categoryId,
            brandId: $this->brandId,
            sort: $this->sort,
        );

        return view(
            'livewire.admin.catalog.products.index',
            [
                'products' => $products,

                'readinessByProduct' => $products->getCollection()
                    ->mapWithKeys(fn (Product $product): array => [
                        $product->id => $this->readiness->assess($product),
                    ]),

                'categories' => Category::query()
                    ->where('is_visible', true)
                    ->orderBy('position')
                    ->get(),

                'brands' => Brand::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(),
            ]
        );
    }
}
