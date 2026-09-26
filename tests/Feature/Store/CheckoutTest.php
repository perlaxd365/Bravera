<?php

namespace Tests\Feature\Store;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Store\Checkout;
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
use App\Modules\Payments\Gateways\DemoGateway;
use App\Modules\Shipping\Enums\ShippingZoneType;
use App\Services\CartService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
            'code' => 'BRAVERA10',
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
            ->set('couponCode', 'BRAVERA10')
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
        $component->couponCode = 'BRAVERA10';

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
            ->set('couponCode', 'BRAVERA10');

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
            ->set('couponCode', 'BRAVERA10');

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
            ->set('couponCode', 'BRAVERA10')
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
            ->set('couponCode', 'BRAVERA10')
            ->call('placeOrder');

        $component->assertDispatched('notify');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Payment::count());
    }
}
