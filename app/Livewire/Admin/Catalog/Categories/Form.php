<?php

namespace App\Livewire\Admin\Catalog\Categories;

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;

class Form extends Component
{
    public ?Category $category = null;

    public bool $show = false;

    public string $name = '';
    public string $slug = '';
    public ?int $parent_id = null;
    public bool $is_visible = true;
    public int $position = 0;

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

            'position' => 'required|integer|min:0',

            'is_visible' => 'boolean',

        ];
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
            'position' => $this->category->position,
            'is_visible' => $this->category->is_visible,

        ]);

        $this->show = true;
    }

    public function updatedName()
    {
        $this->slug = Str::slug($this->name);
    }

    public function save()
    {
        // Si es una categoría nueva, buscar primero en eliminadas
        if (!$this->category) {

            $deleted = Category::onlyTrashed()
                ->where('slug', $this->slug)
                ->where('parent_id', $this->parent_id)
                ->first();

            if ($deleted) {

                $deleted->restore();

                $deleted->update([
                    'name' => $this->name,
                    'parent_id' => $this->parent_id,
                    'position' => $this->position,
                    'is_visible' => $this->is_visible,
                ]);

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'La categoría fue restaurada correctamente.'
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
                'name'        => $this->name,
                'slug'        => $this->slug,
                'parent_id'   => $this->parent_id,
                'position'    => $this->position,
                'is_visible'  => $this->is_visible,
            ]);

            $category = $this->category;
        } else {

            // CREAR
            $category = Category::create([
                'name'        => $this->name,
                'slug'        => $this->slug,
                'parent_id'   => $this->parent_id,
                'position'    => $this->position,
                'is_visible'  => $this->is_visible,

                // Valor temporal para evitar el error de MySQL
                'path'        => '',
            ]);
        }

        // Generar el path correcto
        $path = $category->parent_id
            ? $category->parent->path . '/' . $category->slug
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
                : 'Categoría creada correctamente.'
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
            'position'
        ]);

        $this->is_visible = true;
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
