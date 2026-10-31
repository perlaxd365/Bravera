<?php

namespace Tests\Feature\Store;

use App\Livewire\Account\Points;
use App\Livewire\Admin\Points\Index as AdminPoints;
use App\Livewire\Store\Home;
use App\Mail\PointsRedeemedMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\PointRedemption;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PointsAndFavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_points_ignore_shipping_and_are_awarded_once(): void
    {
        $user = $this->customer();
        $order = Order::create([
            'order_number' => 'BVR-PTS-001',
            'user_id' => $user->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 180,
            'shipping_total' => 25,
            'discount_total' => 20,
            'total' => 185,
            'cost_total' => 80,
            'currency' => 'PEN',
        ]);

        $transaction = app(PointsService::class)->awardOrder($order);

        $this->assertNotNull($transaction);
        $this->assertSame(160, $transaction->points);
        $this->assertSame(160, (int) $user->fresh()->points_balance);
        $this->assertNotNull($order->fresh()->points_awarded_at);

        $this->assertNull(app(PointsService::class)->awardOrder($order->fresh()));
        $this->assertSame(160, (int) $user->fresh()->points_balance);
    }

    public function test_redemption_requires_minimum_and_refund_restores_points(): void
    {
        $admin = $this->customer('admin-points@example.test');
        $user = $this->customer('canje@example.test');
        $user->forceFill(['points_balance' => 1000])->save();

        $service = app(PointsService::class);
        $redemption = $service->redeem($user, 1000);

        $this->assertSame(0, (int) $user->fresh()->points_balance);
        $this->assertSame('pending', $redemption->status);
        $this->assertSame(50.0, (float) $redemption->amount);

        $service->reject($redemption->fresh(), $admin, 'Datos incompletos');

        $this->assertSame(1000, (int) $user->fresh()->points_balance);
        $this->assertSame('rejected', $redemption->fresh()->status);
    }

    public function test_points_page_sends_request_email_and_creates_pending_redemption(): void
    {
        Mail::fake();

        $user = $this->customer('puntos@example.test');
        $user->forceFill(['points_balance' => 2000])->save();

        Livewire::actingAs($user)->test(Points::class)
            ->assertSee('Brevare Puntos')
            ->set('pointsToRedeem', 1000)
            ->call('redeem')
            ->assertHasNoErrors();

        $this->assertSame(1000, (int) $user->fresh()->points_balance);
        $this->assertDatabaseHas('point_redemptions', [
            'user_id' => $user->id,
            'status' => 'pending',
            'points' => 1000,
        ]);
        Mail::assertSent(PointsRedeemedMail::class);
    }

    public function test_favorites_can_be_toggled_and_listed(): void
    {
        $user = $this->customer('favoritos@example.test');
        $product = $this->product('Producto Favorito', 'producto-favorito');

        $this->actingAs($user)
            ->post(route('favorites.toggle', $product))
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->get(route('account.favorites'))
            ->assertOk()
            ->assertSee('Mi cuenta')
            ->assertSee('Navegación móvil')
            ->assertSee('Producto Favorito');

        $this->actingAs($user)
            ->post(route('favorites.toggle', $product))
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_admin_can_validate_and_mark_a_redemption_as_paid(): void
    {
        Mail::fake();

        $admin = $this->customer('points-admin@example.test');
        $user = $this->customer('canje-admin@example.test');
        $user->forceFill(['points_balance' => 1000])->save();
        $redemption = app(PointsService::class)->redeem($user, 1000);

        Livewire::actingAs($admin)->test(AdminPoints::class)
            ->call('approve', $redemption->id)
            ->assertHasNoErrors()
            ->call('markPaid', $redemption->id, 'DEP-001')
            ->assertHasNoErrors();

        $redemption->refresh();
        $this->assertSame(PointRedemption::STATUS_PAID, $redemption->status);
        $this->assertSame('DEP-001', $redemption->payout_reference);
    }

    public function test_offers_page_only_lists_discounted_products(): void
    {
        $this->product('Oferta Real', 'oferta-real', 160, 355);
        $this->product('Precio Normal', 'precio-normal', 100, null);

        $this->get(route('store.offers'))
            ->assertOk()
            ->assertSee('Oferta Real')
            ->assertDontSee('Precio Normal');
    }

    public function test_home_includes_mobile_navigation_whatsapp_and_animated_strip(): void
    {
        $christmasCategory = Category::create([
            'name' => 'Navidad',
            'slug' => 'navidad',
            'path' => 'navidad',
            'is_visible' => true,
        ]);
        $product = $this->product('Corona navideña', 'corona-navidena');
        $product->update(['category_id' => $christmasCategory->id]);
        $this->attachPrimaryImage($product);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Navegación móvil')
            ->assertSee('Menú Brevare')
            ->assertSee('Navegación principal móvil')
            ->assertSee('wa.me', false)
            ->assertSee('data-desktop-whatsapp', false)
            ->assertSee('home-promo-ticker', false)
            ->assertSee('Corona navideña')
            ->assertDontSee('home-product-rotation', false)
            ->assertSee('min-h-[459px]', false);
    }

    public function test_home_promo_strip_lists_the_latest_available_products(): void
    {
        $older = $this->product('Producto anterior', 'producto-anterior');
        $older->forceFill(['created_at' => now()->subDay()])->saveQuietly();
        $this->attachPrimaryImage($older);

        $newest = $this->product('Producto recién agregado', 'producto-recien-agregado');
        $this->attachPrimaryImage($newest);

        Livewire::test(Home::class)
            ->assertViewHas('promoTickerItems', function (array $items): bool {
                return array_column($items, 'name') === [
                    'Producto recién agregado',
                    'Producto anterior',
                ] && $items[0]['eyebrow'] === 'Nuevo · Ofertas test';
            });
    }

    private function attachPrimaryImage(Product $product): void
    {
        $variant = $product->variants()->firstOrFail();

        ProductImage::create([
            'product_variant_id' => $variant->id,
            'public_id' => 'productos/'.$product->slug,
            'file_name' => $product->slug.'.jpg',
            'url' => 'https://res.cloudinary.com/demo/image/upload/v1/'.$product->slug.'.jpg',
            'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/'.$product->slug.'.jpg',
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    private function customer(string $email = 'puntos-base@example.test'): User
    {
        return User::create([
            'name' => 'Cliente Puntos',
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    private function product(
        string $name,
        string $slug,
        float $salePrice = 100,
        ?float $comparePrice = null,
    ): Product {
        $category = Category::firstOrCreate(
            ['path' => 'ofertas-test'],
            ['name' => 'Ofertas test', 'slug' => 'ofertas-test', 'is_visible' => true],
        );

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'status' => true,
            'is_visible' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.strtoupper(substr(md5($slug), 0, 8)),
            'sale_price' => $salePrice,
            'compare_price' => $comparePrice,
            'is_default' => true,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'PTS-'.strtoupper(substr(md5($slug.'-supplier'), 0, 8)),
            'business_name' => 'Proveedor Puntos',
            'status' => 'active',
        ]);

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'stock' => 10,
            'reserved_stock' => 0,
            'is_default' => true,
            'is_active' => true,
        ]);

        return $product;
    }
}
