<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $status = '';

    public array $counters = [];

    public function boot(): void
    {
        $this->counters = [
            'all' => Order::count(),
            'pending' => Order::where('status', OrderStatus::PENDING)->count(),
            'processing' => Order::where('status', OrderStatus::PROCESSING)->count(),
            'shipped' => Order::where('status', OrderStatus::SHIPPED)->count(),
            'delivered' => Order::where('status', OrderStatus::DELIVERED)->count(),
            'cancelled' => Order::where('status', OrderStatus::CANCELLED)->count(),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = Order::query()
            ->with(['user', 'items'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($query) {
                    $query->where('order_number', 'like', "%{$this->search}%")
                        ->orWhere('customer_snapshot', 'like', "%{$this->search}%")
                        ->orWhere('coupon_code', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
        ]);
    }
}
