<?php

namespace App\Modules\Payments\Gateways\MercadoPago;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Models\Order;
use App\Models\Payment;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Order\OrderClient;
use MercadoPago\Client\Payment\PaymentRefundClient;
use MercadoPago\Resources\Order as MercadoPagoOrder;
use Throwable;

/**
 * Checkout API de Mercado Pago: pago con tarjeta dentro de Brevare.
 *
 * El navegador tokeniza la tarjeta con MercadoPago.js. Este servidor recibe
 * únicamente el token opaco y crea una order en la Orders API (`POST /v1/orders`).
 * Los datos completos de la tarjeta nunca pasan por Laravel.
 */
class MercadoPagoGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'mercadopago';
    }

    public function isAvailable(): bool
    {
        $publicKey = trim((string) config('payments.mercadopago.public_key'));
        $url = trim((string) config('payments.mercadopago.webhook_url'));

        return $this->hasApiCredentials()
            && $publicKey !== ''
            && str_starts_with($url, 'https://');
    }

    /**
     * Credenciales mínimas para consultar y confirmar pagos en el servidor.
     * La Public Key solo hace falta para abrir el formulario del navegador.
     */
    public function hasApiCredentials(): bool
    {
        return (bool) config('payments.mercadopago.enabled')
            && trim((string) config('payments.mercadopago.access_token')) !== ''
            && trim((string) config('payments.mercadopago.webhook_secret')) !== '';
    }

    public function charge(Payment $payment): array
    {
        $this->ensureConfigured();

        if ((float) $payment->amount <= 0 || strtoupper((string) $payment->currency) !== 'PEN') {
            return $this->failure('El pedido debe tener un total válido en soles.', 'invalid_amount_or_currency');
        }

        $order = $payment->order?->loadMissing(['items', 'user']);

        if ($order === null) {
            return $this->failure('No encontramos el pedido asociado al pago.', 'order_not_found');
        }

        $token = trim((string) $payment->source_id);
        $paymentMethodId = trim((string) data_get($payment->raw_response, 'payment_method_id', ''));
        $identification = preg_replace('/\D+/', '', (string) data_get($payment->raw_response, 'identification_number', ''));

        if ($token === '' || $paymentMethodId === '') {
            return $this->failure(
                'No recibimos los datos tokenizados de la tarjeta. Vuelve a intentarlo.',
                'missing_card_token'
            );
        }

        if (! is_string($identification) || strlen($identification) < 8 || strlen($identification) > 12) {
            return $this->failure(
                'Ingresa un documento de identidad válido para completar el pago.',
                'invalid_identification'
            );
        }

        $payerEmail = trim((string) ($order->user?->email ?? data_get($order->customer_snapshot, 'email', '')));

        if ($payerEmail === '' || filter_var($payerEmail, FILTER_VALIDATE_EMAIL) === false) {
            return $this->failure(
                'El pedido no tiene un correo válido para procesar el pago.',
                'missing_payer_email'
            );
        }

        $payload = $this->buildOrderPayload($payment, $order, $token, $paymentMethodId, $identification, $payerEmail);

        try {
            $remote = (new OrderClient)->create(
                $payload,
                $this->requestOptions('brevare-order-payment-'.$payment->id)
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failure(
                'No pudimos procesar el pago con Mercado Pago. Revisa los datos de tu tarjeta e inténtalo otra vez.',
                'order_creation_failed'
            );
        }

        return $this->resultFromOrder($remote);
    }

    /**
     * Construye el payload oficial de la Orders API.
     *
     * El monto y la referencia salen del pedido local, nunca del navegador.
     *
     * @return array<string, mixed>
     */
    public function buildOrderPayload(
        Payment $payment,
        Order $order,
        string $token,
        string $paymentMethodId,
        string $identification,
        string $payerEmail,
    ): array {
        $amount = number_format((float) $payment->amount, 2, '.', '');
        $payerName = trim((string) ($order->user?->name ?? data_get($order->customer_snapshot, 'name', '')));
        $nameParts = $this->payerNameParts($payerName);
        $statementDescriptor = mb_substr(
            trim((string) config('payments.mercadopago.statement_descriptor', 'BREVARE')),
            0,
            22
        );

        return [
            'type' => 'online',
            'processing_mode' => 'automatic',
            'total_amount' => $amount,
            'external_reference' => (string) $order->order_number,
            'payer' => array_filter([
                'email' => $payerEmail,
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'identification' => [
                    'type' => (string) config('payments.mercadopago.identification_type', 'DNI'),
                    'number' => $identification,
                ],
            ], static fn ($value) => $value !== '' && $value !== []),
            'items' => [[
                'id' => (string) $order->order_number,
                'title' => mb_substr('Pedido Brevare '.$order->order_number, 0, 250),
                'description' => mb_substr('Compra en Brevare', 0, 250),
                'quantity' => 1,
                'unit_price' => $amount,
            ]],
            'transactions' => [
                'payments' => [[
                    'amount' => $amount,
                    'payment_method' => [
                        'id' => $paymentMethodId,
                        'type' => $this->paymentMethodType($paymentMethodId),
                        'token' => $token,
                        'installments' => 1,
                        'statement_descriptor' => $statementDescriptor,
                    ],
                ]],
            ],
        ];
    }

    public function refund(Payment $payment, ?RefundReason $reason = null, ?float $amount = null): array
    {
        $this->ensureConfigured();
        $transactionId = trim((string) $payment->gateway_transaction_id);

        if ($transactionId === '') {
            return $this->failure('El pago no tiene un identificador reembolsable de Mercado Pago.', 'missing_payment_id');
        }

        if ($amount !== null && abs($amount - (float) $payment->amount) > 0.005) {
            return $this->failure(
                'Mercado Pago solo admite devoluciones totales en esta integración.',
                'partial_refund_unsupported'
            );
        }

        try {
            $legacyPaymentId = ctype_digit($transactionId);

            // Los pagos nuevos usan el ID de la order. Se conserva el camino
            // anterior para pagos históricos de Checkout Pro, cuyo ID es numérico.
            if ($legacyPaymentId) {
                $refund = (new PaymentRefundClient)->refundTotal(
                    (int) $transactionId,
                    $this->requestOptions('brevare-refund-'.$payment->id)
                );
                $status = strtolower((string) ($refund->status ?? ''));
                $refundId = (string) ($refund->id ?? $transactionId);
                $refundSucceeded = in_array($status, ['approved', 'refunded'], true);
            } else {
                $refund = (new OrderClient)->refund(
                    $transactionId,
                    null,
                    $this->requestOptions('brevare-refund-'.$payment->id)
                );
                $status = strtolower((string) ($refund->status ?? ''));
                $detail = strtolower((string) ($refund->status_detail ?? ''));
                $refundId = (string) (data_get($refund, 'transactions.refunds.0.id') ?? $transactionId);

                if ($status === 'processed' && $detail === 'refunded') {
                    $status = 'refunded';
                }

                $refundSucceeded = $status === 'refunded'
                    || ($status === 'processed' && in_array($detail, ['refunded', 'partially_refunded'], true));
            }

            if ($refundSucceeded) {
                return [
                    'success' => true,
                    'transaction_id' => $refundId,
                    'status' => PaymentStatus::REFUNDED->value,
                    'message' => 'Reembolso procesado.',
                    'raw' => [
                        'refund_id' => $refundId,
                        'status' => $status,
                        'order_id' => $legacyPaymentId ? null : $transactionId,
                    ],
                ];
            }

            return $this->failure('Mercado Pago todavía no confirma el reembolso.', 'refund_not_approved');
        } catch (Throwable $e) {
            report($e);

            return $this->failure('No se pudo procesar el reembolso. Inténtalo de nuevo.', 'refund_request_failed');
        }
    }

    /**
     * Convierte la respuesta de la Orders API en el contrato interno.
     */
    public function resultFromOrder(MercadoPagoOrder $remote): array
    {
        $status = strtolower((string) ($remote->status ?? ''));
        $detail = strtolower((string) ($remote->status_detail ?? ''));
        $remoteId = (string) ($remote->id ?? '');
        $remotePayment = data_get($remote, 'transactions.payments.0');
        $paymentId = (string) data_get($remotePayment, 'id', '');

        $raw = [
            'order_id' => $remoteId,
            'order_status' => $status,
            'order_status_detail' => $detail,
            'payment_id' => $paymentId,
            'payment_status' => (string) data_get($remotePayment, 'status', ''),
            'payment_status_detail' => (string) data_get($remotePayment, 'status_detail', ''),
            'payment_method_id' => (string) data_get($remotePayment, 'payment_method.id', ''),
            'transaction_amount' => (string) ($remote->total_amount ?? ''),
            'currency' => (string) ($remote->currency ?? 'PEN'),
        ];

        if ($status === 'processed' && $detail === 'accredited') {
            return [
                'success' => true,
                'transaction_id' => $remoteId,
                'status' => PaymentStatus::PAID->value,
                'message' => 'Pago aprobado.',
                'raw' => array_merge($raw, ['message' => 'Pago aprobado.']),
            ];
        }

        if (in_array($status, ['created', 'processing', 'action_required', 'in_review'], true)) {
            return [
                'success' => false,
                'transaction_id' => $remoteId,
                'status' => PaymentStatus::PENDING->value,
                'message' => 'Tu pago está en proceso. Te avisaremos cuando se confirme.',
                'raw' => array_merge($raw, [
                    'message' => 'Tu pago está en proceso. Te avisaremos cuando se confirme.',
                ]),
            ];
        }

        if ($status === 'refunded' || ($status === 'processed' && in_array($detail, ['refunded', 'partially_refunded'], true))) {
            return [
                'success' => false,
                'transaction_id' => $remoteId,
                'status' => PaymentStatus::FAILED->value,
                'message' => 'El pago fue reembolsado y el pedido no se puede confirmar.',
                'raw' => array_merge($raw, [
                    'message' => 'El pago fue reembolsado y el pedido no se puede confirmar.',
                ]),
            ];
        }

        return [
            'success' => false,
            'transaction_id' => $remoteId,
            'status' => PaymentStatus::FAILED->value,
            'message' => 'No se pudo procesar el pago. Revisa los datos de tu tarjeta e inténtalo otra vez.',
            'raw' => array_merge($raw, [
                'message' => 'No se pudo procesar el pago. Revisa los datos de tu tarjeta e inténtalo otra vez.',
            ]),
        ];
    }

    private function ensureConfigured(): void
    {
        if (! $this->isAvailable()) {
            throw new PaymentGatewayNotConfiguredException(
                'Mercado Pago no está listo. Configura MP_ENABLED, MP_ACCESS_TOKEN, MP_PUBLIC_KEY, '
                    .'MP_WEBHOOK_SECRET y una URL HTTPS en MP_WEBHOOK_URL.'
            );
        }
    }

    private function requestOptions(?string $idempotencyKey = null): RequestOptions
    {
        $options = new RequestOptions;
        $options->setAccessToken((string) config('payments.mercadopago.access_token'));
        $options->setConnectionTimeout(max(1, (int) config('payments.mercadopago.timeout', 15)) * 1000);
        $options->setMaxRetries(0);

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $options->setCustomHeaders(['X-Idempotency-Key: '.$idempotencyKey]);
        }

        return $options;
    }

    private function paymentMethodType(string $paymentMethodId): string
    {
        return str_starts_with(strtolower($paymentMethodId), 'deb') ? 'debit_card' : 'credit_card';
    }

    /**
     * @return array{first_name: string, last_name: string}
     */
    private function payerNameParts(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');

        if ($name === '') {
            return ['first_name' => '', 'last_name' => ''];
        }

        $parts = explode(' ', $name, 2);

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '',
        ];
    }

    private function failure(string $message, string $code): array
    {
        return [
            'success' => false,
            'transaction_id' => null,
            'status' => PaymentStatus::FAILED->value,
            'message' => $message,
            'raw' => ['code' => $code, 'message' => $message],
        ];
    }
}
