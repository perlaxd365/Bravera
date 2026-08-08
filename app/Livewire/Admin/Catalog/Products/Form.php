<?php

namespace App\Livewire\Admin\Catalog\Products;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Supplier;
use App\Modules\Product\Forms\ProductForm;
use App\Modules\Product\Forms\ProductVariantForm;
use App\Modules\Product\Forms\SupplierVariantForm;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Services\ProductService;
use App\Modules\Product\Services\ProductVariantService;
use App\Modules\Product\Services\SupplierVariantService;
use Livewire\Attributes\On;
use Livewire\Component;



class Form extends Component
{
    /**
     * Form Object del producto.
     */
    public ProductForm $form;

    /**
     * Form Object de la variante.
     */
    public ProductVariantForm $variantForm;

    /**
     * Form Object del proveedor de la variante.
     */
    public SupplierVariantForm $supplierVariantForm;


    /**
     * Variante actualmente seleccionada para administrar proveedores.
     */
    public ?int $supplierVariantVariantId = null;

    /**
     * Mostrar modal.
     */
    public bool $show = false;

    /**
     * Repositorio de productos.
     */
    protected ProductRepository $repository;

    /**
     * Servicio de productos.
     */
    protected ProductService $service;

    /**
     * Servicio de variantes.
     */
    protected ProductVariantService $variantService;

    /**
     * Servicio de proveedores de variantes.
     */
    protected SupplierVariantService $supplierVariantService;

    /**
     * Inicializar dependencias.
     */
    public function boot(
        ProductRepository $repository,
        ProductService $service,
        ProductVariantService $variantService,
        SupplierVariantService $supplierVariantService
    ): void {
        $this->repository = $repository;

        $this->service = $service;

        $this->variantService = $variantService;

        $this->supplierVariantService = $supplierVariantService;
    }

    /**
     * Nuevo producto.
     */
    #[On('product-create')]
    public function create(): void
    {
        $this->form->resetForm();

        $this->variantForm->resetForm();

        $this->supplierVariantForm->resetForm();

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

        if (!$product) {
            return;
        }

        $this->form->fromModel($product);

        $this->variantForm->resetForm();

        $this->supplierVariantForm->resetForm();

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

            if (!$product) {
                return;
            }

            $this->service->update(
                $product,
                $this->form->toDto()
            );

            $message = 'Producto actualizado correctamente.';
        } else {
            $product = $this->service->create(
                $this->form->toDto()
            );

            /*
             * Cargamos el ID del producto recién creado.
             *
             * Esto permite continuar trabajando con sus
             * variantes sin cerrar el modal.
             */
            $this->form->fromModel($product);

            $message = 'Producto creado correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->dispatch('product-saved');

        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANTES
    |--------------------------------------------------------------------------
    */

    /**
     * Preparar formulario para nueva variante.
     */
    public function createVariant(): void
    {
        if (!$this->form->id) {
            return;
        }

        $this->variantForm->resetForm();

        $this->variantForm->product_id = $this->form->id;

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();
    }

    /**
     * Editar variante.
     */
    public function editVariant(int $id): void
    {
        if (!$this->form->id) {
            return;
        }

        $variant = $this->variantService->find($id);

        if (!$variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        |
        | La variante debe pertenecer al producto actual.
        |
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->variantForm->fromModel($variant);

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();
    }

    /**
     * Guardar o actualizar variante.
     */
    public function saveVariant(): void
    {
        if (!$this->form->id) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero debes guardar el producto.',
            ]);

            return;
        }

        $this->variantForm->product_id = $this->form->id;

        $this->variantForm->validate();

        if ($this->variantForm->id) {
            $variant = $this->variantService->find(
                $this->variantForm->id
            );

            if (!$variant) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Seguridad
            |--------------------------------------------------------------------------
            */

            if ($variant->product_id !== $this->form->id) {
                return;
            }

            $this->variantService->update(
                $variant,
                $this->variantForm->toDto()
            );

            $message = 'Variante actualizada correctamente.';
        } else {
            $this->variantService->create(
                $this->variantForm->toDto()
            );

            $message = 'Variante creada correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->variantForm->resetForm();

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();
    }

    /**
     * Eliminar variante.
     */
    public function deleteVariant(int $id): void
    {
        if (!$this->form->id) {
            return;
        }

        $variant = $this->variantService->find($id);

        if (!$variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->variantService->delete($variant);

        if ($this->variantForm->id === $id) {
            $this->variantForm->resetForm();
        }

        $this->supplierVariantForm->resetForm();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Variante eliminada correctamente.',
        ]);
    }

    /**
     * Cambiar estado de una variante.
     */
    public function toggleVariantStatus(int $id): void
    {
        if (!$this->form->id) {
            return;
        }

        $variant = $this->variantService->find($id);

        if (!$variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->variantService->toggleStatus($variant);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Estado de la variante actualizado.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PROVEEDORES DE VARIANTES
    |--------------------------------------------------------------------------
    */

    /**
     * Preparar formulario para nuevo proveedor.
     */
    /**
     * Preparar formulario para nuevo proveedor.
     */
    public function createSupplierVariant(int $variantId): void
    {
        if (!$this->form->id) {
            return;
        }

        $variant = $this->variantService->find($variantId);

        if (!$variant) {
            return;
        }

        /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    |
    | La variante debe pertenecer al producto actual.
    |
    */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        /*
    |--------------------------------------------------------------------------
    | Variante seleccionada
    |--------------------------------------------------------------------------
    */

        $this->supplierVariantVariantId = $variant->id;

        /*
    |--------------------------------------------------------------------------
    | Preparar formulario
    |--------------------------------------------------------------------------
    */

        $this->supplierVariantForm->resetForm();

        $this->supplierVariantForm->product_variant_id = $variant->id;

        $this->resetValidation();
    }

    /**
     * Editar proveedor de variante.
     */
    public function editSupplierVariant(int $id): void
    {
        if (!$this->form->id) {
            return;
        }

        $supplierVariant = $this->supplierVariantService->find($id);

        if (!$supplierVariant) {
            return;
        }

        $variant = $supplierVariant->productVariant;

        if (!$variant) {
            return;
        }

        /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->supplierVariantVariantId = $variant->id;

        $this->supplierVariantForm->fromModel($supplierVariant);

        $this->resetValidation();
    }

    /**
     * Guardar o actualizar proveedor de variante.
     */
    public function saveSupplierVariant(): void
    {
        if (!$this->form->id) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero debes guardar el producto.',
            ]);

            return;
        }

        if (!$this->supplierVariantForm->product_variant_id) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero debes seleccionar una variante.',
            ]);

            return;
        }

        $variant = $this->variantService->find(
            $this->supplierVariantForm->product_variant_id
        );

        if (!$variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->supplierVariantForm->validate();

        /*
        |--------------------------------------------------------------------------
        | Actualizar
        |--------------------------------------------------------------------------
        */

        if ($this->supplierVariantForm->id) {
            $supplierVariant = $this->supplierVariantService->find(
                $this->supplierVariantForm->id
            );

            if (!$supplierVariant) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Seguridad adicional
            |--------------------------------------------------------------------------
            |
            | El proveedor debe pertenecer a la variante actual.
            |
            */

            if (
                $supplierVariant->product_variant_id !== $variant->id
            ) {
                return;
            }

            $this->supplierVariantService->update(
                $supplierVariant,
                $this->supplierVariantForm->toDto()
            );

            $message = 'Proveedor de variante actualizado correctamente.';
        } else {
            /*
            |--------------------------------------------------------------------------
            | Crear
            |--------------------------------------------------------------------------
            */

            $this->supplierVariantService->create(
                $this->supplierVariantForm->toDto()
            );

            $message = 'Proveedor de variante creado correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();
    }

    /**
     * Eliminar proveedor de variante.
     */
    public function deleteSupplierVariant(int $id): void
    {
        if (!$this->form->id) {
            return;
        }

        $supplierVariant = $this->supplierVariantService->find($id);

        if (!$supplierVariant) {
            return;
        }

        $variant = $supplierVariant->productVariant;

        if (!$variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->supplierVariantService->delete(
            $supplierVariant
        );

        if ($this->supplierVariantForm->id === $id) {
            $this->supplierVariantForm->resetForm();
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Proveedor de variante eliminado correctamente.',
        ]);
    }

    /**
     * Cambiar estado del proveedor de variante.
     */
    public function toggleSupplierVariantStatus(int $id): void
    {
        if (!$this->form->id) {
            return;
        }

        $supplierVariant = $this->supplierVariantService->find($id);

        if (!$supplierVariant) {
            return;
        }

        $variant = $supplierVariant->productVariant;

        if (!$variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->supplierVariantService->toggleStatus(
            $supplierVariant
        );

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Estado del proveedor actualizado.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        $variants = collect();

        if ($this->form->id) {
            $variants = $this->variantService->paginate(
                productId: $this->form->id,
                search: null,
                perPage: 50
            );
        }
        $supplierVariants = collect();
        if ($this->supplierVariantVariantId) {
            $supplierVariants = $this->supplierVariantService->paginate(
                productVariantId: $this->supplierVariantVariantId,
                search: null,
                perPage: 50
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Atributos disponibles para las variantes
        |--------------------------------------------------------------------------
        */

        $attributes = Attribute::query()
            ->active()
            ->with([
                'values' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('value');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Proveedores disponibles
        |--------------------------------------------------------------------------
        |
        | Los proveedores siguen perteneciendo a la aplicación general,
        | no al módulo Product.
        |
        */

        $suppliers = Supplier::query()
            ->active()
            ->orderBy('business_name')
            ->orderBy('trade_name')
            ->get();

        return view(
            'livewire.admin.catalog.products.form',
            [
                'supplierVariants' => $supplierVariants,
                'categories' => Category::query()
                    ->where('is_visible', true)
                    ->orderBy('position')
                    ->get(),

                'brands' => Brand::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(),

                'variants' => $variants,

                'attributes' => $attributes,

                'suppliers' => $suppliers,
            ]
        );
    }
}
