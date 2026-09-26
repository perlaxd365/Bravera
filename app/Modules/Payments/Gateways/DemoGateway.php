<?php

namespace App\Modules\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use Illuminate\Support\Str;

/**
 * Pasarela de demostración.
 *
 * Aprueba todos los cobros sin mover dinero. Solo puede activarse cuando
 * config('payments.allow_demo_gateway') es true, es decir, únicamente en
 * desarrollo. En cualquier otro entorno lanza una excepción antes de cobrar.
 */
class DemoGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function charge(Payment $payment): array
    {
        $this->guardEnabled();

        if ((float) $payment->amount <= 0) {
            return [
                'success' => false,
                'transaction_id' => null,
                'status' => PaymentStatus::FAILED->value,
                'message' => 'El monto del pago debe ser mayor a cero.',
                'raw' => ['code' => 'invalid_amount'],
            ];
        }

        return [
            'success' => true,
            'transaction_id' => 'DEMO-'.strtoupper(Str::random(12)),
            'status' => PaymentStatus::PAID->value,
            'message' => 'Pago aprobado (modo demostración).',
            'raw' => [
                'code' => 'charge_success',
                'mode' => 'demo',
                'processed_at' => now()->toDateTimeString(),
            ],
        ];
    }

    public function refund(Payment $payment): array
    {
        $this->guardEnabled();

        return [
            'success' => true,
            'transaction_id' => $payment->gateway_transaction_id,
            'status' => PaymentStatus::REFUNDED->value,
            'message' => 'Reembolso registrado (modo demostración).',
            'raw' => [
                'code' => 'refund_success',
                'mode' => 'demo',
                'processed_at' => now()->toDateTimeString(),
            ],
        ];
    }

    /**
     * Evita que esta pasarela procese un cobro fuera de desarrollo.
     */
    private function guardEnabled(): void
    {
        if (! config('payments.allow_demo_gateway')) {
            throw new PaymentGatewayNotConfiguredException(
                'La pasarela de demostración está deshabilitada. '
                .'Registra una pasarela real en config/payments.php, o define '
                .'PAYMENT_ALLOW_DEMO=true solo en desarrollo.'
            );
        }
    }
}
