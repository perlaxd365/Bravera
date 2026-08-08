<?php

namespace App\Modules\Product\Forms;

use App\Models\ProductVariant;
use App\Modules\Product\DTO\ProductVariantData;
use Livewire\Form;

class ProductVariantForm extends Form
{
    public ?int $id = null;

    public ?int $product_id = null;

    public string $sku = '';

    public ?string $barcode = null;

    public float $cost_price = 0;

    public float $sale_price = 0;

    public ?float $compare_price = null;

    public ?float $weight = null;

    public ?float $length = null;

    public ?float $width = null;

    public ?float $height = null;

    public bool $sync_enabled = true;

    public bool $is_default = false;

    public bool $is_active = true;

    /**
     * Valores de atributos seleccionados.
     *
     * Formato:
     *
     * [
     *     attribute_id => attribute_value_id,
     * ]
     */
    public array $attribute_values = [];

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'exists:products,id',
            ],

            'sku' => [
                'required',
                'string',
                'max:100',
                'unique:product_variants,sku,' . $this->id,
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',
            ],

            'cost_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'sale_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'compare_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'weight' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'length' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'width' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'height' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'sync_enabled' => [
                'boolean',
            ],

            'is_default' => [
                'boolean',
            ],

            'is_active' => [
                'boolean',
            ],

            'attribute_values' => [
                'array',
            ],

            'attribute_values.*' => [
                'nullable',
                'integer',
                'exists:attribute_values,id',
            ],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio.',
            'product_id.exists' => 'El producto seleccionado no es válido.',

            'sku.required' => 'El SKU es obligatorio.',
            'sku.unique' => 'El SKU ya existe.',
            'sku.max' => 'El SKU no puede superar los 100 caracteres.',

            'barcode.max' => 'El código de barras no puede superar los 100 caracteres.',

            'cost_price.required' => 'El costo de adquisición es obligatorio.',
            'cost_price.numeric' => 'El costo de adquisición debe ser numérico.',
            'cost_price.min' => 'El costo de adquisición no puede ser negativo.',

            'sale_price.required' => 'El precio de venta es obligatorio.',
            'sale_price.numeric' => 'El precio de venta debe ser numérico.',
            'sale_price.min' => 'El precio de venta no puede ser negativo.',

            'compare_price.numeric' => 'El precio referencial debe ser numérico.',
            'compare_price.min' => 'El precio referencial no puede ser negativo.',

            'weight.numeric' => 'El peso debe ser numérico.',
            'weight.min' => 'El peso no puede ser negativo.',

            'length.numeric' => 'El largo debe ser numérico.',
            'length.min' => 'El largo no puede ser negativo.',

            'width.numeric' => 'El ancho debe ser numérico.',
            'width.min' => 'El ancho no puede ser negativo.',

            'height.numeric' => 'El alto debe ser numérico.',
            'height.min' => 'El alto no puede ser negativo.',

            'attribute_values.array' => 'Los atributos seleccionados no son válidos.',

            'attribute_values.*.integer' => 'El valor del atributo no es válido.',

            'attribute_values.*.exists' => 'Uno de los valores de atributo seleccionados no existe.',
        ];
    }

    /**
     * Nombres amigables para los atributos.
     */
    public function validationAttributes(): array
    {
        return [
            'product_id' => 'producto',
            'sku' => 'SKU',
            'barcode' => 'código de barras',
            'cost_price' => 'costo de adquisición',
            'sale_price' => 'precio de venta',
            'compare_price' => 'precio referencial',
            'weight' => 'peso',
            'length' => 'largo',
            'width' => 'ancho',
            'height' => 'alto',
            'sync_enabled' => 'sincronización',
            'is_default' => 'variante principal',
            'is_active' => 'estado',
            'attribute_values' => 'atributos',
            'attribute_values.*' => 'valor del atributo',
        ];
    }

    /**
     * Cargar información desde el modelo.
     */
    public function fromModel(ProductVariant $variant): void
    {
        $this->id = $variant->id;

        $this->product_id = $variant->product_id;

        $this->sku = $variant->sku;

        $this->barcode = $variant->barcode;

        $this->cost_price = (float) $variant->cost_price;

        $this->sale_price = (float) $variant->sale_price;

        $this->compare_price = $variant->compare_price !== null
            ? (float) $variant->compare_price
            : null;

        $this->weight = $variant->weight !== null
            ? (float) $variant->weight
            : null;

        $this->length = $variant->length !== null
            ? (float) $variant->length
            : null;

        $this->width = $variant->width !== null
            ? (float) $variant->width
            : null;

        $this->height = $variant->height !== null
            ? (float) $variant->height
            : null;

        $this->sync_enabled = $variant->sync_enabled;

        $this->is_default = $variant->is_default;

        $this->is_active = $variant->is_active;

        /*
        |--------------------------------------------------------------------------
        | Atributos de la variante
        |--------------------------------------------------------------------------
        */

        $this->attribute_values = $variant->attributeValues()
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->attribute_id => $item->attribute_value_id,
                ];
            })
            ->toArray();
    }

    /**
     * Convierte el formulario a DTO.
     */
    public function toDto(): ProductVariantData
    {
        return ProductVariantData::fromArray([
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'cost_price' => $this->cost_price,
            'sale_price' => $this->sale_price,
            'compare_price' => $this->compare_price,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'sync_enabled' => $this->sync_enabled,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'attribute_values' => $this->attribute_values,
        ]);
    }

    /**
     * Limpia el formulario.
     */
    public function resetForm(): void
    {
        $this->reset();

        $this->cost_price = 0;

        $this->sale_price = 0;

        $this->sync_enabled = true;

        $this->is_default = false;

        $this->is_active = true;

        $this->attribute_values = [];
    }
}
