<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\Catalog;
use App\Livewire\Store\ProductDetail;
use App\Models\CartItem;
use App\Models\Product;
use Database\Seeders\HolidayCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Verifica que el catálogo de temporada quede comprable en la tienda:
 * producto visible con foto, descripción y precio, y que se pueda agregar
 * al carrito desde la ficha de producto.
 */
class HolidayCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'corona-navidad-luces-led-40-cm';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HolidayCatalogSeeder::class);
    }

    public function test_publica_al_menos_cinco_productos_con_foto_precio_y_descripcion(): void
    {
        $products = Product::query()->active()->visible()->get();

        $this->assertGreaterThanOrEqual(5, $products->count());

        foreach ($products as $product) {
            $variant = $product->variants()->first();

            $this->assertNotNull($variant, "El producto «{$product->name}» no tiene variante.");
            $this->assertGreaterThan(0, (float) $variant->sale_price, "El producto «{$product->name}» no tiene precio.");
            $this->assertNotEmpty($product->short_description, "El producto «{$product->name}» no tiene resumen.");
            $this->assertNotEmpty($product->description, "El producto «{$product->name}» no tiene descripción.");
            $this->assertGreaterThan(0, $variant->images()->count(), "El producto «{$product->name}» no tiene foto.");
            $this->assertTrue($product->hasStock(), "El producto «{$product->name}» no tiene stock.");
        }
    }

    public function test_el_catalogo_lista_los_productos_con_su_precio(): void
    {
        Livewire::test(Catalog::class)
            ->assertSee('Corona Navideña con Luces LED 40 cm')
            ->assertSee('S/ 90.30');
    }

    public function test_la_busqueda_encuentra_productos_navidena_por_categoria_y_plural(): void
    {
        Livewire::test(Catalog::class)
            ->set('search', 'productos navideños')
            ->assertSee('Corona Navideña con Luces LED 40 cm')
            ->assertDontSee('No encontramos una coincidencia exacta');
    }

    public function test_una_busqueda_sin_resultados_siempre_muestra_productos_similares(): void
    {
        Livewire::test(Catalog::class)
            ->set('search', 'tetera')
            ->assertSee('No encontramos una coincidencia exacta')
            ->assertSee('También te puede interesar')
            ->assertSee('Corona Navideña con Luces LED 40 cm');
    }

    public function test_la_ficha_de_producto_muestra_precio_y_permite_agregar_al_carrito(): void
    {
        $component = Livewire::test(ProductDetail::class, ['slug' => self::SLUG])
            ->assertSee('Corona Navideña con Luces LED 40 cm')
            ->assertSee('S/ 90.30');

        $variantId = $component->get('selectedVariantId');

        $this->assertNotNull($variantId, 'La ficha no preseleccionó una variante comprable.');

        $component->call('addToCart')->assertHasNoErrors();

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variantId,
        ]);

        $this->assertGreaterThan(0, CartItem::query()->count());
    }

    public function test_volver_a_ejecutar_el_seeder_no_duplica_productos(): void
    {
        $before = Product::count();

        $this->seed(HolidayCatalogSeeder::class);

        $this->assertSame($before, Product::count());
    }
}
