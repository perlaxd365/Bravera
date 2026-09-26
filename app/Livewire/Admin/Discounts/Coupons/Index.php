<?php

namespace App\Livewire\Admin\Discounts\Coupons;

use App\Models\Coupon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('coupon-saved')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    public function toggle(Coupon $coupon): void
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->usages()->delete();
        $coupon->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Cupón eliminado correctamente.',
        ]);
    }

    public function render()
    {
        $coupons = Coupon::withCount('usages')
            ->when($this->search !== '', fn ($q) => $q->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.admin.discounts.coupons.index', [
            'coupons' => $coupons,
        ]);
    }
}
