<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\Dashboard\Index as DashboardIndex;
use App\Livewire\Admin\Dropshipping\Orders\Index;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
        ]);
    }

    private function admin(): User
    {
        return User::where('email', 'administracion@brevare.com')->firstOrFail();
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'Cliente Demo',
            'email' => 'cliente@test.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ])->assignRole('Cliente');
    }

    private function customerAt(Carbon $createdAt, string $email, bool $isGuest = false): User
    {
        $customer = User::factory()->create(['email' => $email]);
        $customer->forceFill([
            'created_at' => $createdAt,
            'is_guest' => $isGuest,
        ])->saveQuietly();
        $customer->assignCustomerRole();

        return $customer;
    }

    public function test_admin_can_open_panel_pages(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.catalog.brands.index'))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.dropshipping.orders.index'))->assertOk();
        $this->get(route('admin.discounts.coupons.index'))->assertOk();
    }

    public function test_dashboard_reports_daily_customer_registrations(): void
    {
        $this->travelTo(Carbon::parse('2026-10-21 12:00:00'));

        $this->customerAt(today()->subDays(3), 'antiguo@test.com');
        $this->customerAt(today()->subDay(), 'ayer@test.com');
        $this->customerAt(today(), 'hoy-uno@test.com');
        $this->customerAt(today(), 'hoy-dos@test.com');
        $this->customerAt(today(), 'invitado@test.com', isGuest: true);

        $this->actingAs($this->admin());

        Livewire::test(DashboardIndex::class)
            ->assertSee('Nuevos integrantes')
            ->assertSee('+1 vs. ayer')
            ->assertSee('4 en total')
            ->assertViewHas('newCustomers', fn (array $metric): bool => $metric['today'] === 2
                && $metric['yesterday'] === 1
                && $metric['total'] === 4
                && $metric['change'] === 1);
    }

    public function test_admin_can_open_order_detail(): void
    {
        $order = Order::create([
            'order_number' => 'BRV-TEST-0001',
            'user_id' => $this->customer()->id,
            'status' => OrderStatus::PENDING->value,
            'payment_status' => PaymentStatus::PENDING->value,
            'subtotal' => 100.00,
            'shipping_total' => 12.00,
            'discount_total' => 0.00,
            'total' => 112.00,
            'cost_total' => 60.00,
            'currency' => 'PEN',
            'customer_snapshot' => ['name' => 'Cliente Demo', 'email' => 'cliente@test.com'],
            'address_snapshot' => ['full_name' => 'Cliente Demo', 'phone' => '999999999'],
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('BRV-TEST-0001');
    }

    public function test_customer_cannot_open_panel(): void
    {
        $this->actingAs($this->customer());

        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_supplier_order_status_can_be_updated(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-TEST',
            'business_name' => 'Proveedor Test S.A.C.',
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'BRV-TEST-0002',
            'user_id' => $this->customer()->id,
            'status' => OrderStatus::CONFIRMED->value,
            'payment_status' => PaymentStatus::PAID->value,
            'subtotal' => 100.00,
            'shipping_total' => 10.00,
            'discount_total' => 0.00,
            'total' => 110.00,
            'cost_total' => 55.00,
            'currency' => 'PEN',
        ]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'supplier_order_number' => 'SOP-TEST-0001',
            'status' => 'pending',
            'total_cost' => 55.00,
        ]);

        $this->actingAs($this->admin());

        Livewire::test(Index::class)
            ->set('quickStatus.'.$supplierOrder->id, 'sent')
            ->call('updateStatus', $supplierOrder->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('supplier_orders', [
            'id' => $supplierOrder->id,
            'status' => 'sent',
        ]);
    }
}
