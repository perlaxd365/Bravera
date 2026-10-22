<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_suggestions_return_only_matching_in_stock_products(): void
    {
        [$available] = $this->product('Licuadora portátil', 'licuadora-portatil', 'LIC-001', 4);
        $this->product('Organizador de cocina', 'organizador-cocina', 'ORG-001', 3);
        $this->product('Licuadora sin stock', 'licuadora-sin-stock', 'LIC-002', 0);

        $this->getJson(route('store.search.suggestions', ['q' => 'licuadora']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Licuadora portátil')
            ->assertJsonPath('0.url', route('store.product', ['slug' => $available->slug]));
    }

    /**
     * @return array{0: Product, 1: ProductVariant}
     */
    private function product(string $name, string $slug, string $sku, int $stock): array
    {
        $category = Category::firstOrCreate(
            ['path' => 'tecnologia'],
            ['name' => 'Tecnología', 'slug' => 'tecnologia', 'is_visible' => true],
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
            'sku' => $sku,
            'sale_price' => 99,
            'cost_price' => 50,
            'is_active' => true,
            'is_default' => true,
        ]);

        $supplier = Supplier::firstOrCreate(
            ['code' => 'SUP-SEARCH'],
            ['business_name' => 'Brevare', 'status' => 'active'],
        );

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'stock' => $stock,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        return [$product, $variant];
    }
}
