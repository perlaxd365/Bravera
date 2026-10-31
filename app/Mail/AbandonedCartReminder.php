<?php

namespace App\Mail;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AbandonedCartReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Cart $cart) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu carrito en Brevare te espera',
        );
    }

    public function content(): Content
    {
        $this->cart->loadMissing([
            'user',
            'items.variant.product',
            'items.variant.images',
            'items.variant.attributeValues.attribute',
            'items.variant.attributeValues.value',
            'items.supplierVariant',
        ]);

        return new Content(
            view: 'emails.abandoned-cart-reminder',
            with: [
                'cart' => $this->cart,
                'recoveryUrl' => $this->recoveryUrl(),
                'unsubscribeUrl' => $this->unsubscribeUrl(),
                'company' => config('brevare.company'),
            ],
        );
    }

    public function headers(): Headers
    {
        $unsubscribe = $this->unsubscribeUrl();

        return new Headers(
            text: $unsubscribe
                ? ['List-Unsubscribe' => '<'.$unsubscribe.'>']
                : [],
        );
    }

    private function recoveryUrl(): string
    {
        if (blank($this->cart->recovery_token)) {
            return route('store.cart');
        }

        return route('cart.recover', ['token' => $this->cart->recovery_token]);
    }

    private function unsubscribeUrl(): ?string
    {
        if (! $this->cart->user_id) {
            return null;
        }

        return URL::signedRoute('cart.recovery.opt-out', [
            'user' => $this->cart->user_id,
        ]);
    }
}
