<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Livewire\Admin\Refunds\Index as RefundsIndex;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\CulqiResponses;
use Tests\TestCase;

/**
 * La validación de devoluciones vive en el panel: los clientes no entran y el
 * reembolso solo sale hacia la pasarela cuando un administrador lo aprueba.
 */
class RefundManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesAndPermissionsSeeder::class, UserSeeder::class]);

        config()->set('payments.default_gateway', 'culqi');
        config()->set('payments.culqi.public_key', 'pk_test_test_key');
        config()->set('payments.culqi.secret_key', 'sk_test_test_key');
        config()->set('payments.culqi.secure_url', 'https://secure.culqi.com/v2');
        config()->set('payments.culqi.api_url', 'https://api.culqi.com/v2');
    }

    private function admin(): User
    {
        return User::where('email', 'administracion@brevare.com')->firstOrFail();
    }

    private function paidOrder(float $total = 100.00): Order
    {
        $customer = User::create([
            'name' => 'Cliente Devolución',
            'email' => 'cliente.devolucion@brevare.test',
            'password' => bcrypt('password'),
        ])->assignRole('Cliente');

        $order = Order::create([
            'order_number' => 'BRV-DEV-0001',
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'payment_status' => PaymentStatus::PAID->value,
            'subtotal' => $total,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => $total,
            'cost_total' => 0,
            'currency' => 'PEN',
            'customer_snapshot' => ['name' => $customer->name, 'email' => $customer->email],
        ]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'gateway' => 'culqi',
            'method' => 'card',
            'gateway_transaction_id' => 'chr_test_TLdqSCUFh6mh1KMD',
            'amount' => $total,
            'currency' => 'PEN',
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);

        return $order->fresh(['payment', 'refunds']);
    }

    private function pendingRefund(Order $order, float $amount = 100.00): Refund
    {
        return Refund::create([
            'order_id' => $order->id,
            'payment_id' => $order->payment->id,
            'amount' => $amount,
            'currency' => 'PEN',
            'reason' => 'solicitud_comprador',
            'status' => RefundStatus::PENDING,
            'requested_at' => now(),
        ]);
    }

    public function test_admin_puede_abrir_la_seccion_de_devoluciones(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.refunds.index'))
            ->assertOk()
            ->assertSee('Devoluciones');
    }

    public function test_un_cliente_no_entra_a_la_seccion_de_devoluciones(): void
    {
        $customer = User::where('email', 'cliente.devolucion@brevare.test')->first()
            ?? User::create([
                'name' => 'Cliente',
                'email' => 'cliente.devolucion@brevare.test',
                'password' => bcrypt('password'),
            ])->assignRole('Cliente');

        $this->actingAs($customer)
            ->get(route('admin.refunds.index'))
            ->assertForbidden();
    }

    public function test_el_administrador_aprueba_la_devolucion_y_se_ejecuta_en_culqi(): void
    {
        Http::fake([
            'api.culqi.com/v2/refunds' => Http::response(CulqiResponses::completedRefund(), 201),
        ]);

        $order = $this->paidOrder();
        $refund = $this->pendingRefund($order);

        Livewire::actingAs($this->admin())
            ->test(RefundsIndex::class)
            ->call('approve', $refund->id)
            ->assertHasNoErrors();

        $this->assertSame(RefundStatus::COMPLETED, $refund->fresh()->status);
        $this->assertSame($this->admin()->id, $refund->fresh()->validated_by);
        $this->assertSame(PaymentStatus::REFUNDED, $order->payment->fresh()->status);
        $this->assertSame(PaymentStatus::REFUNDED, $order->fresh()->payment_status);
    }

    public function test_el_administrador_rechaza_la_devolucion_sin_reembolsar(): void
    {
        Http::fake();

        $order = $this->paidOrder();
        $refund = $this->pendingRefund($order);

        Livewire::actingAs($this->admin())
            ->test(RefundsIndex::class)
            ->set('rejectReason.'.$refund->id, 'Fuera del plazo de devolución.')
            ->call('reject', $refund->id)
            ->assertHasNoErrors();

        $refund->refresh();

        $this->assertSame(RefundStatus::REJECTED, $refund->status);
        $this->assertSame('Fuera del plazo de devolución.', $refund->resolution_notes);
        $this->assertSame(PaymentStatus::PAID, $order->payment->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_el_pedido_registra_la_solicitud_de_devolucion_y_queda_pendiente(): void
    {
        $order = $this->paidOrder();

        Livewire::actingAs($this->admin())
            ->test(OrderShow::class, ['order' => $order])
            ->set('refundReason', 'duplicado')
            ->set('refundAmount', '40.00')
            ->set('refundNotes', 'Se cobró dos veces la misma tarjeta.')
            ->call('requestRefund')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 40.00,
            'reason' => 'duplicado',
            'status' => RefundStatus::PENDING->value,
        ]);

        // La solicitud no toca la pasarela ni el estado del pago.
        $this->assertSame(PaymentStatus::PAID, $order->payment->fresh()->status);
    }

    public function test_la_solicitud_rechaza_un_monto_mayor_al_saldo_del_pago(): void
    {
        $order = $this->paidOrder();

        Livewire::actingAs($this->admin())
            ->test(OrderShow::class, ['order' => $order])
            ->set('refundReason', 'duplicado')
            ->set('refundAmount', '150.00')
            ->call('requestRefund')
            ->assertHasErrors('refundAmount');

        $this->assertDatabaseCount('refunds', 0);
    }
}
