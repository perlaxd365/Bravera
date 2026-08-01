<?php

namespace App\Livewire\Admin\Catalog\Suppliers;

use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use App\Services\SupplierService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public int $perPage = 10;

    protected SupplierRepository $repository;

    protected SupplierService $service;

    public function boot(
        SupplierRepository $repository,
        SupplierService $service
    ): void {
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Reinicia la paginación cuando cambia la búsqueda.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Refresca el listado cuando se guarda un proveedor.
     */
    #[On('supplier-saved')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    /**
     * Elimina un proveedor.
     */
    public function delete(Supplier $supplier): void
    {
        $this->service->delete($supplier);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Proveedor eliminado correctamente.',
        ]);
    }

    /**
     * Renderiza el componente.
     */
    public function render()
    {
        return view(
            'livewire.admin.catalog.suppliers.index',
            [
                'suppliers' => $this->repository->paginate(
                    perPage: $this->perPage,
                    search: $this->search,
                ),
            ]
        );
    }
}
