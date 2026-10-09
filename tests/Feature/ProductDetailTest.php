<?php

namespace Tests\Feature;

use App\Livewire\Store\ProductDetail;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
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
            ->assertSee('S/ 120.00')
            ->assertSee('metodos-de-pago.svg', false)
            ->assertSee('Pago 100% seguro');
    }

    public function test_no_se_puede_agregar_al_carrito_sin_seleccionar_talla(): void
    {
        [$product, $variantSmall] = $this->productWithSizes();

        Livewire::test(ProductDetail::class, ['slug' => $product->slug])
            ->assertSet('selectedVariantId', null)
            ->assertSet('selectedAttributes', [])
            ->call('addToCart')
            ->assertHasErrors(['selectedVariantId' => 'required'])
            ->assertSet('selectedVariantId', null);

        $this->assertSame(0, CartItem::count());
    }

    public function test_se_puede_agregar_al_carrito_tras_seleccionar_talla(): void
    {
        [$product, $variantSmall] = $this->productWithSizes();

        $tallaS = AttributeValue::where('value', 'S')->firstOrFail();

        Livewire::test(ProductDetail::class, ['slug' => $product->slug])
            ->set('selectedAttributes', [$tallaS->attribute_id => $tallaS->id])
            ->assertSet('selectedVariantId', $variantSmall->id)
            ->call('addToCart')
            ->assertHasNoErrors();

        $this->assertSame(1, CartItem::count());
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variantSmall->id,
            'quantity' => 1,
        ]);
    }

    public function test_no_se_puede_agregar_al_carrito_solo_con_color_si_hay_talla(): void
    {
        [$product, $attrs] = $this->productWithColorAndSize();
        $color = $attrs['color']['negro'];

        Livewire::test(ProductDetail::class, ['slug' => $product->slug])
            ->set('selectedAttributes', [$color->attribute_id => $color->id])
            ->call('addToCart')
            ->assertHasErrors(['selectedVariantId']);

        $this->assertCount(0, CartItem::all());
    }

    public function test_se_agrega_directo_producto_sin_atributos(): void
    {
        [$product] = $this->simpleProduct();

        Livewire::test(ProductDetail::class, ['slug' => $product->slug])
            ->call('addToCart')
            ->assertHasNoErrors();

        $this->assertCount(1, CartItem::all());
    }

    /**
     * Producto con atributo Talla (S/M) y stock disponible.
     *
     * @return array{0: Product, 1: ProductVariant}
     */
    private function productWithSizes(): array
    {
        $category = Category::create([
            'name' => 'Moda',
            'slug' => 'moda-'.Str::random(6),
            'path' => 'moda',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Polo Clásico',
            'slug' => 'polo-'.Str::random(6),
            'status' => true,
            'is_visible' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-'.Str::upper(Str::random(6)),
            'business_name' => 'Textilero Andino',
            'status' => 'active',
        ]);

        $talla = Attribute::create([
            'name' => 'Talla',
            'slug' => 'talla-'.Str::random(6),
            'type' => 'select',
            'is_filter' => true,
            'is_required' => true,
            'is_active' => true,
        ]);

        $sizes = [
            'S' => AttributeValue::create([
                'attribute_id' => $talla->id,
                'value' => 'S',
                'slug' => 's',
                'is_active' => true,
            ]),
            'M' => AttributeValue::create([
                'attribute_id' => $talla->id,
                'value' => 'M',
                'slug' => 'm',
                'is_active' => true,
            ]),
        ];

        $variants = [];

        foreach ($sizes as $label => $value) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => 'POLO-'.$label.'-'.Str::upper(Str::random(5)),
                'sale_price' => 79.9,
                'is_active' => true,
            ]);

            ProductVariantAttributeValue::create([
                'product_variant_id' => $variant->id,
                'attribute_id' => $talla->id,
                'attribute_value_id' => $value->id,
            ]);

            SupplierVariant::create([
                'supplier_id' => $supplier->id,
                'product_variant_id' => $variant->id,
                'stock' => 10,
                'reserved_stock' => 0,
                'is_active' => true,
            ]);

            $variants[] = $variant;
        }

        return [$product, $variants[0]];
    }

    /**
     * @return array{0: Product, 1: array}
     */
    private function productWithColorAndSize(): array
    {
        $category = Category::create([
            'name' => 'Ropa',
            'slug' => 'ropa-'.Str::random(6),
            'path' => 'ropa',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Camisa Test',
            'slug' => 'camisa-test-'.Str::random(6),
            'status' => true,
            'is_visible' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-'.Str::upper(Str::random(6)),
            'business_name' => 'Test Supplier',
            'status' => 'active',
        ]);

        $colorAttr = Attribute::create([
            'name' => 'Color',
            'slug' => 'color-'.Str::random(6),
            'type' => 'color',
            'is_filter' => true,
            'is_required' => true,
            'is_active' => true,
        ]);

        $tallaAttr = Attribute::create([
            'name' => 'Talla',
            'slug' => 'talla-'.Str::random(6),
            'type' => 'select',
            'is_filter' => true,
            'is_required' => true,
            'is_active' => true,
        ]);

        $negro = AttributeValue::create([
            'attribute_id' => $colorAttr->id,
            'value' => 'Negro',
            'slug' => 'negro',
            'is_active' => true,
        ]);

        $azul = AttributeValue::create([
            'attribute_id' => $colorAttr->id,
            'value' => 'Azul',
            'slug' => 'azul',
            'is_active' => true,
        ]);

        $s = AttributeValue::create([
            'attribute_id' => $tallaAttr->id,
            'value' => 'S',
            'slug' => 's',
            'is_active' => true,
        ]);

        $m = AttributeValue::create([
            'attribute_id' => $tallaAttr->id,
            'value' => 'M',
            'slug' => 'm',
            'is_active' => true,
        ]);

        foreach ([['negro', $negro], ['azul', $azul]] as $c) {
            foreach ([$s, $m] as $tval) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => 'TEST-'.$c[0].'-'.$tval->value.'-'.Str::upper(Str::random(3)),
                    'sale_price' => 100,
                    'is_active' => true,
                ]);

                ProductVariantAttributeValue::insert([
                    ['product_variant_id' => $variant->id, 'attribute_id' => $colorAttr->id, 'attribute_value_id' => $c[1]->id, 'created_at' => now(), 'updated_at' => now()],
                    ['product_variant_id' => $variant->id, 'attribute_id' => $tallaAttr->id, 'attribute_value_id' => $tval->id, 'created_at' => now(), 'updated_at' => now()],
                ]);

                SupplierVariant::create([
                    'supplier_id' => $supplier->id,
                    'product_variant_id' => $variant->id,
                    'stock' => 5,
                    'reserved_stock' => 0,
                    'is_active' => true,
                ]);
            }
        }

        return [$product, [
            'color' => ['negro' => $negro, 'azul' => $azul],
            'talla' => ['s' => $s, 'm' => $m],
        ]];
    }

    /**
     * @return array{0: Product}
     */
    private function simpleProduct(): array
    {
        $category = Category::create([
            'name' => 'Otros',
            'slug' => 'otros-'.Str::random(6),
            'path' => 'otros',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto Simple',
            'slug' => 'producto-simple-'.Str::random(6),
            'status' => true,
            'is_visible' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-'.Str::upper(Str::random(6)),
            'business_name' => 'Simple Supplier',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SIMPLE-'.Str::upper(Str::random(5)),
            'sale_price' => 50,
            'is_active' => true,
        ]);

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'stock' => 10,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        return [$product];
    }
}
