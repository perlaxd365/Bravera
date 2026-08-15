<?php

namespace App\Livewire\Admin\Catalog\Suppliers;

use App\Livewire\Forms\SupplierForm;
use App\Modules\Location\Services\LocationService;
use App\Repositories\SupplierRepository;
use App\Services\SupplierService;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public SupplierForm $form;

    public bool $show = false;

    /**
     * Ubicación seleccionada en el formulario.
     */
    public ?int $departmentId = null;

    public ?int $provinceId = null;

    public ?int $districtId = null;

    /**
     * Catálogos dependientes.
     */
    public $departments = [];

    public $provinces = [];

    public $districts = [];

    protected SupplierRepository $repository;

    protected SupplierService $service;

    protected LocationService $locationService;

    public function boot(
        SupplierRepository $repository,
        SupplierService $service,
        LocationService $locationService
    ): void {
        $this->repository = $repository;
        $this->service = $service;
        $this->locationService = $locationService;
    }

    /**
     * Inicializa los departamentos.
     */
    public function mount(): void
    {
        $this->departments = $this->locationService->getDepartments();
    }

    #[On('supplier-create')]
    public function create(): void
    {
        $this->form->resetForm();

        $this->departmentId = null;
        $this->provinceId = null;
        $this->districtId = null;

        $this->provinces = [];
        $this->districts = [];

        $this->resetValidation();

        $this->show = true;
    }

    #[On('supplier-edit')]
    public function edit(int $id): void
    {
        $supplier = $this->repository->find($id);

        $this->form->fromModel($supplier);

        $this->resetValidation();

        $this->departmentId = null;
        $this->provinceId = null;
        $this->districtId = $supplier->location_id;

        $this->provinces = [];
        $this->districts = [];

        if ($supplier->location_id) {
            $location = $this->locationService
                ->findWithHierarchy($supplier->location_id);

            if ($location) {
                $this->districtId = $location->id;

                if ($location->parent) {
                    $this->provinceId = $location->parent->id;

                    if ($location->parent->parent) {
                        $this->departmentId = $location->parent->parent->id;
                    }
                }

                if ($this->departmentId) {
                    $this->provinces = $this->locationService
                        ->getProvinces($this->departmentId);
                }

                if ($this->provinceId) {
                    $this->districts = $this->locationService
                        ->getDistricts($this->provinceId);
                }
            }
        }

        $this->show = true;
    }

    /**
     * Cuando cambia el departamento.
     */
    public function updatedDepartmentId($value): void
    {
        $this->provinceId = null;
        $this->districtId = null;
        $this->form->location_id = null;

        $this->provinces = [];
        $this->districts = [];

        if ($value) {
            $this->provinces = $this->locationService
                ->getProvinces((int) $value);
        }
    }

    /**
     * Cuando cambia la provincia.
     */
    public function updatedProvinceId($value): void
    {
        $this->districtId = null;
        $this->form->location_id = null;

        $this->districts = [];

        if ($value) {
            $this->districts = $this->locationService
                ->getDistricts((int) $value);
        }
    }

    /**
     * Cuando cambia el distrito.
     */
    public function updatedDistrictId($value): void
    {
        $this->form->location_id = $value
            ? (int) $value
            : null;
    }

    public function save(): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $supplier = $this->repository->find($this->form->id);

            $this->service->update(
                $supplier,
                $this->form->toDto()
            );

            $message = 'Proveedor actualizado correctamente.';
        } else {
            $this->service->create(
                $this->form->toDto()
            );

            $message = 'Proveedor creado correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->dispatch('supplier-saved');

        $this->show = false;

        $this->form->resetForm();

        $this->departmentId = null;
        $this->provinceId = null;
        $this->districtId = null;

        $this->provinces = [];
        $this->districts = [];
    }

    public function render()
    {
        return view('livewire.admin.catalog.suppliers.form');
    }
}
