<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClaimConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Claim $claim) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recibimos tu '.Claim::types()[$this->claim->claim_type].' '.$this->claim->code.' — Brevare',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.claim-confirmation',
            with: ['claim' => $this->claim],
        );
    }
}
