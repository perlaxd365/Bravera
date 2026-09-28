<?php

namespace App\Livewire\Store;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class OrderPlaced extends Component
{
    public ?Order $order = null;

    public function mount(?Order $order): void
    {
        $this->order = $order?->load([
            'items.variant.images',
            'items.product.variants.images',
            'items.supplierVariant.supplier',
            'payment',
            'supplierOrders.supplier',
        ]);

        abort_unless($this->order, 404);

        if (auth()->id() !== $this->order->user_id) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.store.order-placed');
    }
}
