<?php

namespace App\Modules\Payments\Gateways\MercadoPago;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Models\Order;
use App\Models\Payment;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Payment\PaymentRefundClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Resources\Payment as MercadoPagoPayment;
use Throwable;

/**
 * Checkout API de Mercado Pago: pago con tarjeta dentro de Brevare.
 *
 * El navegador tokeniza la tarjeta con MercadoPago.js. Este servidor recibe
 * únicamente el token opaco y crea el pago con la Payments API, que es el
 * contrato habilitado para la aplicación Checkout API de Brevare.
 *
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
        $issuerId = trim((string) data_get($payment->raw_response, 'issuer_id', ''));

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

        $payload = $this->buildPaymentPayload(
            $payment,
            $order,
            $token,
            $paymentMethodId,
            $identification,
            $payerEmail,
            $issuerId,
        );

        try {
            $remote = (new PaymentClient)->create(
                $payload,
                $this->requestOptions('brevare-payment-'.$payment->id)
            );
        } catch (MPApiException $e) {
            $body = $e->getApiResponse()->getContent();
            $json = is_array($body) ? $body : json_decode((string) $body, true);
            $cause = data_get($json, 'cause.0.description')
                ?? data_get($json, 'message')
                ?? 'api_error';

            Log::warning('Mercado Pago rechazó el pago.', [
                'local_payment_id' => $payment->id,
                'http_status' => $e->getStatusCode(),
                'cause' => is_scalar($cause) ? $cause : json_encode($cause),
            ]);

            return $this->failure(
                'Mercado Pago no pudo procesar el pago. Revisa los datos de la tarjeta e inténtalo otra vez.',
                'payment_rejected'
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failure(
                'No pudimos procesar el pago con Mercado Pago. Revisa los datos de tu tarjeta e inténtalo otra vez.',
                'payment_creation_failed'
            );
        }

        return $this->resultFromPayment($remote);
    }

    /**
     * Construye el payload de la Payments API.
     *
     * El monto y la referencia salen del pedido local, nunca del navegador.
     *
     * @return array<string, mixed>
     */
    public function buildPaymentPayload(
        Payment $payment,
        Order $order,
        string $token,
        string $paymentMethodId,
        string $identification,
        string $payerEmail,
        string $issuerId = '',
    ): array {
        $amount = round((float) $payment->amount, 2);
        $payerName = trim((string) ($order->user?->name ?? data_get($order->customer_snapshot, 'name', '')));
        $nameParts = $this->payerNameParts($payerName);
        $statementDescriptor = mb_substr(
            trim((string) config('payments.mercadopago.statement_descriptor', 'BREVARE')),
            0,
            22
        );

        $payload = [
            'transaction_amount' => $amount,
            'token' => $token,
            'description' => mb_substr('Pedido Brevare '.$order->order_number, 0, 256),
            'installments' => 1,
            'payment_method_id' => $paymentMethodId,
            'payer' => array_filter([
                'email' => $payerEmail,
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'identification' => [
                    'type' => (string) config('payments.mercadopago.identification_type', 'DNI'),
                    'number' => $identification,
                ],
            ], static fn ($value) => $value !== '' && $value !== []),
            'external_reference' => (string) $order->order_number,
            'statement_descriptor' => $statementDescriptor,
            'notification_url' => (string) config('payments.mercadopago.webhook_url'),
            'metadata' => [
                'order_number' => (string) $order->order_number,
                'local_payment_id' => (int) $payment->id,
            ],
        ];

        if ($issuerId !== '') {
            $payload['issuer_id'] = $issuerId;
        }

        return $payload;
    }

    public function refund(Payment $payment, ?RefundReason $reason = null, ?float $amount = null): array
    {
        $this->ensureConfigured();
        $paymentId = trim((string) $payment->gateway_transaction_id);

        if (! ctype_digit($paymentId)) {
            return $this->failure(
                'El pago no tiene un identificador numérico reembolsable de Mercado Pago.',
                'missing_payment_id'
            );
        }

        if ($amount !== null && abs($amount - (float) $payment->amount) > 0.005) {
            return $this->failure(
                'Mercado Pago solo admite devoluciones totales en esta integración.',
                'partial_refund_unsupported'
            );
        }

        try {
            $refund = (new PaymentRefundClient)->refundTotal(
                (int) $paymentId,
                $this->requestOptions('brevare-refund-'.$payment->id)
            );
            $status = strtolower((string) ($refund->status ?? ''));
            $refundId = (string) ($refund->id ?? $paymentId);

            if (in_array($status, ['approved', 'refunded'], true)) {
                return [
                    'success' => true,
                    'transaction_id' => $refundId,
                    'status' => PaymentStatus::REFUNDED->value,
                    'message' => 'Reembolso procesado.',
                    'raw' => ['refund_id' => $refundId, 'status' => $status],
                ];
            }

            return $this->failure('Mercado Pago todavía no confirma el reembolso.', 'refund_not_approved');
        } catch (Throwable $e) {
            report($e);

            return $this->failure('No se pudo procesar el reembolso. Inténtalo de nuevo.', 'refund_request_failed');
        }
    }

    /**
     * Convierte la respuesta de la Payments API en el contrato interno.
     */
    public function resultFromPayment(MercadoPagoPayment $remote): array
    {
        $status = strtolower((string) ($remote->status ?? ''));
        $detail = strtolower((string) ($remote->status_detail ?? ''));
        $remoteId = (string) ($remote->id ?? '');

        $raw = [
            'payment_id' => $remoteId,
            'status' => $status,
            'status_detail' => $detail,
            'transaction_amount' => (string) ($remote->transaction_amount ?? ''),
            'currency_id' => (string) ($remote->currency_id ?? ''),
            'payment_method_id' => (string) ($remote->payment_method_id ?? ''),
            'payment_type_id' => (string) ($remote->payment_type_id ?? ''),
            'external_reference' => (string) ($remote->external_reference ?? ''),
        ];

        if ($status === 'approved') {
            return [
                'success' => true,
                'transaction_id' => $remoteId,
                'status' => PaymentStatus::PAID->value,
                'message' => 'Pago aprobado.',
                'raw' => array_merge($raw, ['message' => 'Pago aprobado.']),
            ];
        }

        if (in_array($status, ['pending', 'in_process', 'authorized'], true)) {
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

        if (in_array($status, ['refunded', 'charged_back'], true)) {
            return [
                'success' => false,
                'transaction_id' => $remoteId,
                'status' => PaymentStatus::FAILED->value,
                'message' => 'El pago fue reembolsado o desconocido por el emisor.',
                'raw' => array_merge($raw, [
                    'message' => 'El pago fue reembolsado o desconocido por el emisor.',
                ]),
            ];
        }

        $message = match ($detail) {
            'cc_rejected_insufficient_amount' => 'La tarjeta no tiene fondos suficientes. Prueba con otra tarjeta.',
            'cc_rejected_bad_filled_card_data',
            'cc_rejected_bad_filled_security_code',
            'cc_rejected_bad_filled_date' => 'Revisa los datos de tu tarjeta e inténtalo otra vez.',
            'cc_rejected_call_for_authorize' => 'El banco requiere que autorices el pago. Contacta a tu banco.',
            'cc_rejected_high_risk' => 'El pago fue rechazado por seguridad. Prueba con otra tarjeta.',
            default => 'No se pudo procesar el pago. Revisa los datos de tu tarjeta e inténtalo otra vez.',
        };

        return [
            'success' => false,
            'transaction_id' => $remoteId,
            'status' => PaymentStatus::FAILED->value,
            'message' => $message,
            'raw' => array_merge($raw, ['message' => $message]),
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
