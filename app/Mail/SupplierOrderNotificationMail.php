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
            subject: 'Nueva orden: '.$this->supplierOrder->supplier_order_number.' — Bravera',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.supplier-order-notification',
            with: [
                'order' => $this->supplierOrder,
            ],
        );
    }
}
