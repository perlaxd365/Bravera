<?php

namespace App\Livewire\Admin\Catalog\AttributeValues;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?AttributeValue $attributeValue = null;

    public bool $show = false;

    public ?int $attribute_id = null;
    public string $value = '';
    public string $slug = '';
    public ?string $color = null;
    public int $sort_order = 0;
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'attribute_id' => ['required', 'exists:attributes,id'],
            'value' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'max:170',
                Rule::unique('attribute_values')
                    ->ignore($this->attributeValue?->id)
                    ->where(fn($q) => $q->where('attribute_id', $this->attribute_id)),
            ],
            'color' => ['nullable', 'max:10'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function updatedValue($value): void
    {
        $this->slug = Str::slug($value);
    }

    #[On('attribute-value-create')]
    public function create(): void
    {
        $this->resetForm();
        $this->show = true;
    }

    #[On('attribute-value-edit')]
    public function edit(int $id): void
    {
        $this->attributeValue = AttributeValue::findOrFail($id);

        $this->attribute_id = $this->attributeValue->attribute_id;
        $this->value = $this->attributeValue->value;
        $this->slug = $this->attributeValue->slug;
        $this->color = $this->attributeValue->color;
        $this->sort_order = $this->attributeValue->sort_order;
        $this->is_active = $this->attributeValue->is_active;

        $this->show = true;
    }

    public function save(): void
    {
        $this->validate();

        $deleted = AttributeValue::onlyTrashed()
            ->where('attribute_id', $this->attribute_id)
            ->where('slug', $this->slug)
            ->first();

        if ($deleted) {
            $deleted->restore();

            $deleted->update([
                'value' => $this->value,
                'color' => $this->color,
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
            ]);
        } else {
            AttributeValue::updateOrCreate(
                ['id' => $this->attributeValue?->id],
                [
                    'attribute_id' => $this->attribute_id,
                    'value' => $this->value,
                    'slug' => $this->slug,
                    'color' => $this->color,
                    'sort_order' => $this->sort_order,
                    'is_active' => $this->is_active,
                ]
            );
        }

        $this->dispatch('attribute-value-saved');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Valor guardado correctamente.'
        ]);

        $this->show = false;

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->resetValidation();

        $this->attributeValue = null;

        $this->attribute_id = null;
        $this->value = '';
        $this->slug = '';
        $this->color = null;
        $this->sort_order = 0;
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.admin.catalog.attribute-values.form', [
            'attributes' => Attribute::active()
                ->orderBy('name')
                ->get(),
        ]);
    }
}
