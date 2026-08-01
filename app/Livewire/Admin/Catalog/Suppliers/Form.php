<?php

namespace App\Livewire\Admin\Catalog\Suppliers;

use App\Livewire\Forms\SupplierForm;
use App\Repositories\SupplierRepository;
use App\Services\SupplierService;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public SupplierForm $form;

    public bool $show = false;

    protected SupplierRepository $repository;

    protected SupplierService $service;

    public function boot(
        SupplierRepository $repository,
        SupplierService $service
    ): void {
        $this->repository = $repository;
        $this->service = $service;
    }

    #[On('supplier-create')]
    public function create(): void
    {
        $this->form->resetForm();

        $this->resetValidation();

        $this->show = true;

        logger('Show: ' . $this->show);
    }
    #[On('supplier-edit')]
    public function edit(int $id): void
    {
        $supplier = $this->repository->find($id);

        $this->form->fromModel($supplier);

        $this->resetValidation();

        $this->show = true;
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
    }

    public function render()
    {
        return view('livewire.admin.catalog.suppliers.form');
    }
}
