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

    public string $product_type = 'physical';

    public ?string $course_price = null;

    public string $name = '';

    public string $slug = '';

    public ?string $short_description = null;

    public ?string $description = null;

    public bool $status = true;

    public bool $is_featured = false;

    public bool $is_visible = false;

    public bool $allows_shalom_cod = false;

    public ?string $seo_title = null;

    public ?string $seo_description = null;

    /**
     * Contenido traducido. Las columnas originales siguen siendo la versión
     * canónica en español para no alterar pedidos ni búsquedas existentes.
     *
     * @var array<string, array<string, string|null>>
     */
    public array $translations = [
        'en' => [
            'name' => '',
            'short_description' => '',
            'description' => '',
            'seo_title' => '',
            'seo_description' => '',
        ],
    ];

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

            'product_type' => [
                'required',
                'in:physical,course',
            ],

            'course_price' => [
                'nullable',
                'numeric',
                'min:0.01',
                'required_if:product_type,course',
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

            'allows_shalom_cod' => [
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

            'translations.en.name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'translations.en.short_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'translations.en.description' => [
                'nullable',
                'string',
            ],

            'translations.en.seo_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'translations.en.seo_description' => [
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

            'course_price.required_if' => 'Ingresa el precio del curso.',
            'course_price.numeric' => 'El precio del curso debe ser numérico.',
            'course_price.min' => 'El precio del curso debe ser mayor a cero.',

            'name.required' => 'El nombre del producto es obligatorio.',

            'name.max' => 'El nombre del producto no puede superar los 255 caracteres.',

            'slug.required' => 'El slug es obligatorio.',

            'slug.unique' => 'El slug ya existe.',

            'slug.max' => 'El slug no puede superar los 255 caracteres.',

            'short_description.max' => 'La descripción corta no puede superar los 500 caracteres.',

            'translations.en.name.max' => 'El nombre en inglés no puede superar los 255 caracteres.',
            'translations.en.short_description.max' => 'La descripción corta en inglés no puede superar los 500 caracteres.',
            'translations.en.seo_title.max' => 'El título SEO en inglés no puede superar los 255 caracteres.',
            'translations.en.seo_description.max' => 'La descripción SEO en inglés no puede superar los 320 caracteres.',
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
            'product_type' => 'tipo de producto',
            'course_price' => 'precio del curso',
            'name' => 'nombre',
            'slug' => 'slug',
            'short_description' => 'descripción corta',
            'description' => 'descripción',
            'status' => 'estado',
            'is_featured' => 'producto destacado',
            'is_visible' => 'visible en tienda',
            'allows_shalom_cod' => 'envío contra entrega en Shalom',
            'seo_title' => 'título SEO',
            'seo_description' => 'descripción SEO',
            'translations.en.name' => 'nombre en inglés',
            'translations.en.short_description' => 'descripción corta en inglés',
            'translations.en.description' => 'descripción en inglés',
            'translations.en.seo_title' => 'título SEO en inglés',
            'translations.en.seo_description' => 'descripción SEO en inglés',
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

        $this->product_type = $product->product_type->value;

        $this->course_price = $product->course_price !== null ? (string) $product->course_price : null;

        $this->name = $product->name;

        $this->slug = $product->slug;

        $this->short_description = $product->short_description;

        $this->description = $product->description;

        $this->status = (bool) $product->status;

        $this->is_featured = (bool) $product->is_featured;

        $this->is_visible = (bool) $product->is_visible;

        $this->allows_shalom_cod = (bool) $product->allows_shalom_cod;

        $this->seo_title = $product->seo_title;

        $this->seo_description = $product->seo_description;

        $english = $product->relationLoaded('translations')
            ? $product->translations->firstWhere('locale', 'en')
            : $product->translations()->where('locale', 'en')->first();

        $this->translations = [
            'en' => [
                'name' => (string) ($english?->name ?? ''),
                'short_description' => (string) ($english?->short_description ?? ''),
                'description' => (string) ($english?->description ?? ''),
                'seo_title' => (string) ($english?->seo_title ?? ''),
                'seo_description' => (string) ($english?->seo_description ?? ''),
            ],
        ];
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
            'product_type' => $this->product_type,
            'course_price' => $this->course_price,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_visible' => $this->is_visible,
            'allows_shalom_cod' => $this->allows_shalom_cod,
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

        $this->product_type = 'physical';

        $this->course_price = null;

        $this->name = '';

        $this->slug = '';

        $this->short_description = null;

        $this->description = null;

        $this->seo_title = null;

        $this->seo_description = null;

        $this->translations = [
            'en' => [
                'name' => '',
                'short_description' => '',
                'description' => '',
                'seo_title' => '',
                'seo_description' => '',
            ],
        ];

        $this->status = true;

        $this->is_featured = false;

        $this->is_visible = false;

        $this->allows_shalom_cod = false;
    }
}
