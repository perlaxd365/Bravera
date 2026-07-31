<?php

namespace App\Livewire\Admin\Catalog\Attributes;

use App\Models\Attribute;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?Attribute $attribute = null;

    public bool $show = false;

    public string $name = '';

    public string $slug = '';

    public string $type = 'select';

    public bool $is_filter = true;

    public bool $is_required = false;

    public bool $is_active = true;

    public int $sort_order = 0;

    protected function rules()
    {
        return [

            'name' => 'required|min:3|max:100',

            'slug' => [
                'required',
                'max:120',
                Rule::unique('attributes', 'slug')
                    ->ignore($this->attribute?->id),
            ],

            'type' => 'required|in:text,number,select,color,boolean',

            'sort_order' => 'required|integer|min:0',

            'is_filter' => 'boolean',

            'is_required' => 'boolean',

            'is_active' => 'boolean',

        ];
    }

    #[On('attribute-create')]
    public function create()
    {
        $this->resetForm();

        $this->show = true;
    }

    #[On('attribute-edit')]
    public function edit($id)
    {
        $this->attribute = Attribute::findOrFail($id);

        $this->fill([

            'name' => $this->attribute->name,

            'slug' => $this->attribute->slug,

            'type' => $this->attribute->type,

            'is_filter' => $this->attribute->is_filter,

            'is_required' => $this->attribute->is_required,

            'is_active' => $this->attribute->is_active,

            'sort_order' => $this->attribute->sort_order,

        ]);

        $this->show = true;
    }

    public function updatedName()
    {
        if (!$this->attribute) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save()
    {
        $this->validate();

        Attribute::updateOrCreate(

            ['id' => $this->attribute?->id],

            [

                'name' => $this->name,

                'slug' => $this->slug,

                'type' => $this->type,

                'is_filter' => $this->is_filter,

                'is_required' => $this->is_required,

                'is_active' => $this->is_active,

                'sort_order' => $this->sort_order,

            ]

        );

        $this->dispatch('attribute-saved');

        $this->dispatch('notify', [

            'type' => 'success',

            'message' => $this->attribute
                ? 'Atributo actualizado correctamente.'
                : 'Atributo creado correctamente.',

        ]);

        $this->show = false;

        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset([

            'attribute',

            'name',

            'slug',

            'sort_order',

        ]);

        $this->type = 'select';

        $this->is_filter = true;

        $this->is_required = false;

        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.admin.catalog.attributes.form');
    }
}
