<?php

namespace App\Modules\Payments\Contracts;

use App\Enums\RefundReason;
use App\Models\Payment;

interface PaymentGateway
{
    /**
     * Nombre interno del gateway (se guarda en payments.gateway).
     */
    public function name(): string;

    /**
     * Procesa el cobro de un pago y devuelve el resultado.
     *
     * @return array{success: bool, transaction_id: ?string, status: string, message: string, raw: array}
     */
    public function charge(Payment $payment): array;

    /**
     * Reembolsa un pago.
     *
     * `$amount` es el monto a devolver cuando la pasarela admite devoluciones
     * parciales (Culqi sí: POST /v2/refunds acepta `amount` en céntimos). Con
     * `null` se devuelve el total del pago.
     *
     * @return array{success: bool, transaction_id: ?string, status: string, message: string, raw: array}
     */
    public function refund(Payment $payment, ?RefundReason $reason = null, ?float $amount = null): array;
}
