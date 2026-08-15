<?php

namespace App\Livewire\Admin\Shipping\Rates;

use App\Models\ShippingRate;
use App\Modules\Shipping\Repositories\ShippingRateRepository;
use App\Modules\Shipping\Services\ShippingRateService;
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

    protected ShippingRateRepository $repository;

    protected ShippingRateService $service;

    public function boot(
        ShippingRateRepository $repository,
        ShippingRateService $service
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
     * Refresca el listado cuando se guarda una tarifa.
     */
    #[On('shipping-rate-saved')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    /**
     * Elimina una tarifa.
     */
    public function delete(ShippingRate $rate): void
    {
        $this->service->delete($rate);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Tarifa de envío eliminada correctamente.',
        ]);
    }

    /**
     * Activa o desactiva una tarifa.
     */
    public function toggleStatus(ShippingRate $rate): void
    {
        $this->service->toggleStatus($rate);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Estado de la tarifa actualizado correctamente.',
        ]);
    }

    public function render()
    {
        return view(
            'livewire.admin.shipping.rates.index',
            [
                'rates' => $this->repository->paginate(
                    perPage: $this->perPage,
                    search: $this->search,
                ),
            ]
        );
    }
}
