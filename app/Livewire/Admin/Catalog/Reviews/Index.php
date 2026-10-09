<?php

namespace App\Livewire\Admin\Catalog\Reviews;

use App\Models\ProductReview;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $status = 'pending';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $this->updateStatus($id, ProductReview::STATUS_APPROVED, 'Reseña aprobada y publicada.');
    }

    public function reject(int $id): void
    {
        $this->updateStatus($id, ProductReview::STATUS_REJECTED, 'Reseña rechazada.');
    }

    public function delete(int $id): void
    {
        ProductReview::query()->whereKey($id)->delete();

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Reseña eliminada.']);
    }

    private function updateStatus(int $id, string $status, string $message): void
    {
        $review = ProductReview::query()->findOrFail($id);
        $review->update(['status' => $status]);

        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
    }

    public function render()
    {
        $counts = [
            'pending' => ProductReview::query()->where('status', ProductReview::STATUS_PENDING)->count(),
            'approved' => ProductReview::query()->where('status', ProductReview::STATUS_APPROVED)->count(),
            'rejected' => ProductReview::query()->where('status', ProductReview::STATUS_REJECTED)->count(),
        ];

        $reviews = ProductReview::query()
            ->with(['product:id,name,slug', 'user:id,name,email'])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.addcslashes(trim($this->search), '%_\\').'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('comment', 'like', $term)
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', $term))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.catalog.reviews.index', compact('reviews', 'counts'))
            ->title('Reseñas | Brevare');
    }
}
