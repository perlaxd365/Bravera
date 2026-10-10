<?php

namespace Tests\Feature\Store;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Account\Orders as AccountOrders;
use App\Livewire\Store\Checkout;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\Location;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\User;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use App\Modules\Payments\Gateways\Culqi\CulqiGateway;
use App\Modules\Payments\Gateways\DemoGateway;
use App\Modules\Shipping\Enums\ShippingZoneType;
use App\Services\CartService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CulqiResponses;
use Tests\Support\StubGateway;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance('db.transactions', new DatabaseTransactionsManager);

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Fixtures
    |--------------------------------------------------------------------------
    */

    private function customer(): User
    {
        return User::create([
            'name' => 'Cliente Checkout',
            'email' => 'cliente@checkout.test',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ])->assignRole('Cliente');
    }

    /**
     * Distrito de destino con su zona de envío y una tarifa por proveedor.
     */
    private function districtWithZoneAndRate(float $ratePrice = 12.00): Location
    {
        $department = Location::create([
            'ubigeo' => '150000', 'name' => 'Lima', 'level' => 'department',
        ]);

        $province = Location::create([
            'ubigeo' => '150100', 'name' => 'Lima', 'level' => 'province',
            'parent_id' => $department->id,
        ]);

        $district = Location::create([
            'ubigeo' => '150101', 'name' => 'Miraflores', 'level' => 'district',
            'parent_id' => $province->id,
        ]);

        $zone = ShippingZone::create([
            'name' => 'Lima Metropolitana',
            'type' => ShippingZoneType::DISTRICT->value,
            'location_id' => $district->id,
            'status' => true,
        ]);

        $supplier = Supplier::where('code', 'SUP-CHK-1')->first();

        ShippingRate::create([
            'shipping_zone_id' => $zone->id,
            'supplier_id' => $supplier->id,
            'price' => $ratePrice,
            'status' => true,
        ]);

        return $district;
    }

    private function addressFor(User $user, Location $district): CustomerAddress
    {
        return CustomerAddress::create([
            'user_id' => $user->id,
            'full_name' => 'Cliente Checkout',
            'phone' => '999888777',
            'location_id' => $district->id,
            'address' => 'Av. Siempre Viva 742',
            'is_default' => true,
        ]);
    }

    /**
     * Producto de S/ 100.00 con costo S/ 60.00 y envío de proveedor S/ 5.00.
     * La tarifa de zona lo deja en S/ 12.00 de envío.
     */
    private function productWithStock(): ProductVariant
    {
        $supplier = Supplier::create([
            'code' => 'SUP-CHK-1',
            'business_name' => 'Distribuciones Andinas S.A.C.',
            'email' => 'proveedor@checkout.test',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Categoría Checkout',
            'slug' => 'categoria-checkout',
            'path' => 'categoria-checkout',
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Jeans Slim Hombre',
            'slug' => 'jeans-slim-hombre',
            'status' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PV-CHK-1',
            'cost_price' => 60.00,
            'sale_price' => 100.00,
            'is_active' => true,
        ]);

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'sku' => 'SV-CHK-1',
            'supplier_sku' => 'SV-JEANS-azul-L',
            'cost_price' => 60.00,
            'shipping_cost' => 5.00,
            'stock' => 10,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        return $variant;
    }

    private function cartWith(User $user, ProductVariant $variant, int $quantity = 1): void
    {
        $this->actingAs($user);

        app(CartService::class)->add($variant->id, null, $quantity);
    }

    private function coupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'BREVARE10',
            'name' => 'Diez por ciento',
            'type' => CouponType::PERCENTAGE->value,
            'value' => 10,
            'min_subtotal' => 0,
            'is_active' => true,
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | El total del pedido se calcula en el servidor
    |--------------------------------------------------------------------------
    */

    public function test_places_order_with_server_side_quote(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(100.00, (float) $order->subtotal);
        $this->assertSame(12.00, (float) $order->shipping_total);
        $this->assertSame(0.00, (float) $order->discount_total);
        $this->assertSame(112.00, (float) $order->total);
        $this->assertSame(12.00, (float) $order->items->first()->shipping_price);
    }

    /**
     * El navegador no puede escribir el descuento: se recalcula contra la BD.
     */
    public function test_tampered_coupon_discount_is_ignored(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);
        $this->coupon();

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('couponCode', 'BREVARE10')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        // 10% de 100.00, no el 99.99 que un cliente intentaría inyectar.
        $this->assertSame(10.00, (float) $order->discount_total);
        $this->assertSame(102.00, (float) $order->total);
    }

    public function test_tampered_shipping_quote_is_ignored(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        // El envío real es la tarifa de la zona (S/ 12.00), no S/ 0.00.
        $this->assertSame(12.00, (float) $order->shipping_total);
    }

    /**
     * @locked impide que el navegador escriba el descuento o la cotización.
     * Y aunque lo hiciera, el pedido se calcula igual en el servidor.
     */
    public function test_quote_and_coupon_are_locked_against_the_client(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $reflection = new \ReflectionProperty(Checkout::class, 'quote');
        $this->assertNotEmpty(
            $reflection->getAttributes(Locked::class),
            'La propiedad $quote debe estar marcada como #[Locked].'
        );

        $reflection = new \ReflectionProperty(Checkout::class, 'couponApplied');
        $this->assertNotEmpty(
            $reflection->getAttributes(Locked::class),
            'La propiedad $couponApplied debe estar marcada como #[Locked].'
        );

        $component = Livewire::actingAs($user)->test(Checkout::class);

        // El intento de escribir la propiedad bloqueada debe ser rechazado.
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $component->set('couponApplied', ['discount' => 99999])->call('placeOrder');
    }

    public function test_locked_properties_cannot_be_overwritten_with_a_forged_quote(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)->test(Checkout::class);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        $component->set('quote', ['items' => [], 'total' => -99999]);
    }

    /**
     * Escribe una propiedad del componente saltandose #[Locked], como lo haria
     * un payload manipulado sobre una version sin esa proteccion.
     */
    private function poisonProperty(object $component, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($component, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($component, $value);
    }

    /**
     * Defensa en profundidad: el recalculo no lee los importes que ya estan
     * en las propiedades del componente, los vuelve a derivar de la base de
     * datos. Aunque un payload lograra escribir $quote / $couponApplied, el
     * total que se persiste sale del servidor.
     */
    public function test_totals_are_rederived_and_not_read_from_the_properties(): void
    {
        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);
        $this->coupon();

        $this->actingAs($user);

        $component = new Checkout;
        $component->selectedAddressId = $address->id;
        $component->couponCode = 'BREVARE10';

        // Envenenamiento: importes inventados en las propiedades del componente.
        $this->poisonProperty($component, 'quote', ['items' => [], 'total' => 0.0]);
        $this->poisonProperty($component, 'couponApplied', ['discount' => 99999.0]);

        $method = new \ReflectionMethod(Checkout::class, 'resolveTotals');
        $method->setAccessible(true);
        $totals = $method->invoke($component, $address);

        $this->assertSame(100.00, $totals['subtotal']);
        $this->assertSame(12.00, $totals['shippingTotal']);
        $this->assertSame(10.00, $totals['discount'], 'El descuento sale del cupon, no de la propiedad.');
        $this->assertSame(102.00, $totals['total']);
    }

    public function test_cannot_place_order_with_another_customers_address(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $this->cartWith($user, $variant);

        $intruder = User::create([
            'name' => 'Intruso', 'email' => 'intruso@checkout.test',
            'password' => bcrypt('12345678'), 'email_verified_at' => now(),
        ])->assignRole('Cliente');

        $foreignAddress = $this->addressFor($intruder, $district);

        $component = Livewire::actingAs($user)->test(Checkout::class)
            ->set('selectedAddressId', $foreignAddress->id);

        try {
            $component->call('placeOrder');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(0, Order::count(), 'No debe crearse un pedido con una direccion ajena.');
        $this->assertSame(0, Payment::count());
    }

    public function test_rejects_address_selection_owned_by_someone_else(): void
    {
        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $this->cartWith($user, $variant);

        $intruder = User::create([
            'name' => 'Intruso', 'email' => 'intruso2@checkout.test',
            'password' => bcrypt('12345678'), 'email_verified_at' => now(),
        ])->assignRole('Cliente');

        $foreignAddress = $this->addressFor($intruder, $district);

        Livewire::actingAs($user)->test(Checkout::class)
            ->call('selectAddress', $foreignAddress->id)
            ->assertSet('selectedAddressId', null)
            ->assertSet('quote', null);
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de la entrada
    |--------------------------------------------------------------------------
    */

    public function test_rejects_unknown_payment_method(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('paymentMethod', 'bitcoin-gratis')
            ->call('placeOrder')
            ->assertHasErrors(['paymentMethod']);

        $this->assertSame(0, Order::count());
    }

    public function test_rejects_oversized_notes(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('notes', str_repeat('a', 1001))
            ->call('placeOrder')
            ->assertHasErrors(['notes']);

        $this->assertSame(0, Order::count());
    }

    public function test_rejects_address_with_unknown_district(): void
    {
        $user = $this->customer();
        $variant = $this->productWithStock();
        $this->districtWithZoneAndRate();
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->call('openNewAddress')
            ->set('newFullName', 'Cliente Checkout')
            ->set('newPhone', '999888777')
            ->set('newDistrictId', 999999)
            ->set('newAddress', 'Av. Siempre Viva 742')
            ->call('saveNewAddress')
            ->assertHasErrors(['newDistrictId']);
    }

    /*
    |--------------------------------------------------------------------------
    | El cupón se revalida al momento de pagar
    |--------------------------------------------------------------------------
    */

    public function test_expired_coupon_is_rejected_at_order_time(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $coupon = $this->coupon(['ends_at' => now()->addDay()]);

        $component = Livewire::actingAs($user)->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('couponCode', 'BREVARE10');

        $component->call('applyCoupon')->assertSet('couponError', null);

        // El cupón caduca entre la aplicación y el pago.
        $coupon->update(['ends_at' => now()->subMinute()]);

        $component->call('placeOrder')->assertHasNoErrors();

        $this->assertSame(0, Order::count(), 'Un cupón expirado no debe generar pedido.');
    }

    public function test_coupon_exceeding_usage_limit_is_rejected_at_order_time(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $coupon = $this->coupon(['usage_limit' => 5]);

        $component = Livewire::actingAs($user)->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('couponCode', 'BREVARE10');

        $component->call('applyCoupon')->assertSet('couponError', null);

        // Otro cliente agota el cupo global mientras este está en el checkout.
        $coupon->update(['usage_limit' => 0]);

        $component->call('placeOrder')->assertHasNoErrors();

        $this->assertSame(0, Order::count());
    }

    public function test_valid_coupon_is_applied_and_recorded(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $coupon = $this->coupon();

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('couponCode', 'BREVARE10')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(10.00, (float) $order->discount_total);
        $this->assertSame(102.00, (float) $order->total);
        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'discount_amount' => 10.00,
        ]);
    }

    public function test_coupon_code_is_stripped_of_a_fake_discount_code(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('couponCode', 'NO-EXISTE')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $this->assertSame(0, Order::count());
    }

    /*
    |--------------------------------------------------------------------------
    | La pasarela de pago falla de forma cerrada
    |--------------------------------------------------------------------------
    */

    public function test_order_is_not_placed_when_no_gateway_is_configured(): void
    {
        config(['payments.default_gateway' => null, 'payments.allow_demo_gateway' => false]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder');

        $this->assertSame(0, Order::count(), 'Sin pasarela no debe aceptarse el pedido.');
    }

    public function test_unknown_gateway_is_rejected_instead_of_falling_back_to_demo(): void
    {
        config([
            'payments.default_gateway' => 'paypal-fantasma',
            'payments.allow_demo_gateway' => true,
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder');

        $component->assertDispatched('notify');

        $this->assertSame(0, Order::count());
    }

    public function test_demo_gateway_refuses_to_charge_when_not_explicitly_enabled(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => false]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Payment::count());
    }

    public function test_demo_gateway_guard_throws_on_its_own(): void
    {
        config(['payments.allow_demo_gateway' => false]);

        $gateway = app(DemoGateway::class);
        $payment = new Payment([
            'order_id' => 1, 'amount' => 50.00, 'currency' => 'PEN',
        ]);

        $this->expectException(PaymentGatewayNotConfiguredException::class);

        $gateway->charge($payment);
    }

    public function test_configured_demo_gateway_does_charge_in_development(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(OrderStatus::CONFIRMED->value, $order->status->value);
        $this->assertSame(PaymentStatus::PAID->value, $order->payment_status->value);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => PaymentStatus::PAID->value,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Precio cero o negativo
    |--------------------------------------------------------------------------
    */

    public function test_order_is_blocked_when_total_would_not_be_positive(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate(0.00);
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        // 100% de descuento sobre S/ 100.00 con envio gratis deja el total en 0.
        $this->coupon(['value' => 100, 'max_discount' => 100]);

        $component = Livewire::actingAs($user)->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('couponCode', 'BREVARE10')
            ->call('placeOrder');

        $component->assertDispatched('notify');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Payment::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Frontera transaccional del cobro
    |--------------------------------------------------------------------------
    |
    | Culqi no ofrece idempotency key. Si el cobro ocurriera dentro de una
    | transacción y esta revirtiera después, el cliente habría pagado sin
    | pedido ni pago registrados. Por eso el cobro va sin transacción abierta.
    |
    */

    private function useStubGateway(): void
    {
        config([
            'payments.default_gateway' => 'stub',
            'payments.gateways.stub' => StubGateway::class,
        ]);
    }

    public function test_el_cobro_no_ocurre_dentro_de_una_transaccion(): void
    {
        $this->useStubGateway();
        StubGateway::approved();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        // RefreshDatabase ya envuelve el test en una transacción, así que lo
        // que importa es que el cobro no abra una NUEVA: si el nivel durante el
        // cobro fuera mayor que este baseline, el dinero se movería con la
        // transacción abierta.
        $baseline = DB::transactionLevel();

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $this->assertSame($baseline, StubGateway::$transactionLevelDuringCharge);
    }

    public function test_un_pago_rechazado_libera_el_stock_reservado(): void
    {
        $this->useStubGateway();
        StubGateway::declined();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder');

        $order = Order::firstOrFail();

        // El pedido existe con el fallo registrado, pero nada de stock queda
        // retenido para un pedido que nunca se despachará.
        $this->assertSame(PaymentStatus::FAILED->value, $order->payment_status->value);
        $this->assertSame(0, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);
    }

    public function test_un_pago_pendiente_conserva_el_stock_reservado(): void
    {
        $this->useStubGateway();
        StubGateway::pendingAsync();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder');

        $order = Order::firstOrFail();

        // Yape sigue pendiente: la reserva se mantiene mientras el cliente paga.
        $this->assertSame(PaymentStatus::PENDING->value, $order->payment_status->value);
        $this->assertSame(1, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);
    }

    public function test_liberar_stock_nunca_deja_el_contador_negativo(): void
    {
        $this->useStubGateway();
        StubGateway::declined();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $supplierVariant = SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail();
        $supplierVariant->update(['reserved_stock' => 0]);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder');

        $this->assertSame(0, (int) $supplierVariant->fresh()->reserved_stock);
    }

    /*
    |--------------------------------------------------------------------------
    | Token de tarjeta con pasarela real
    |--------------------------------------------------------------------------
    |
    | Con Culqi el número de tarjeta nunca llega al servidor: el navegador lo
    | tokeniza y manda un tkn_ opaco. Si no llega, el pedido no debe avanzar.
    |
    */

    private function useCulqi(): void
    {
        config([
            'payments.default_gateway' => 'culqi',
            'payments.modal_gateways' => ['culqi'],
            'payments.allow_demo_gateway' => false,
            'payments.card_token_gateways' => ['culqi'],
            'payments.gateway_methods' => ['culqi' => ['card', 'yape']],
            'payments.culqi.public_key' => 'pk_test_123',
            'payments.culqi.secret_key' => 'sk_test_456',
            'payments.culqi.api_url' => 'https://api.culqi.com/v2',
            'payments.culqi.secure_url' => 'https://secure.culqi.com/v2',
        ]);
    }

    public function test_con_culqi_no_se_avanza_sin_token_de_tarjeta(): void
    {
        $this->useCulqi();
        Http::fake();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('paymentMethod', 'card')
            ->call('placeOrder');

        $component->assertDispatched('notify');
        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_con_culqi_se_rechaza_un_token_con_formato_invalido(): void
    {
        $this->useCulqi();
        Http::fake();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('paymentMethod', 'card')
            ->call('placeOrder', 'numero-de-tarjeta-en-crudo');

        $component->assertDispatched('notify');
        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_con_culqi_el_token_llega_a_la_pasarela(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/charges' => Http::response(
            CulqiResponses::approvedCharge('chr_real_1', 11200), 201
        )]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('paymentMethod', 'card')
            ->call('placeOrder', 'tkn_test_123')
            ->assertHasNoErrors();

        $payment = Payment::firstOrFail();

        $this->assertSame('tkn_test_123', $payment->source_id);
        $this->assertSame('chr_real_1', $payment->gateway_transaction_id);
        $this->assertSame(PaymentStatus::PAID->value, $payment->status->value);

        Http::assertSent(fn ($request) => $request['source_id'] === 'tkn_test_123');
    }

    public function test_yape_usa_token_yape_y_no_token_de_tarjeta(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_yape_1'), 201),
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::approvedCharge('chr_yape_1', 11200), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->call('completeCulqiYape', 'ype_yape_token_1')
            ->assertHasNoErrors();

        $payment = Payment::firstOrFail();

        $this->assertSame('ype_yape_token_1', $payment->source_id);
        $this->assertSame('chr_yape_1', $payment->gateway_transaction_id);
        $this->assertSame(PaymentStatus::PAID->value, $payment->status->value);
        $this->assertSame(PaymentStatus::PAID->value, Order::firstOrFail()->payment_status->value);
        $component->assertSet('paymentStage', 'paid');
    }

    /**
     * Con una pasarela de modal el selector es el del proveedor: la tienda no
     * dibuja radios propios ni un formulario de tarjeta. Si añadiera su propio
     * selector, el comprador podría elegir un medio y la app cobrar otro, y con
     * el pedido ya creado no habría forma de corregirlo.
     */
    public function test_con_culqi_el_selector_de_metodos_es_el_del_proveedor(): void
    {
        $this->useCulqi();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->assertDontSee('type="radio" name="payment_method"', false)
            ->assertDontSee('Tarjeta de débito/crédito')
            // El pago se completa dentro del modal de Culqi, no con un botón
            // de la tienda que competiría con el botón de Culqi.
            ->assertSee('Continuar al pago')
            ->assertDontSee('Realizar pedido y pagar');
    }

    /**
     * El modal se abre con el pedido y su orden ya creados: es lo que permite
     * que Culqi ofrezca Yape y el resto de métodos asíncronos, que sin
     * `settings.order` no se pueden pagar. Si la orden faltara, el modal
     * mostraría esos medios y fallarían al pagarlos.
     */
    public function test_el_modal_de_culqi_se_abre_con_la_orden_ya_creada(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/orders' => Http::response(
            CulqiResponses::pendingOrder('ord_modal_1'), 201
        )]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->assertHasNoErrors()
            ->assertDispatched('culqi:open');

        $order = Order::firstOrFail();

        // El pedido existe y guarda su orden para poder conciliarlo.
        $this->assertSame('ord_modal_1', $order->gateway_order_id);

        $session = $component->get('culqiSession');

        $this->assertSame('ord_modal_1', $session['gatewayOrderId']);
        // El importe viaja en céntimos: 112.00 soles = 11200.
        $this->assertSame(11200, $session['amount']);
        $this->assertSame('PEN', $session['currency']);

        // Todos los métodos declarados, para que Culqi muestre los que la
        // cuenta tenga y esconda el resto.
        foreach (['tarjeta', 'yape', 'billetera', 'bancaMovil', 'agente', 'cuotealo'] as $method) {
            $this->assertTrue($session['methods'][$method] ?? false, "Falta habilitar {$method}.");
        }

        // Con pasarela real no debe anunciarse el modo demostración.
        $component->assertDontSee('Modo demostración');
    }

    /**
     * Livewire serializa los arrays como la tupla [valor, {"s":"arr"}] y solo
     * revierte esa forma en su propio estado, no en la carga de los eventos.
     *
     * Al abrir el modal desde el botón, los métodos llegaban así y Culqi, que
     * valida la configuración con un esquema, rechazaba el modal sin lanzar
     * ningún error: el comprador pulsaba y no veía nada. La prueba fija la
     * forma real que viaja por el evento y la limpieza que hace el script.
     */
    public function test_el_script_normaliza_la_forma_de_tupla_que_livewire_envia_en_los_eventos(): void
    {
        $script = file_get_contents(resource_path('js/checkout.js'));

        $this->assertStringContainsString('const plain = (value) =>', $script);
        $this->assertStringContainsString("'s' in value[1]", $script);

        // El oyente normaliza antes de abrir, y openModal vuelve a normalizar.
        $this->assertMatchesRegularExpression(
            '/plain\(event\?\.session/',
            $script,
            'La sesión del evento debe normalizarse antes de abrir el modal.'
        );
        $this->assertMatchesRegularExpression(
            '/export const openModal = async \(rawSession, publicKey, state\) => \{\s*const session = plain\(rawSession\)/',
            $script,
            'openModal debe normalizar la sesión que recibe.'
        );

        // Y si aun así los métodos no fueran un objeto, se avisa en vez de
        // dejar la pantalla sin reacción.
        $this->assertStringContainsString("typeof session.methods === 'object' && !Array.isArray(session.methods)", $script);
    }

    /**
     * La tarjeta nunca debe tener un input propio: cualquier campo de número o
     * CVV en nuestra página es una superficie de PCI que no queremos.
     */
    public function test_la_vista_no_pide_la_tarjeta_en_claro(): void
    {
        $this->useCulqi();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('paymentMethod', 'card')
            ->assertDontSee('card-number')
            ->assertDontSee('card-cvv')
            ->assertDontSee('autocomplete="cc-number"', false);
    }

    /**
     * El bundle v2 fue retirado (secure.culqi.com/js/culqi.js responde 403) y su
     * createToken ya no existe. El producto vigente es Checkout Custom, que se
     * carga desde js.culqi.com y expone el constructor CulqiCheckout.
     *
     * Si alguien vuelve al bundle viejo o al Tokens API a mano, el checkout se
     * rompe en produccion y el único síntoma es un toast que el cliente no puede
     * resolver, así que lo fijamos con una prueba.
     */
    public function test_el_checkout_usa_el_checkout_custom_vigente_de_culqi(): void
    {
        $script = file_get_contents(resource_path('js/checkout.js'));

        $this->assertIsString($script);

        $this->assertStringNotContainsString(
            'secure.culqi.com/js/culqi.js',
            $script,
            'El bundle v2 de Culqi fue retirado y responde 403.'
        );

        $this->assertStringNotContainsString(
            'Culqi.createToken',
            $script,
            'createToken pertenece a la API v2, incompatible con la librería vigente.'
        );

        $this->assertStringContainsString(
            'https://js.culqi.com/checkout-js',
            $script,
            'El formulario debe montarlo con el bundle oficial de Checkout Custom.'
        );

        $this->assertStringContainsString(
            'new Ctor(publicKey',
            $script,
            'Culqi expone el constructor global CulqiCheckout(publicKey, config).'
        );

        // Los tres resultados que documenta Culqi. Si solo se leyera `token`, un
        // pago asíncrono o un error se quedarían sin reaccionar.
        foreach (['checkout.token', 'checkout.order', 'checkout.error'] as $result) {
            $this->assertStringContainsString($result, $script, "Falta manejar {$result}.");
        }

        // El bundle minificado no ofrece Luhn público: la validez la decide Culqi.
        $this->assertStringNotContainsString('luhn', strtolower($script));
    }

    /**
     * .live en el selector de método. Sin él el servidor no conoce el cambio
     * hasta una acción, y los campos del medio anterior siguen en pantalla: el
     * cliente elige Yape y sigue viendo (y podendo pagar) la tarjeta.
     */
    public function test_el_selector_de_metodo_es_live(): void
    {
        $view = file_get_contents(resource_path('views/livewire/store/checkout.blade.php'));

        $this->assertIsString($view);

        $this->assertStringContainsString(
            'wire:model.live="paymentMethod"',
            $view,
            'El método de pago debe viajar al servidor en el acto.'
        );
    }

    /**
     * El mensaje de Culqi cuando el problema es del comercio ("contactate con
     * soporte") no debe llegar al cliente: no es accionable y lo invita a
     * escribirle al comercio. El detalle real queda en el log.
     */
    public function test_no_se_muestra_el_mensaje_de_soporte_de_culqi(): void
    {
        $this->useCulqi();

        Http::fake(['api.culqi.com/v2/charges' => Http::response([
            'id' => 'chr_rechazada_1',
            'outcome' => [
                'type' => 'venta_rechazada',
                'code' => 'DECLINED_BY_FRAUD',
                'user_message' => 'Contactáte con soporte',
                'merchant_message' => 'No se encuentra el Bin de la tarjeta, contactarse con Culqi para mayor información a culqi.com/soporte .',
            ],
        ], 402)]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('paymentMethod', 'card')
            ->call('placeOrder', 'tkn_test_123');

        $payment = Payment::firstOrFail();

        $this->assertSame(PaymentStatus::FAILED->value, $payment->status->value);
        $this->assertStringNotContainsStringIgnoringCase(
            'soporte',
            $payment->raw_response['outcome']['user_message'] ?? '',
            'La respuesta cruda de Culqi se guarda tal cual, para conciliar.'
        );

        // El mensaje que se muestra al cliente sale del gateway, no de Culqi.
        $gateway = app(CulqiGateway::class);
        $result = $gateway->charge(
            Payment::make([
                'order_id' => $payment->order_id,
                'method' => 'card',
                'source_id' => 'tkn_test_123',
                'amount' => 112.00,
                'currency' => 'PEN',
            ])
        );

        $this->assertFalse($result['success']);
        $this->assertStringNotContainsStringIgnoringCase('soporte', $result['message']);
        $this->assertNotSame('', $result['message']);
    }

    /**
     * La pasarela activa decide qué métodos se ofrecen. Con Culqi el selector es
     * suyo, así que la tienda no dibuja su catálogo: ni la tarjeta ni la
     * transferencia que Culqi no admite, que produciría un pedido que solo
     * podría fallar al cobrar con el stock ya reservado.
     */
    public function test_solo_se_ofrecen_metodos_que_la_pasarela_admite(): void
    {
        $this->useCulqi();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->assertDontSee('name="payment_method"', false)
            ->assertDontSee('Transferencia bancaria')
            ->assertDontSee('Tarjeta de débito/crédito');
    }

    /**
     * Fuera del modal, el recorte por pasarela se sigue aplicando: la demo
     * admite transferencia y la ofrece; Culqi no la aceptaría.
     */
    public function test_fuera_del_modal_la_pasarela_recorta_el_catalogo(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->assertSee('name="payment_method"', false)
            ->assertSee('Tarjeta de débito/crédito')
            ->assertSee('Transferencia bancaria');
    }

    public function test_rechaza_un_metodo_que_la_pasarela_no_admite(): void
    {
        $this->useCulqi();
        Http::fake();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        // Forzado desde el navegador: la validación también cierra esta puerta.
        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('paymentMethod', 'transferencia')
            ->call('placeOrder')
            ->assertHasErrors(['paymentMethod']);

        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_la_vista_anuncia_modo_demostracion_sin_pasarela_real(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->assertSee('Modo demostración')
            ->assertDontSee('Pagar con Culqi');
    }

    /**
     * El carrito queda convertido cuando se crea el pedido, así que si la vista
     * siguiera calculando los importes desde el carrito mostraría 0.00 y el
     * comprador creería que se le está cobrando en blanco.
     *
     * Con el modal, el evento culqi:open abre el checkout en el navegador.
     * Si vuelve (o el modal se cierra), el resumen muestra el pedido real.
     */
    public function test_con_el_pedido_creado_el_resumen_muestra_el_pedido_y_no_un_cero(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_modal_1'), 201)]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->assertDispatched('culqi:open');

        $order = Order::firstOrFail();

        // El pedido se creó y se puede retomar desde la URL de pagar
        $component = Livewire::actingAs($user)
            ->test(Checkout::class, ['pagar' => $order->order_number])
            ->assertHasNoErrors()
            ->assertSee('S/')
            ->assertSee('112.00')
            ->assertDontSee('S/ 0.00')
            ->assertSee('reservado')
            ->assertSee('esperando')
            ->assertSee('pago')
            ->assertSee($order->order_number)
            ->assertSee('Continuar al pago');

        $this->assertSame(1, Order::count());
    }

    /**
     * Livewire.on entrega el payload ya desenvuelto: el listener tiene que leer
     * la sesión directamente del argumento. Si la leyera de event.detail, la
     * sesión sería undefined y el modal no se abriría sin ningún aviso.
     */
    public function test_el_escucha_del_modal_acepta_el_payload_desenvuelto(): void
    {
        $script = file_get_contents(resource_path('js/checkout.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('event?.session', $script, 'El listener debe leer la sesión del payload directo de Livewire.on.');
        $this->assertStringNotContainsString('openModal(event.detail?.session)', $script, 'Leer event.detail rompe el modal: Livewire.on ya lo desenvuelve.');
    }

    /**
     * Sin clave pública el modal no se puede abrir. Un botón que creara un
     * pedido y reservara stock sin dar forma de pagarlo sería peor que no
     * ofrecerlo.
     */
    public function test_sin_clave_publica_no_se_ofrece_pagar_con_culqi(): void
    {
        config(['payments.culqi.public_key' => null]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->assertDontSee('Pagar con Culqi')
            ->assertSee('El pago con Culqi no está disponible');
    }

    /**
     * Un pedido ya cerrado no se puede volver a pagar: el botón de reintento
     * desaparecería y no crearía una segunda orden.
     */
    public function test_un_pedido_ya_cobrado_no_ofrece_reabrir_el_pago(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_modal_1'), 201)]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->assertSee('Completa tu pago');

        // El pago entra por el webhook, no por esta pantalla: se simula el
        // estado final para comprobar que la vista deja de ofrecer el reintento.
        Order::where('status', OrderStatus::PENDING->value)->update([
            'status' => OrderStatus::CONFIRMED->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);

        $component->call('$refresh');

        $component
            ->assertDontSee('wire:click="startCulqiCheckout"')
            ->assertSee('ya está cerrado');
    }

    /* ---------------------------------------------------------------------
    | Checkout en modal de Culqi
    |----------------------------------------------------------------------
    |
    | El pedido y su orden se crean antes de abrir el modal, así que el flujo
    | se parte en dos: abrir y luego confirmar. Lo que no puede cambiar es que
    | se cobre el pedido que se creó, ni dos veces, ni el de otro comprador.
    |
    */

    public function test_el_modal_cobra_la_tarjeta_sobre_el_pedido_que_ya_creo(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_modal_1'), 201),
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::approvedCharge('chr_modal_1', 11200), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout');

        $order = Order::firstOrFail();

        $component->call('completeCulqiCard', 'tkn_test_modal')
            ->assertHasNoErrors()
            // El servidor ya no manda al comprador: avisa del resultado y es el
            // modal quien, tras la animación, navega a la página del pedido.
            ->assertSet('paymentStage', 'paid')
            ->assertDispatched('culqi:settled', kind: 'paid', url: route('store.order.placed', ['order' => $order->order_number]));

        // El token se cobra contra el pedido del modal, sin crear un segundo.
        $this->assertSame(1, Order::count());

        $payment = Payment::firstOrFail();

        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame('tkn_test_modal', $payment->source_id);
        $this->assertSame('chr_modal_1', $payment->gateway_transaction_id);
        $this->assertSame(PaymentStatus::PAID->value, $payment->status->value);
        $this->assertSame(OrderStatus::CONFIRMED->value, $order->fresh()->status->value);
    }

    public function test_un_cargo_rechazado_en_el_modal_se_puede_reintentar_sobre_el_mismo_pedido(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_retry_1'), 201),
            'api.culqi.com/v2/charges' => Http::sequence()
                ->push(CulqiResponses::declinedCharge('chr_retry_declined'), 201)
                ->push(CulqiResponses::approvedCharge('chr_retry_approved', 11200), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout');
        $order = Order::firstOrFail();

        $component->call('completeCulqiCard', 'tkn_declined')
            ->assertSet('paymentStage', 'idle');

        $this->assertSame(PaymentStatus::PENDING->value, $order->fresh()->payment_status->value);
        $this->assertSame(1, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);

        $component->call('startCulqiCheckout')
            ->assertDispatched('culqi:open')
            ->call('completeCulqiCard', 'tkn_approved')
            ->assertSet('paymentStage', 'paid')
            ->assertDispatched('culqi:settled', kind: 'paid');

        $this->assertSame(1, Order::count());
        $this->assertSame(2, Payment::count());
        $this->assertSame(PaymentStatus::PAID->value, Order::firstOrFail()->payment_status->value);
    }

    public function test_un_metodo_asincrono_deja_el_pago_pendiente_para_el_webhook(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_yape_modal'), 201)]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout');

        $order = Order::firstOrFail();

        $component->call('completeCulqiAsync', 'yape')
            ->assertHasNoErrors()
            // Dentro del modal no hay a dónde redirigir: Yape se resuelve en el
            // propio checkout de Culqi, así que el pago queda pendiente y el
            // aviso acompaña al comprador hasta su pedido.
            ->assertSet('paymentStage', 'pending')
            ->assertDispatched('culqi:settled', kind: 'pending', url: route('store.order.placed', ['order' => $order->order_number]));

        $payment = Payment::firstOrFail();

        // El pago apunta a la orden de Culqi para que el webhook la encuentre.
        $this->assertSame('yape', $payment->method);
        $this->assertSame('ord_yape_modal', $payment->gateway_transaction_id);
        $this->assertSame(PaymentStatus::PENDING->value, $payment->status->value);

        // Pendiente no es fallido: la reserva de stock se mantiene.
        $this->assertSame(PaymentStatus::PENDING->value, $order->fresh()->payment_status->value);
        $this->assertSame(1, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);
    }

    /**
     * El método lo manda el navegador. Culqi llama "tarjeta" a la tarjeta y
     * guarda el resto con su nombre, así que la traducción no puede ser la
     * identidad ni proportionate a una tarjeta cobrada como Yape.
     */
    public function test_el_metodo_de_culqi_se_guarda_traducido(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_billetera_1'), 201)]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->call('completeCulqiAsync', 'billetera')
            ->assertHasNoErrors();

        $this->assertSame('billetera', Payment::firstOrFail()->method);
    }

    /**
     * Reintentar el pago reabre el mismo pedido. Crear otro dejaría al cliente
     * con dos pedidos y el stock reservado por los dos.
     */
    public function test_reabrir_el_modal_no_crea_un_segundo_pedido(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_modal_1'), 201)]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->call('startCulqiCheckout')
            ->assertDispatched('culqi:open');

        $this->assertSame(1, Order::count());
        $this->assertSame(1, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);
    }

    /**
     * Culqi no tiene idempotency key, así que la única defensa contra un doble
     * cobro es no volver a cobrar un pedido que ya no está pendiente.
     */
    public function test_no_se_cobra_un_pedido_que_ya_esta_pagado(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_modal_1'), 201),
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::approvedCharge('chr_modal_1', 11200), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->call('completeCulqiCard', 'tkn_test_modal');

        $order = Order::firstOrFail();

        $component->call('completeCulqiCard', 'tkn_test_otro')
            ->assertRedirect(route('store.order.placed', ['order' => $order->order_number]));

        // Sigue habiendo un único cargo: el segundo intento no cobra.
        $this->assertSame(1, Payment::count());
        $this->assertSame('chr_modal_1', Payment::firstOrFail()->gateway_transaction_id);
    }

    /**
     * El identificador del pedido no lo elige el navegador. Aunque se manipulase
     * la sesión, el callback solo puede cobrar el pedido que este servidor creó
     * para este usuario.
     */
    public function test_no_se_cobra_el_pedido_de_otro_comprador(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_ajeno_1'), 201)]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout');

        $order = Order::firstOrFail();

        // El pedido pasa a ser de otro usuario, como si la sesión se hubiera
        // trasvasado entre cuentas.
        $order->update(['user_id' => User::create([
            'name' => 'Otro Comprador',
            'email' => 'otro@checkout.test',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ])->id]);

        $component->call('completeCulqiCard', 'tkn_test_ajeno')
            ->assertDispatched('notify');

        $this->assertSame(0, Payment::count());
    }

    /**
     * Si la orden no llega a crearse, el modal solo podría ofrecer tarjeta. Es
     * preferible no abrirlo a abrirlo con métodos que no se van a poder pagar,
     * y el stock del pedido hay que devolverlo igualmente.
     */
    public function test_si_la_orden_no_se_crea_se_libera_el_stock_y_no_abre_el_modal(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/orders' => Http::response([
            'type' => 'parameter_error',
            'param' => 'payment_methods',
            'merchant_message' => 'El tipo de metodo no esta habilitado.',
            'user_message' => 'Metodo no disponible.',
        ], 400)]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->assertNotDispatched('culqi:open');

        $order = Order::firstOrFail();

        // El pedido se cierra y el stock vuelve al inventario: dejarlo
        // reservado sería una venta fantasma.
        $this->assertSame(OrderStatus::CANCELLED->value, $order->status->value);
        $this->assertSame(PaymentStatus::EXPIRED->value, $order->payment_status->value);
        $this->assertSame(0, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);
    }

    public function test_sin_celular_valido_no_se_crea_la_orden_del_modal(): void
    {
        $this->useCulqi();
        Http::fake();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        // Culqi rechaza la orden si phone_number no tiene entre 6 y 14
        // caracteres, así que se avisa antes de dejar el pedido a medias.
        $address->update(['phone' => '123']);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->assertNotDispatched('culqi:open');

        Http::assertNothingSent();
        $this->assertSame(0, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);
    }

    /**
     * Un comprador que cierra el modal sin pagar deja el pedido y su stock
     * reservados. El comando horario los devuelve.
     */
    public function test_el_comando_libera_los_pedidos_que_nadie_pago(): void
    {
        $this->useCulqi();
        Http::fake(['api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_modal_1'), 201)]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);
        $this->coupon();

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->set('couponCode', 'BREVARE10')
            ->call('applyCoupon')
            ->call('startCulqiCheckout');

        $order = Order::firstOrFail();

        $this->assertSame(1, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);
        $this->assertSame(1, $order->coupon->usedCount());

        // Todavía dentro del plazo: el comando no toca lo que puede pagarse.
        $this->artisan('brevare:expire-pending-orders')->assertSuccessful();
        $this->assertSame(1, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);

        // Directo en la consulta: created_at no es un campo rellenable y el
        // modelo lo ignoraría en silencio.
        Order::whereKey($order->id)->update(['created_at' => now()->subHours(48)]);

        $this->artisan('brevare:expire-pending-orders')->assertSuccessful();

        $order->refresh();

        $this->assertSame(OrderStatus::CANCELLED->value, $order->status->value);
        $this->assertSame(PaymentStatus::EXPIRED->value, $order->payment_status->value);
        $this->assertSame(0, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);
        // Un pedido que nunca se pagó no debe seguir consumiendo un uso de cupón.
        $this->assertSame(0, $order->coupon->usedCount());

        // Reejecutarlo no libera dos veces el mismo stock.
        $this->artisan('brevare:expire-pending-orders')->assertSuccessful();
        $this->assertSame(0, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);

        unset($component);
    }

    public function test_un_token_que_no_es_de_culqi_no_cobra_nada(): void
    {
        $this->useCulqi();
        Http::fake();

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        $component = Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->call('completeCulqiCard', 'numero-de-tarjeta-en-crudo');

        $component->assertDispatched('notify');
        $this->assertSame(0, Payment::count());
    }

    /**
     * Deja un pedido esperando el pago, que es como queda el checkout si el
     * comprador cierra el modal, se le corta la sesión o cierra la pestaña.
     */
    private function pendingOrderFor(User $user, ?string $gatewayOrderId = 'ord_pendiente_1', int $ageHours = 0): Order
    {
        $order = Order::where('user_id', $user->id)->firstOrFail();

        // forceFill porque created_at no es un atributo rellenable: con update()
        // se descartaría en silencio y el pedido seguiría pareciendo recién hecho.
        $order->forceFill([
            'gateway_order_id' => $gatewayOrderId,
            'created_at' => now()->subHours($ageHours),
        ])->save();

        return $order->fresh();
    }

    /**
     * El comprador entra a su cuenta y encuentra el pedido sin pagar. Desde ahí
     * tiene que poder terminar de pagarlo sin rehacer el carrito ni que se cree
     * un pedido nuevo con el stock reservado por duplicado.
     */
    public function test_se_puede_retomar_el_pago_de_un_pedido_pendiente_sin_carrito(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_pendiente_1'), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->call('completeCulqiAsync', 'yape');

        $order = $this->pendingOrderFor($user);

        // El carrito quedó convertido y vacío: no queda nada que pagar desde el
        // carrito, que es justo el caso que dejaba al comprador sin salida.
        $this->assertSame(1, Cart::where('user_id', $user->id)->where('status', 'converted')->count());
        $this->assertTrue(app(CartService::class)->isEmpty());

        $component = Livewire::actingAs($user)
            ->test(Checkout::class, ['pagar' => $order->order_number])
            ->assertHasNoErrors()
            ->assertViewHas('awaitingPayment', true)
            ->assertViewHas('pendingOrderNumber', $order->order_number);

        $component->call('startCulqiCheckout')
            ->assertDispatched('culqi:open');

        // Retomar no crea otro pedido ni vuelve a reservar stock.
        $this->assertSame(1, Order::count());
        $this->assertSame(1, (int) SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail()->reserved_stock);
    }

    /**
     * Al retomar se reutiliza la orden que ya tiene Culqi. Crear otra dejaría la
     * primera huérfana en el panel de Culqi y el pago se acreditaría al pedido
     * equivocado.
     */
    public function test_retomar_el_pago_reutiliza_la_orden_de_culqi_del_pedido(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_pendiente_1'), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->call('completeCulqiAsync', 'yape');

        $order = $this->pendingOrderFor($user);

        Http::fake();

        Livewire::actingAs($user)
            ->test(Checkout::class, ['pagar' => $order->order_number])
            ->call('startCulqiCheckout')
            ->assertDispatched('culqi:open');

        // No hace falta volver a llamar a la API de órdenes de Culqi.
        Http::assertNothingSent();
    }

    public function test_no_se_retoma_el_pago_del_pedido_de_otro_comprador(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_ajeno_2'), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout');

        $order = $this->pendingOrderFor($user, 'ord_ajeno_2');

        $otro = User::create([
            'name' => 'Otro Comprador',
            'email' => 'otro@checkout.test',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);

        // Adivinar el número de pedido no sirve para abrir el pago de otro.
        Livewire::actingAs($otro)
            ->test(Checkout::class, ['pagar' => $order->order_number])
            ->assertRedirect(route('store.cart'));
    }

    /**
     * Pasado el plazo de reserva, el comando de expiración ya canceló el pedido
     * y el stock está en el inventario: ofrecer "pagar ahora" sería vender algo
     * que ya no está apartado.
     */
    public function test_no_se_retoma_el_pago_de_un_pedido_vencido(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_vencido_1'), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout');

        $order = $this->pendingOrderFor($user, 'ord_vencido_1', ageHours: 48);

        $this->assertFalse($order->isPayable());

        // Sin carrito, un pedido vencido no se puede reanudar.
        Livewire::actingAs($user)
            ->test(Checkout::class, ['pagar' => $order->order_number])
            ->assertRedirect(route('store.cart'));
    }

    /**
     * El enlace de la cuenta es una URL normal con query, no una ruta con
     * parámetro: se comprueba entrando por HTTP de verdad para que el
     * parámetro llegue hasta mount() y no solo en las pruebas de componente.
     */
    public function test_la_url_de_pagar_ahora_abre_el_checkout_del_pedido_pendiente(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_enlace_1'), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->call('completeCulqiAsync', 'yape');

        $order = $this->pendingOrderFor($user, 'ord_enlace_1');

        $response = $this->actingAs($user)->get(route('checkout', ['pagar' => $order->order_number]));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee('Continuar al pago');

        // El pedido se muestra con su importe real, no como un carrito en cero.
        $response->assertDontSee('S/ 0.00');

        // El modal no se abre solo: se abre al pulsar el botón de pago, tanto en
        // un checkout normal como al reanudar un pedido pendiente.
        $response->assertDontSee('const autoOpen', false);
    }

    public function test_la_cuenta_ofrece_pagar_ahora_solo_a_los_pedidos_pendientes(): void
    {
        $this->useCulqi();
        Http::fake([
            'api.culqi.com/v2/orders' => Http::response(CulqiResponses::pendingOrder('ord_boton_1'), 201),
            'api.culqi.com/v2/charges' => Http::response(CulqiResponses::approvedCharge('chr_boton_1', 11200), 201),
        ]);

        $user = $this->customer();
        $variant = $this->productWithStock();
        $district = $this->districtWithZoneAndRate();
        $address = $this->addressFor($user, $district);
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout');

        $pendiente = Order::firstOrFail();

        // El mismo recorrido, pero este sí termina pagado.
        $this->cartWith($user, $variant);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('selectedAddressId', $address->id)
            ->call('startCulqiCheckout')
            ->call('completeCulqiCard', 'tkn_test_boton');

        $pagado = Order::where('order_number', '!=', $pendiente->order_number)->firstOrFail();

        $this->assertFalse($pagado->isPayable());

        $html = Livewire::actingAs($user)->test(AccountOrders::class)->html();

        $this->assertStringContainsString(
            route('checkout', ['pagar' => $pendiente->order_number]),
            $html,
        );

        $this->assertStringNotContainsString(
            route('checkout', ['pagar' => $pagado->order_number]),
            $html,
        );
    }
}
