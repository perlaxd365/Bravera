<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\SupplierPaymentStatus;
use App\Livewire\Admin\Orders\Show;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderItemCancelledMail;
use App\Mail\OrderStatusChangedMail;
use App\Mail\SupplierOrderNotificationMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierPayment;
use App\Models\SupplierVariant;
use App\Models\User;
use App\Modules\Ordering\Events\OrderStatusChanged;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Los eventos ShouldDispatchAfterCommit esperan al commit, pero en tests
        // la transacción se revierte. Con un gestor de transacciones sin
        // transacciones pendientes, los callbacks se ejecutan de inmediato,
        // que es exactamente lo que ocurre en producción.
        $this->app->instance('db.transactions', new DatabaseTransactionsManager);

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

    /**
     * Pedido de 2 productos, cada uno de un proveedor distinto.
     */
    private function orderWithTwoProducts(): array
    {
        $customer = $this->customer();

        $order = Order::create([
            'order_number' => 'BRV-TEST-1001',
            'user_id' => $customer->id,
            'status' => OrderStatus::CONFIRMED->value,
            'payment_status' => PaymentStatus::PAID->value,
            'subtotal' => 200.00,
            'shipping_total' => 20.00,
            'discount_total' => 0.00,
            'total' => 220.00,
            'cost_total' => 120.00,
            'currency' => 'PEN',
            'customer_snapshot' => ['name' => 'Cliente Demo', 'email' => 'cliente@test.com'],
            'address_snapshot' => [
                'full_name' => 'Cliente Demo',
                'phone' => '999999999',
                'address' => 'Av. Siempre Viva 742',
                'location_label' => 'Lima > Lima > Miraflores',
            ],
        ]);

        $suppliers = collect(['Proveedor Uno S.A.C.', 'Proveedor Dos S.A.C.'])->map(
            fn ($name, $i) => Supplier::create([
                'code' => 'SUP-TEST-'.$i,
                'business_name' => $name,
                'email' => "proveedor{$i}@test.pe",
                'status' => 'active',
            ])
        );

        $items = collect([
            ['Producto Uno', 100.00, 50.00, 10.00],
            ['Producto Dos', 100.00, 50.00, 10.00],
        ])->map(function ($data, $i) use ($order, $suppliers) {
            $supplier = $suppliers[$i];

            $category = Category::create([
                'name' => 'Categoría Test '.$i,
                'slug' => 'categoria-test-'.$i,
                'path' => 'categoria-test-'.$i,
                'status' => true,
            ]);

            $product = Product::create([
                'category_id' => $category->id,
                'name' => $data[0],
                'slug' => 'producto-test-'.$i,
                'status' => true,
            ]);

            $productVariant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => 'PV-TEST-'.$i,
                'cost_price' => $data[2],
                'sale_price' => $data[1],
                'is_active' => true,
            ]);

            $variant = SupplierVariant::create([
                'supplier_id' => $supplier->id,
                'product_variant_id' => $productVariant->id,
                'sku' => 'SV-TEST-'.$i,
                'cost_price' => $data[2],
                'shipping_cost' => 0,
                'stock' => 10,
                'reserved_stock' => 0,
                'is_active' => true,
            ]);

            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variant_id' => $productVariant->id,
                'supplier_id' => $supplier->id,
                'supplier_variant_id' => $variant->id,
                'product_name' => $data[0],
                'supplier_name' => $supplier->business_name,
                'quantity' => 1,
                'status' => OrderItemStatus::ACTIVE->value,
                'unit_price' => $data[1],
                'unit_cost' => $data[2],
                'supplier_shipping_cost' => 0,
                'shipping_price' => $data[3],
                'line_subtotal' => $data[1],
                'line_cost_total' => $data[2],
            ]);

            $supplierOrder = SupplierOrder::create([
                'order_id' => $order->id,
                'supplier_id' => $supplier->id,
                'supplier_order_number' => 'SOP-TEST-'.($i + 1),
                'status' => SupplierOrderStatus::PENDING->value,
                'total_cost' => $data[2],
            ]);

            SupplierOrderItem::create([
                'supplier_order_id' => $supplierOrder->id,
                'order_item_id' => $item->id,
                'supplier_variant_id' => $variant->id,
                'quantity' => 1,
                'unit_cost' => $data[2],
                'supplier_shipping_cost' => 0,
                'line_cost_total' => $data[2],
            ]);

            return [$item, $supplierOrder, $variant];
        });

        return [$order->fresh('items'), $items, $suppliers];
    }

    public function test_supplier_payment_status_enum_is_autoloadable(): void
    {
        // El enum debe vivir en su propio archivo (PSR-4) o el pago a proveedor falla.
        $this->assertSame('paid', SupplierPaymentStatus::PAID->value);
        $this->assertSame('Pagado', SupplierPaymentStatus::PAID->label());
    }

    public function test_customer_confirmation_email_lists_every_product(): void
    {
        Mail::fake();

        [$order] = $this->orderWithTwoProducts();

        Mail::to($order->user->email)->send(new OrderConfirmationMail($order));

        Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) {
            $html = $mail->render();

            foreach (['Producto Uno', 'Producto Dos'] as $name) {
                $this->assertStringContainsString($name, $html);
            }

            return true;
        });
    }

    public function test_each_supplier_receives_only_its_own_products(): void
    {
        Mail::fake();

        [$order, $items, $suppliers] = $this->orderWithTwoProducts();

        foreach ($items as [$item, $supplierOrder]) {
            Mail::to($supplierOrder->supplier->email)
                ->send(new SupplierOrderNotificationMail($supplierOrder->load(['supplier', 'order', 'items.orderItem'])));
        }

        Mail::assertQueued(SupplierOrderNotificationMail::class, 2);

        $subjects = Mail::queued(SupplierOrderNotificationMail::class);

        $first = $subjects->first(fn ($m) => str_contains($m->render(), 'Producto Uno'));
        $second = $subjects->first(fn ($m) => str_contains($m->render(), 'Producto Dos'));

        $this->assertNotNull($first, 'El proveedor 1 debe recibir su producto.');
        $this->assertNotNull($second, 'El proveedor 2 debe recibir su producto.');

        // Cada proveedor solo ve su parte, nunca la del otro.
        $this->assertStringNotContainsString('Producto Dos', $first->render());
        $this->assertStringNotContainsString('Producto Uno', $second->render());

        // Y ambos correos referencian el pedido del cliente.
        $this->assertStringContainsString('BRV-TEST-1001', $first->render());
        $this->assertStringContainsString('BRV-TEST-1001', $second->render());
    }

    public function test_admin_can_register_supplier_payment_with_margin(): void
    {
        Mail::fake();

        [$order, $items] = $this->orderWithTwoProducts();
        [, [$item, $supplierOrder]] = $items->all();

        $this->actingAs($this->admin());

        Livewire::test(Show::class, ['order' => $order])
            ->set('paymentMethod.'.$supplierOrder->id, 'transferencia')
            ->set('paymentReference.'.$supplierOrder->id, 'OP-123')
            ->call('registerPayment', $supplierOrder->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_order_id' => $supplierOrder->id,
            'reference' => 'OP-123',
            'amount' => 50.00,
            'status' => 'paid',
        ]);

        $payment = SupplierPayment::where('supplier_order_id', $supplierOrder->id)->firstOrFail();

        // 100 (producto) + 10 (envío) - 50 (costo) = 60
        $this->assertEquals(60.00, (float) $payment->margin_amount);

        $this->assertDatabaseHas('supplier_orders', [
            'id' => $supplierOrder->id,
            'status' => 'accepted',
        ]);
    }

    public function test_status_change_sends_email_to_customer(): void
    {
        Mail::fake();

        [$order] = $this->orderWithTwoProducts();

        $this->actingAs($this->admin());

        Livewire::test(Show::class, ['order' => $order])
            ->set('currentStatus', OrderStatus::PROCESSING->value)
            ->call('updateStatus')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);

        Mail::assertQueued(OrderStatusChangedMail::class, function ($mail) use ($order) {
            return $mail->hasTo($order->user->email)
                && $mail->newStatus === OrderStatus::PROCESSING
                && $mail->previousStatus === OrderStatus::CONFIRMED;
        });
    }

    public function test_status_change_to_same_value_does_not_send_email(): void
    {
        Mail::fake();

        [$order] = $this->orderWithTwoProducts();

        $this->actingAs($this->admin());

        Livewire::test(Show::class, ['order' => $order])
            ->set('currentStatus', OrderStatus::CONFIRMED->value)
            ->call('updateStatus')
            ->assertHasNoErrors();

        Mail::assertNothingSent();
    }

    public function test_admin_can_cancel_a_single_product(): void
    {
        Mail::fake();

        [$order, $items] = $this->orderWithTwoProducts();
        [$first, $firstSupplierOrder, $variant] = $items[0];

        $this->actingAs($this->admin());

        Livewire::test(Show::class, ['order' => $order])
            ->set('cancelReason.'.$first->id, 'Cliente se arrepintió')
            ->call('cancelItem', $first->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('order_items', [
            'id' => $first->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Cliente se arrepintió',
        ]);

        // El pedido sigue activo con un solo producto.
        $order->refresh();
        $this->assertSame(OrderStatus::CONFIRMED, $order->status);
        $this->assertEquals(100.00, (float) $order->subtotal);
        $this->assertEquals(10.00, (float) $order->shipping_total);
        $this->assertEquals(110.00, (float) $order->total);

        // Se devuelve el stock al proveedor.
        $this->assertSame(11, (int) $variant->fresh()->stock);

        // La orden del proveedor sin items vivos se cancela.
        $this->assertDatabaseHas('supplier_orders', [
            'id' => $firstSupplierOrder->id,
            'status' => 'cancelled',
        ]);

        Mail::assertQueued(OrderItemCancelledMail::class, function ($mail) use ($order, $first) {
            return $mail->hasTo($order->user->email)
                && $mail->itemId === $first->id
                && $mail->wholeOrderCancelled === false;
        });
    }

    public function test_cancelling_every_product_cancels_the_whole_order(): void
    {
        Mail::fake();

        [$order, $items] = $this->orderWithTwoProducts();

        $this->actingAs($this->admin());

        Livewire::test(Show::class, ['order' => $order])
            ->call('cancelWholeOrder')
            ->assertHasNoErrors();

        $this->assertSame(0, $order->items()->where('status', 'active')->count());
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
        $this->assertNotNull($order->fresh()->cancelled_at);

        $this->assertEquals(0.00, (float) $order->fresh()->total);

        Mail::assertQueued(OrderStatusChangedMail::class, function ($mail) {
            return $mail->newStatus === OrderStatus::CANCELLED;
        });
    }

    public function test_admin_can_restore_a_cancelled_product(): void
    {
        Mail::fake();

        [$order, $items] = $this->orderWithTwoProducts();
        [$first, $firstSupplierOrder, $variant] = $items[0];

        $this->actingAs($this->admin());

        Livewire::test(Show::class, ['order' => $order])
            ->call('cancelItem', $first->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('supplier_orders', [
            'id' => $firstSupplierOrder->id,
            'status' => 'cancelled',
        ]);

        Livewire::test(Show::class, ['order' => $order])
            ->call('restoreItem', $first->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('order_items', [
            'id' => $first->id,
            'status' => 'active',
        ]);

        // Al restaurar el producto, la orden del proveedor vuelve a estar vigente.
        $this->assertDatabaseHas('supplier_orders', [
            'id' => $firstSupplierOrder->id,
            'status' => 'pending',
        ]);

        $order->refresh();
        $this->assertEquals(220.00, (float) $order->total);
        $this->assertSame(10, (int) $variant->fresh()->stock);
    }

    public function test_order_status_changed_event_is_dispatched(): void
    {
        Event::fake([OrderStatusChanged::class]);

        [$order] = $this->orderWithTwoProducts();

        $this->actingAs($this->admin());

        Livewire::test(Show::class, ['order' => $order])
            ->set('currentStatus', OrderStatus::SHIPPED->value)
            ->call('updateStatus')
            ->assertHasNoErrors();

        Event::assertDispatched(OrderStatusChanged::class, function ($event) use ($order) {
            return $event->order->is($order)
                && $event->from === OrderStatus::CONFIRMED
                && $event->to === OrderStatus::SHIPPED;
        });
    }
}
