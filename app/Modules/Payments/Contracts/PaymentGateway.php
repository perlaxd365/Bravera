<?php

namespace App\Modules\Payments\Contracts;

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
     * @return array{success: bool, transaction_id: ?string, status: string, message: string, raw: array}
     */
    public function refund(Payment $payment): array;
}
