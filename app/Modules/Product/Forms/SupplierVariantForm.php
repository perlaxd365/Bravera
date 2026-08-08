<?php

namespace App\Modules\Product\Forms;

use App\Models\SupplierVariant;
use App\Modules\Product\DTO\SupplierVariantData;
use Livewire\Form;

class SupplierVariantForm extends Form
{
    public ?int $id = null;

    public ?int $supplier_id = null;

    public ?int $product_variant_id = null;

    public string $supplier_sku = '';

    public ?string $supplier_product_url = null;

    public float $cost_price = 0;

    public float $shipping_cost = 0;

    public float $supplier_sale_price = 0;

    public int $stock = 0;

    public int $reserved_stock = 0;

    public int $minimum_stock = 0;

    public ?array $extra_data = null;

    public int $estimated_dispatch_days = 1;

    public bool $is_default = false;

    public bool $is_active = true;

    public ?string $internal_notes = null;

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'product_variant_id' => [
                'required',
                'exists:product_variants,id',
            ],

            'supplier_sku' => [
                'nullable',
                'string',
                'max:100',
            ],

            'supplier_product_url' => [
                'nullable',
                'url',
                'max:1000',
            ],

            'cost_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'shipping_cost' => [
                'required',
                'numeric',
                'min:0',
            ],

            'supplier_sale_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'reserved_stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'minimum_stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'extra_data' => [
                'nullable',
                'array',
            ],

            'estimated_dispatch_days' => [
                'required',
                'integer',
                'min:0',
            ],

            'is_default' => [
                'boolean',
            ],

            'is_active' => [
                'boolean',
            ],

            'internal_notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'El proveedor es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido.',

            'product_variant_id.required' => 'La variante es obligatoria.',
            'product_variant_id.exists' => 'La variante seleccionada no es válida.',

            'supplier_sku.max' => 'El SKU del proveedor no puede superar los 100 caracteres.',

            'supplier_product_url.url' => 'La URL del producto del proveedor no es válida.',
            'supplier_product_url.max' => 'La URL no puede superar los 500 caracteres.',

            'cost_price.required' => 'El costo del proveedor es obligatorio.',
            'cost_price.numeric' => 'El costo del proveedor debe ser numérico.',
            'cost_price.min' => 'El costo del proveedor no puede ser negativo.',

            'shipping_cost.required' => 'El costo de envío es obligatorio.',
            'shipping_cost.numeric' => 'El costo de envío debe ser numérico.',
            'shipping_cost.min' => 'El costo de envío no puede ser negativo.',

            'supplier_sale_price.required' => 'El precio del proveedor es obligatorio.',
            'supplier_sale_price.numeric' => 'El precio del proveedor debe ser numérico.',
            'supplier_sale_price.min' => 'El precio del proveedor no puede ser negativo.',

            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',

            'reserved_stock.required' => 'El stock reservado es obligatorio.',
            'reserved_stock.integer' => 'El stock reservado debe ser un número entero.',
            'reserved_stock.min' => 'El stock reservado no puede ser negativo.',

            'minimum_stock.required' => 'El stock mínimo es obligatorio.',
            'minimum_stock.integer' => 'El stock mínimo debe ser un número entero.',
            'minimum_stock.min' => 'El stock mínimo no puede ser negativo.',

            'estimated_dispatch_days.required' => 'Los días de despacho son obligatorios.',
            'estimated_dispatch_days.integer' => 'Los días de despacho deben ser un número entero.',
            'estimated_dispatch_days.min' => 'Los días de despacho no pueden ser negativos.',

            'internal_notes.string' => 'Las notas internas deben ser texto.',
            'supplier_product_url.max' => 'La URL no puede superar los 1000 caracteres.',
        ];
    }

    /**
     * Nombres amigables para los atributos.
     */
    public function validationAttributes(): array
    {
        return [
            'supplier_id' => 'proveedor',
            'product_variant_id' => 'variante',
            'supplier_sku' => 'SKU del proveedor',
            'supplier_product_url' => 'URL del proveedor',
            'cost_price' => 'costo del proveedor',
            'shipping_cost' => 'costo de envío',
            'supplier_sale_price' => 'precio del proveedor',
            'stock' => 'stock',
            'reserved_stock' => 'stock reservado',
            'minimum_stock' => 'stock mínimo',
            'extra_data' => 'datos adicionales',
            'estimated_dispatch_days' => 'días de despacho',
            'is_default' => 'proveedor principal',
            'is_active' => 'estado',
            'internal_notes' => 'notas internas',
        ];
    }

    /**
     * Cargar información desde el modelo.
     */
    public function fromModel(SupplierVariant $supplierVariant): void
    {
        $this->id = $supplierVariant->id;

        $this->supplier_id = $supplierVariant->supplier_id;

        $this->product_variant_id = $supplierVariant->product_variant_id;

        $this->supplier_sku = $supplierVariant->supplier_sku ?? '';

        $this->supplier_product_url = $supplierVariant->supplier_product_url;

        $this->cost_price = (float) $supplierVariant->cost_price;

        $this->shipping_cost = (float) $supplierVariant->shipping_cost;

        $this->supplier_sale_price = (float) $supplierVariant->supplier_sale_price;

        $this->stock = (int) $supplierVariant->stock;

        $this->reserved_stock = (int) $supplierVariant->reserved_stock;

        $this->minimum_stock = (int) $supplierVariant->minimum_stock;

        $this->extra_data = $supplierVariant->extra_data;

        $this->estimated_dispatch_days = (int) $supplierVariant->estimated_dispatch_days;

        $this->is_default = (bool) $supplierVariant->is_default;

        $this->is_active = (bool) $supplierVariant->is_active;

        $this->internal_notes = $supplierVariant->internal_notes;
    }

    /**
     * Convierte el formulario a DTO.
     */
    public function toDto(): SupplierVariantData
    {
        return SupplierVariantData::fromArray([
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'product_variant_id' => $this->product_variant_id,
            'supplier_sku' => $this->supplier_sku,
            'supplier_product_url' => $this->supplier_product_url,
            'cost_price' => $this->cost_price,
            'shipping_cost' => $this->shipping_cost,
            'supplier_sale_price' => $this->supplier_sale_price,
            'stock' => $this->stock,
            'reserved_stock' => $this->reserved_stock,
            'minimum_stock' => $this->minimum_stock,
            'extra_data' => $this->extra_data,
            'estimated_dispatch_days' => $this->estimated_dispatch_days,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'internal_notes' => $this->internal_notes,
        ]);
    }

    /**
     * Limpia el formulario.
     */
    public function resetForm(): void
    {
        $this->reset();

        $this->cost_price = 0;

        $this->shipping_cost = 0;

        $this->supplier_sale_price = 0;

        $this->stock = 0;

        $this->reserved_stock = 0;

        $this->minimum_stock = 0;

        $this->estimated_dispatch_days = 1;

        $this->is_default = false;

        $this->is_active = true;

        $this->extra_data = null;
    }
}
