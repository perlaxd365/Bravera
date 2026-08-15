<?php

namespace App\Livewire\Admin\Shipping\Zones;

use App\Models\ShippingZone;
use App\Modules\Shipping\Repositories\ShippingZoneRepository;
use App\Modules\Shipping\Services\ShippingZoneService;
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

    protected ShippingZoneRepository $repository;

    protected ShippingZoneService $service;

    public function boot(
        ShippingZoneRepository $repository,
        ShippingZoneService $service
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
     * Refresca el listado cuando se guarda una zona.
     */
    #[On('shipping-zone-saved')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    /**
     * Elimina una zona.
     */
    public function delete(ShippingZone $zone): void
    {
        $this->service->delete($zone);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Zona de envío eliminada correctamente.',
        ]);
    }

    /**
     * Activa o desactiva una zona.
     */
    public function toggleStatus(ShippingZone $zone): void
    {
        $this->service->toggleStatus($zone);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Estado de la zona actualizado correctamente.',
        ]);
    }

    /**
     * Renderiza el componente.
     */
    public function render()
    {
        return view(
            'livewire.admin.shipping.zones.index',
            [
                'zones' => $this->repository->paginate(
                    perPage: $this->perPage,
                    search: $this->search,
                ),
            ]
        );
    }
}
