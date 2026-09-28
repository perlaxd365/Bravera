<?php

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderStatus $previousStatus,
        public OrderStatus $newStatus,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu pedido '.$this->order->order_number.' — '.$this->newStatus->label().' | Brevare',
        );
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
            view: 'emails.order-status-changed',
            with: [
                'order' => $this->order,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
            ],
        );
    }
}
