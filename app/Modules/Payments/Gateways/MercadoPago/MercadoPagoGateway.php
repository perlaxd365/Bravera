<?php

namespace App\Modules\Payments\Gateways\MercadoPago;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Models\Payment;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use Illuminate\Support\Str;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentRefundClient;
use MercadoPago\Client\Preference\PreferenceClient;
use Throwable;

/** Checkout Pro hosted flow. Card details are entered only on Mercado Pago. */
class MercadoPagoGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'mercadopago';
    }

    public function isAvailable(): bool
    {
        $token = config('payments.mercadopago.access_token');
        $secret = config('payments.mercadopago.webhook_secret');
        $url = config('payments.mercadopago.webhook_url');

        return (bool) config('payments.mercadopago.enabled')
            && is_string($token) && trim($token) !== ''
            && is_string($secret) && trim($secret) !== ''
            && is_string($url) && str_starts_with($url, 'https://');
    }

    public function charge(Payment $payment): array
    {
        $this->ensureConfigured();

        if ((float) $payment->amount <= 0 || $payment->currency !== 'PEN') {
            return $this->failure('El pedido debe tener un total válido en soles.', 'invalid_amount_or_currency');
        }

        $order = $payment->order?->loadMissing(['items', 'user']);

        if ($order === null) {
            return $this->failure('No encontramos el pedido asociado al pago.', 'order_not_found');
        }

        $idempotencyKey = $payment->source_id ?: (string) Str::uuid();
        $payment->forceFill(['source_id' => $idempotencyKey])->save();

        if (! $order->items()->exists()) {
            return $this->failure('El pedido no contiene productos para cobrar.', 'empty_order');
        }

        // El total congelado del pedido incluye envío y descuentos; cobrar el
        // total como una sola línea evita discrepancias entre checkout y orden.
        $items = [[
            'id' => (string) $order->order_number,
            'title' => mb_substr('Pedido Brevare '.$order->order_number, 0, 250),
            'quantity' => 1,
            'unit_price' => round((float) $order->total, 2),
            'currency_id' => 'PEN',
        ]];

        $orderUrl = route('payments.mercadopago.return', [], true);
        $payerEmail = (string) ($order->user?->email ?? data_get($order->customer_snapshot, 'email', ''));
        $expiresFrom = now()->addMinute();

        $preferenceData = [
            'items' => $items,
            'payer' => array_filter([
                'name' => (string) data_get($order->customer_snapshot, 'name', ''),
                'email' => $payerEmail,
            ], static fn ($value) => $value !== ''),
            'external_reference' => (string) $order->order_number,
            'notification_url' => config('payments.mercadopago.webhook_url'),
            'expires' => true,
            'expiration_date_from' => $expiresFrom->toIso8601String(),
            'expiration_date_to' => $expiresFrom->copy()->addHours(max(1, (int) config('payments.pending_order_hours', 24)))->toIso8601String(),
            'back_urls' => [
                'success' => $orderUrl,
                'pending' => $orderUrl,
                'failure' => $orderUrl,
            ],
            'statement_descriptor' => (string) config('payments.mercadopago.statement_descriptor', 'BREVARE'),
            'metadata' => [
                'order_number' => (string) $order->order_number,
                'payment_id' => (int) $payment->id,
            ],
        ];

        // Mercado Pago solo admite auto_return si todas las back_urls son HTTPS.
        if (str_starts_with($orderUrl, 'https://')) {
            $preferenceData['auto_return'] = 'approved';
        }

        try {
            $preference = (new PreferenceClient)->create($preferenceData, $this->requestOptions($idempotencyKey));
        } catch (Throwable $e) {
            report($e);

            return $this->failure('No pudimos iniciar el pago con Mercado Pago. Puedes intentarlo otra vez.', 'preference_creation_failed');
        }

        $preferenceId = (string) ($preference->id ?? '');
        $checkoutUrl = (string) ($preference->init_point ?? '');

        if ($preferenceId === '' || ! filter_var($checkoutUrl, FILTER_VALIDATE_URL)
            || ! str_starts_with($checkoutUrl, 'https://www.mercadopago.')) {
            return $this->failure('Mercado Pago no devolvió un enlace de pago válido.', 'invalid_preference_response');
        }

        return [
            'success' => false,
            'transaction_id' => null,
            'source_id' => $idempotencyKey,
            'checkout_url' => $checkoutUrl,
            'status' => PaymentStatus::PENDING->value,
            'message' => 'Continúa el pago en Mercado Pago.',
            'raw' => [
                'preference_id' => $preferenceId,
                'external_reference' => (string) $order->order_number,
            ],
        ];
    }

    public function refund(Payment $payment, ?RefundReason $reason = null): array
    {
        $this->ensureConfigured();
        $paymentId = $payment->gateway_transaction_id;

        if (! is_string($paymentId) || ! ctype_digit($paymentId)) {
            return $this->failure('El pago no tiene un identificador reembolsable de Mercado Pago.', 'missing_payment_id');
        }

        try {
            $refund = (new PaymentRefundClient)->refundTotal((int) $paymentId, $this->requestOptions((string) Str::uuid()));
            $status = strtolower((string) ($refund->status ?? ''));

            if ($status === 'approved') {
                return [
                    'success' => true,
                    'transaction_id' => (string) ($refund->id ?? $paymentId),
                    'status' => PaymentStatus::REFUNDED->value,
                    'message' => 'Reembolso procesado.',
                    'raw' => ['refund_id' => $refund->id ?? null, 'status' => $status],
                ];
            }

            return $this->failure('Mercado Pago todavía no confirma el reembolso.', 'refund_not_approved');
        } catch (Throwable $e) {
            report($e);

            return $this->failure('No se pudo procesar el reembolso. Inténtalo de nuevo.', 'refund_request_failed');
        }
    }

    private function ensureConfigured(): void
    {
        if (! $this->isAvailable()) {
            throw new PaymentGatewayNotConfiguredException(
                'Mercado Pago no está listo. Configura MP_ENABLED, MP_ACCESS_TOKEN, MP_WEBHOOK_SECRET y una URL HTTPS en MP_WEBHOOK_URL.'
            );
        }
    }

    private function requestOptions(string $idempotencyKey): RequestOptions
    {
        $options = new RequestOptions;
        $options->setAccessToken((string) config('payments.mercadopago.access_token'));
        $options->setConnectionTimeout(max(1, (int) config('payments.mercadopago.timeout', 15)) * 1000);
        $options->setMaxRetries(0);
        $options->setCustomHeaders(['X-Idempotency-Key: '.$idempotencyKey]);

        return $options;
    }

    private function failure(string $message, string $code): array
    {
        return [
            'success' => false,
            'transaction_id' => null,
            'status' => PaymentStatus::FAILED->value,
            'message' => $message,
            'raw' => ['code' => $code],
        ];
    }
}
