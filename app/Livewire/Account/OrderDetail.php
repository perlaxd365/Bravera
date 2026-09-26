<?php

namespace App\Livewire\Account;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.account')]
class OrderDetail extends Component
{
    public ?Order $order = null;

    public function mount(?Order $order): void
    {
        $this->order = $order?->load([
            'items.variant.product',
            'items.supplierVariant.supplier',
            'payment',
            'supplierOrders.supplier',
        ]);

        abort_unless($this->order, 404);

        if ($this->order->user_id !== auth()->id()) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.account.order-detail');
    }
}
