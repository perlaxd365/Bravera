<?php

namespace App\Livewire\Admin\Catalog\Attributes;

use App\Models\Attribute;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[On('attribute-saved')]
    public function refresh()
    {
        $this->resetPage();
    }

    public function delete(Attribute $attribute)
    {
        $attribute->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Atributo eliminado correctamente.',
        ]);
    }

    public function render()
    {
        $attributes = Attribute::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%");
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.catalog.attributes.index', [
            'attributes' => $attributes,
        ]);
    }
}
