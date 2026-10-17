<?php

namespace Tests\Support;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Models\Payment;
use App\Modules\Payments\Contracts\PaymentGateway;

class FakeMercadoPagoGateway implements PaymentGateway
{
    /** @var array<string, mixed> */
    public static array $result = [];

    public static function approved(string $orderId = 'ORD-TEST-APPROVED'): void
    {
        static::$result = [
            'success' => true,
            'transaction_id' => $orderId,
            'status' => PaymentStatus::PAID->value,
            'message' => 'Pago aprobado.',
            'raw' => [
                'order_id' => $orderId,
                'order_status' => 'processed',
                'order_status_detail' => 'accredited',
                'message' => 'Pago aprobado.',
            ],
        ];
    }

    public function name(): string
    {
        return 'mercadopago';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function charge(Payment $payment): array
    {
        return static::$result;
    }

    public function refund(Payment $payment, ?RefundReason $reason = null, ?float $amount = null): array
    {
        return static::$result;
    }
}
