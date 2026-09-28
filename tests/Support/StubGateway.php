<?php

namespace Tests\Support;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Models\Payment;
use App\Modules\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;

/**
 * Pasarela de laboratorio: devuelve el resultado que cada test necesite y
 * registra el nivel de transacción en el momento del cobro, que es lo que
 * permite comprobar que no se cobran dinero con una transacción abierta.
 */
class StubGateway implements PaymentGateway
{
    /** @var array<string, mixed> */
    public static array $result = [];

    public static ?int $transactionLevelDuringCharge = null;

    public static int $chargeCalls = 0;

    public static function fake(array $result): void
    {
        static::$result = $result;
        static::$transactionLevelDuringCharge = null;
        static::$chargeCalls = 0;
    }

    public static function declined(): self
    {
        static::fake([
            'success' => false,
            'transaction_id' => 'chr_stub_declined',
            'status' => PaymentStatus::FAILED->value,
            'message' => 'Tu banco no aprobó el pago.',
            'raw' => ['code' => 'declined'],
        ]);

        return new self;
    }

    public static function pendingAsync(): self
    {
        static::fake([
            'success' => false,
            'transaction_id' => 'ord_stub_1',
            'status' => PaymentStatus::PENDING->value,
            'message' => 'Completa el pago para confirmar tu pedido.',
            'raw' => ['data' => ['id' => 'ord_stub_1']],
            'source_id' => 'ord_stub_1',
            'checkout_url' => 'https://pago.culqi.com/ord_stub_1',
        ]);

        return new self;
    }

    public static function approved(): self
    {
        static::fake([
            'success' => true,
            'transaction_id' => 'chr_stub_paid',
            'status' => PaymentStatus::PAID->value,
            'message' => 'Pago aprobado.',
            'raw' => ['data' => ['id' => 'chr_stub_paid', 'status' => 'paid']],
        ]);

        return new self;
    }

    public function name(): string
    {
        return 'stub';
    }

    public function charge(Payment $payment): array
    {
        static::$chargeCalls++;
        static::$transactionLevelDuringCharge = DB::transactionLevel();

        return static::$result;
    }

    public function refund(Payment $payment, ?RefundReason $reason = null): array
    {
        return static::$result;
    }
}
