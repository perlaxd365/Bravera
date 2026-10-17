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
use Tests\TestCase;

/**
 * El IPN de Izipay es la fuente de verdad del cobro. Va firmado con kr-hash
 * (HMAC-SHA256 de kr-answer), así que sin firma válida no puede confirmar
 * nada, y aunque la firma sea válida se contrasta el importe y el número de
 * pedido contra la base local.
 */
class IzipayWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const HMAC_KEY = 'hmac_key_test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payments.izipay.enabled', true);
        config()->set('payments.izipay.shop_id', '500019031');
        config()->set('payments.izipay.rest_password', 'rest_password_test');
        config()->set('payments.izipay.public_key', '500019031:testpublickey_abc');
        config()->set('payments.izipay.hmac_key', self::HMAC_KEY);
        config()->set('payments.izipay.endpoint', 'https://api.micuentaweb.pe');
    }

    private function order(float $total = 100.20): Order
    {
        $user = User::factory()->create(['email' => 'comprador@brevare.test']);

        return Order::create([
            'order_number' => 'BVR-IPN'.uniqid(),
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'subtotal' => $total,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => $total,
            'cost_total' => 0,
            'customer_snapshot' => ['name' => 'Ana Perez', 'email' => $user->email],
            'address_snapshot' => ['phone' => '999888777'],
            'currency' => 'PEN',
        ]);
    }

    private function payment(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'izipay',
            'method' => 'card',
            'amount' => $order->total,
            'currency' => 'PEN',
            'status' => PaymentStatus::PENDING,
        ]);
    }

    /**
     * Cuerpo del IPN tal como lo envía Izipay, con la firma real.
     *
     * @param  array<string, mixed>  $answer
     * @return array<string, string>
     */
    private function payload(array $answer, ?string $key = null, ?string $hash = null): array
    {
        $json = json_encode($answer, JSON_UNESCAPED_UNICODE);

        return [
            'kr-answer' => $json,
            'kr-hash' => $hash ?? hash_hmac('sha256', str_replace('\/', '/', $json), $key ?? self::HMAC_KEY),
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'sha256_hmac',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function answer(Order $order, array $overrides = []): array
    {
        return array_replace_recursive([
            'orderStatus' => 'PAID',
            'orderDetails' => [
                'orderId' => $order->order_number,
                'amount' => (int) round((float) $order->total * 100),
                'currency' => 'PEN',
            ],
            'transactions' => [
                ['uuid' => 'izi-uuid-1', 'status' => 'CAPTURED', 'paymentMethodType' => 'CARD'],
            ],
        ], $overrides);
    }

    public function test_confirma_el_pedido_cuando_izipay_reporta_el_pago(): void
    {
        Event::fake([OrderPaid::class]);

        $order = $this->order();
        $payment = $this->payment($order);

        $this->post('/webhooks/izipay', $this->payload($this->answer($order)))->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame('izi-uuid-1', $payment->fresh()->gateway_transaction_id);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
        Event::assertDispatched(OrderPaid::class, 1);
    }

    public function test_rechaza_una_notificacion_sin_firma_valida(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);

        $this->post('/webhooks/izipay', $this->payload($this->answer($order), hash: str_repeat('a', 64)))
            ->assertStatus(401);

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }

    public function test_no_confirma_si_el_importe_no_coincide_con_el_pedido(): void
    {
        $order = $this->order(100.20);
        $payment = $this->payment($order);

        $answer = $this->answer($order, [
            'orderDetails' => ['amount' => 100],
        ]);

        $this->post('/webhooks/izipay', $this->payload($answer))->assertStatus(503);

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }

    public function test_una_notificacion_repetida_no_duplica_la_confirmacion(): void
    {
        Event::fake([OrderPaid::class]);

        $order = $this->order();
        $this->payment($order);
        $payload = $this->payload($this->answer($order));

        $this->post('/webhooks/izipay', $payload)->assertOk();
        $this->post('/webhooks/izipay', $payload)->assertOk();

        Event::assertDispatched(OrderPaid::class, 1);
    }

    public function test_una_autorizacion_sin_capturar_deja_el_pedido_pendiente(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);

        $answer = $this->answer($order, ['orderStatus' => 'AUTHORISED']);

        $this->post('/webhooks/izipay', $this->payload($answer))->assertOk();

        $this->assertSame(PaymentStatus::AUTHORIZED, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }

    public function test_la_firma_tambien_se_acepta_con_la_contrasena_de_la_api_rest(): void
    {
        Event::fake([OrderPaid::class]);

        $order = $this->order();
        $payment = $this->payment($order);

        $payload = $this->payload($this->answer($order), key: 'rest_password_test');
        $payload['kr-hash-key'] = 'password';

        $this->post('/webhooks/izipay', $payload)->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
    }
}
