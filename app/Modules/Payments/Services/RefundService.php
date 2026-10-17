<?php

namespace App\Modules\Payments\Services;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use App\Modules\Payments\Gateways\Culqi\CulqiGateway;
use App\Modules\Payments\Gateways\Culqi\CulqiStatus;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Devoluciones de dinero en dos pasos.
 *
 * Brevare no devuelve dinero por sí solo: primero se registra una solicitud
 * (pending) y un administrador la valida en el panel. Solo en ese momento se
 * envía a la pasarela. Separar solicitud y ejecución es lo que permite que
 * Culqi reciba una devolución revisada y deja constancia de quién la autorizó.
 *
 * Culqi no ofrece idempotency key para POST /v2/refunds, así que la guarda
 * contra dobles envíos es local: la solicitud se reclama con un UPDATE
 * condicional antes de salir a la red.
 */
class RefundService
{
    /**
     * Códigos con los que la pasarela rechaza la devolución ANTES de crearla
     * (verificación del cargo, del monto o de la configuración). En esos casos
     * no existe nada en Culqi, así que la solicitud puede volver a `pending`.
     *
     * Cualquier otro fallo se queda en `failed`: si la petición ya salió, no se
     * puede descartar que Culqi haya registrado la devolución.
     */
    private const PRE_FLIGHT_FAILURES = [
        'no_charge_id',
        'charge_not_found',
        'charge_not_paid',
        'refund_exceeds_charge',
        'invalid_amount',
        'partial_refund_unsupported',
        'gateway_not_configured',
    ];

    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    /**
     * Registra una solicitud de devolución. No toca la pasarela.
     */
    public function request(
        Order $order,
        RefundReason $reason,
        ?float $amount = null,
        ?string $notes = null,
        ?int $requestedBy = null,
    ): Refund {
        $payment = $this->refundablePayment($order);
        $available = $this->refundableAmount($payment);
        $amount = round($amount ?? (float) $payment->amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('El monto a devolver debe ser mayor a cero.');
        }

        if ($amount > $available) {
            throw new InvalidArgumentException(
                'El monto supera el saldo reembolsable del pago (S/ '.number_format($available, 2).').'
            );
        }

        return Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'requested_by' => $requestedBy,
            'amount' => $amount,
            'currency' => $payment->currency ?: ($order->currency ?: 'PEN'),
            'reason' => $reason,
            'status' => RefundStatus::PENDING,
            'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            'requested_at' => now(),
        ]);
    }

    /**
     * Aprueba la solicitud y ejecuta la devolución en la pasarela.
     *
     * El retorno del endpoint es un resultado, no un lanzamiento: un fallo de
     * la pasarela deja la devolución en `failed` con el mensaje a la vista.
     * Solo un problema de configuración se propaga, porque no es un fallo de
     * la devolución sino del entorno.
     */
    public function approve(Refund $refund, ?int $validatedBy = null): Refund
    {
        // Reclamo atómico: si otro administrador ya la validó, este UPDATE
        // afecta 0 filas y no se envía una segunda devolución a Culqi.
        $claimed = Refund::query()
            ->whereKey($refund->id)
            ->where('status', RefundStatus::PENDING->value)
            ->update([
                'status' => RefundStatus::PROCESSING->value,
                'validated_by' => $validatedBy,
                'validated_at' => now(),
            ]);

        $refund->refresh();

        if ($claimed === 0) {
            return $refund;
        }

        $payment = $refund->payment;

        if ($payment === null) {
            return $this->settle(
                $refund,
                RefundStatus::FAILED,
                null,
                null,
                'El pago asociado ya no existe; la devolución no puede ejecutarse.'
            );
        }

        $available = $this->availableFor($payment, $refund);

        if ((float) $refund->amount > $available + 0.005) {
            return $this->settle(
                $refund,
                RefundStatus::FAILED,
                null,
                null,
                'El saldo reembolsable del pago cambió (disponible S/ '.number_format($available, 2).').'
            );
        }

        try {
            $gateway = $this->paymentService->gatewayFor((string) $payment->gateway);
        } catch (PaymentGatewayNotConfiguredException $e) {
            // No es un fallo de la devolución: se libera la solicitud para
            // poder reintentarla cuando la pasarela esté configurada.
            $this->releaseForRetry($refund, null, 'gateway_not_configured');

            throw $e;
        }

        $result = $gateway->refund($payment, $refund->reason, (float) $refund->amount);

        $message = (string) ($result['message'] ?? '');
        $code = is_string($result['raw']['code'] ?? null) ? $result['raw']['code'] : null;
        $refundId = is_string($result['transaction_id'] ?? null) ? $result['transaction_id'] : null;
        $raw = is_array($result['raw'] ?? null) ? $result['raw'] : null;

        if (! ($result['success'] ?? false)) {
            // Un fallo de verificación previa no creó nada en Culqi: la
            // solicitud vuelve a quedar pendiente para reintentarla (por
            // ejemplo, después de vincular el cargo del pago).
            if ($code !== null && in_array($code, self::PRE_FLIGHT_FAILURES, true)) {
                return $this->releaseForRetry($refund, $message, $code);
            }

            return $this->settle($refund, RefundStatus::FAILED, $refundId, $raw, $message);
        }

        if (($result['status'] ?? null) === PaymentStatus::REFUNDED->value) {
            return $this->complete($refund, $refundId, $raw);
        }

        // La pasarela aceptó la devolución pero la deja en trámite.
        return $this->settle($refund, RefundStatus::PROCESSING, $refundId, $raw, $message);
    }

    /**
     * Vincula el cargo de Culqi a un pago cobrado con una orden (`ord_...`).
     *
     * El spec de Culqi no documenta cómo obtener el cargo de una orden: el
     * único camino documentado es consultar el cargo por su ID
     * (GET /v2/charges/{id}), así que el identificador se toma del panel de
     * Culqi y aquí se valida contra la API antes de guardarlo.
     */
    public function linkCharge(Payment $payment, string $chargeId): Payment
    {
        $chargeId = trim($chargeId);

        if (! str_starts_with($chargeId, 'chr_')) {
            throw new InvalidArgumentException(
                'El identificador del cargo de Culqi debe empezar con "chr_".'
            );
        }

        $gateway = $this->paymentService->gatewayFor((string) $payment->gateway);

        if (! $gateway instanceof CulqiGateway) {
            throw new InvalidArgumentException('Vincular un cargo solo aplica a pagos de Culqi.');
        }

        $charge = $gateway->verifyCharge($chargeId);

        if ($charge === null) {
            throw new InvalidArgumentException(
                'Culqi no reconoce el cargo '.$chargeId.' con esta llave. Verifica la cuenta y '
                    .'el entorno (test o producción).'
            );
        }

        if (CulqiStatus::resolve($charge) !== CulqiStatus::PAID) {
            throw new InvalidArgumentException(
                'El cargo '.$chargeId.' no figura cobrado en Culqi.'
            );
        }

        $chargeCents = is_numeric($charge['amount'] ?? null) ? (int) $charge['amount'] : null;

        if ($chargeCents !== null && $chargeCents < (int) round(((float) $payment->amount) * 100)) {
            throw new InvalidArgumentException(
                'El cargo '.$chargeId.' es por un monto menor al del pago; verifica que sea el cargo correcto.'
            );
        }

        $payment->update(['gateway_charge_id' => $chargeId]);

        return $payment->refresh();
    }

    /**
     * Consulta en Culqi el estado real de una devolución ya enviada
     * (GET /v2/refunds/{id}) y ajusta el estado local.
     */
    public function refreshWithGateway(Refund $refund): Refund
    {
        $refundId = (string) $refund->gateway_refund_id;

        if ($refundId === '') {
            throw new InvalidArgumentException(
                'La devolución todavía no tiene un identificador en la pasarela.'
            );
        }

        $remote = $this->culqiGatewayFor($refund)?->verifyRefund($refundId);

        if ($remote === null) {
            throw new InvalidArgumentException(
                'Culqi no devolvió la devolución '.$refundId.'. Revisa el entorno de la llave.'
            );
        }

        return $this->applyGatewayStatus($refund, $remote);
    }

    /**
     * Aplica el estado que Culqi reporta para una devolución.
     *
     * Lo usa la validación en el panel, el webhook y la consulta manual: así la
     * respuesta de Culqi se interpreta en un solo lugar.
     *
     * @param  array<string, mixed>  $remote
     */
    public function applyGatewayStatus(Refund $refund, array $remote): Refund
    {
        $refundId = is_string($remote['id'] ?? null) ? $remote['id'] : $refund->gateway_refund_id;

        return match (CulqiStatus::resolveRefund($remote)) {
            CulqiStatus::PAID => $this->complete($refund, $refundId, $remote),

            CulqiStatus::PENDING => $this->settle(
                $refund,
                RefundStatus::PROCESSING,
                $refundId,
                $remote,
                null
            ),

            CulqiStatus::FAILED => $this->settle(
                $refund,
                RefundStatus::FAILED,
                $refundId,
                $remote,
                'Culqi reporta la devolución como rechazada.'
            ),

            // Estado no reconocido: no se da por bueno ni se toca el pago.
            default => $refund,
        };
    }

    /**
     * Devuelve la solicitud a `pending` porque no llegó a crearse en Culqi.
     *
     * Se limpia la validación anterior: la decisión del administrador no se
     * ejecutó, así que debe volver a aprobarse (y el saldo sigue reservado por
     * estar en un estado que no libera fondos).
     */
    public function releaseForRetry(Refund $refund, ?string $note = null, ?string $code = null): Refund
    {
        $refund->update([
            'status' => RefundStatus::PENDING,
            'validated_by' => null,
            'validated_at' => null,
            'gateway_response' => $code !== null ? ['code' => $code] : null,
            'resolution_notes' => $note !== null && trim($note) !== '' ? trim($note) : null,
        ]);

        return $refund->refresh();
    }

    /**
     * Rechaza la solicitud. No toca la pasarela ni el pago.
     */
    public function reject(Refund $refund, ?int $validatedBy = null, ?string $reason = null): Refund
    {
        Refund::query()
            ->whereKey($refund->id)
            ->where('status', RefundStatus::PENDING->value)
            ->update([
                'status' => RefundStatus::REJECTED->value,
                'validated_by' => $validatedBy,
                'validated_at' => now(),
                'resolution_notes' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            ]);

        return $refund->refresh();
    }

    /**
     * Pago que puede devolverse: el último cobro acreditado del pedido.
     */
    public function refundablePayment(Order $order): Payment
    {
        $payment = $order->payments()
            ->whereIn('status', [PaymentStatus::PAID->value, PaymentStatus::REFUNDED->value])
            ->latest('id')
            ->first();

        if ($payment === null) {
            throw new InvalidArgumentException(
                'El pedido no tiene un pago confirmado que se pueda devolver.'
            );
        }

        return $payment;
    }

    /**
     * Saldo del pago que todavía no está comprometido en otra devolución.
     */
    public function refundableAmount(Payment $payment): float
    {
        return round(max(0, (float) $payment->amount - $payment->refundedTotal()), 2);
    }

    /**
     * Pasarela de Culqi del pago de la devolución, si aplica.
     */
    private function culqiGatewayFor(Refund $refund): ?CulqiGateway
    {
        $payment = $refund->payment;

        if ($payment === null) {
            return null;
        }

        $gateway = $this->paymentService->gatewayFor((string) $payment->gateway);

        return $gateway instanceof CulqiGateway ? $gateway : null;
    }

    /**
     * Cierra la devolución como confirmada por la pasarela.
     *
     * @param  array<string, mixed>|null  $raw
     */
    private function complete(Refund $refund, ?string $refundId, ?array $raw): Refund
    {
        $refund = $this->settle($refund, RefundStatus::COMPLETED, $refundId, $raw, null, now());

        if ($refund->payment !== null) {
            $this->syncPaymentAfterRefund($refund->payment);
        }

        return $refund;
    }

    /**
     * @param  array<string, mixed>|null  $raw
     */
    private function settle(
        Refund $refund,
        RefundStatus $status,
        ?string $gatewayRefundId = null,
        ?array $raw = null,
        ?string $message = null,
        ?DateTimeInterface $completedAt = null,
    ): Refund {
        $refund->update(array_filter([
            'status' => $status,
            'gateway_refund_id' => $gatewayRefundId,
            'gateway_response' => $raw,
            'resolution_notes' => $message !== null && trim($message) !== '' ? trim($message) : null,
            'completed_at' => $completedAt,
        ], fn ($value) => $value !== null));

        return $refund->refresh();
    }

    /**
     * Saldo disponible excluyendo la devolución que se está validando.
     */
    private function availableFor(Payment $payment, Refund $refund): float
    {
        $committed = (float) $payment->refunds()
            ->whereKeyNot($refund->id)
            ->holdingFunds()
            ->sum('amount');

        return round(max(0, (float) $payment->amount - $committed), 2);
    }

    /**
     * Un pago solo pasa a `refunded` cuando la suma de las devoluciones
     * confirmadas cubre el total cobrado. Una devolución parcial no basta.
     */
    private function syncPaymentAfterRefund(Payment $payment): void
    {
        $refunded = (float) $payment->refunds()
            ->where('status', RefundStatus::COMPLETED->value)
            ->sum('amount');

        if ($refunded + 0.005 < (float) $payment->amount) {
            return;
        }

        DB::transaction(function () use ($payment): void {
            $payment->update(['status' => PaymentStatus::REFUNDED]);
            $payment->order?->update(['payment_status' => PaymentStatus::REFUNDED]);
        });

        Log::info('Devolución completa registrada.', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => (float) $payment->amount,
        ]);
    }
}
