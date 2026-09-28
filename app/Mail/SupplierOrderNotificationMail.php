<?php

namespace App\Mail;

use App\Models\SupplierOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupplierOrderNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupplierOrder $supplierOrder) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva orden: '.$this->supplierOrder->supplier_order_number.' — Brevare',
        );
    }

    public function content(): Content
    {
        // Las fotos viajan en el correo, así que se cargan aquí y no en el
        // constructor: al encolar el mailable el modelo se serializa y las
        // relaciones cargadas en el constructor se pierden.
        $this->supplierOrder->loadMissing([
            'supplier',
            'order',
            'items.orderItem.variant.images',
            'items.orderItem.product.variants.images',
        ]);

        return new Content(
            view: 'emails.supplier-order-notification',
            with: [
                'order' => $this->supplierOrder,
            ],
        );
    }
}
