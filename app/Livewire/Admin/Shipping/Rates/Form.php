<?php

namespace App\Livewire\Admin\Shipping\Rates;

use App\Livewire\Forms\ShippingRateForm;
use App\Models\ShippingRate;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Modules\Shipping\Repositories\ShippingRateRepository;
use App\Modules\Shipping\Repositories\ShippingZoneRepository;
use App\Modules\Shipping\Services\ShippingRateService;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ShippingRateForm $form;

    public bool $show = false;

    public $suppliers = [];

    public $shippingZones = [];

    public $products = [];

    public $variants = [];

    protected ShippingRateRepository $repository;

    protected ShippingRateService $service;

    protected ShippingZoneRepository $shippingZoneRepository;

    public function boot(
        ShippingRateRepository $repository,
        ShippingRateService $service,
        ShippingZoneRepository $shippingZoneRepository
    ): void {
        $this->repository = $repository;
        $this->service = $service;
        $this->shippingZoneRepository = $shippingZoneRepository;
    }

    public function mount(): void
    {
        $this->suppliers = Supplier::query()
            ->active()
            ->orderBy('business_name')
            ->get();

        $this->shippingZones = $this->shippingZoneRepository
            ->getActive();
    }

    /**
     * Crear una nueva tarifa.
     */
    #[On('shipping-rate-create')]
    public function create(): void
    {
        $this->form->resetForm();

        $this->products = [];
        $this->variants = [];

        $this->resetValidation();

        $this->show = true;
    }

    /**
     * Editar una tarifa existente.
     */
    #[On('shipping-rate-edit')]
    public function edit(int $id): void
    {
        $rate = $this->repository->find($id);

        if (! $rate) {
            return;
        }

        $this->form->fromModel($rate);

        $this->products = [];
        $this->variants = [];

        $this->loadProducts();

        if ($this->form->product_id) {
            $this->loadVariants();
        }

        $this->resetValidation();

        $this->show = true;
    }

    /**
     * Cuando cambia el proveedor.
     */
    public function updatedFormSupplierId($value): void
    {
        $this->form->product_id = null;
        $this->form->product_variant_id = null;

        $this->products = [];
        $this->variants = [];

        if ($value) {
            $this->loadProducts();
        }
    }

    /**
     * Cuando cambia el producto.
     */
    public function updatedFormProductId($value): void
    {
        $this->form->product_variant_id = null;

        $this->variants = [];

        if ($value) {
            $this->loadVariants();
        }
    }

    /**
     * Cargar productos disponibles para el proveedor.
     */
    private function loadProducts(): void
    {
        if (! $this->form->supplier_id) {
            return;
        }

        $this->products = SupplierVariant::query()
            ->where('supplier_id', $this->form->supplier_id)
            ->where('is_active', true)
            ->with('productVariant.product')
            ->get()
            ->pluck('productVariant.product')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * Cargar variantes disponibles para el proveedor y producto.
     */
    private function loadVariants(): void
    {
        if (
            ! $this->form->supplier_id ||
            ! $this->form->product_id
        ) {
            return;
        }

        $this->variants = SupplierVariant::query()
            ->where('supplier_id', $this->form->supplier_id)
            ->where('is_active', true)
            ->whereHas(
                'productVariant',
                function ($query) {
                    $query->where(
                        'product_id',
                        $this->form->product_id
                    );
                }
            )
            ->with('productVariant')
            ->get()
            ->pluck('productVariant')
            ->filter()
            ->unique('id')
            ->sortBy('sku')
            ->values();
    }

    /**
     * Guardar tarifa.
     */
    public function save(): void
    {
        $this->form->validate();

        if ($this->form->id) {

            $rate = $this->repository->find(
                $this->form->id
            );

            if (! $rate) {
                return;
            }

            $this->service->update(
                $rate,
                $this->form->toDto()
            );

            $message = 'Tarifa de envío actualizada correctamente.';
        } else {

            $this->service->create(
                $this->form->toDto()
            );

            $message = 'Tarifa de envío creada correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->dispatch('shipping-rate-saved');

        $this->close();
    }

    /**
     * Cerrar formulario.
     */
    public function close(): void
    {
        $this->show = false;

        $this->form->resetForm();

        $this->products = [];
        $this->variants = [];

        $this->resetValidation();
    }

    public function render()
    {
        return view(
            'livewire.admin.shipping.rates.form'
        );
    }
}
