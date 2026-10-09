<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeoAndCustomerAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesAndPermissionsSeeder::class, UserSeeder::class]);
    }

    public function test_product_seo_is_server_rendered_and_product_images_are_in_sitemap_and_feed(): void
    {
        $category = Category::create([
            'name' => 'Cámaras',
            'slug' => 'camaras',
            'path' => 'camaras',
            'is_visible' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cámara de prueba',
            'slug' => 'camara-de-prueba',
            'short_description' => 'Cámara ligera para fotografía diaria.',
            'status' => true,
            'is_visible' => true,
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-SEO-1',
            'business_name' => 'Proveedor SEO',
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CAM-SEO-1',
            'sale_price' => 1299.90,
            'is_default' => true,
            'is_active' => true,
        ]);
        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'stock' => 3,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);
        ProductImage::create([
            'product_variant_id' => $variant->id,
            'public_id' => 'test/camera',
            'file_name' => 'camera.jpg',
            'url' => 'https://images.example.test/camera.jpg',
            'secure_url' => 'https://images.example.test/camera.jpg',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->get(route('store.product', ['slug' => $product->slug]))
            ->assertOk()
            ->assertSee('Cámara de prueba | Brevare')
            ->assertSee('"@type":"Product"', false)
            ->assertSee('https://images.example.test/camera.jpg', false)
            ->assertSee('1299.90', false);

        $sitemapResponse = $this->get(route('sitemap'));
        $sitemapResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $sitemap = $sitemapResponse->streamedContent();
        $this->assertStringContainsString('/producto/camara-de-prueba', $sitemap);
        $this->assertStringContainsString('https://images.example.test/camera.jpg', $sitemap);

        $feedResponse = $this->get(route('google.merchant-feed'));
        $feedResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $feed = $feedResponse->streamedContent();
        $this->assertStringContainsString('<g:id>brv-v-'.$variant->id.'</g:id>', $feed);
        $this->assertStringContainsString('<g:price>1299.90 PEN</g:price>', $feed);
        $this->assertStringContainsString('/producto/camara-de-prueba?variant=CAM-SEO-1', $feed);

        $this->get(route('store.product', ['slug' => $product->slug]).'?variant=CAM-SEO-1')
            ->assertOk()
            ->assertSee('rel="canonical" href="'.route('store.product', ['slug' => $product->slug]).'?variant=CAM-SEO-1"', false);
    }

    public function test_admin_can_search_registered_customers_and_open_prefilled_whatsapp_followup(): void
    {
        $customer = User::create([
            'name' => 'María Cliente',
            'email' => 'maria@example.test',
            'phone' => '987654321',
            'password' => bcrypt('password'),
        ]);
        $customer->assignRole('Cliente');
        $order = Order::create([
            'order_number' => 'BVR-SEO-001',
            'user_id' => $customer->id,
            'status' => OrderStatus::SHIPPED->value,
            'payment_status' => PaymentStatus::PAID->value,
            'subtotal' => 100,
            'shipping_total' => 10,
            'discount_total' => 0,
            'total' => 110,
            'cost_total' => 70,
            'currency' => 'PEN',
        ]);

        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail())
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('María Cliente')
            ->assertSee('BVR-SEO-001');

        $expectedUrl = 'https://wa.me/51987654321?text='.rawurlencode(
            'Hola María Cliente, te escribimos de Brevare para informarte sobre tu pedido BVR-SEO-001. Su estado actual es: Enviado.'
        );

        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail());

        Livewire::test(\App\Livewire\Admin\Customers\Index::class)
            ->set('search', $order->order_number)
            ->assertSee('María Cliente')
            ->assertSee($expectedUrl, false);
    }

    public function test_customer_cannot_open_customer_admin_section(): void
    {
        $customer = User::create([
            'name' => 'Cliente Bloqueado',
            'email' => 'blocked@example.test',
            'password' => bcrypt('password'),
        ]);
        $customer->assignRole('Cliente');

        $this->actingAs($customer)
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }
}
