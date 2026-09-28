<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use App\Modules\Payments\Gateways\Culqi\CulqiGateway;
use App\Modules\Payments\Gateways\Culqi\CulqiStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\CulqiResponses;
use Tests\TestCase;

/**
 * Los fakes de este archivo replican respuestas REALES de la API de Culqi en
 * modo test (ver Tests\Support\CulqiResponses). Importa porque la API no usa
 * el envoltorio `data` y el cargo no trae `status`: el resultado vive en
 * `outcome.code` / `outcome.type`.
 */
class CulqiGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payments.default_gateway', 'culqi');
        config()->set('payments.culqi.public_key', 'pk_test_test_key');
        config()->set('payments.culqi.secret_key', 'sk_test_test_key');
        config()->set('payments.culqi.secure_url', 'https://secure.culqi.com/v2');
        config()->set('payments.culqi.api_url', 'https://api.culqi.com/v2');
        config()->set('payments.culqi.capture', true);
        config()->set('payments.culqi_methods', ['card', 'yape']);
    }

    /* ---------------------------------------------------------------------
    | Fixtures
    | ------------------------------------------------------------------ */

    private function order(float $total = 51.00): Order
    {
        $user = User::factory()->create(['email' => 'comprador@brevare.test']);

        return Order::create([
            'order_number' => 'SOP-'.uniqid(),
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $total,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => $total,
            'cost_total' => 0,
            'customer_snapshot' => ['name' => 'Ana Perez', 'email' => $user->email],
            'address_snapshot' => ['phone' => '999888777', 'address' => 'Av. Siempre Viva 742'],
            'currency' => 'PEN',
        ]);
    }

    private function payment(?Order $order = null, string $method = 'card', ?string $sourceId = 'tkn_test_TjKR1Ezk3OKz2KCw'): Payment
    {
        $order ??= $this->order();

        return Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'culqi',
            'method' => $method,
            'source_id' => $sourceId,
            'amount' => $order->total,
            'currency' => 'PEN',
            'status' => PaymentStatus::PENDING,
        ]);
    }

    /* ---------------------------------------------------------------------
    | Tarjeta
    | ------------------------------------------------------------------ */

    public function test_cobra_tarjeta_y_devuelve_pagado(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::approvedCharge(), 201),
        ]);

        $result = app(CulqiGateway::class)->charge($this->payment());

        $this->assertTrue($result['success']);
        $this->assertSame(PaymentStatus::PAID->value, $result['status']);
        $this->assertSame('chr_test_TLdqSCUFh6mh1KMD', $result['transaction_id']);
    }

    public function test_lee_el_exito_desde_outcome_y_no_desde_status(): void
    {
        // Un cargo exitoso no trae campo `status`. Si el gateway lo buscara,
        // marcaría como fallido un pago que Culqi si approve.
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::approvedCharge(), 201),
        ]);

        $result = app(CulqiGateway::class)->charge($this->payment());

        $this->assertArrayNotHasKey('status', CulqiResponses::approvedCharge());
        $this->assertTrue($result['success']);
    }

    public function test_envia_el_monto_en_centimos(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::approvedCharge(), 201),
        ]);

        app(CulqiGateway::class)->charge($this->payment($this->order(51.00)));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.culqi.com/v2/charges'
                && $request['amount'] === 5100
                && $request['currency_code'] === 'PEN'
                && $request['source_id'] === 'tkn_test_TjKR1Ezk3OKz2KCw';
        });
    }

    public function test_usa_la_clave_secreta_en_el_servidor(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::approvedCharge(), 201),
        ]);

        app(CulqiGateway::class)->charge($this->payment());

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk_test_test_key'));
    }

    public function test_rechaza_sin_token_de_tarjeta(): void
    {
        Http::fake();

        $result = app(CulqiGateway::class)->charge($this->payment(sourceId: null));

        $this->assertFalse($result['success']);
        $this->assertSame('missing_card_token', $result['raw']['code']);
        Http::assertNothingSent();
    }

    public function test_muestra_el_user_message_de_culqi_ante_un_error(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::apiError(), 400),
        ]);

        $result = app(CulqiGateway::class)->charge($this->payment());

        $this->assertFalse($result['success']);
        // El mensaje de Culqi es apto para el comprador.
        $this->assertSame('Revisa los datos de tu tarjeta', $result['message']);
        // El detalle técnico nunca se muestra al cliente.
        $this->assertStringNotContainsString('Bin de la tarjeta', $result['message']);
    }

    /* ---------------------------------------------------------------------
    | Rechazos y outcomes desconocidos
    | ------------------------------------------------------------------ */

    public function test_traduce_un_cargo_rechazado(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::declinedCharge(), 201),
        ]);

        $result = app(CulqiGateway::class)->charge($this->payment());

        $this->assertFalse($result['success']);
        $this->assertSame(PaymentStatus::FAILED->value, $result['status']);
        $this->assertSame('Tu banco no aprobo el pago', $result['message']);
    }

    public function test_un_outcome_desconocido_no_se_marca_pagado(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::unknownOutcomeCharge(), 201),
        ]);

        $result = app(CulqiGateway::class)->charge($this->payment());

        // A favor de la caution: sin code AUT0000 ni paid=true, no se confirma.
        $this->assertFalse($result['success']);
        $this->assertSame(PaymentStatus::FAILED->value, $result['status']);
    }

    public function test_un_cargo_sin_outcome_queda_pendiente_para_conciliar(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::chargeWithoutOutcome(), 201),
        ]);

        $result = app(CulqiGateway::class)->charge($this->payment());

        $this->assertFalse($result['success']);
        $this->assertSame(PaymentStatus::PENDING->value, $result['status']);
    }

    public function test_considera_pagado_un_cargo_con_paid_true(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges' => Http::response([
                'object' => 'charge',
                'id' => 'chr_prod_1',
                'outcome' => ['type' => 'confirmada', 'code' => 'AUT0123'],
                'paid' => true,
            ], 201),
        ]);

        $result = app(CulqiGateway::class)->charge($this->payment());

        $this->assertTrue($result['success']);
    }

    public function test_rechaza_metodo_no_soportado(): void
    {
        $result = app(CulqiGateway::class)->charge($this->payment($this->order(), 'transferencia'));

        $this->assertFalse($result['success']);
        $this->assertSame('unsupported_method', $result['raw']['code']);
    }

    public function test_rechaza_monto_no_positivo(): void
    {
        $result = app(CulqiGateway::class)->charge($this->payment($this->order(0)));

        $this->assertFalse($result['success']);
        $this->assertSame('invalid_amount', $result['raw']['code']);
    }

    public function test_falla_de_forma_cerrada_sin_credenciales(): void
    {
        config()->set('payments.culqi.secret_key', null);
        Http::fake();

        $this->expectException(PaymentGatewayNotConfiguredException::class);

        app(CulqiGateway::class)->charge($this->payment());
    }

    /* ---------------------------------------------------------------------
    | Sin idempotency key: nunca se cobra dos veces
    | ------------------------------------------------------------------ */

    public function test_no_vuelve_a_cobrar_si_ya_existe_un_cargo(): void
    {
        Http::fake([
            'api.culqi.com/v2/charges/*' => Http::response(
                CulqiResponses::approvedCharge('chr_test_TLdqSCUFh6mh1KMD'),
                200
            ),
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::approvedCharge('chr_nuevo'), 201),
        ]);

        $payment = $this->payment();
        $payment->update(['gateway_transaction_id' => 'chr_test_TLdqSCUFh6mh1KMD']);

        $result = app(CulqiGateway::class)->charge($payment);

        $this->assertTrue($result['success']);
        $this->assertSame('chr_test_TLdqSCUFh6mh1KMD', $result['transaction_id']);

        // Solo consultó, nunca creó un cargo nuevo.
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'GET');
    }

    /* ---------------------------------------------------------------------
    | Yape (asíncrono)
    | ------------------------------------------------------------------ */

    public function test_yape_crea_una_orden_y_queda_pendiente(): void
    {
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_test_1'), 201),
        ]);

        $result = app(CulqiGateway::class)->charge($this->payment($this->order(), 'yape', null));

        $this->assertFalse($result['success']);
        $this->assertSame(PaymentStatus::PENDING->value, $result['status']);
        $this->assertSame('ord_test_1', $result['source_id']);
        $this->assertSame(
            'https://pre1a.payment.pagoefectivo.pe/SG3R938W-0DNKYQ57-8OODJL5J-95CDTSPK-PVGK.html',
            $result['checkout_url'],
        );
    }

    /* ---------------------------------------------------------------------
    | Reembolsos
    | ------------------------------------------------------------------ */

    public function test_reembolsa_un_cargo(): void
    {
        Http::fake([
            'api.culqi.com/v2/refunds' => Http::response(CulqiResponses::completedRefund(), 201),
        ]);

        $payment = $this->payment();
        $payment->update(['gateway_transaction_id' => 'chr_test_TLdqSCUFh6mh1KMD']);

        $result = app(CulqiGateway::class)->refund($payment);

        $this->assertTrue($result['success']);
        $this->assertSame(PaymentStatus::REFUNDED->value, $result['status']);
    }

    public function test_no_reembolsa_sin_cargo_de_culqi(): void
    {
        Http::fake();

        $result = app(CulqiGateway::class)->refund($this->payment());

        $this->assertFalse($result['success']);
        $this->assertSame('no_charge_id', $result['raw']['code']);
    }

    public function test_envia_el_reembolso_con_los_campos_que_exige_culqi(): void
    {
        Http::fake([
            'api.culqi.com/v2/refunds' => Http::response(CulqiResponses::completedRefund(), 201),
        ]);

        $payment = $this->payment();
        $payment->update(['gateway_transaction_id' => 'chr_test_TLdqSCUFh6mh1KMD']);

        app(CulqiGateway::class)->refund($payment, RefundReason::SolicitudComprador);

        // Verificado contra la API real: `charges_id` (plural) y omitir `reason`
        // devuelven 400/401 parameter_error.
        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $request->url() === 'https://api.culqi.com/v2/refunds'
                && ($body['charge_id'] ?? null) === 'chr_test_TLdqSCUFh6mh1KMD'
                && ! array_key_exists('charges_id', $body)
                && ($body['reason'] ?? null) === 'solicitud_comprador';
        });
    }

    public function test_un_reembolso_pendiente_no_se_marca_reembolsado(): void
    {
        Http::fake([
            'api.culqi.com/v2/refunds' => Http::response([
                'object' => 'refund',
                'id' => 'ref_test_pendiente',
                'charge_id' => 'chr_test_TLdqSCUFh6mh1KMD',
                'amount' => 5100,
                'status' => 'pendiente',
            ], 201),
        ]);

        $payment = $this->payment();
        $payment->update(['gateway_transaction_id' => 'chr_test_TLdqSCUFh6mh1KMD']);

        $result = app(CulqiGateway::class)->refund($payment);

        $this->assertTrue($result['success']);
        $this->assertSame(PaymentStatus::PENDING->value, $result['status']);
    }

    /* ---------------------------------------------------------------------
     | Contrato de la orden (Yape / PagoEfectivo)
     | ------------------------------------------------------------------ */

    public function test_crea_la_orden_con_los_campos_que_exige_culqi(): void
    {
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_test_1'), 201),
        ]);

        $result = app(CulqiGateway::class)->charge($this->payment($this->order(), 'yape', null));

        // Verificado contra la API real: `customer` en vez de `client_details`,
        // y `expiration_date` en epoch SEGUNDOS, devuelven 400 parameter_error.
        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $request->url() === 'https://api.culqi.com/v2/orders'
                && ! array_key_exists('customer', $body)
                && isset($body['client_details']['phone_number'])
                && is_int($body['expiration_date'] ?? null)
                && $body['expiration_date'] > time();
        });
    }

    public function test_no_crea_la_orden_si_falta_el_celular(): void
    {
        Http::fake();

        $order = $this->order();
        $order->update(['address_snapshot' => ['full_name' => 'Cliente', 'phone' => '123']]);

        $result = app(CulqiGateway::class)->charge($this->payment($order, 'yape', null));

        $this->assertFalse($result['success']);
        $this->assertSame('invalid_client_details', $result['raw']['code']);
        Http::assertNothingSent();
    }

    public function test_una_orden_pagada_se_reconoce_por_paid_at(): void
    {
        $this->assertSame(
            PaymentStatus::PAID->value,
            CulqiStatus::resolve(CulqiResponses::paidOrder('ord_test_pagada')),
        );
    }

    public function test_una_orden_pendiente_se_reconoce_por_state(): void
    {
        $this->assertSame(
            PaymentStatus::PENDING->value,
            CulqiStatus::resolve(CulqiResponses::pendingOrder()),
        );
    }
}
