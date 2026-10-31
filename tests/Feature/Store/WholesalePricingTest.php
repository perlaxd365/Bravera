<?php

namespace Tests\Feature\Store;

use App\Livewire\Admin\Catalog\Products\Form as ProductFormComponent;
use App\Livewire\Store\ProductDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Modules\Product\DTO\ProductVariantData;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class WholesalePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_uses_retail_price_below_wholesale_minimum(): void
    {
        [, $variant, $supplierVariant] = $this->productWithWholesalePricing();

        $cart = app(CartService::class);
        $item = $cart->add($variant->id, $supplierVariant->id, 1);

        $this->assertSame(100.0, (float) $item->unit_price);
        $this->assertSame(100.0, $cart->subtotal());
    }

    public function test_cart_applies_quantity_discount_tiers(): void
    {
        [, $variant, $supplierVariant] = $this->productWithWholesalePricing();

        $cart = app(CartService::class);

        $twoUnits = $cart->add($variant->id, $supplierVariant->id, 2);
        $this->assertSame(95.0, (float) $twoUnits->unit_price);

        $cart->updateQuantity($twoUnits->id, 3);
        $this->assertSame(90.0, (float) $twoUnits->fresh()->unit_price);

        $cart->updateQuantity($twoUnits->id, 5);
        $this->assertSame(85.0, (float) $twoUnits->fresh()->unit_price);
    }

    public function test_cart_uses_wholesale_price_at_wholesale_minimum(): void
    {
        [, $variant, $supplierVariant] = $this->productWithWholesalePricing();

        $cart = app(CartService::class);
        $item = $cart->add($variant->id, $supplierVariant->id, 12);

        $this->assertSame(80.0, (float) $item->unit_price);
        $this->assertSame(960.0, $cart->subtotal());
    }

    public function test_quantity_pricing_always_uses_the_lower_price(): void
    {
        $tierWins = new ProductVariant([
            'sale_price' => 100,
            'wholesale_price' => 95,
            'wholesale_min_quantity' => 3,
        ]);
        $wholesaleWins = new ProductVariant([
            'sale_price' => 100,
            'wholesale_price' => 80,
            'wholesale_min_quantity' => 3,
        ]);

        $this->assertSame(90.0, $tierWins->unitPriceForQuantity(3));
        $this->assertSame(80.0, $wholesaleWins->unitPriceForQuantity(3));
    }

    public function test_updating_cart_quantity_switches_between_wholesale_and_volume_prices(): void
    {
        [, $variant, $supplierVariant] = $this->productWithWholesalePricing();

        $cart = app(CartService::class);
        $item = $cart->add($variant->id, $supplierVariant->id, 12);

        $cart->updateQuantity($item->id, 5);
        $this->assertSame(85.0, (float) $item->fresh()->unit_price);

        $cart->updateQuantity($item->id, 15);
        $this->assertSame(80.0, (float) $item->fresh()->unit_price);
    }

    public function test_product_detail_shows_wholesale_price_and_threshold(): void
    {
        [$product] = $this->productWithWholesalePricing();

        Livewire::test(ProductDetail::class, ['slug' => $product->slug])
            ->assertSee('S/ 100.00')
            ->assertSee('Descuento desde la 2da unidad')
            ->assertSee('Lleva 2 unidades')
            ->assertSee('5% OFF')
            ->assertSee('Lleva 3 unidades')
            ->assertSee('10% OFF')
            ->assertSee('Lleva 5 unidades')
            ->assertSee('15% OFF')
            ->assertSee('Múltiples opciones de pago')
            ->assertSee('Retiro Express en 2 a 6 horas')
            ->assertSee('Precio al por mayor: S/ 80.00')
            ->assertSee('desde 12 unidades.')
            ->set('quantity', 12)
            ->assertSee('Estás comprando 12 unidades con precio al por mayor.');
    }

    public function test_variant_payload_keeps_wholesale_configuration(): void
    {
        $payload = ProductVariantData::fromArray([
            'product_id' => 1,
            'sku' => 'SKU-MAYOREO',
            'sale_price' => 100,
            'wholesale_price' => 80,
            'wholesale_min_quantity' => 12,
        ])->toArray();

        $this->assertSame(80.0, $payload['wholesale_price']);
        $this->assertSame(12, $payload['wholesale_min_quantity']);
    }

    public function test_admin_can_save_wholesale_configuration_on_variant(): void
    {
        $category = Category::create([
            'name' => 'Admin Mayoreo',
            'slug' => 'admin-mayoreo-'.Str::lower(Str::random(6)),
            'path' => 'admin-mayoreo',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto admin',
            'slug' => 'producto-admin-'.Str::lower(Str::random(6)),
            'status' => true,
            'is_visible' => false,
        ]);

        Livewire::test(ProductFormComponent::class)
            ->call('edit', $product->id)
            ->set('variantForm.sku', 'SKU-MAYOREO-ADMIN')
            ->set('variantForm.cost_price', 50)
            ->set('variantForm.sale_price', 100)
            ->set('variantForm.discount_percent', 0)
            ->set('variantForm.wholesale_price', 80)
            ->set('variantForm.wholesale_min_quantity', 12)
            ->call('saveVariant')
            ->assertHasNoErrors();

        $variant = ProductVariant::where('sku', 'SKU-MAYOREO-ADMIN')->first();

        $this->assertNotNull($variant);
        $this->assertSame('80.00', (string) $variant->wholesale_price);
        $this->assertSame(12, $variant->wholesale_min_quantity);
    }

    public function test_variant_prices_are_calculated_and_recalculate_relative_values(): void
    {
        $category = Category::create([
            'name' => 'Auto precios',
            'slug' => 'auto-precios-'.Str::lower(Str::random(6)),
            'path' => 'auto-precios',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto auto precios',
            'slug' => 'producto-auto-precios-'.Str::lower(Str::random(6)),
            'status' => true,
            'is_visible' => false,
        ]);

        $component = Livewire::test(ProductFormComponent::class)
            ->call('edit', $product->id)
            ->set('variantForm.sku', 'SKU-AUTO-PRICE')
            ->set('variantForm.cost_price', 30)
            ->assertSet('variantForm.sale_price', 100.0)
            ->assertSet('variantForm.retail_price', 50.0)
            ->assertSet('variantForm.discount_percent', 50.0)
            ->assertSet('variantForm.wholesale_price', 45.0)
            ->assertSet('variantForm.wholesale_min_quantity', 3)
            ->set('variantForm.cost_price', 40);

        $this->assertEqualsWithDelta(133.33, $component->get('variantForm.sale_price'), 0.001);
        $this->assertEqualsWithDelta(66.67, $component->get('variantForm.retail_price'), 0.001);
        $this->assertEqualsWithDelta(60.00, $component->get('variantForm.wholesale_price'), 0.001);

        $component
            ->set('variantForm.sale_price', 120)
            ->assertSet('variantForm.retail_price', 60.0)
            ->assertSet('variantForm.wholesale_price', 54.0)
            ->set('variantForm.retail_price', 55)
            ->call('saveVariant')
            ->assertHasNoErrors();

        $variant = ProductVariant::where('sku', 'SKU-AUTO-PRICE')->firstOrFail();

        $this->assertSame('55.00', (string) $variant->sale_price);
        $this->assertSame('120.00', (string) $variant->compare_price);
        $this->assertSame('49.50', (string) $variant->wholesale_price);
        $this->assertSame(54.17, (float) $variant->discount_percent);
        $this->assertSame(3, $variant->wholesale_min_quantity);
    }

    public function test_admin_rejects_wholesale_price_not_lower_than_retail(): void
    {
        $category = Category::create([
            'name' => 'Admin Validación',
            'slug' => 'admin-validacion-'.Str::lower(Str::random(6)),
            'path' => 'admin-validacion',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto validación',
            'slug' => 'producto-validacion-'.Str::lower(Str::random(6)),
            'status' => true,
            'is_visible' => false,
        ]);

        Livewire::test(ProductFormComponent::class)
            ->call('edit', $product->id)
            ->set('variantForm.sku', 'SKU-MAYOREO-INVALIDO')
            ->set('variantForm.cost_price', 50)
            ->set('variantForm.sale_price', 100)
            ->set('variantForm.wholesale_price', 100)
            ->set('variantForm.wholesale_min_quantity', 12)
            ->call('saveVariant')
            ->assertHasErrors(['variantForm.wholesale_price']);

        $this->assertDatabaseMissing('product_variants', [
            'sku' => 'SKU-MAYOREO-INVALIDO',
        ]);
    }

    /**
     * @return array{0: Product, 1: ProductVariant, 2: SupplierVariant}
     */
    private function productWithWholesalePricing(): array
    {
        $category = Category::create([
            'name' => 'Mayoreo',
            'slug' => 'mayoreo-'.Str::lower(Str::random(6)),
            'path' => 'mayoreo',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto de prueba',
            'slug' => 'producto-mayoreo-'.Str::lower(Str::random(6)),
            'status' => true,
            'is_visible' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'WHOLESALE-'.Str::upper(Str::random(8)),
            'sale_price' => 100,
            'wholesale_price' => 80,
            'wholesale_min_quantity' => 12,
            'is_default' => true,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-'.Str::upper(Str::random(6)),
            'business_name' => 'Proveedor de prueba',
            'status' => 'active',
        ]);

        $supplierVariant = SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'stock' => 100,
            'reserved_stock' => 0,
            'is_default' => true,
            'is_active' => true,
        ]);

        return [$product, $variant, $supplierVariant];
    }
}
