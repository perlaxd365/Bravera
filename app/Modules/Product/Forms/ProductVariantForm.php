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

    /**
     * Precio minorista final. Se calcula desde el precio normal y el
     * descuento, pero también puede editarse directamente.
     */
    public float $retail_price = 0;

    public ?float $wholesale_price = null;

    public ?int $wholesale_min_quantity = null;

    public ?float $compare_price = null;

    public float $discount_percent = 0;

    public ?float $weight = null;

    public ?float $length = null;

    public ?float $width = null;

    public ?float $height = null;

    public bool $sync_enabled = true;

    public bool $is_default = false;

    public bool $is_active = true;

    public bool $pricing_initialized = false;

    public float $last_auto_cost = 0;

    public float $last_auto_retail = 0;

    public float $wholesale_ratio = 0.9;

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
                'unique:product_variants,sku,'.$this->id,
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

            'retail_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'wholesale_price' => [
                'nullable',
                'numeric',
                'min:0',
                'required_with:wholesale_min_quantity',
                function ($attribute, $value, $fail) {
                    if (blank($value) || blank($this->wholesale_min_quantity)) {
                        return;
                    }

                    $retailPrice = round(
                        $this->sale_price * (1 - ($this->discount_percent / 100)),
                        2
                    );

                    if ((float) $value >= $retailPrice) {
                        $fail('El precio al por mayor debe ser menor que el precio minorista.');
                    }
                },
            ],

            'wholesale_min_quantity' => [
                'nullable',
                'integer',
                'min:2',
                'required_with:wholesale_price',
            ],

            'compare_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'discount_percent' => [
                'required',
                'numeric',
                'min:0',
                'max:90',
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

            'wholesale_price.numeric' => 'El precio al por mayor debe ser numérico.',
            'wholesale_price.min' => 'El precio al por mayor no puede ser negativo.',
            'wholesale_price.required_with' => 'Indica el precio al por mayor o deja vacía la cantidad mínima.',

            'wholesale_min_quantity.integer' => 'La cantidad mínima para mayoreo debe ser un número entero.',
            'wholesale_min_quantity.min' => 'La cantidad mínima para mayoreo debe ser al menos 2.',
            'wholesale_min_quantity.required_with' => 'Indica la cantidad mínima para mayoreo o deja vacío el precio al por mayor.',

            'compare_price.numeric' => 'El precio referencial debe ser numérico.',
            'compare_price.min' => 'El precio referencial no puede ser negativo.',

            'discount_percent.required' => 'El descuento es obligatorio; usa 0 si no aplica.',
            'discount_percent.numeric' => 'El descuento debe ser numérico.',
            'discount_percent.min' => 'El descuento no puede ser negativo.',
            'discount_percent.max' => 'El descuento no puede superar el 90%.',

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
            'retail_price' => 'precio minorista final',
            'wholesale_price' => 'precio al por mayor',
            'wholesale_min_quantity' => 'cantidad mínima para mayoreo',
            'compare_price' => 'precio referencial',
            'discount_percent' => 'descuento',
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

        $this->discount_percent = (float) $variant->discount_percent;

        // El precio base se muestra al editar para que cambiar el porcentaje
        // vuelva a calcular el precio final sin aplicar el descuento dos veces.
        $this->sale_price = $this->discount_percent > 0 && $variant->compare_price !== null
            ? (float) $variant->compare_price
            : (float) $variant->sale_price;

        $this->retail_price = (float) $variant->sale_price;

        $this->wholesale_price = $variant->wholesale_price !== null
            ? (float) $variant->wholesale_price
            : null;

        $this->wholesale_min_quantity = $variant->wholesale_min_quantity !== null
            ? (int) $variant->wholesale_min_quantity
            : null;

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

        $this->pricing_initialized = true;
        $this->last_auto_cost = (float) $variant->cost_price;
        $this->last_auto_retail = (float) $variant->sale_price;
        $this->wholesale_ratio = $this->retail_price > 0 && $variant->wholesale_price !== null
            ? round((float) $variant->wholesale_price / $this->retail_price, 4)
            : 0.9;

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
        $this->applyAutomaticPricing('base');

        return ProductVariantData::fromArray([
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'cost_price' => $this->cost_price,
            'sale_price' => $this->sale_price,
            'wholesale_price' => $this->wholesale_price,
            'wholesale_min_quantity' => $this->wholesale_min_quantity,
            'compare_price' => $this->compare_price,
            'discount_percent' => $this->discount_percent,
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

        $this->retail_price = 0;

        $this->wholesale_price = null;

        $this->wholesale_min_quantity = null;

        $this->sync_enabled = true;

        $this->is_default = false;

        $this->is_active = true;

        $this->pricing_initialized = false;
        $this->last_auto_cost = 0;
        $this->last_auto_retail = 0;
        $this->wholesale_ratio = 0.9;

        $this->attribute_values = [];
    }

    /**
     * Recalcula los precios dependientes cuando cambia un campo.
     *
     * Regla inicial (ejemplo S/30):
     * normal = 30 × 3.33 = 100, descuento = 50%,
     * minorista = 50, mayorista = 45 desde 3 unidades.
     */
    public function applyAutomaticPricing(string $source): void
    {
        $source = in_array($source, ['cost', 'base', 'discount', 'retail', 'wholesale'], true)
            ? $source
            : 'base';

        if ($source === 'cost') {
            $cost = max(0, round((float) $this->cost_price, 2));

            if (! $this->pricing_initialized || $this->sale_price <= 0) {
                $this->sale_price = round($cost * (10 / 3), 2);
                $this->discount_percent = 50;
                $this->wholesale_ratio = 0.9;
                $this->wholesale_min_quantity = 3;
            } else {
                $factor = $this->last_auto_cost > 0
                    ? $cost / $this->last_auto_cost
                    : 1;

                $this->sale_price = round($this->sale_price * $factor, 2);

                if ($this->wholesale_price !== null) {
                    $this->wholesale_price = round($this->wholesale_price * $factor, 2);
                }
            }

            $this->last_auto_cost = $cost;
        }

        $basePrice = max(0, round((float) $this->sale_price, 2));
        $discount = min(90, max(0, round((float) $this->discount_percent, 2)));
        $this->discount_percent = $discount;

        if ($source === 'retail') {
            $retailPrice = max(0, round((float) $this->retail_price, 2));

            if ($basePrice > 0 && $retailPrice > 0 && $retailPrice < $basePrice) {
                $this->discount_percent = round((1 - ($retailPrice / $basePrice)) * 100, 2);
            } elseif ($discount > 0) {
                $this->sale_price = round($retailPrice / (1 - ($discount / 100)), 2);
                $basePrice = $this->sale_price;
            } else {
                $this->sale_price = $retailPrice;
                $basePrice = $retailPrice;
            }
        } else {
            $this->retail_price = round($basePrice * (1 - ($discount / 100)), 2);
        }

        $this->sale_price = max(0, $basePrice);
        $this->retail_price = max(0, round((float) $this->retail_price, 2));

        if ($source === 'wholesale' && $this->wholesale_price !== null && $this->retail_price > 0) {
            $this->wholesale_ratio = round(
                (float) $this->wholesale_price / $this->retail_price,
                4,
            );
        }

        if ($source !== 'wholesale') {
            $this->wholesale_price = $this->retail_price > 0
                ? round($this->retail_price * $this->wholesale_ratio, 2)
                : null;
        }

        $this->last_auto_retail = $this->retail_price;
        $this->pricing_initialized = true;
    }
}
