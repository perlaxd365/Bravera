<?php

namespace App\Livewire\Admin\Catalog\Brands;

use App\Models\Brand;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;

class Form extends Component
{
    public ?Brand $brand = null;

    public bool $show = false;

    public string $name = '';
    public string $slug = '';
    public ?string $description = null;
    public ?string $image = null;
    public bool $is_active = true;
    public int $sort_order = 0;

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

            'image' => 'nullable|string|max:255',

            'sort_order' => 'required|integer|min:0',

            'is_active' => 'boolean',

        ];
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

        $this->show = true;
    }

    public function updatedName()
    {
        $this->slug = Str::slug($this->name);
    }

    public function save()
    {
        // Si es una marca nueva, buscar primero en eliminadas
        if (!$this->brand) {

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
                    'message' => 'La marca fue restaurada correctamente.'
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
                : 'Marca creada correctamente.'
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
        ]);

        $this->is_active = true;
    }

    protected function messages()
    {
        return [

            'name.required' => 'Debe ingresar el nombre de la marca.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',

            'slug.required' => 'El slug es obligatorio.',
            'slug.unique' => 'Ya existe una marca con ese nombre.',

        ];
    }

    public function render()
    {
        return view('livewire.admin.catalog.brands.form');
    }
}