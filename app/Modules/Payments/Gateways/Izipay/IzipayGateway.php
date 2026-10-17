<?php

namespace App\Modules\Payments\Gateways\Izipay;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Models\Order;
use App\Models\Payment;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use Illuminate\Support\Str;
use Throwable;

/**
 * Izipay (Mi Cuenta Web) con formulario alojado.
 *
 * Igual que Mercado Pago, no cobra en el servidor de la tienda: crea la orden
 * en la pasarela y devuelve al comprador a su formulario. La diferencia es que
 * Izipay trabaja con un formToken que el navegador entrega al SmartForm, así
 * que aquí se guarda el token y la página de pago lo consume.
 *
 * Los datos de tarjeta nunca pasan por Brevare: viajan dentro del formulario
 * de Izipay, que es quien los tokeniza.
 */
class IzipayGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'izipay';
    }

    public function isAvailable(): bool
    {
        return (bool) config('payments.izipay.enabled')
            && $this->shopId() !== ''
            && $this->restPassword() !== ''
            && $this->publicKey() !== ''
            && str_starts_with($this->endpoint(), 'https://');
    }

    public function charge(Payment $payment): array
    {
        $this->ensureConfigured();

        if ((float) $payment->amount <= 0 || strtoupper((string) $payment->currency) !== 'PEN') {
            return $this->failure('El pedido debe tener un total válido en soles.', 'invalid_amount_or_currency');
        }

        $order = $payment->order?->loadMissing(['user']);

        if ($order === null) {
            return $this->failure('No encontramos el pedido asociado al pago.', 'order_not_found');
        }

        try {
            $created = $this->createFormToken($order);
        } catch (IzipayApiException $e) {
            report($e);

            return $this->failure(
                'No pudimos iniciar el pago con Izipay. Puedes intentarlo otra vez.',
                'form_token_failed'
            );
        }

        return [
            // El cobro no está hecho todavía: el comprador paga en el
            // formulario de Izipay y el pedido se confirma por IPN o retorno.
            'success' => false,
            'transaction_id' => null,
            'source_id' => $payment->source_id ?: (string) Str::uuid(),
            'checkout_url' => route('payments.izipay.pay', ['order' => $order->order_number], true),
            'status' => PaymentStatus::PENDING->value,
            'message' => 'Continúa el pago en el formulario seguro de Izipay.',
            'raw' => [
                'order_number' => (string) $order->order_number,
                'form_token' => $created['form_token'],
                'shop_id' => $this->shopId(),
            ],
        ];
    }

    /**
     * Devoluciones: se hacen desde el back office de Izipay.
     *
     * La API REST de Izipay permite reembolsos, pero exigen el identificador
     * de la transacción y, según el contrato, autorización del comercio. Se
     * falla de forma cerrada en vez de fingir una devolución que no se hizo.
     */
    public function refund(Payment $payment, ?RefundReason $reason = null, ?float $amount = null): array
    {
        return $this->failure(
            'Las devoluciones de Izipay se gestionan desde su back office.',
            'refund_not_supported'
        );
    }

    /**
     * Pide a Izipay el formToken de un pedido.
     *
     * @return array{form_token: string, raw: array<string, mixed>}
     *
     * @throws IzipayApiException
     */
    public function createFormToken(Order $order): array
    {
        $this->ensureConfigured();

        $order->loadMissing(['user']);

        $payload = [
            // Izipay trabaja en céntimos.
            'amount' => $this->amountInCents((float) $order->total),
            'currency' => strtoupper((string) $order->currency),
            'orderId' => (string) $order->order_number,
            'customer' => array_filter([
                'email' => (string) ($order->user?->email ?? data_get($order->customer_snapshot, 'email', '')),
                'billingDetails' => array_filter([
                    'firstName' => (string) data_get($order->customer_snapshot, 'name', ''),
                    'phoneNumber' => (string) data_get($order->customer_snapshot, 'phone', ''),
                    'address' => (string) data_get($order->customer_snapshot, 'address', ''),
                    'city' => (string) data_get($order->customer_snapshot, 'city', ''),
                    'country' => (string) config('brevare.company.country', 'PE'),
                ], static fn ($value): bool => $value !== ''),
            ], static fn ($value): bool => $value !== '' && $value !== []),
        ];

        $body = IzipayClient::fromConfig()->createPayment($payload);
        $formToken = data_get($body, 'answer.formToken');

        if (! is_string($formToken) || $formToken === '') {
            throw new IzipayApiException('Izipay no devolvió un formToken.', 200, $body);
        }

        return [
            'form_token' => $formToken,
            'raw' => [
                'web_service' => 'Charge/CreatePayment',
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'shop_id' => $this->shopId(),
            ],
        ];
    }

    public function publicKey(): string
    {
        return trim((string) config('payments.izipay.public_key', ''));
    }

    public function shopId(): string
    {
        return trim((string) config('payments.izipay.shop_id', ''));
    }

    private function restPassword(): string
    {
        return trim((string) config('payments.izipay.rest_password', ''));
    }

    private function endpoint(): string
    {
        return rtrim((string) config('payments.izipay.endpoint', 'https://api.micuentaweb.pe'), '/');
    }

    private function amountInCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function ensureConfigured(): void
    {
        if (! $this->isAvailable()) {
            throw new PaymentGatewayNotConfiguredException(
                'Izipay no está listo. Configura IZIPAY_SHOP_ID, IZIPAY_REST_PASSWORD e IZIPAY_PUBLIC_KEY.'
            );
        }
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
