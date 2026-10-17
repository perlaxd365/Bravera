<?php

namespace Tests\Feature\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Store\MercadoPagoPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Gateways\MercadoPago\MercadoPagoGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use MercadoPago\Resources\Payment as MercadoPagoApiPayment;
use Tests\Support\FakeMercadoPagoGateway;
use Tests\TestCase;

class MercadoPagoCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    private function configureMercadoPago(): void
    {
        config([
            'payments.mercadopago.enabled' => true,
            'payments.mercadopago.access_token' => 'APP_USR-test-access',
            'payments.mercadopago.public_key' => 'TEST-test-public',
            'payments.mercadopago.webhook_secret' => 'test-webhook-secret',
            'payments.mercadopago.webhook_url' => 'https://brevare.test/webhooks/mercadopago',
        ]);
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'Ana María Torres',
            'email' => 'comprador@brevare.test',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
    }

    private function orderFor(User $user): Order
    {
        return Order::create([
            'order_number' => 'BRV-MP-TEST-0001',
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'subtotal' => 100,
            'shipping_total' => 12,
            'discount_total' => 0,
            'total' => 112,
            'cost_total' => 60,
            'currency' => 'PEN',
            'customer_snapshot' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function test_builds_payments_api_payload_with_server_amount_and_dni(): void
    {
        $this->configureMercadoPago();
        $user = $this->customer();
        $order = $this->orderFor($user);
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'mercadopago',
            'method' => 'card',
            'source_id' => 'card-token',
            'amount' => 112,
            'currency' => 'PEN',
            'status' => PaymentStatus::PENDING,
            'raw_response' => [
                'payment_method_id' => 'visa',
                'identification_number' => '123456789',
            ],
        ]);

        $payload = (new MercadoPagoGateway)->buildPaymentPayload(
            $payment,
            $order,
            'card-token',
            'visa',
            '123456789',
            'comprador@brevare.test',
            'issuer-123',
        );

        $this->assertSame(112.0, $payload['transaction_amount']);
        $this->assertSame($order->order_number, $payload['external_reference']);
        $this->assertSame('comprador@brevare.test', $payload['payer']['email']);
        $this->assertSame('Ana', $payload['payer']['first_name']);
        $this->assertSame('María Torres', $payload['payer']['last_name']);
        $this->assertSame('DNI', $payload['payer']['identification']['type']);
        $this->assertSame('123456789', $payload['payer']['identification']['number']);
        $this->assertSame('card-token', $payload['token']);
        $this->assertSame('visa', $payload['payment_method_id']);
        $this->assertSame(1, $payload['installments']);
        $this->assertSame('issuer-123', $payload['issuer_id']);
        $this->assertSame($order->order_number, $payload['metadata']['order_number']);
        $this->assertSame($payment->id, $payload['metadata']['local_payment_id']);
        $this->assertArrayNotHasKey('transactions', $payload);
    }

    public function test_maps_payments_api_statuses(): void
    {
        $gateway = new MercadoPagoGateway;

        $this->assertTrue($this->remotePayment('approved', 'accredited', $gateway)['success']);
        $this->assertSame(
            PaymentStatus::PAID->value,
            $this->remotePayment('approved', 'accredited', $gateway)['status']
        );
        $this->assertSame(
            PaymentStatus::PENDING->value,
            $this->remotePayment('in_process', 'pending_contingency', $gateway)['status']
        );
        $this->assertSame(
            PaymentStatus::FAILED->value,
            $this->remotePayment('rejected', 'cc_rejected_insufficient_amount', $gateway)['status']
        );
    }

    private function remotePayment(string $status, string $detail, MercadoPagoGateway $gateway): array
    {
        $remote = new MercadoPagoApiPayment;
        $remote->id = 123456789;
        $remote->status = $status;
        $remote->status_detail = $detail;
        $remote->transaction_amount = 112.00;
        $remote->currency_id = 'PEN';
        $remote->payment_method_id = 'visa';
        $remote->payment_type_id = 'credit_card';
        $remote->external_reference = 'BRV-MP-TEST-0001';

        return $gateway->resultFromPayment($remote);
    }

    public function test_payment_screen_is_separate_and_requires_the_order_owner(): void
    {
        $this->configureMercadoPago();
        $user = $this->customer();
        $order = $this->orderFor($user);

        Livewire::actingAs($user)
            ->test(MercadoPagoPayment::class, ['order' => $order])
            ->assertSee('Finaliza tu compra')
            ->assertSee('Modo prueba')
            ->assertSee('form-checkout')
            ->assertSee('DNI del titular');

        $otherUser = User::create([
            'name' => 'Otra Persona',
            'email' => 'otra@brevare.test',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('payments.mercadopago.pay', ['order' => $order->order_number]))
            ->assertForbidden();
    }

    public function test_pay_marks_order_as_paid_with_gateway_token(): void
    {
        $this->configureMercadoPago();
        config(['payments.gateways.mercadopago' => FakeMercadoPagoGateway::class]);
        FakeMercadoPagoGateway::approved();

        $user = $this->customer();
        $order = $this->orderFor($user);

        Livewire::actingAs($user)
            ->test(MercadoPagoPayment::class, ['order' => $order])
            ->call('pay', 'card-token', 'visa', '123456789')
            ->assertDispatched('mercadopago:result');

        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
        $this->assertSame('ORD-TEST-APPROVED', $order->payments()->latest('id')->firstOrFail()->gateway_transaction_id);
    }

    public function test_order_webhook_rejects_an_invalid_signature(): void
    {
        $this->configureMercadoPago();

        $this->postJson('/webhooks/mercadopago?type=payment&data.id=123456789', [], [
            'x-signature' => 'ts=1,v1=invalid',
            'x-request-id' => 'request-test-1',
        ])->assertStatus(401);
    }
}
