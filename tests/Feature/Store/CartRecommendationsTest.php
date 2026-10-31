<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\Cart\Index as CartIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_recommends_a_related_product_and_allows_adding_it(): void
    {
        $user = User::create([
            'name' => 'Cliente carrito',
            'email' => 'cart-recommendations@example.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $category = Category::create([
            'name' => 'Navidad',
            'slug' => 'navidad',
            'path' => 'navidad',
            'is_visible' => true,
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-CART-REC',
            'business_name' => 'Brevare',
            'status' => 'active',
            'is_internal' => true,
        ]);

        $mainVariant = $this->product($category, $supplier, 'Corona principal', 'corona-principal');
        $relatedVariant = $this->product($category, $supplier, 'Lazo relacionado', 'lazo-relacionado');
        $this->product($category, $supplier, 'Ramo relacionado', 'ramo-relacionado');

        app(CartService::class)->add($mainVariant->id, null, 1);

        $component = Livewire::test(CartIndex::class)
            ->assertSee('Completa tu compra')
            ->assertSee('Lazo relacionado')
            ->assertViewHas('recommendations', fn ($recommendations): bool => $recommendations->count() >= 2);

        $component->call('addRecommendedProduct', $relatedVariant->id)
            ->assertDispatched('cart-added')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $relatedVariant->id,
            'quantity' => 1,
        ]);
    }

    private function product(
        Category $category,
        Supplier $supplier,
        string $name,
        string $slug,
    ): ProductVariant {
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
            'sale_price' => 49.90,
            'cost_price' => 25,
            'is_active' => true,
            'is_default' => true,
        ]);

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'stock' => 20,
            'reserved_stock' => 0,
            'minimum_stock' => 0,
            'estimated_dispatch_days' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);

        return $variant;
    }
}
