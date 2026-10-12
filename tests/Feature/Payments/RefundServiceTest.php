<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\Support\CulqiResponses;
use Tests\TestCase;

/**
 * Devoluciones: solicitud local y ejecución contra Culqi.
 *
 * El contrato de POST /v2/refunds está verificado contra la API real (modo
 * test): exige `charge_id` en singular, `amount` en céntimos y un `reason` de
 * la lista cerrada, y responde `status: "completa"` (no "refunded").
 */
class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    private const CHARGE_ID = 'chr_test_TLdqSCUFh6mh1KMD';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payments.default_gateway', 'culqi');
        config()->set('payments.culqi.public_key', 'pk_test_test_key');
        config()->set('payments.culqi.secret_key', 'sk_test_test_key');
        config()->set('payments.culqi.secure_url', 'https://secure.culqi.com/v2');
        config()->set('payments.culqi.api_url', 'https://api.culqi.com/v2');
        config()->set('payments.culqi.timeout', 20);
    }

    /**
     * Pedido con un cobro acreditado, que es el único caso devolvible.
     */
    private function paidOrder(float $total = 100.00, string $paymentStatus = PaymentStatus::PAID->value): Order
    {
        $customer = User::factory()->create(['email' => 'comprador@brevare.test']);

        $order = Order::create([
            'order_number' => 'BRV-REF-'.uniqid(),
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'payment_status' => $paymentStatus,
            'subtotal' => $total,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => $total,
            'cost_total' => 0,
            'currency' => 'PEN',
            'customer_snapshot' => ['name' => 'Ana Perez', 'email' => $customer->email],
        ]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'gateway' => 'culqi',
            'method' => 'card',
            'gateway_transaction_id' => self::CHARGE_ID,
            'amount' => $total,
            'currency' => 'PEN',
            'status' => $paymentStatus,
            'paid_at' => now(),
        ]);

        return $order->fresh(['payment', 'payments']);
    }

    public function test_una_solicitud_exige_un_pago_confirmado(): void
    {
        $order = $this->paidOrder(100.00, PaymentStatus::PENDING->value);

        $this->expectException(InvalidArgumentException::class);

        app(RefundService::class)->request($order, RefundReason::SolicitudComprador, 10.00);
    }

    public function test_el_monto_no_puede_superar_el_saldo_del_pago(): void
    {
        $order = $this->paidOrder(100.00);

        $this->expectException(InvalidArgumentException::class);

        app(RefundService::class)->request($order, RefundReason::Duplicado, 100.01);
    }

    public function test_la_solicitud_no_toca_la_pasarela(): void
    {
        Http::fake();

        $order = $this->paidOrder(100.00);
        $refund = app(RefundService::class)->request($order, RefundReason::Duplicado, 25.00, 'Pedido duplicado');

        $this->assertSame(RefundStatus::PENDING, $refund->status);
        $this->assertSame(RefundReason::Duplicado, $refund->reason);
        $this->assertSame('25.00', $refund->amount);
        $this->assertNotNull($refund->requested_at);
        $this->assertSame(PaymentStatus::PAID, $order->payment->status);
        Http::assertNothingSent();
    }

    public function test_aprobar_envia_la_devolucion_a_culqi_con_el_contrato_que_exige(): void
    {
        Http::fake([
            'api.culqi.com/v2/refunds' => Http::response(CulqiResponses::completedRefund(), 201),
        ]);

        $order = $this->paidOrder(100.00);
        $refund = app(RefundService::class)->request($order, RefundReason::Duplicado, 100.00);

        $result = app(RefundService::class)->approve($refund, $order->user_id);

        $this->assertSame(RefundStatus::COMPLETED, $result->status);
        $this->assertSame('ref_test_HmMIaPxAsKYlK1dt', $result->gateway_refund_id);
        $this->assertNotNull($result->completed_at);
        $this->assertNotNull($result->validated_at);

        // La devolución completa cierra el pago y el estado de pago del pedido.
        $this->assertSame(PaymentStatus::REFUNDED, $order->payment->fresh()->status);
        $this->assertSame(PaymentStatus::REFUNDED, $order->fresh()->payment_status);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.culqi.com/v2/refunds'
            && ($request['charge_id'] ?? null) === self::CHARGE_ID
            && ($request['amount'] ?? null) === 10000
            && ($request['reason'] ?? null) === RefundReason::Duplicado->value);
    }

    public function test_una_devolucion_parcial_no_marca_el_pago_como_reembolsado(): void
    {
        Http::fake([
            'api.culqi.com/v2/refunds' => Http::response(CulqiResponses::completedRefund(amount: 2000), 201),
        ]);

        $order = $this->paidOrder(100.00);
        $refund = app(RefundService::class)->request($order, RefundReason::SolicitudComprador, 20.00);

        $result = app(RefundService::class)->approve($refund);

        $this->assertSame(RefundStatus::COMPLETED, $result->status);
        $this->assertSame(PaymentStatus::PAID, $order->payment->fresh()->status);
        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
        $this->assertSame(80.00, $order->payment->fresh()->refundableAmount());
    }

    public function test_una_solicitud_rechazada_libera_el_saldo(): void
    {
        $order = $this->paidOrder(100.00);
        $refund = app(RefundService::class)->request($order, RefundReason::Fraudulento, 60.00);

        $this->assertSame(40.00, $order->payment->fresh()->refundableAmount());

        app(RefundService::class)->reject($refund, null, 'No cumple la política de devoluciones.');

        $this->assertSame(RefundStatus::REJECTED, $refund->fresh()->status);
        $this->assertSame(100.00, $order->payment->fresh()->refundableAmount());
    }

    public function test_aprobar_dos_veces_envia_una_sola_devolucion_a_culqi(): void
    {
        Http::fake([
            'api.culqi.com/v2/refunds' => Http::response(CulqiResponses::completedRefund(), 201),
        ]);

        $order = $this->paidOrder(100.00);
        $refund = app(RefundService::class)->request($order, RefundReason::Duplicado, 100.00);

        app(RefundService::class)->approve($refund);
        app(RefundService::class)->approve($refund->fresh());

        Http::assertSentCount(1);
    }

    public function test_el_rechazo_de_culqi_deja_la_devolucion_fallida_y_el_pago_intacto(): void
    {
        Http::fake([
            'api.culqi.com/v2/refunds' => Http::response(
                CulqiResponses::apiError('parameter_error', 'charge_id'),
                400
            ),
        ]);

        $order = $this->paidOrder(100.00);
        $refund = app(RefundService::class)->request($order, RefundReason::Duplicado, 100.00);

        $result = app(RefundService::class)->approve($refund);

        $this->assertSame(RefundStatus::FAILED, $result->status);
        $this->assertNotEmpty($result->resolution_notes);
        $this->assertSame(PaymentStatus::PAID, $order->payment->fresh()->status);
        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
    }

    public function test_no_se_puede_devolver_un_pago_sin_cargo_de_culqi(): void
    {
        Http::fake();

        $order = $this->paidOrder(100.00);
        $order->payment->update(['gateway_transaction_id' => null]);

        $refund = app(RefundService::class)->request($order->fresh(['payment']), RefundReason::Duplicado, 50.00);
        $result = app(RefundService::class)->approve($refund);

        $this->assertSame(RefundStatus::FAILED, $result->status);
        $this->assertSame(PaymentStatus::PAID, $order->payment->fresh()->status);
    }
}
