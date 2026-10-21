<?php

namespace Tests\Feature\Seo;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_uses_editorial_category_order_and_brand_identity(): void
    {
        $this->seedEditorialCategories();

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('<span class="sr-only">Brevare: </span>', false);
        $html = $response->getContent();
        $schemas = $this->jsonLd($html);

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $labels = [];
        foreach ($xpath->query('//nav[@aria-label="Categorías"]//a') as $node) {
            $labels[] = trim($node->textContent);
        }

        $this->assertSame(
            ['Inicio', 'Navidad', 'Tecnología', 'Hogar', 'Ropa Mujer', 'Ropa Hombre', 'Ropa Niño'],
            array_slice($labels, 0, 7),
        );
        $this->assertStringStartsWith(
            'Brevare:',
            trim((string) $xpath->query('//h1')->item(0)?->textContent),
        );

        $categoryList = collect($schemas)->first(
            fn (array $schema) => ($schema['@type'] ?? null) === 'ItemList'
                && str_ends_with($schema['@id'] ?? '', '#categorias')
        );

        $this->assertNotNull($categoryList);
        $this->assertSame(
            ['Navidad', 'Tecnología', 'Hogar', 'Ropa Mujer', 'Ropa Hombre', 'Ropa Niño'],
            array_column($categoryList['itemListElement'], 'name'),
        );

        $organization = collect($schemas)->first(
            fn (array $schema) => ($schema['@type'] ?? null) === 'Organization'
        );

        $this->assertNotNull($organization);
        $this->assertSame('2026-10-17', $organization['foundingDate']);
        $this->assertSame(['Raúl Baca', 'Perla García'], array_column($organization['founder'], 'name'));
        $this->assertSame('Lima', $organization['address']['addressRegion']);
        $this->assertStringContainsString('favicon-192.png', $organization['logo']['url']);
    }

    public function test_home_uses_distinct_category_images_for_men_and_shoes(): void
    {
        $this->seedEditorialCategories();

        Category::updateOrCreate(['path' => 'calzado'], [
            'name' => 'Calzado',
            'slug' => 'calzado',
            'position' => 2,
            'is_visible' => true,
            'image' => null,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('pexels-photo-1043474.jpeg', false)
            ->assertSee('pexels-photo-2529148.jpeg', false);
    }

    public function test_about_page_exposes_founders_founding_date_and_office(): void
    {
        $this->seedEditorialCategories();

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Raúl Baca')
            ->assertSee('Perla García')
            ->assertSee('17/10/2026')
            ->assertSee('Lima, Perú')
            ->assertSee('rel="canonical" href="'.route('about').'"', false);

        $schemas = $this->jsonLd($this->get(route('about'))->getContent());
        $this->assertNotNull($this->firstSchema($schemas, 'AboutPage'));
    }

    public function test_category_page_has_unique_metadata_and_collection_schema(): void
    {
        $this->seedEditorialCategories();

        $response = $this->get(route('store.category', ['path' => 'tecnologia']))
            ->assertOk()
            ->assertSee('<title>Tecnología en Brevare | Compra online</title>', false)
            ->assertSee('Compra Tecnología en Brevare');

        $schemas = $this->jsonLd($response->getContent());
        $collection = $this->firstSchema($schemas, 'CollectionPage');
        $itemList = collect($schemas)->first(
            fn (array $schema) => ($schema['@type'] ?? null) === 'ItemList'
                && str_ends_with($schema['@id'] ?? '', '#itemlist')
        );

        $this->assertNotNull($collection);
        $this->assertNotNull($itemList);
        $this->assertSame(route('store.category', ['path' => 'tecnologia']), $collection['url']);
    }

    public function test_sitemap_prioritizes_editorial_categories_and_includes_about(): void
    {
        $this->seedEditorialCategories();

        $response = $this->get(route('sitemap'))->assertOk();
        $xml = simplexml_load_string($response->streamedContent());
        $locations = [];

        foreach ($xml->url as $url) {
            $locations[] = (string) $url->loc;
        }

        $this->assertContains(route('about'), $locations);

        $categoryLocations = array_values(array_filter(
            $locations,
            fn (string $location) => str_contains($location, '/categoria/'),
        ));

        $this->assertSame(route('store.category', ['path' => 'navidad']), $categoryLocations[0]);
        $this->assertSame(route('store.category', ['path' => 'tecnologia']), $categoryLocations[1]);
        $this->assertSame(route('store.category', ['path' => 'hogar']), $categoryLocations[2]);
    }

    public function test_product_page_has_product_breadcrumb_and_item_page_schema(): void
    {
        $this->seedEditorialCategories();
        [$product] = $this->productWithVariant('tecnologia', true);

        $response = $this->get(route('store.product', ['slug' => $product->slug]))->assertOk();
        $schemas = $this->jsonLd($response->getContent());
        $productSchema = $this->firstSchema($schemas, 'Product');
        $breadcrumb = $this->firstSchema($schemas, 'BreadcrumbList');
        $itemPage = $this->firstSchema($schemas, 'ItemPage');

        $this->assertNotNull($productSchema);
        $this->assertStringEndsWith('#product', $productSchema['@id']);
        $this->assertNotNull($breadcrumb);
        $this->assertNotNull($itemPage);
        $this->assertSame($productSchema['@id'], $itemPage['mainEntity']['@id']);
    }

    public function test_merchant_feed_includes_out_of_stock_identifier_and_category(): void
    {
        $this->seedEditorialCategories();
        [$product] = $this->productWithVariant('tecnologia', false);

        $feed = $this->get(route('google.merchant-feed'))->streamedContent();

        $this->assertStringContainsString('<g:availability>out_of_stock</g:availability>', $feed);
        $this->assertStringContainsString('<g:mpn>SKU-'.$product->id.'</g:mpn>', $feed);
        $this->assertStringContainsString('<g:identifier_exists>yes</g:identifier_exists>', $feed);
        $this->assertStringContainsString('<g:brand>Brevare</g:brand>', $feed);
        $this->assertStringContainsString('<g:google_product_category>Electronics</g:google_product_category>', $feed);
    }

    private function seedEditorialCategories(): void
    {
        $moda = Category::updateOrCreate(['path' => 'moda'], [
            'name' => 'Moda',
            'slug' => 'moda',
            'position' => 1,
            'is_visible' => true,
        ]);

        Category::updateOrCreate(['path' => 'moda/mujer'], [
            'name' => 'Moda Mujer',
            'slug' => 'mujer',
            'parent_id' => $moda->id,
            'position' => 1,
            'is_visible' => true,
        ]);

        Category::updateOrCreate(['path' => 'moda/hombre'], [
            'name' => 'Moda Hombre',
            'slug' => 'hombre',
            'parent_id' => $moda->id,
            'position' => 2,
            'is_visible' => true,
        ]);

        foreach ([
            ['Navidad', 'navidad', 1],
            ['Tecnología', 'tecnologia', 4],
            ['Hogar', 'hogar', 3],
            ['Niño', 'nino', 3],
            ['Juguetes', 'juguetes', 0],
        ] as [$name, $slug, $position]) {
            Category::updateOrCreate(['path' => $slug], [
                'name' => $name,
                'slug' => $slug,
                'position' => $position,
                'is_visible' => true,
            ]);
        }
    }

    /**
     * @return array{0: Product, 1: ProductVariant}
     */
    private function productWithVariant(string $categoryPath, bool $inStock): array
    {
        $category = Category::where('path', $categoryPath)->firstOrFail();

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto SEO '.Str::random(4),
            'slug' => 'producto-seo-'.Str::lower(Str::random(8)),
            'short_description' => 'Producto de prueba para datos estructurados.',
            'status' => true,
            'is_visible' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.$product->id,
            'sale_price' => 129.90,
            'is_default' => true,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-'.Str::upper(Str::random(6)),
            'business_name' => 'Proveedor SEO',
            'status' => 'active',
        ]);

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'stock' => $inStock ? 5 : 0,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_variant_id' => $variant->id,
            'public_id' => 'seo/'.Str::random(8),
            'file_name' => 'producto.jpg',
            'url' => 'https://images.example.test/producto.jpg',
            'secure_url' => 'https://images.example.test/producto.jpg',
            'is_primary' => true,
            'is_active' => true,
        ]);

        return [$product, $variant];
    }

    private function jsonLd(string $html): array
    {
        preg_match_all(
            '/<script type="application\/ld\+json">(.*?)<\/script>/s',
            $html,
            $matches,
        );

        return collect($matches[1] ?? [])
            ->map(fn (string $json) => json_decode(html_entity_decode($json), true))
            ->filter(fn ($schema) => is_array($schema))
            ->values()
            ->all();
    }

    private function firstSchema(array $schemas, string $type): ?array
    {
        return collect($schemas)->first(
            fn (array $schema) => ($schema['@type'] ?? null) === $type
        );
    }
}
