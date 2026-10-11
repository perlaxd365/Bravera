<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Sends Laravel mail through Brevo's HTTPS transactional email API.
 */
final class BrevoApiTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $apiKey,
        private readonly int $timeout = 10,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $sentMessage): void
    {
        if (trim($this->apiKey) === '') {
            throw new TransportException('Brevo API key is not configured.');
        }

        $message = $sentMessage->getOriginalMessage();

        if (! $message instanceof Email) {
            throw new TransportException('Brevo API transport only supports structured email messages.');
        }

        $sender = $this->firstAddress($message->getFrom());
        $recipients = $this->addresses($message->getTo());

        if ($sender === null || $recipients === []) {
            throw new TransportException('Brevo email requires a sender and at least one recipient.');
        }

        $payload = [
            'sender' => $sender,
            'to' => $recipients,
            'subject' => (string) $message->getSubject(),
        ];

        if (($html = $this->body($message->getHtmlBody())) !== null) {
            $payload['htmlContent'] = $html;
        }

        if (($text = $this->body($message->getTextBody())) !== null) {
            $payload['textContent'] = $text;
        }

        if (isset($payload['htmlContent']) === false && isset($payload['textContent']) === false) {
            throw new TransportException('Brevo email must contain an HTML or plain-text body.');
        }

        foreach (['cc', 'bcc'] as $field) {
            $addresses = $this->addresses($message->{'get'.ucfirst($field)}());

            if ($addresses !== []) {
                $payload[$field] = $addresses;
            }
        }

        if (($replyTo = $this->firstAddress($message->getReplyTo())) !== null) {
            $payload['replyTo'] = $replyTo;
        }

        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            $filename = $attachment->getFilename();

            if ($filename === null || $filename === '') {
                continue;
            }

            $attachments[] = [
                'name' => $filename,
                'content' => base64_encode($attachment->getBody()),
            ];
        }

        if ($attachments !== []) {
            $payload['attachment'] = $attachments;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['api-key' => $this->apiKey])
                ->timeout(max(1, $this->timeout))
                ->connectTimeout(min(5, max(1, $this->timeout)))
                ->post('https://api.brevo.com/v3/smtp/email', $payload);
        } catch (\Throwable $exception) {
            throw new TransportException('Could not connect to the Brevo email API.', 0, $exception);
        }

        if (! $response->successful()) {
            throw new TransportException(sprintf(
                'Brevo email API returned HTTP %d.',
                $response->status(),
            ));
        }

        $messageId = $response->json('messageId');

        if (is_string($messageId) && $messageId !== '') {
            $sentMessage->setMessageId($messageId);
        }
    }

    public function __toString(): string
    {
        return 'brevo-api';
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array{email: string, name?: string}|null
     */
    private function firstAddress(array $addresses): ?array
    {
        $address = $addresses[0] ?? null;

        if (! $address instanceof Address) {
            return null;
        }

        return $this->formatAddress($address);
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array{email: string, name?: string}>
     */
    private function addresses(array $addresses): array
    {
        return array_values(array_map(
            fn (Address $address): array => $this->formatAddress($address),
            array_filter($addresses, fn (mixed $address): bool => $address instanceof Address),
        ));
    }

    /**
     * @return array{email: string, name?: string}
     */
    private function formatAddress(Address $address): array
    {
        $formatted = ['email' => $address->getAddress()];

        if ($address->getName() !== '') {
            $formatted['name'] = $address->getName();
        }

        return $formatted;
    }

    private function body(mixed $body): ?string
    {
        if (is_string($body)) {
            return $body;
        }

        if (is_resource($body)) {
            $contents = stream_get_contents($body);

            return $contents === false ? null : $contents;
        }

        return null;
    }
}
