<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Repositories\SupplierRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalSellerTest extends TestCase
{
    use RefreshDatabase;

    public function test_brevare_is_a_non_public_supplier_and_stock_is_detected_on_product(): void
    {
        $seller = Supplier::query()->where('code', 'BREVARE')->firstOrFail();
        $this->assertTrue($seller->is_internal);

        $category = Category::create([
            'name' => 'Propios',
            'slug' => 'propios',
            'path' => 'propios',
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto Brevare',
            'slug' => 'producto-brevare',
            'status' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BR-OWN-1',
            'sale_price' => 49.90,
            'is_active' => true,
        ]);

        SupplierVariant::create([
            'supplier_id' => $seller->id,
            'product_variant_id' => $variant->id,
            'cost_price' => 20,
            'shipping_cost' => 9.90,
            'stock' => 3,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        $this->assertTrue($product->isSoldByBrevare());
        $this->assertSame(0, app(SupplierRepository::class)->paginate()->total());
    }
}
