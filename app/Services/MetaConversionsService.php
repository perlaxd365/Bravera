<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaConversionsService
{
    public function pixelEnabled(): bool
    {
        return (bool) config('meta.pixel_enabled')
            && filled(config('meta.pixel_id'));
    }

    public function capiEnabled(): bool
    {
        return (bool) config('meta.capi_enabled')
            && filled(config('meta.pixel_id'))
            && filled(config('meta.access_token'));
    }

    public function consentGranted(?Order $order = null): bool
    {
        if ($order !== null) {
            return (bool) $order->marketing_consent;
        }

        return request()->cookie(config('meta.consent_cookie')) === 'all';
    }

    /**
     * Envía un evento a la Conversions API. Devuelve true solo si Meta aceptó
     * la petición; nunca lanza una excepción hacia el checkout o el webhook.
     */
    public function track(
        string $eventName,
        string $eventId,
        array $customData,
        ?Order $order = null,
    ): bool {
        if (! $this->capiEnabled() || ! $this->consentGranted($order)) {
            return false;
        }

        $version = trim((string) config('meta.graph_version', 'v23.0'), '/');
        $pixelId = (string) config('meta.pixel_id');
        $url = "https://graph.facebook.com/{$version}/{$pixelId}/events";
        $payload = [
            'data' => [[
                'event_name' => $eventName,
                'event_time' => now()->timestamp,
                'event_id' => $eventId,
                'action_source' => 'website',
                'event_source_url' => $this->eventSourceUrl($order),
                'user_data' => $this->userData($order),
                'custom_data' => $this->clean($customData),
            ]],
        ];

        $testEventCode = config('meta.test_event_code');
        if (filled($testEventCode)) {
            $payload['test_event_code'] = $testEventCode;
        }

        try {
            $response = Http::withToken((string) config('meta.access_token'))
                ->timeout(5)
                ->connectTimeout(2)
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI no respondió.', [
                'event' => $eventName,
                'exception' => $e::class,
            ]);

            return false;
        }

        if ($response->successful()) {
            return true;
        }

        Log::warning('Meta CAPI rechazó un evento.', [
            'event' => $eventName,
            'status' => $response->status(),
            'error' => mb_substr((string) $response->json('error.message'), 0, 180),
        ]);

        return false;
    }

    /**
     * Purchase se envía una sola vez por pedido y comparte event_id con el
     * Pixel del navegador para que Meta deduplique el evento.
     */
    public function purchase(Order $order): bool
    {
        if (! $this->capiEnabled() || ! $order->marketing_consent || $order->meta_purchase_sent_at) {
            return false;
        }

        $order->loadMissing(['items', 'user']);

        $eventId = $order->meta_purchase_event_id
            ?: 'purchase_'.$order->order_number;

        if (! $order->meta_purchase_event_id) {
            $order->forceFill(['meta_purchase_event_id' => $eventId])->save();
        }

        $contentIds = $order->items
            ->map(fn ($item) => $item->variant_sku ?: (string) $item->product_id)
            ->filter()
            ->values()
            ->all();

        $contents = $order->items
            ->map(fn ($item) => [
                'id' => $item->variant_sku ?: (string) $item->product_id,
                'quantity' => (int) $item->quantity,
                'item_price' => round((float) $item->unit_price, 2),
            ])
            ->values()
            ->all();

        $sent = $this->track('Purchase', $eventId, [
            'currency' => $order->currency ?: 'PEN',
            'value' => round((float) $order->total, 2),
            'content_ids' => $contentIds,
            'contents' => $contents,
            'content_type' => 'product',
            'num_items' => (int) $order->items->sum('quantity'),
            'order_id' => $order->order_number,
        ], $order);

        if ($sent) {
            $order->forceFill(['meta_purchase_sent_at' => now()])->save();
        }

        return $sent;
    }

    private function userData(?Order $order = null): array
    {
        if ($order !== null) {
            $customer = (array) ($order->customer_snapshot ?? []);
            $address = (array) ($order->address_snapshot ?? []);
            $email = $customer['email'] ?? $order->user?->email;
            $phone = $address['phone'] ?? $order->user?->phone;
            $externalId = $order->user_id;
            $fbc = $customer['meta_fbc'] ?? null;
            $fbp = $customer['meta_fbp'] ?? null;
            $ip = $customer['meta_client_ip'] ?? null;
            $userAgent = $customer['meta_client_user_agent'] ?? null;
        } else {
            $user = auth()->user();
            $email = $user?->email;
            $phone = $user?->phone;
            $externalId = $user?->id;
            $fbc = request()->cookie('_fbc');
            $fbp = request()->cookie('_fbp');
            $ip = request()->ip();
            $userAgent = request()->userAgent();
        }

        $data = [
            'em' => filled($email) ? [hash('sha256', mb_strtolower(trim((string) $email)))] : null,
            'ph' => filled($phone) ? [hash('sha256', $this->normalizePhone((string) $phone))] : null,
            'external_id' => filled($externalId) ? [hash('sha256', (string) $externalId)] : null,
            'fbc' => $fbc,
            'fbp' => $fbp,
            'client_ip_address' => $ip,
            'client_user_agent' => $userAgent,
        ];

        return $this->clean($data);
    }

    private function eventSourceUrl(?Order $order = null): ?string
    {
        if ($order !== null) {
            return ($order->customer_snapshot ?? [])['meta_event_source_url'] ?? null;
        }

        return request()->headers->get('referer') ?: request()->fullUrl();
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
            return '51'.$digits;
        }

        return $digits;
    }

    private function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->clean($value);
            }

            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
