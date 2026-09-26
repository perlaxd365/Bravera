<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_preselects_the_variant_that_has_images(): void
    {
        $category = Category::create([
            'name' => 'Electrónica',
            'slug' => 'electronica',
            'path' => 'electronica',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Teléfono Pro',
            'slug' => 'telefono-pro',
            'status' => true,
            'is_visible' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-'.Str::upper(Str::random(6)),
            'business_name' => 'Proveedor Oficial',
            'status' => 'active',
        ]);

        $noPhoto = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-SIN-FOTO',
            'sale_price' => 90,
            'is_default' => true,
            'is_active' => true,
        ]);

        $withPhoto = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-CON-FOTO',
            'sale_price' => 120,
            'is_active' => true,
        ]);

        foreach ([$noPhoto, $withPhoto] as $variant) {
            SupplierVariant::create([
                'supplier_id' => $supplier->id,
                'product_variant_id' => $variant->id,
                'stock' => 5,
                'reserved_stock' => 0,
                'is_active' => true,
            ]);
        }

        ProductImage::create([
            'product_variant_id' => $withPhoto->id,
            'public_id' => 'img-'.Str::random(8),
            'file_name' => 'foto.jpg',
            'url' => 'https://img.example.com/foto.jpg',
            'secure_url' => 'https://img.example.com/foto.jpg',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->get('/producto/'.$product->slug)
            ->assertOk()
            ->assertSee('img.example.com')
            ->assertSee('Proveedor Oficial')
            ->assertSee('S/ 120.00');
    }
}
