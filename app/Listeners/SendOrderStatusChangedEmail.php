<?php

namespace App\Listeners;

use App\Mail\OrderStatusChangedMail;
use App\Modules\Ordering\Events\OrderStatusChanged;
use App\Services\ReliableMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderStatusChangedEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Aviso al cliente cuando el pedido cambia de estado
     * (en preparación, enviado, entregado, cancelado, reembolsado).
     */
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->from === $event->to) {
            return;
        }

        $order = $event->order;
        $recipient = $order->user?->email ?? $order->customer_snapshot['email'] ?? null;

        if (! $recipient) {
            return;
        }

        ReliableMailer::send(
            [$recipient],
            new OrderStatusChangedMail($order, $event->from, $event->to),
            description: 'aviso de estado del pedido',
            context: [
                'order_id' => $order->id,
                'from' => $event->from->value,
                'to' => $event->to->value,
            ],
        );
    }
}
