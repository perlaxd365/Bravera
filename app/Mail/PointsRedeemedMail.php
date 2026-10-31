<?php

namespace App\Mail;

use App\Models\PointRedemption;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PointsRedeemedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public PointRedemption $redemption,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recibimos tu solicitud de canje de Brevare Puntos',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.points-redeemed',
            with: [
                'user' => $this->user,
                'redemption' => $this->redemption,
                'company' => config('brevare.company'),
            ],
        );
    }
}
