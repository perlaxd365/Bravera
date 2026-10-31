<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Products\Index as ProductIndex;
use App\Livewire\Admin\Dashboard\Index as DashboardIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\User;
use App\Modules\Product\Services\ProductReadinessService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductReadinessTest extends TestCase
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

    public function test_a_complete_product_reaches_one_hundred_percent(): void
    {
        $product = $this->createReadyProduct();

        $readiness = app(ProductReadinessService::class)->assess($product);

        $this->assertSame(100, $readiness['percent']);
        $this->assertTrue($readiness['ready']);
        $this->assertSame([], $readiness['missing']);
    }

    public function test_readiness_is_visible_in_the_product_list_and_dashboard(): void
    {
        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail());

        $this->createReadyProduct();

        $incomplete = Product::create([
            'category_id' => Category::query()->firstOrFail()->id,
            'name' => 'Producto incompleto',
            'slug' => 'producto-incompleto',
            'status' => true,
            'is_visible' => false,
        ]);
        $readiness = app(ProductReadinessService::class)->assess($incomplete);

        Livewire::test(ProductIndex::class)
            ->assertSee('Publicación')
            ->assertSee($readiness['percent'].'%')
            ->assertSee('Falta:');

        Livewire::test(DashboardIndex::class)
            ->assertSee('Productos por publicar')
            ->assertViewHas('pendingPublication', 1);
    }

    private function createReadyProduct(): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'productos-listos'],
            [
                'name' => 'Productos listos',
                'path' => 'productos-listos',
                'is_visible' => true,
            ],
        );

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto listo',
            'slug' => 'producto-listo',
            'short_description' => 'Resumen listo para la tienda.',
            'description' => 'Descripción completa del producto.',
            'seo_title' => 'Producto listo | Brevare',
            'seo_description' => 'Compra este producto listo en Brevare.',
            'status' => true,
            'is_visible' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'READY-001',
            'sale_price' => 99,
            'cost_price' => 50,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductImage::create([
            'product_variant_id' => $variant->id,
            'public_id' => 'productos/ready-001',
            'file_name' => 'ready-001.jpg',
            'url' => 'https://res.cloudinary.com/demo/image/upload/v1/ready-001.jpg',
            'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/ready-001.jpg',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $supplier = Supplier::query()->firstOrCreate(
            ['code' => 'SUP-READY'],
            [
                'business_name' => 'Brevare',
                'status' => 'active',
                'is_internal' => true,
            ],
        );

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'supplier_sku' => 'READY-001',
            'cost_price' => 50,
            'shipping_cost' => 0,
            'stock' => 10,
            'reserved_stock' => 0,
            'minimum_stock' => 0,
            'estimated_dispatch_days' => 1,
            'is_active' => true,
        ]);

        return $product->fresh([
            'variants.images',
            'variants.supplierVariants.supplier',
        ]);
    }
}
