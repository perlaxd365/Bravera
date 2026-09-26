<?php

namespace App\Livewire\Admin\Catalog\AttributeValues;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public ?int $attributeFilter = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingAttributeFilter(): void
    {
        $this->resetPage();
    }

    #[On('attribute-value-saved')]
    public function refreshList(): void
    {
        // Solo refresca el componente
    }

    public function delete(int $id): void
    {
        AttributeValue::findOrFail($id)->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Valor eliminado correctamente.',
        ]);
    }

    public function render()
    {
        $values = AttributeValue::with('attribute')
            ->when($this->search, function ($query) {
                $query->where('value', 'like', "%{$this->search}%");
            })
            ->when($this->attributeFilter, function ($query) {
                $query->where('attribute_id', $this->attributeFilter);
            })
            ->orderBy('sort_order')
            ->orderBy('value')
            ->paginate(10);

        return view('livewire.admin.catalog.attribute-values.index', [
            'values' => $values,
            'attributes' => Attribute::active()
                ->orderBy('name')
                ->get(),
        ])->layout('admin.layouts.app');
    }
}
