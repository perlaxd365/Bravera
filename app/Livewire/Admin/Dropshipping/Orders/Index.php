<?php

namespace App\Livewire\Admin\Dropshipping\Orders;

use App\Enums\SupplierOrderStatus;
use App\Models\SupplierOrder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $status = '';

    public array $quickStatus = [];

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updateStatus(SupplierOrder $supplierOrder): void
    {
        $status = $this->quickStatus[$supplierOrder->id] ?? $supplierOrder->status->value;

        $supplierOrder->update(['status' => $status]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Orden '.$supplierOrder->supplier_order_number.' actualizada a '.SupplierOrderStatus::from($status)->label().'.',
        ]);
    }

    public function render()
    {
        $supplierOrders = SupplierOrder::query()
            ->with(['supplier', 'order', 'payment'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
            ->paginate(15);

        foreach ($supplierOrders as $so) {
            $this->quickStatus[$so->id] ??= $so->status->value;
        }

        return view('livewire.admin.dropshipping.orders.index', [
            'supplierOrders' => $supplierOrders,
            'totals' => [
                'pending' => SupplierOrder::where('status', SupplierOrderStatus::PENDING)->count(),
                'sent' => SupplierOrder::where('status', SupplierOrderStatus::SENT)->count(),
                'payable' => SupplierOrder::whereDoesntHave('payment')->sum('total_cost'),
            ],
        ]);
    }
}
