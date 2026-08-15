<?php

namespace App\Livewire\Forms;

use App\DTOs\ShippingRateDTO;
use App\Models\ShippingRate;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ShippingRateForm extends Form
{
    public ?int $id = null;

    #[Validate('required|integer|exists:shipping_zones,id')]
    public ?int $shipping_zone_id = null;

    #[Validate('required|integer|exists:suppliers,id')]
    public ?int $supplier_id = null;

    #[Validate('nullable|integer|exists:products,id')]
    public ?int $product_id = null;

    #[Validate('nullable|integer|exists:product_variants,id')]
    public ?int $product_variant_id = null;

    #[Validate('required|numeric|min:0|max:99999999.99')]
    public ?float $price = null;

    #[Validate('boolean')]
    public bool $status = true;

    /**
     * Reiniciar el formulario.
     */
    public function resetForm(): void
    {
        $this->reset();

        $this->shipping_zone_id = null;
        $this->supplier_id = null;
        $this->product_id = null;
        $this->product_variant_id = null;
        $this->price = null;
        $this->status = true;
    }

    /**
     * Cargar una tarifa existente.
     */
    public function fromModel(ShippingRate $rate): void
    {
        $this->id = $rate->id;

        $this->shipping_zone_id = $rate->shipping_zone_id;
        $this->supplier_id = $rate->supplier_id;
        $this->product_id = $rate->product_id;
        $this->product_variant_id = $rate->product_variant_id;
        $this->price = (float) $rate->price;
        $this->status = $rate->status;
    }

    /**
     * Convertir el formulario a DTO.
     */
    public function toDto(): ShippingRateDTO
    {
        return new ShippingRateDTO(
            shipping_zone_id: (int) $this->shipping_zone_id,
            supplier_id: (int) $this->supplier_id,
            product_id: $this->product_id
                ? (int) $this->product_id
                : null,
            product_variant_id: $this->product_variant_id
                ? (int) $this->product_variant_id
                : null,
            price: (float) $this->price,
            status: $this->status,
        );
    }
}
