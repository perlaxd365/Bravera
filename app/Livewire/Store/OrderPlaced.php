<?php

namespace App\Livewire\Store;

use App\Models\Order;
use App\Models\Payment;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class OrderPlaced extends Component
{
    public ?Order $order = null;

    public ?Payment $latestPayment = null;

    public function mount(?Order $order): void
    {
        $this->order = $order?->load([
            'items.variant.images',
            'items.product.variants.images',
            'items.supplierVariant.supplier',
            'supplierOrders.supplier',
        ]);

        abort_unless($this->order, 404);

        if (auth()->id() !== $this->order->user_id) {
            abort(403);
        }

        $this->latestPayment = $this->order->payments()->latest('id')->first();
    }

    public function render()
    {
        return view('livewire.store.order-placed');
    }
}
