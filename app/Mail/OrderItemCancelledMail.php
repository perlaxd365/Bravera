<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderItemCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public int $itemId,
        public bool $wholeOrderCancelled = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->wholeOrderCancelled
            ? 'Tu pedido '.$this->order->order_number.' fue cancelado | Brevare'
            : 'Actualización de tu pedido '.$this->order->order_number.' | Brevare';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        // Las fotos viajan en el correo, así que se cargan aquí y no en el
        // constructor: al encolar el mailable el modelo se serializa y las
        // relaciones cargadas en el constructor se pierden.
        $this->order->loadMissing([
            'user',
            'items.variant.images',
            'items.product.variants.images',
        ]);

        return new Content(
            view: 'emails.order-item-cancelled',
            with: [
                'order' => $this->order,
                'item' => $this->order->items->firstWhere('id', $this->itemId),
                'status' => $this->order->status,
                'wholeOrderCancelled' => $this->wholeOrderCancelled,
            ],
        );
    }
}
