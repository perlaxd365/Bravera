<?php

namespace App\Listeners;

use App\Mail\OrderItemCancelledMail;
use App\Modules\Ordering\Events\OrderItemCancelled;
use App\Services\ReliableMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderItemCancelledEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Avisa al cliente cuando se cancela un producto de su pedido.
     */
    public function handle(OrderItemCancelled $event): void
    {
        $order = $event->order;
        $recipient = $order->user?->email ?? $order->customer_snapshot['email'] ?? null;

        if (! $recipient) {
            return;
        }

        ReliableMailer::send(
            [$recipient],
            new OrderItemCancelledMail(
                $order->load('items'),
                $event->item->id,
                $event->wholeOrderCancelled,
            ),
            description: 'aviso de producto cancelado',
            context: [
                'order_id' => $order->id,
                'order_item_id' => $event->item->id,
            ],
        );
    }
}
