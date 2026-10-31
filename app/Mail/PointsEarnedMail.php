<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PointsEarnedMail extends Mailable implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $points,
        public int $balance,
        public string $orderNumber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ganaste '.$this->points.' Brevare Puntos',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.points-earned',
            with: [
                'user' => $this->user,
                'points' => $this->points,
                'balance' => $this->balance,
                'amount' => app(\App\Services\PointsService::class)->rewardAmount($this->balance),
                'orderNumber' => $this->orderNumber,
                'company' => config('brevare.company'),
            ],
        );
    }
}
