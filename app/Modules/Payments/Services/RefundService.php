<?php

namespace App\Modules\Payments\Services;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
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
            $refund->update([
                'status' => RefundStatus::PENDING,
                'validated_by' => null,
                'validated_at' => null,
            ]);

            throw $e;
        }

        $result = $gateway->refund($payment, $refund->reason, (float) $refund->amount);

        $message = (string) ($result['message'] ?? '');
        $refundId = is_string($result['transaction_id'] ?? null) ? $result['transaction_id'] : null;
        $raw = is_array($result['raw'] ?? null) ? $result['raw'] : null;

        if (! ($result['success'] ?? false)) {
            return $this->settle($refund, RefundStatus::FAILED, $refundId, $raw, $message);
        }

        if (($result['status'] ?? null) === PaymentStatus::REFUNDED->value) {
            $refund = $this->settle($refund, RefundStatus::COMPLETED, $refundId, $raw, null, now());
            $this->syncPaymentAfterRefund($payment);

            return $refund;
        }

        // La pasarela aceptó la devolución pero la deja en trámite.
        return $this->settle($refund, RefundStatus::PROCESSING, $refundId, $raw, $message);
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

        Log::info('Devolución completa de Culqi registrada.', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => (float) $payment->amount,
        ]);
    }
}
