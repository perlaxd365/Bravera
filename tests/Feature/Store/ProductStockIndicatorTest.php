<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductStockIndicatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_stock_total_de_las_variantes_en_la_tarjeta(): void
    {
        [$product, $variants] = $this->productWithStock();

        $product->load([
            'brand',
            'category',
            'translations',
            'variants.images',
            'variants.attributeValues.attribute',
            'variants.attributeValues.value',
            'variants.supplierVariants.supplier',
        ]);

        $html = Blade::render(
            '@include("store.components.product-card", ["product" => $product])',
            ['product' => $product],
        );

        $this->assertSame(11, $product->availableStock());
        $this->assertStringContainsString('11 disponibles', $html);
        $this->assertStringContainsString('brevare-stock--available', $html);

        $variants[1]->supplierVariants()->update(['stock' => 0]);
        $variants[0]->supplierVariants()->update(['stock' => 5, 'reserved_stock' => 0]);

        $product->unsetRelation('variants');
        $product->load('variants.supplierVariants');

        $lowStockHtml = Blade::render(
            '@include("store.components.product-card", ["product" => $product])',
            ['product' => $product],
        );

        $this->assertSame(5, $product->availableStock());
        $this->assertStringContainsString('Quedan 5', $lowStockHtml);
        $this->assertStringContainsString('brevare-stock--low', $lowStockHtml);
    }

    /**
     * @return array{0: Product, 1: array<int, ProductVariant>}
     */
    private function productWithStock(): array
    {
        $category = Category::create([
            'name' => 'Decoración',
            'slug' => 'decoracion-stock',
            'path' => 'hogar/decoracion-stock',
            'is_visible' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Guirnalda con stock visible',
            'slug' => 'guirnalda-stock-visible',
            'short_description' => 'Guirnalda para probar el stock visible.',
            'description' => 'Descripción de prueba del indicador de stock.',
            'status' => true,
            'is_visible' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-STOCK',
            'business_name' => 'Proveedor de prueba',
            'status' => 'active',
        ]);

        $variants = collect([
            ['sku' => 'STOCK-3M', 'stock' => 12, 'reserved' => 4],
            ['sku' => 'STOCK-5M', 'stock' => 3, 'reserved' => 0],
        ])->map(function (array $data) use ($product, $supplier): ProductVariant {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $data['sku'],
                'sale_price' => 39.90,
                'is_default' => $data['sku'] === 'STOCK-3M',
                'is_active' => true,
            ]);

            SupplierVariant::create([
                'supplier_id' => $supplier->id,
                'product_variant_id' => $variant->id,
                'stock' => $data['stock'],
                'reserved_stock' => $data['reserved'],
                'is_active' => true,
            ]);

            return $variant;
        })->all();

        return [$product, $variants];
    }
}
