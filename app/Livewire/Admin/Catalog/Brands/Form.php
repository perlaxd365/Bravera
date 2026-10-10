<?php

namespace App\Livewire\Admin\Catalog\Brands;

use App\Models\Brand;
use App\Services\CloudinaryImageService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Brand $brand = null;

    public bool $show = false;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public ?string $image = null;

    public bool $is_active = true;

    public int $sort_order = 0;

    /**
     * Logo seleccionado para subir a Cloudinary.
     */
    public $logo = null;

    /**
     * Marca si el usuario quiere quitar el logo actual.
     */
    public bool $remove_logo = false;

    /**
     * URL del logo anterior que fue quitado, para borrarla al guardar.
     */
    public ?string $removed_image = null;

    protected function rules()
    {
        return [

            'name' => 'required|min:3|max:150',

            'slug' => [
                'required',
                'max:180',
                Rule::unique('brands', 'slug')
                    ->ignore($this->brand?->id),
            ],

            'description' => 'nullable|string',

            'image' => 'nullable|string|max:2048',

            'sort_order' => 'required|integer|min:0',

            'is_active' => 'boolean',

        ];
    }

    /**
     * Al elegir un archivo de logo lo valida y muestra la vista previa.
     */
    public function updatedLogo(): void
    {
        $this->validate([
            'logo' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    /**
     * Quita el logo actual (solo lo marca; la eliminación se hace al guardar).
     */
    public function removeImage(): void
    {
        if ($this->image) {
            $this->removed_image = $this->image;
        }

        $this->image = null;

        $this->logo = null;

        $this->remove_logo = true;
    }

    #[On('brand-create')]
    public function create()
    {
        $this->resetForm();

        $this->show = true;
    }

    #[On('brand-edit')]
    public function edit($id)
    {
        $this->brand = Brand::findOrFail($id);

        $this->fill([
            'name' => $this->brand->name,
            'slug' => $this->brand->slug,
            'description' => $this->brand->description,
            'image' => $this->brand->image,
            'sort_order' => $this->brand->sort_order,
            'is_active' => $this->brand->is_active,
        ]);

        $this->logo = null;

        $this->remove_logo = false;

        $this->removed_image = null;

        $this->show = true;
    }

    public function updatedName()
    {
        $this->slug = Str::slug($this->name);
    }

    public function save(CloudinaryImageService $cloudinary)
    {
        // Validar el logo si se eligió un archivo nuevo.
        if ($this->logo) {
            $this->validate([
                'logo' => ['required', 'image', 'max:5120'],
            ]);
        }

        // Subir el logo a Cloudinary si se eligió uno nuevo.
        if ($this->logo) {
            $uploaded = $cloudinary->upload($this->logo, CloudinaryImageService::FOLDER_BRANDS);

            // Eliminar la imagen anterior en Cloudinary si existía.
            if ($this->image) {
                $cloudinary->delete($cloudinary->publicIdFromUrl($this->image));
            }

            $this->image = $uploaded['secure_url'];
        }

        // Eliminar en Cloudinary un logo que fue quitado.
        if ($this->remove_logo && $this->removed_image) {
            $cloudinary->delete($cloudinary->publicIdFromUrl($this->removed_image));
        }

        // Si es una marca nueva, buscar primero en eliminadas
        if (! $this->brand) {

            $deleted = Brand::onlyTrashed()
                ->where('slug', $this->slug)
                ->first();

            if ($deleted) {

                $deleted->restore();

                $deleted->update([
                    'name' => $this->name,
                    'description' => $this->description,
                    'image' => $this->image,
                    'sort_order' => $this->sort_order,
                    'is_active' => $this->is_active,
                ]);

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'La marca fue restaurada correctamente.',
                ]);

                $this->dispatch('brand-saved');

                $this->show = false;

                $this->resetForm();

                return;
            }
        }

        $this->validate();

        if ($this->brand) {

            $this->brand->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description,
                'image' => $this->image,
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
            ]);

        } else {

            Brand::create([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description,
                'image' => $this->image,
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
            ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->brand
                ? 'Marca actualizada correctamente.'
                : 'Marca creada correctamente.',
        ]);

        $this->dispatch('brand-saved');

        $this->show = false;

        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'brand',
            'name',
            'slug',
            'description',
            'image',
            'sort_order',
            'logo',
            'removed_image',
        ]);

        $this->is_active = true;

        $this->remove_logo = false;
    }

    protected function messages()
    {
        return [

            'name.required' => 'Debe ingresar el nombre de la marca.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',

            'slug.required' => 'El slug es obligatorio.',
            'slug.unique' => 'Ya existe una marca con ese nombre.',

            'image.max' => 'La URL del logo es demasiado larga. Intenta con un archivo de nombre más corto.',
        ];
    }

    public function render()
    {
        return view('livewire.admin.catalog.brands.form');
    }
}
