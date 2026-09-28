<?php

namespace Tests\Feature\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Ordering\Events\OrderPaid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Support\CulqiResponses;
use Tests\TestCase;

/**
 * El webhook es la única vía por la que un pago asíncrono (Yape) o un cargo
 * diferido se confirma. Nunca se confía en el cuerpo recibido: se re-consulta a
 * Culqi antes de marcar el pedido como pagado.
 */
class CulqiWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payments.default_gateway', 'culqi');
        config()->set('payments.culqi.public_key', 'pk_test_123');
        config()->set('payments.culqi.secret_key', 'sk_test_456');
        config()->set('payments.culqi.api_url', 'https://api.culqi.com/v2');
        config()->set('payments.culqi.secure_url', 'https://secure.culqi.com/v2');
    }

    private function order(): Order
    {
        $user = User::factory()->create();

        return Order::create([
            'order_number' => 'SOP-'.uniqid(),
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'subtotal' => 51, 'shipping_total' => 0, 'discount_total' => 0,
            'total' => 51, 'cost_total' => 0,
            'customer_snapshot' => ['name' => 'Ana Perez', 'email' => $user->email],
            'address_snapshot' => ['phone' => '999888777'],
            'currency' => 'PEN',
        ]);
    }

    private function payment(Order $order, string $method = 'card', ?string $transactionId = 'chr_1'): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'culqi',
            'method' => $method,
            'gateway_transaction_id' => $transactionId,
            'source_id' => $transactionId,
            'amount' => 51,
            'currency' => 'PEN',
            'status' => PaymentStatus::PENDING,
        ]);
    }

    public function test_confirma_el_pago_cuando_culqi_lo_reporta_pagado(): void
    {
        Event::fake([OrderPaid::class]);

        Http::fake(['api.culqi.com/v2/charges/*' => Http::response(CulqiResponses::approvedCharge('chr_1'))]);

        $order = $this->order();
        $payment = $this->payment($order);

        $this->postJson('/webhooks/culqi', [
            'type' => 'charge.paid',
            'data' => ['id' => 'chr_1'],
        ])->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
        Event::assertDispatched(OrderPaid::class, 1);
    }

    public function test_no_confirma_un_pago_si_culqi_no_lo_confirma(): void
    {
        Event::fake([OrderPaid::class]);

        // El atacante envía charge.paid, pero en Culqi el cargo está declinado.
        Http::fake(['api.culqi.com/v2/charges/*' => Http::response(CulqiResponses::declinedCharge('chr_1'))]);

        $order = $this->order();
        $payment = $this->payment($order);

        $this->postJson('/webhooks/culqi', [
            'type' => 'charge.paid',
            'data' => ['id' => 'chr_1'],
        ])->assertOk();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
        Event::assertNotDispatched(OrderPaid::class);
    }

    public function test_es_idempotente_ante_eventos_repetidos(): void
    {
        Event::fake([OrderPaid::class]);

        Http::fake(['api.culqi.com/v2/charges/*' => Http::response(CulqiResponses::approvedCharge('chr_1'))]);

        $order = $this->order();
        $payment = $this->payment($order);

        foreach (range(1, 3) as $ignored) {
            $this->postJson('/webhooks/culqi', [
                'type' => 'charge.paid',
                'data' => ['id' => 'chr_1'],
            ])->assertOk();
        }

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        Event::assertDispatched(OrderPaid::class, 1);
    }

    public function test_confirma_una_orden_de_yape(): void
    {
        Event::fake([OrderPaid::class]);

        Http::fake(['api.culqi.com/v2/orders/*' => Http::response(CulqiResponses::paidOrder('ord_1'))]);

        $order = $this->order();
        $payment = $this->payment($order, 'yape', 'ord_1');

        $this->postJson('/webhooks/culqi', [
            'type' => 'order.paid',
            'data' => ['id' => 'ord_1'],
        ])->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
    }

    /**
     * Con el checkout en modal, Culqi puede confirmar la orden antes de que el
     * navegador devuelva el evento de pago. Si el webhook no reconociera la
     * orden se descartaría con un 200 y el pago se perdería: el cliente habría
     * pagado y el pedido acabaría cancelado por el comando de expiración.
     */
    public function test_confirma_una_orden_del_modal_que_aun_no_tiene_pago_local(): void
    {
        Event::fake([OrderPaid::class]);

        Http::fake(['api.culqi.com/v2/orders/*' => Http::response(CulqiResponses::paidOrder('ord_modal_1'))]);

        $order = $this->order();
        $order->update(['gateway_order_id' => 'ord_modal_1']);

        // No hay ningún Payment: el webhook llega antes que el navegador.
        $this->assertSame(0, Payment::count());

        $this->postJson('/webhooks/culqi', [
            'type' => 'order.paid',
            'data' => ['id' => 'ord_modal_1'],
        ])->assertOk();

        $payment = Payment::firstOrFail();

        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame('ord_modal_1', $payment->gateway_transaction_id);
        $this->assertSame(PaymentStatus::PAID, $payment->status);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
    }

    /**
     * El respaldo no puede inventar pagos: si Culqi no confirma la orden, el
     * pago queda pendiente y el pedido sin tocar.
     */
    public function test_no_confirma_una_orden_del_modal_que_culqi_no_reporta_pagada(): void
    {
        Event::fake([OrderPaid::class]);

        Http::fake(['api.culqi.com/v2/orders/*' => Http::response(CulqiResponses::pendingOrder('ord_modal_2'))]);

        $order = $this->order();
        $order->update(['gateway_order_id' => 'ord_modal_2']);

        $this->postJson('/webhooks/culqi', [
            'type' => 'order.paid',
            'data' => ['id' => 'ord_modal_2'],
        ])->assertOk();

        $this->assertSame(PaymentStatus::PENDING, Payment::firstOrFail()->status);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }

    /**
     * Un cargo suelto no debe Aprovechar el respaldo de las órdenes: solo se
     * acepta un ord_ que esta tienda creó.
     */
    public function test_no_inventa_un_pago_para_un_cargo_suelto(): void
    {
        Http::fake();

        $this->order();

        $this->postJson('/webhooks/culqi', [
            'type' => 'charge.paid',
            'data' => ['id' => 'chr_no_existe'],
        ])->assertOk();

        $this->assertSame(0, Payment::count());
    }

    public function test_ignora_eventos_de_otra_pasarela(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $payment->update(['gateway' => 'manual']);

        Http::fake();

        $this->postJson('/webhooks/culqi', [
            'type' => 'charge.paid',
            'data' => ['id' => 'chr_1'],
        ])->assertOk();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_responde_ok_a_un_pago_desconocido(): void
    {
        Http::fake();

        $this->postJson('/webhooks/culqi', [
            'type' => 'charge.paid',
            'data' => ['id' => 'chr_no_existe'],
        ])->assertOk();
    }

    public function test_registra_el_reembolso(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);

        // El reembolso exige que el cargo exista en Culqi: no se confía en el
        // cuerpo del evento.
        Http::fake(['api.culqi.com/v2/charges/*' => Http::response(CulqiResponses::approvedCharge('chr_1'))]);

        $this->postJson('/webhooks/culqi', [
            'type' => 'charge.refunded',
            'data' => ['id' => 'chr_1'],
        ])->assertOk();

        $this->assertSame(PaymentStatus::REFUNDED, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::REFUNDED, $order->fresh()->payment_status);
    }

    public function test_no_registra_un_reembolso_que_culqi_no_confirma(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);

        Http::fake(['api.culqi.com/v2/charges/*' => Http::response(['message' => 'Not found'], 404)]);

        $this->postJson('/webhooks/culqi', [
            'type' => 'charge.refunded',
            'data' => ['id' => 'chr_1'],
        ])->assertOk();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::PENDING, $order->fresh()->payment_status);
    }
}
