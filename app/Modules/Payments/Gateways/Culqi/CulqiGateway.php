<?php

namespace App\Modules\Payments\Gateways\Culqi;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Models\Order;
use App\Models\Payment;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\CulqiApiException;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Pasarela Culqi (ecosistema BCP).
 *
 * El checkout se resuelve en el modal de Checkout Custom de Culqi, donde el
 * propio Culqi muestra sus métodos de pago. Como los métodos asíncronos
 * (Yape, billetera, banca móvil, agente, Cuotéalo) no funcionan sin una orden
 * previa, el pedido y su ord_ se crean antes de abrir el modal.
 *
 * Tarjeta: el modal tokeniza dentro del iframe con alcance PCI y devuelve un
 * tkn_ opaco. Aquí se crea el cargo contra api.culqi.com, que responde de
 * forma síncrona.
 *
 * Yape y demás asíncronos: la orden ya está creada, así que el pago queda
 * pendiente y se confirma únicamente cuando llega el webhook.
 *
 * IMPORTANTE: Culqi no ofrece idempotency key. Si createCharge se responde con
 * error de red no sabemos si el cargo se creó. Por eso, si el pago ya tiene un
 * gateway_transaction_id, nunca se vuelve a cobrar: primero se consulta el
 * cargo existente.
 *
 * Formato verificado contra la API real (modo test): los objetos vienen en la
 * raíz, sin envoltorio `data`, y un cargo no trae campo `status`. La
 * interpretación del resultado vive en CulqiStatus.
 */
class CulqiGateway implements PaymentGateway
{
    public function __construct(
        protected CulqiClient $client,
    ) {}

    public function name(): string
    {
        return 'culqi';
    }

    public function charge(Payment $payment): array
    {
        if ((float) $payment->amount <= 0) {
            return $this->failure('El monto del pago debe ser mayor a cero.', 'invalid_amount');
        }

        if (! in_array($payment->method, (array) config('payments.gateway_methods.' . $this->name(), []), true)) {
            return $this->failure(
                "Culqi no admite el método de pago [{$payment->method}].",
                'unsupported_method'
            );
        }

        return $payment->method === 'yape'
            ? $this->chargeYape($payment)
            : $this->chargeCard($payment);
    }

    public function refund(Payment $payment, ?RefundReason $reason = null): array
    {
        $chargeId = $payment->gateway_transaction_id;

        if (! is_string($chargeId) || ! str_starts_with($chargeId, 'chr_')) {
            return $this->failure('El pago no tiene un cargo de Culqi reembolsable.', 'no_charge_id');
        }

        try {
            $refund = $this->client->refund(
                $chargeId,
                $this->toCents($payment->amount),
                $reason ?? RefundReason::SolicitudComprador,
            );
        } catch (PaymentGatewayNotConfiguredException $e) {
            // Un problema de configuración no es un fallo de pago: debe verse.
            throw $e;
        } catch (CulqiApiException $e) {
            report($e);

            return $this->failure(
                $e->userMessage() ?? 'No se pudo procesar el reembolso. Inténtalo de nuevo.',
                'refund_rejected'
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failure('No se pudo procesar el reembolso. Inténtalo de nuevo.', 'refund_request_failed');
        }

        $status = strtolower((string) ($refund['status'] ?? ''));
        $refundId = (string) ($refund['id'] ?? $chargeId);

        // Verificado contra la API real (modo test): el refund trae
        // `status: "completa"` (o "pendiente"), no los valores en inglés que
        // asumíamos. Un estado desconocido no se marca reembolsado.
        return match ($status) {
            'completa', 'completado', 'completed', 'refunded' => [
                'success' => true,
                'transaction_id' => $refundId,
                'status' => PaymentStatus::REFUNDED->value,
                'message' => 'Reembolso procesado.',
                'raw' => $refund,
            ],

            'pendiente', 'pending' => [
                'success' => true,
                'transaction_id' => $refundId,
                'status' => PaymentStatus::PENDING->value,
                'message' => 'El reembolso está en proceso.',
                'raw' => $refund,
            ],

            default => [
                'success' => false,
                'transaction_id' => $refundId,
                'status' => PaymentStatus::FAILED->value,
                'message' => 'Culqi no confirmó el reembolso (estado: '
                    . ($status !== '' ? $status : 'desconocido') . ').',
                'raw' => $refund,
            ],
        };
    }

    /**
     * Consulta un cargo existente. Útil para conciliar cuando un request quedó a
     * medias: Culqi no tiene idempotency key, así que la única forma de saber
     * si el cargo se creó es consultarlo.
     *
     * @return array<string, mixed>|null
     */
    public function verifyCharge(string $chargeId): ?array
    {
        try {
            return $this->client->getCharge($chargeId);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Consulta una orden de Yape.
     *
     * @return array<string, mixed>|null
     */
    public function verifyOrder(string $orderId): ?array
    {
        try {
            return $this->client->getOrder($orderId);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function chargeCard(Payment $payment): array
    {
        $token = $payment->source_id;

        if (! is_string($token) || ! str_starts_with($token, 'tkn_')) {
            return $this->failure('No se recibió un token de tarjeta válido.', 'missing_card_token');
        }

        // Sin idempotency key: si ya hay un cargo, consultamos en vez de cobrar de nuevo.
        $existing = $payment->gateway_transaction_id;

        if (is_string($existing) && str_starts_with($existing, 'chr_')) {
            $charge = $this->verifyCharge($existing);

            return $charge === null
                ? $this->failure('No se pudo verificar el cobro anterior.', 'charge_not_found')
                : $this->mapCharge($charge);
        }

        $order = $payment->order;

        try {
            $charge = $this->client->createCharge([
                'amount' => $this->toCents($payment->amount),
                'currency_code' => $payment->currency ?: 'PEN',
                'email' => $order?->user?->email ?? data_get($order?->customer_snapshot, 'email', ''),
                'source_id' => $token,
                'capture' => (bool) config('payments.culqi.capture', true),
                'antifraud_details' => $this->antifraudDetails($order),
            ]);
        } catch (PaymentGatewayNotConfiguredException $e) {
            throw $e;
        } catch (CulqiApiException $e) {
            report($e);

            return $this->failure(
                $e->userMessage() ?? 'No pudimos procesar el pago. Revisa los datos e inténtalo de nuevo.',
                'charge_rejected'
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failure('No pudimos procesar el pago. Inténtalo de nuevo.', 'charge_request_failed');
        }

        return $this->mapCharge($charge);
    }

    private function chargeYape(Payment $payment): array
    {
        $token = $payment->source_id;

        if (! is_string($token) || ! str_starts_with($token, 'ype_')) {
            return $this->failure(
                'No se recibió un token de Yape válido.',
                'missing_yape_token'
            );
        }

        // Sin idempotency key: si ya existe un cargo, consultamos
        // en vez de crear otro.
        $existing = $payment->gateway_transaction_id;

        if (is_string($existing) && str_starts_with($existing, 'chr_')) {
            $charge = $this->verifyCharge($existing);

            return $charge === null
                ? $this->failure(
                    'No se pudo verificar el cobro anterior.',
                    'charge_not_found'
                )
                : $this->mapCharge($charge);
        }

        $order = $payment->order;

        try {
            $charge = $this->client->createCharge([
                'amount' => $this->toCents($payment->amount),
                'currency_code' => $payment->currency ?: 'PEN',
                'email' => $order?->user?->email
                    ?? data_get($order?->customer_snapshot, 'email', ''),
                'source_id' => $token,
                'capture' => (bool) config('payments.culqi.capture', true),
                'antifraud_details' => $this->antifraudDetails($order),
            ]);
        } catch (PaymentGatewayNotConfiguredException $e) {
            throw $e;
        } catch (CulqiApiException $e) {
            report($e);

            return $this->failure(
                $e->userMessage()
                    ?? 'No pudimos procesar el pago con Yape. Inténtalo de nuevo.',
                'charge_rejected'
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failure(
                'No pudimos procesar el pago con Yape. Inténtalo de nuevo.',
                'charge_request_failed'
            );
        }
        Log::info('[Culqi] RESPUESTA CARGO YAPE', [
            'charge' => $charge,
        ]);
        return $this->mapCharge($charge);
    }

    /**
     * Crea en Culqi la orden (ord_) que habilita los métodos de pago asíncronos.
     *
     * La usan dos caminos distintos:
     *
     *  - El modal de Checkout Custom, que rechaza cualquier método que no sea
     *    tarjeta si no se le pasa un `settings.order` ya creado. Por eso el
     *    pedido y esta orden existen antes de abrir el modal.
     *  - El flujo clásico de Yape, que en vez de pintar el modal redirige a
     *    `url_pe` y espera el webhook.
     *
     * La tarjeta no pasa por aquí: Culqi la cobra con un cargo (chr_...).
     *
     * @param  list<string>  $methods  Tipos de método que aceptará la orden.
     * @return array{id: ?string, checkout_url: ?string, raw: array<string, mixed>}
     *
     * @throws PaymentGatewayNotConfiguredException falta una credencial.
     * @throws CulqiApiException Culqi rechazó la orden.
     * @throws RuntimeException faltan datos del titular.
     */
    public function createPaymentOrder(Order $order, array $methods): array
    {
        $clientDetails = $this->clientDetails($order);

        // Culqi rechaza la orden si client_details.phone_number no tiene entre
        // 6 y 14 caracteres. Es mejor avisar aquí que devolver un 400 opaco.
        if ($clientDetails === null) {
            throw new RuntimeException(
                'Para pagar con este método necesitamos un número de celular válido en tu dirección de entrega.'
            );
        }

        $minutes = (int) config('payments.culqi.yape_expiration_minutes', 30);

        $payload = [
            'amount' => $this->toCents($order->total),
            'currency_code' => $order->currency ?: 'PEN',
            'order_number' => (string) $order->order_number,
            'description' => 'Pago del pedido ' . $order->order_number,
            'client_details' => $clientDetails,
            'expiration_date' => $this->expirationDate($minutes),
            'payment_methods' => array_map(
                static fn(string $type): array => ['type' => $type],
                array_values($methods)
            ),
        ];

        \Log::info('[Culqi] Creating order with payload', $payload);

        try {
            $created = $this->client->createOrder($payload);
        } catch (\Exception $e) {
            $responseBody = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
            }
            \Log::error('[Culqi] createOrder failed', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'response' => $responseBody,
            ]);
            throw $e;
        }

        \Log::info('[Culqi] Full order response', $created);
        \Log::info('[Culqi] Order created successfully', ['id' => $created['id'] ?? null, 'url_pe' => $created['url_pe'] ?? null]);

        $orderId = (string) ($created['id'] ?? '');

        // Verificado contra la API real: la orden trae `url_pe` (página de pago)
        // y `qr`. `payment_url` no existe en este endpoint.
        $checkoutUrl = (string) ($created['url_pe'] ?? $created['payment_url'] ?? '');

        return [
            'id' => str_starts_with($orderId, 'ord_') ? $orderId : null,
            'checkout_url' => $checkoutUrl !== '' ? $checkoutUrl : null,
            'raw' => $created,
        ];
    }

    /**
     * Vigencia de la orden en epoch segundos.
     *
     * Culqi la exige en el futuro y con un mínimo de unos minutos: 60 s
     * devuelve 400 parameter_error, 300 s sí se acepta.
     */
    private function expirationDate(int $minutes): int
    {
        $minutes = max(5, $minutes);

        return time() + ($minutes * 60);
    }

    /**
     * Datos del titular para el antifraude de Culqi.
     *
     * Devuelve null si falta el celular: Culqi exige phone_number con 6-14
     * caracteres y responde 400 parameter_error si no llega.
     *
     * @return array<string, string>|null
     */
    private function clientDetails(?object $order): ?array
    {
        $address = (array) ($order->address_snapshot ?? []);
        $name = trim((string) data_get($order?->customer_snapshot, 'name')
            ?: data_get($address, 'full_name', ''));
        $parts = preg_split('/\s+/', $name, 2) ?: ['', ''];
        $phone = preg_replace('/\D+/', '', (string) data_get($address, 'phone', '')) ?? '';

        \Log::info('[Culqi] clientDetails input', [
            'name' => $name,
            'phone_raw' => data_get($address, 'phone', ''),
            'phone_clean' => $phone,
            'email' => $order?->user?->email ?? data_get($order?->customer_snapshot, 'email', ''),
            'address_snapshot' => $address,
        ]);

        if (strlen($phone) < 6 || strlen($phone) > 14) {
            \Log::warning('[Culqi] Invalid phone number', ['phone' => $phone, 'length' => strlen($phone)]);
            return null;
        }

        $details = array_filter([
            'email' => (string) ($order?->user?->email ?? data_get($order?->customer_snapshot, 'email', '')),
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'phone_number' => $phone,
        ], fn($value) => $value !== '');

        \Log::info('[Culqi] clientDetails output', $details);

        return $details === [] ? null : $details;
    }

    /**
     * Traduce la respuesta de Culqi al contrato interno.
     *
     * @param  array<string, mixed>  $charge
     * @return array<string, mixed>
     */
    private function mapCharge(array $charge): array
    {
        $chargeId = (string) ($charge['id'] ?? '');
        $transactionId = $chargeId !== '' ? $chargeId : null;

        return match (CulqiStatus::resolve($charge)) {
            CulqiStatus::PAID => [
                'success' => true,
                'transaction_id' => $transactionId,
                'status' => PaymentStatus::PAID->value,
                'message' => 'Pago aprobado.',
                'raw' => $charge,
            ],

            CulqiStatus::PENDING => [
                'success' => false,
                'transaction_id' => $transactionId,
                'status' => PaymentStatus::PENDING->value,
                'message' => 'El pago está siendo procesado.',
                'raw' => $charge,
            ],

            // Respuesta que no entendemos: no se marca pagado ni fallido de
            // forma automática. Queda pendiente para conciliar con Culqi.
            CulqiStatus::UNKNOWN => $this->unknownOutcome($transactionId, $charge),

            default => $this->declined($transactionId, $charge),
        };
    }

    /**
     * Cargo rechazado por Culqi.
     *
     * El user_message de Culqi a veces solo dice "contactate con soporte", que
     * no le sirve de nada al comprador porque el problema no es suyo. Cuando
     * pasa eso mostramos un texto accionable y dejamos el detalle real de la
     * pasarela en el log, que es donde le sirve al comercio.
     *
     * @param  array<string, mixed>  $charge
     * @return array<string, mixed>
     */
    private function declined(?string $chargeId, array $charge): array
    {
        $userMessage = CulqiStatus::userMessage($charge);
        $merchantMessage = CulqiStatus::merchantMessage($charge);

        if ($userMessage === null && $merchantMessage !== null) {
            Log::warning('Culqi rechazo el cargo con un mensaje no accionable para el cliente.', [
                'charge_id' => $chargeId,
                'outcome' => $charge['outcome'] ?? null,
                'merchant_message' => $merchantMessage,
            ]);

            $userMessage = 'No pudimos procesar tu tarjeta. Verifica los datos e inténtalo de nuevo, '
                . 'o prueba con otra tarjeta.';
        }

        return [
            'success' => false,
            'transaction_id' => $chargeId,
            'status' => PaymentStatus::FAILED->value,
            'message' => $userMessage
                ?? 'No pudimos completar el pago. Inténtalo con otro medio de pago.',
            'raw' => $charge,
        ];
    }

    /**
     * @param  array<string, mixed>  $charge
     * @return array<string, mixed>
     */
    private function unknownOutcome(?string $chargeId, array $charge): array
    {
        Log::warning('Culqi devolvio un resultado no interpretable.', [
            'charge_id' => $chargeId,
            'outcome' => $charge['outcome'] ?? null,
            'merchant_message' => CulqiStatus::merchantMessage($charge),
        ]);

        return [
            'success' => false,
            'transaction_id' => $chargeId,
            'status' => PaymentStatus::PENDING->value,
            'message' => 'Estamos verificando tu pago. En unos minutos te confirmamos.',
            'raw' => $charge,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function antifraudDetails(?object $order): array
    {
        $address = $order->address_snapshot ?? [];
        $name = (string) data_get($order?->customer_snapshot, 'name', '');
        $parts = preg_split('/\s+/', trim($name), 2) ?: ['', ''];

        return array_filter([
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'address' => (string) data_get($address, 'address', ''),
            'city' => 'Lima',
            'country_code' => 'PE',
            'phone_number' => (string) data_get($address, 'phone', ''),
        ], fn($value) => $value !== '');
    }

    /**
     * Culqi trabaja con enteros en céntimos: 10000 = S/ 100.00.
     */
    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * @return array{success: false, transaction_id: null, status: string, message: string, raw: array}
     */
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
