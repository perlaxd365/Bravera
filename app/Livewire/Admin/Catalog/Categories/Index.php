<?php

namespace App\Livewire\Admin\Catalog\Categories;

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $categories = Category::with('parent')
            ->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%");
            })
            ->orderBy('position')
            ->paginate(10);

        return view('livewire.admin.catalog.categories.index', compact('categories'));
    }

    #[On('category-saved')]
    public function refresh()
    {
        $this->resetPage();
    }

    public function delete(Category $category)
    {
        if ($category->children()->exists()) {

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No puedes eliminar una categoría con subcategorías.',
            ]);

            return;
        }

        $category->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Categoría eliminada correctamente.',
        ]);
    }
}
