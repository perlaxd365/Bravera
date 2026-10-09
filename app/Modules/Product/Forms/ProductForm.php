<?php

namespace App\Modules\Product\Forms;

use App\Models\Product;
use App\Modules\Product\DTO\ProductData;
use Illuminate\Support\Str;
use Livewire\Form;

class ProductForm extends Form
{
    public ?int $id = null;

    public ?int $category_id = null;

    public ?int $brand_id = null;

    public string $name = '';

    public string $slug = '';

    public ?string $short_description = null;

    public ?string $description = null;

    public bool $status = true;

    public bool $is_featured = false;

    public bool $is_visible = false;

    public ?string $seo_title = null;

    public ?string $seo_description = null;

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'brand_id' => [
                'nullable',
                'exists:brands,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:products,slug,'.$this->id,
            ],

            'short_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'boolean',
            ],

            'is_featured' => [
                'boolean',
            ],

            'is_visible' => [
                'boolean',
            ],

            'seo_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'seo_description' => [
                'nullable',
                'string',
                'max:320',
            ],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Debe seleccionar una categoría.',

            'category_id.exists' => 'La categoría seleccionada no es válida.',

            'brand_id.exists' => 'La marca seleccionada no es válida.',

            'name.required' => 'El nombre del producto es obligatorio.',

            'name.max' => 'El nombre del producto no puede superar los 255 caracteres.',

            'slug.required' => 'El slug es obligatorio.',

            'slug.unique' => 'El slug ya existe.',

            'slug.max' => 'El slug no puede superar los 255 caracteres.',

            'short_description.max' => 'La descripción corta no puede superar los 500 caracteres.',
        ];
    }

    /**
     * Nombres amigables para los atributos.
     */
    public function validationAttributes(): array
    {
        return [
            'category_id' => 'categoría',
            'brand_id' => 'marca',
            'name' => 'nombre',
            'slug' => 'slug',
            'short_description' => 'descripción corta',
            'description' => 'descripción',
            'status' => 'estado',
            'is_featured' => 'producto destacado',
            'is_visible' => 'visible en tienda',
            'seo_title' => 'título SEO',
            'seo_description' => 'descripción SEO',
        ];
    }

    /**
     * Cargar información desde el modelo.
     */
    public function fromModel(Product $product): void
    {
        $this->id = $product->id;

        $this->category_id = $product->category_id;

        $this->brand_id = $product->brand_id;

        $this->name = $product->name;

        $this->slug = $product->slug;

        $this->short_description = $product->short_description;

        $this->description = $product->description;

        $this->status = (bool) $product->status;

        $this->is_featured = (bool) $product->is_featured;

        $this->is_visible = (bool) $product->is_visible;

        $this->seo_title = $product->seo_title;

        $this->seo_description = $product->seo_description;
    }

    /**
     * Genera automáticamente el slug.
     */
    public function generateSlug(): void
    {
        $this->slug = Str::slug($this->name);
    }

    /**
     * Convierte el formulario a DTO.
     */
    public function toDto(): ProductData
    {
        return ProductData::fromArray([
            'id' => $this->id,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_visible' => $this->is_visible,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
        ]);
    }

    /**
     * Limpia el formulario.
     */
    public function resetForm(): void
    {
        $this->reset();

        $this->id = null;

        $this->category_id = null;

        $this->brand_id = null;

        $this->name = '';

        $this->slug = '';

        $this->short_description = null;

        $this->description = null;

        $this->seo_title = null;

        $this->seo_description = null;

        $this->status = true;

        $this->is_featured = false;

        $this->is_visible = false;
    }
}
