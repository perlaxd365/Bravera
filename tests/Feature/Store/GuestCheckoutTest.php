<?php

namespace Tests\Feature\Store;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Listeners\SendGuestAccountCredentials;
use App\Livewire\Store\Checkout;
use App\Mail\GuestAccountCredentialsMail;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\User;
use App\Modules\Ordering\Events\OrderPaid;
use App\Notifications\VerificationCodeNotification;
use App\Services\CartService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        config([
            'payments.culqi.public_key' => 'pk_test_checkout',
            'payments.culqi.secret_key' => 'sk_test_checkout',
        ]);
    }

    public function test_guest_sees_the_inline_checkout_without_a_previous_login_step(): void
    {
        $this->cartAsGuest();

        Livewire::test(Checkout::class)
            ->assertSee('Contacto')
            ->assertSee('Compra sin crear una contraseña')
            ->assertSee('Iniciar sesión')
            ->assertSee('Ahorro total')
            ->assertSee('20.00')
            ->assertSee('Total a pagar')
            ->assertSee('Compra protegida')
            ->assertDontSee('Compra como invitado');

        $this->assertGuest();
    }

    public function test_existing_email_can_verify_a_code_and_continue_without_a_password(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'Cliente Registrado',
            'email' => 'registrado@example.test',
            'phone' => '999777666',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_guest' => false,
        ]);

        $district = $this->district();
        $this->cartAsGuest();

        $component = Livewire::test(Checkout::class)
            ->set('guestFirstName', 'Otra')
            ->set('guestLastName', 'Persona')
            ->set('guestEmail', 'registrado@example.test')
            ->set('guestPhone', '987654321')
            ->assertSet('emailNeedsVerification', true);

        $code = Notification::sent($user, VerificationCodeNotification::class)->first()->code;

        $component
            ->set('checkoutVerificationCode', $code)
            ->call('verifyGuestEmailCode')
            ->assertHasNoErrors()
            ->assertSet('emailNeedsVerification', false)
            ->set('newDistrictId', $district->id)
            ->set('newAddress', 'Av. Siempre Viva 742')
            ->call('saveNewAddress')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::where('email', 'registrado@example.test')->count());
        $this->assertDatabaseHas('customer_addresses', [
            'user_id' => $user->id,
            'address' => 'Av. Siempre Viva 742',
        ]);
    }

    public function test_guest_is_created_and_can_save_an_address_without_manual_login(): void
    {
        $district = $this->district();
        $this->cartAsGuest();

        Livewire::test(Checkout::class)
            ->set('guestFirstName', 'Cliente')
            ->set('guestLastName', 'Invitado')
            ->set('guestEmail', 'invitado@example.test')
            ->set('guestPhone', '987654321')
            ->set('newDistrictId', $district->id)
            ->set('newAddress', 'Av. Siempre Viva 742')
            ->call('saveNewAddress')
            ->assertHasNoErrors();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'invitado@example.test',
            'name' => 'Cliente Invitado',
            'phone' => '987654321',
            'is_guest' => true,
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'full_name' => 'Cliente Invitado',
            'phone' => '987654321',
            'address' => 'Av. Siempre Viva 742',
            'location_id' => $district->id,
        ]);
    }

    public function test_guest_can_change_an_existing_email_and_use_a_new_one(): void
    {
        Notification::fake();

        User::create([
            'name' => 'Cuenta Existente',
            'email' => 'existente@example.test',
            'phone' => '999777666',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_guest' => false,
        ]);
        $district = $this->district();
        $this->cartAsGuest();

        Livewire::test(Checkout::class)
            ->set('guestFirstName', 'Cliente')
            ->set('guestLastName', 'Nuevo')
            ->set('guestEmail', 'existente@example.test')
            ->set('guestPhone', '987654321')
            ->assertSet('emailNeedsVerification', true)
            ->call('changeGuestEmail')
            ->assertSet('guestEmail', '')
            ->assertSet('emailNeedsVerification', false)
            ->set('guestEmail', 'nuevo@example.test')
            ->set('newDistrictId', $district->id)
            ->set('newAddress', 'Av. Nueva 123')
            ->call('saveNewAddress')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@example.test',
            'is_guest' => true,
        ]);
    }

    public function test_paid_guest_receives_credentials_and_becomes_a_regular_customer(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Cliente Invitado',
            'email' => 'invitado@example.test',
            'phone' => '987654321',
            'password' => bcrypt('temporary-unreachable-password'),
            'email_verified_at' => now(),
            'is_guest' => true,
        ]);

        $order = Order::create([
            'order_number' => 'BRV-TEST-GUEST',
            'user_id' => $user->id,
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
            'subtotal' => 100,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => 100,
            'cost_total' => 60,
            'customer_snapshot' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'currency' => 'PEN',
            'paid_at' => now(),
        ]);

        app(SendGuestAccountCredentials::class)->handle(new OrderPaid($order));

        $user->refresh();

        $this->assertFalse($user->is_guest);
        $this->assertTrue($user->hasRole('Cliente'));
        Mail::assertSent(GuestAccountCredentialsMail::class, function (GuestAccountCredentialsMail $mail) use ($user, $order): bool {
            return $mail->hasTo($user->email)
                && $mail->order->is($order)
                && Hash::check($mail->temporaryPassword, $user->password);
        });
    }

    private function cartAsGuest(): void
    {
        app(CartService::class)->add($this->productWithStock()->id);
    }

    private function productWithStock(): ProductVariant
    {
        $supplier = Supplier::create([
            'code' => 'SUP-GUEST-1',
            'business_name' => 'Proveedor Invitado S.A.C.',
            'email' => 'proveedor@example.test',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Categoría Invitado',
            'slug' => 'categoria-invitado',
            'path' => 'categoria-invitado',
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto Invitado',
            'slug' => 'producto-invitado',
            'status' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PV-GUEST-1',
            'cost_price' => 60,
            'sale_price' => 100,
            'compare_price' => 120,
            'is_active' => true,
        ]);

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'sku' => 'SV-GUEST-1',
            'supplier_sku' => 'SV-GUEST-1',
            'cost_price' => 60,
            'shipping_cost' => 12,
            'stock' => 10,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        return $variant;
    }

    private function district(): Location
    {
        $department = Location::create([
            'ubigeo' => '150000',
            'name' => 'Lima',
            'level' => 'department',
        ]);

        $province = Location::create([
            'ubigeo' => '150100',
            'name' => 'Lima',
            'level' => 'province',
            'parent_id' => $department->id,
        ]);

        return Location::create([
            'ubigeo' => '150101',
            'name' => 'Miraflores',
            'level' => 'district',
            'parent_id' => $province->id,
        ]);
    }
}
