<?php

namespace App\Livewire\Admin\Catalog\Categories;

use App\Models\Category;
use App\Services\CloudinaryImageService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Category $category = null;

    public bool $show = false;

    public string $name = '';

    public string $slug = '';

    public ?int $parent_id = null;

    public ?string $image = null;

    public bool $is_visible = true;

    public int $position = 0;

    /**
     * Imagen seleccionada para subir a Cloudinary.
     */
    public $image_file = null;

    /**
     * Marca si el usuario quiere quitar la imagen actual.
     */
    public bool $remove_image = false;

    /**
     * URL de la imagen anterior quitada, para borrarla al guardar.
     */
    public ?string $removed_image = null;

    protected function rules()
    {
        return [

            'name' => 'required|min:3|max:150',

            'slug' => [
                'required',
                'max:150',
                Rule::unique('categories', 'slug')
                    ->where(function ($query) {
                        return $query->where('parent_id', $this->parent_id);
                    })
                    ->ignore($this->category?->id),
            ],

            'parent_id' => 'nullable|exists:categories,id',

            'image' => 'nullable|string|max:255',

            'position' => 'required|integer|min:0',

            'is_visible' => 'boolean',

        ];
    }

    /**
     * Al elegir una imagen la valida y muestra la vista previa.
     */
    public function updatedImageFile(): void
    {
        $this->validate([
            'image_file' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    /**
     * Quita la imagen actual (solo la marca; se elimina al guardar).
     */
    public function removeImage(): void
    {
        if ($this->image) {
            $this->removed_image = $this->image;
        }

        $this->image = null;

        $this->image_file = null;

        $this->remove_image = true;
    }

    #[On('category-create')]
    public function create()
    {
        $this->resetForm();

        $this->show = true;
    }

    #[On('category-edit')]
    public function edit($id)
    {
        $this->category = Category::findOrFail($id);

        $this->fill([

            'name' => $this->category->name,
            'slug' => $this->category->slug,
            'parent_id' => $this->category->parent_id,
            'image' => $this->category->image,
            'position' => $this->category->position,
            'is_visible' => $this->category->is_visible,

        ]);

        $this->image_file = null;

        $this->remove_image = false;

        $this->removed_image = null;

        $this->show = true;
    }

    public function updatedName()
    {
        $this->slug = Str::slug($this->name);
    }

    public function save(CloudinaryImageService $cloudinary)
    {
        // Validar la imagen si se eligió un archivo nuevo.
        if ($this->image_file) {
            $this->validate([
                'image_file' => ['required', 'image', 'max:5120'],
            ]);
        }

        // Subir la imagen a Cloudinary si se eligió una nueva.
        if ($this->image_file) {
            $uploaded = $cloudinary->upload($this->image_file, CloudinaryImageService::FOLDER_CATEGORIES);

            // Eliminar la imagen anterior en Cloudinary si existía.
            if ($this->image) {
                $cloudinary->delete($cloudinary->publicIdFromUrl($this->image));
            }

            $this->image = $uploaded['secure_url'];
        }

        // Eliminar en Cloudinary una imagen que fue quitada.
        if ($this->remove_image && $this->removed_image) {
            $cloudinary->delete($cloudinary->publicIdFromUrl($this->removed_image));
        }

        // Si es una categoría nueva, buscar primero en eliminadas
        if (! $this->category) {

            $deleted = Category::onlyTrashed()
                ->where('slug', $this->slug)
                ->where('parent_id', $this->parent_id)
                ->first();

            if ($deleted) {

                $deleted->restore();

                $deleted->update([
                    'name' => $this->name,
                    'parent_id' => $this->parent_id,
                    'image' => $this->image,
                    'position' => $this->position,
                    'is_visible' => $this->is_visible,
                ]);

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'La categoría fue restaurada correctamente.',
                ]);

                $this->dispatch('category-saved');

                $this->show = false;

                $this->resetForm();

                return;
            }
        }

        // Recién aquí validar
        $this->validate();

        if ($this->category) {

            // EDITAR
            $this->category->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'parent_id' => $this->parent_id,
                'image' => $this->image,
                'position' => $this->position,
                'is_visible' => $this->is_visible,
            ]);

            $category = $this->category;
        } else {

            // CREAR
            $category = Category::create([
                'name' => $this->name,
                'slug' => $this->slug,
                'parent_id' => $this->parent_id,
                'image' => $this->image,
                'position' => $this->position,
                'is_visible' => $this->is_visible,

                // Valor temporal para evitar el error de MySQL
                'path' => '',
            ]);
        }

        // Generar el path correcto
        $path = $category->parent_id
            ? $category->parent->path.'/'.$category->slug
            : $category->slug;

        if ($category->path !== $path) {
            $category->update([
                'path' => $path,
            ]);
        }
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->category
                ? 'Categoría actualizada correctamente.'
                : 'Categoría creada correctamente.',
        ]);

        $this->dispatch('category-saved');

        $this->show = false;

        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'category',
            'name',
            'slug',
            'parent_id',
            'position',
            'image',
            'image_file',
            'removed_image',
        ]);

        $this->is_visible = true;

        $this->remove_image = false;
    }

    protected function messages()
    {
        return [
            'name.required' => 'Debe ingresar el nombre de la categoría.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',

            'slug.required' => 'El slug es obligatorio.',
            'slug.unique' => 'Ya existe una categoría con ese nombre.',

            'parent_id.exists' => 'La categoría padre seleccionada no existe.',
        ];
    }

    public function render()
    {
        $parents = Category::query()
            ->when($this->category, function ($query) {
                $query->where('id', '!=', $this->category->id);
            })
            ->orderBy('path')
            ->get();

        return view('livewire.admin.catalog.categories.form', compact('parents'));
    }
}
