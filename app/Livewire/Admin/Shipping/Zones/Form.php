<?php

namespace App\Livewire\Admin\Shipping\Zones;

use App\Livewire\Forms\ShippingZoneForm;
use App\Modules\Location\Services\LocationService;
use App\Modules\Shipping\Enums\ShippingZoneType;
use App\Modules\Shipping\Repositories\ShippingZoneRepository;
use App\Modules\Shipping\Services\ShippingZoneService;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ShippingZoneForm $form;

    public bool $show = false;

    public ?int $departmentId = null;

    public ?int $provinceId = null;

    public ?int $districtId = null;

    public string $zoneType = '';

    public $departments = [];

    public $provinces = [];

    public $districts = [];

    protected ShippingZoneRepository $repository;

    protected ShippingZoneService $service;

    protected LocationService $locationService;

    public function boot(
        ShippingZoneRepository $repository,
        ShippingZoneService $service,
        LocationService $locationService
    ): void {
        $this->repository = $repository;
        $this->service = $service;
        $this->locationService = $locationService;
    }

    public function mount(): void
    {
        $this->departments = $this->locationService->getDepartments();
    }

    #[On('shipping-zone-create')]
    public function create(): void
    {
        $this->form->resetForm();

        $this->zoneType = '';

        $this->departmentId = null;
        $this->provinceId = null;
        $this->districtId = null;

        $this->provinces = [];
        $this->districts = [];

        $this->resetValidation();

        $this->show = true;
    }

    #[On('shipping-zone-edit')]
    public function edit(int $id): void
    {
        $zone = $this->repository->find($id);

        if (! $zone) {
            return;
        }

        $this->form->fromModel($zone);

        $this->zoneType = $zone->type->value;

        $this->departmentId = null;
        $this->provinceId = null;
        $this->districtId = null;

        $this->provinces = [];
        $this->districts = [];

        $this->resetValidation();

        $location = $this->locationService->findWithHierarchy(
            $zone->location_id
        );

        if ($location) {
            $this->setLocationHierarchy($location);
        }

        $this->show = true;
    }

    public function updatedZoneType($value): void
    {
        $this->departmentId = null;
        $this->provinceId = null;
        $this->districtId = null;

        $this->form->location_id = null;

        $this->provinces = [];
        $this->districts = [];
    }

    public function updatedDepartmentId($value): void
    {
        $this->provinceId = null;
        $this->districtId = null;

        $this->form->location_id = null;

        $this->provinces = [];
        $this->districts = [];

        if ($value) {
            $this->provinces = $this->locationService->getProvinces(
                (int) $value
            );
        }
    }

    public function updatedProvinceId($value): void
    {
        $this->districtId = null;

        $this->form->location_id = null;

        $this->districts = [];

        if ($value) {
            $this->districts = $this->locationService->getDistricts(
                (int) $value
            );
        }
    }

    public function updatedDistrictId($value): void
    {
        if ($value) {
            $this->form->location_id = (int) $value;
        }
    }

    public function save(): void
    {
        $this->setLocationId();

        $this->form->validate();

        if ($this->form->id) {
            $zone = $this->repository->find($this->form->id);

            if (! $zone) {
                return;
            }

            $this->service->update(
                $zone,
                $this->form->toDto()
            );

            $message = 'Zona de envío actualizada correctamente.';
        } else {
            $this->service->create(
                $this->form->toDto()
            );

            $message = 'Zona de envío creada correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->dispatch('shipping-zone-saved');

        $this->close();
    }

    public function close(): void
    {
        $this->show = false;

        $this->form->resetForm();

        $this->zoneType = '';

        $this->departmentId = null;
        $this->provinceId = null;
        $this->districtId = null;

        $this->provinces = [];
        $this->districts = [];

        $this->resetValidation();
    }

    private function setLocationId(): void
    {
        $locationId = match ($this->zoneType) {
            ShippingZoneType::DEPARTMENT->value => $this->departmentId,
            ShippingZoneType::PROVINCE->value => $this->provinceId,
            ShippingZoneType::DISTRICT->value => $this->districtId,
            default => null,
        };

        $this->form->location_id = $locationId
            ? (int) $locationId
            : null;

        $this->form->type = $this->zoneType;
    }

    private function setLocationHierarchy($location): void
    {
        if ($location->level->value === ShippingZoneType::DEPARTMENT->value) {
            $this->departmentId = $location->id;

            return;
        }

        if ($location->level->value === ShippingZoneType::PROVINCE->value) {
            $this->provinceId = $location->id;

            if ($location->parent) {
                $this->departmentId = $location->parent->id;

                $this->provinces = $this->locationService->getProvinces(
                    $this->departmentId
                );
            }

            return;
        }

        if ($location->level->value === ShippingZoneType::DISTRICT->value) {
            $this->districtId = $location->id;

            if ($location->parent) {
                $this->provinceId = $location->parent->id;

                $this->districts = $this->locationService->getDistricts(
                    $this->provinceId
                );

                if ($location->parent->parent) {
                    $this->departmentId = $location->parent->parent->id;

                    $this->provinces = $this->locationService->getProvinces(
                        $this->departmentId
                    );
                }
            }
        }
    }

    public function render()
    {
        return view(
            'livewire.admin.shipping.zones.form'
        );
    }
}
