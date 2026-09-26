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
            ? 'Tu pedido '.$this->order->order_number.' fue cancelado | Bravera'
            : 'Actualización de tu pedido '.$this->order->order_number.' | Bravera';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
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
