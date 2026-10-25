<?php

namespace App\Livewire\Store;

use App\Enums\PaymentStatus;
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
            'items.product.courseAssets',
            'items.supplierVariant.supplier',
            'supplierOrders.supplier',
        ]);

        abort_unless($this->order, 404);

        if (auth()->id() !== $this->order->user_id) {
            abort(403);
        }

        $this->latestPayment = $this->order->payments()->latest('id')->first();

        if ($this->order->payment_status === PaymentStatus::PAID) {
            $eventId = $this->order->meta_purchase_event_id
                ?: 'purchase_'.$this->order->order_number;

            $this->dispatch('brevare-meta-event', [
                'name' => 'Purchase',
                'event_id' => $eventId,
                'data' => [
                    'content_ids' => $this->order->items
                        ->map(fn ($item) => $item->variant_sku ?: (string) $item->product_id)
                        ->filter()
                        ->values()
                        ->all(),
                    'contents' => $this->order->items
                        ->map(fn ($item) => [
                            'id' => $item->variant_sku ?: (string) $item->product_id,
                            'quantity' => (int) $item->quantity,
                            'item_price' => round((float) $item->unit_price, 2),
                        ])
                        ->values()
                        ->all(),
                    'content_type' => 'product',
                    'value' => round((float) $this->order->total, 2),
                    'currency' => $this->order->currency ?: 'PEN',
                    'num_items' => (int) $this->order->items->sum('quantity'),
                    'order_id' => $this->order->order_number,
                ],
            ]);
        }
    }

    public function render()
    {
        return view('livewire.store.order-placed');
    }
}
