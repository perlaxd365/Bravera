<?php

namespace App\Livewire\Admin\Catalog\Brands;

use App\Models\Brand;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $brands = Brand::query()
            ->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%");
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.catalog.brands.index', compact('brands'));
    }

    #[On('brand-saved')]
    public function refresh()
    {
        $this->resetPage();
    }

    public function delete(Brand $brand)
    {
        // En el futuro validaremos si tiene productos asociados.
        $brand->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Marca eliminada correctamente.',
        ]);
    }
}
