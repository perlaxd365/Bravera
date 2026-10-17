<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use App\Modules\Payments\Gateways\Izipay\IzipayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Izipay no cobra en el servidor: pide un formToken con la API REST y el
 * comprador paga en su formulario. Aquí se verifica que el pedido de token
 * lleve el importe en céntimos, el número de pedido y la autenticación Basic,
 * y que sin credenciales la pasarela falle de forma cerrada.
 */
class IzipayGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payments.izipay.enabled', true);
        config()->set('payments.izipay.shop_id', '500019031');
        config()->set('payments.izipay.rest_password', 'rest_password_test');
        config()->set('payments.izipay.public_key', '500019031:testpublickey_abc');
        config()->set('payments.izipay.hmac_key', 'hmac_key_test');
        config()->set('payments.izipay.endpoint', 'https://api.micuentaweb.pe');
    }

    private function order(float $total = 100.20): Order
    {
        $user = User::factory()->create(['email' => 'comprador@brevare.test']);

        return Order::create([
            'order_number' => 'BVR-IZI'.uniqid(),
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $total,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => $total,
            'cost_total' => 0,
            'customer_snapshot' => ['name' => 'Ana Perez', 'email' => $user->email, 'phone' => '999888777'],
            'address_snapshot' => ['phone' => '999888777', 'address' => 'Av. Siempre Viva 742'],
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

    public function test_pide_el_form_token_con_el_importe_en_centimos_y_devuelve_la_pagina_de_pago(): void
    {
        Http::fake([
            'api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => 'form-token-abc123'],
            ]),
        ]);

        $order = $this->order(100.20);
        $payment = $this->payment($order);

        $result = (new IzipayGateway)->charge($payment);

        $this->assertSame(PaymentStatus::PENDING->value, $result['status']);
        $this->assertFalse($result['success']);
        $this->assertSame('form-token-abc123', $result['raw']['form_token']);
        $this->assertStringContainsString('/pago/izipay/'.$order->order_number, $result['checkout_url']);

        Http::assertSent(function (Request $request) use ($order) {
            $body = $request->data();

            return str_ends_with($request->url(), '/api-payment/V4/Charge/CreatePayment')
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('500019031:rest_password_test'))
                && $body['amount'] === 10020
                && $body['currency'] === 'PEN'
                && $body['orderId'] === $order->order_number
                && $body['customer']['email'] === 'comprador@brevare.test';
        });
    }

    public function test_sin_credenciales_la_pasarela_no_esta_disponible_y_no_deja_cobrar(): void
    {
        config()->set('payments.izipay.rest_password', '');

        $gateway = new IzipayGateway;

        $this->assertFalse($gateway->isAvailable());

        $this->expectException(PaymentGatewayNotConfiguredException::class);

        $gateway->charge($this->payment($this->order()));
    }

    public function test_si_izipay_rechaza_la_creacion_del_token_devuelve_un_pago_fallido(): void
    {
        Http::fake([
            'api.micuentaweb.pe/*' => Http::response([
                'status' => 'ERROR',
                'answer' => ['errorCode' => 'PSP_010'],
            ], 200),
        ]);

        $result = (new IzipayGateway)->charge($this->payment($this->order()));

        $this->assertFalse($result['success']);
        $this->assertSame(PaymentStatus::FAILED->value, $result['status']);
        $this->assertSame('form_token_failed', $result['raw']['code']);
    }

    public function test_las_devoluciones_quedan_bloqueadas_en_la_tienda(): void
    {
        $result = (new IzipayGateway)->refund($this->payment($this->order()));

        $this->assertFalse($result['success']);
        $this->assertSame('refund_not_supported', $result['raw']['code']);
    }
}
