<?php

namespace App\Listeners;

use App\Mail\OrderConfirmationMail;
use App\Mail\AdminOrderConfirmationMail;
use App\Modules\Ordering\Events\OrderPaid;
use App\Services\ReliableMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderPaid $event): void
    {
        $order = $event->order->loadMissing('items');
        $recipient = $order->user?->email ?? $order->customer_snapshot['email'] ?? null;

        if ($recipient) {
            ReliableMailer::send(
                [$recipient],
                new OrderConfirmationMail($order),
                description: 'correo de confirmación de pedido',
                context: ['order_id' => $order->id],
            );
        }

        $adminAddress = config('mail.admin_address');

        if (! is_string($adminAddress) || trim($adminAddress) === '') {
            return;
        }

        ReliableMailer::send(
            [$adminAddress],
            new AdminOrderConfirmationMail($order),
            description: 'notificación administrativa de pedido',
            context: ['order_id' => $order->id],
        );
    }
}
