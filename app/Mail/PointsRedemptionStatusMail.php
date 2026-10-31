<?php

namespace App\Mail;

use App\Models\PointRedemption;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PointsRedemptionStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public PointRedemption $redemption,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->redemption->status) {
            PointRedemption::STATUS_PAID => 'Tu Brevare Puntos fue depositado',
            PointRedemption::STATUS_APPROVED => 'Tu canje de Brevare Puntos fue aprobado',
            PointRedemption::STATUS_REJECTED => 'Tu canje de Brevare Puntos fue rechazado',
            default => 'Actualización de tu canje Brevare Puntos',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.points-redemption-status',
            with: [
                'user' => $this->user,
                'redemption' => $this->redemption,
                'company' => config('brevare.company'),
            ],
        );
    }
}
