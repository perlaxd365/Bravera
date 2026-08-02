<?php

namespace App\Livewire\Admin\Catalog\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Modules\Product\Forms\ProductForm;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Services\ProductService;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    /**
     * Form Object.
     */
    public ProductForm $form;

    /**
     * Mostrar modal.
     */
    public bool $show = false;

    /**
     * Repositorio.
     */
    protected ProductRepository $repository;

    /**
     * Servicio.
     */
    protected ProductService $service;

    /**
     * Inicializar dependencias.
     */
    public function boot(
        ProductRepository $repository,
        ProductService $service
    ): void {

        $this->repository = $repository;

        $this->service = $service;
    }

    /**
     * Nuevo producto.
     */
    #[On('product-create')]
    public function create(): void
    {
        $this->form->resetForm();

        $this->resetValidation();

        $this->show = true;
    }

    /**
     * Editar producto.
     */
    #[On('product-edit')]
    public function edit(int $id): void
    {
        $product = $this->repository->find($id);

        $this->form->fromModel($product);

        $this->resetValidation();

        $this->show = true;
    }

    /**
     * Guardar producto.
     */
    public function save(): void
    {
        $this->form->validate();

        if ($this->form->id) {

            $product = $this->repository->find($this->form->id);

            $this->service->update(
                $product,
                $this->form->toDto()
            );

            $message = 'Producto actualizado correctamente.';
        } else {

            $this->service->create(
                $this->form->toDto()
            );

            $message = 'Producto creado correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->dispatch('product-saved');

        $this->show = false;

        $this->form->resetForm();
    }

    /**
     * Renderizar componente.
     */
    public function render()
    {
        return view(
            'livewire.admin.catalog.products.form',
            [
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
