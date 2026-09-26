<?php

namespace App\Listeners;

use App\Mail\SupplierOrderNotificationMail;
use App\Modules\Ordering\Events\OrderPaid;
use App\Services\ReliableMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuppliersAboutOrders implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Cada proveedor recibe la confirmación de su parte del pedido.
     */
    public function handle(OrderPaid $event): void
    {
        $event->order->supplierOrders()
            ->with(['supplier', 'order', 'items.orderItem'])
            ->get()
            ->each(function ($supplierOrder) {
                $supplier = $supplierOrder->supplier;

                if (! $supplier?->email) {
                    return;
                }

                ReliableMailer::send(
                    [$supplier->email],
                    new SupplierOrderNotificationMail($supplierOrder),
                    description: 'notificación de orden al proveedor',
                    context: [
                        'supplier_order_id' => $supplierOrder->id,
                        'supplier_id' => $supplier->id,
                    ],
                );
            });
    }
}
