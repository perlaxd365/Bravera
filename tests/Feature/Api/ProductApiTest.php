<?php

namespace Tests\Feature\Api;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Services\CloudinaryImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'api-test-password';

    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.product_api.password', self::PASSWORD);
    }

    public function test_rechaza_peticiones_sin_password(): void
    {
        $this->postJson('/api/v1/products', [])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Contraseña de API inválida o ausente.');
    }

    public function test_publica_producto_completo_con_variante_proveedor_e_imagen(): void
    {
        $category = $this->createCategory();
        $this->createSupplier();
        Brand::create([
            'name' => 'Casa Joven',
            'slug' => 'casa-joven',
            'is_active' => true,
        ]);
        $this->mockCloudinaryUpload();

        $response = $this->postJson(
            '/api/v1/products?password='.self::PASSWORD,
            $this->payload($category),
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.ready_for_publication', true)
            ->assertJsonPath('data.missing', [])
            ->assertJsonPath('data.allows_shalom_cod', true)
            ->assertJsonPath('data.variants.0.sku', 'BVR-API-GUIR-3M')
            ->assertJsonPath('data.variants.0.available_stock', 50);

        $product = Product::where('slug', 'guirnalda-api')->firstOrFail();
        $variant = ProductVariant::where('sku', 'BVR-API-GUIR-3M')->firstOrFail();
        $supplierVariant = SupplierVariant::where('product_variant_id', $variant->id)->firstOrFail();

        $this->assertTrue((bool) $product->status);
        $this->assertTrue((bool) $product->is_visible);
        $this->assertSame(38.90, (float) $variant->sale_price);
        $this->assertSame(49.90, (float) $variant->compare_price);
        $this->assertSame(22.04, (float) $variant->discount_percent);
        $this->assertSame(50, (int) $supplierVariant->stock);
        $this->assertSame(1, ProductImage::where('product_variant_id', $variant->id)->count());
        $this->assertTrue(AttributeValue::where('value', '3 m')->exists());

        $this->getJson('/api/v1/products/guirnalda-api?password='.self::PASSWORD)
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.ready_for_publication', true);
    }

    public function test_no_crea_producto_si_falta_la_imagen(): void
    {
        $category = $this->createCategory();
        $this->createSupplier();
        $payload = $this->payload($category);
        $payload['variants'][0]['images'] = [];

        $this->postJson('/api/v1/products?password='.self::PASSWORD, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variants.0.images');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_no_crea_producto_sin_stock_disponible(): void
    {
        $category = $this->createCategory();
        $this->createSupplier();
        $payload = $this->payload($category);
        $payload['variants'][0]['supplier']['stock'] = 0;

        $this->postJson('/api/v1/products?password='.self::PASSWORD, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variants.0.supplier.stock');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_acepta_varios_valores_en_un_atributo_sin_romper_el_formato_anterior(): void
    {
        $category = $this->createCategory();
        $this->createSupplier();
        Brand::create([
            'name' => 'Casa Joven',
            'slug' => 'casa-joven',
            'is_active' => true,
        ]);
        $this->mockCloudinaryUpload();

        $payload = $this->payload($category);
        $payload['variants'][0]['sku'] = 'BVR-API-GUIR-MULTI';
        $payload['variants'][0]['attributes'] = [
            'Talla' => ['S', 'M'],
            'Color' => 'Cálido',
        ];

        $this->postJson('/api/v1/products?password='.self::PASSWORD, $payload)
            ->assertCreated()
            ->assertJsonPath('data.variants.0.attributes.Talla', ['S', 'M'])
            ->assertJsonPath('data.variants.0.attributes.Color', 'Cálido');

        $variant = ProductVariant::where('sku', 'BVR-API-GUIR-MULTI')->firstOrFail();

        $this->assertSame(
            2,
            ProductVariantAttributeValue::where('product_variant_id', $variant->id)
                ->whereHas('attribute', fn ($query) => $query->where('name', 'Talla'))
                ->count(),
        );
        $this->assertSame(
            1,
            ProductVariantAttributeValue::where('product_variant_id', $variant->id)
                ->whereHas('attribute', fn ($query) => $query->where('name', 'Color'))
                ->count(),
        );
    }

    public function test_crea_proveedor_y_opciones_de_catalogo_para_la_ia(): void
    {
        $this->createCategory();

        $this->postJson('/api/v1/suppliers?password='.self::PASSWORD, [
            'business_name' => 'Proveedor API SAC',
            'trade_name' => 'Proveedor API',
            'email' => 'proveedor@example.com',
            'estimated_dispatch_days' => 2,
        ])
            ->assertCreated()
            ->assertJsonPath('data.business_name', 'Proveedor API SAC')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('suppliers', [
            'business_name' => 'Proveedor API SAC',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/catalog/options?password='.self::PASSWORD)
            ->assertOk()
            ->assertJsonPath('categories.0.path', 'hogar/decoracion')
            ->assertJsonPath('limits.max_image_bytes', 5 * 1024 * 1024)
            ->assertJsonFragment(['business_name' => 'Proveedor API SAC']);
    }

    private function createCategory(): Category
    {
        return Category::create([
            'name' => 'Decoración',
            'slug' => 'decoracion',
            'path' => 'hogar/decoracion',
            'is_visible' => true,
        ]);
    }

    private function createSupplier(): Supplier
    {
        return Supplier::firstOrCreate(
            ['code' => 'BREVARE'],
            [
                'business_name' => 'Brevare',
                'status' => 'active',
                'is_internal' => true,
                'estimated_dispatch_days' => 1,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Category $category): array
    {
        return [
            'product' => [
                'category_path' => $category->path,
                'brand_slug' => 'casa-joven',
                'name' => 'Guirnalda API',
                'slug' => 'guirnalda-api',
                'short_description' => 'Guirnalda LED para decorar el hogar.',
                'description' => 'Guirnalda flexible con luces LED y cable reforzado.',
                'seo_title' => 'Guirnalda LED | Brevare',
                'seo_description' => 'Compra una guirnalda LED en Brevare.',
                'allows_shalom_cod' => true,
            ],
            'variants' => [[
                'sku' => 'BVR-API-GUIR-3M',
                'sale_price' => 38.90,
                'compare_price' => 49.90,
                'wholesale_price' => 35.90,
                'wholesale_min_quantity' => 3,
                'cost_price' => 18,
                'weight' => 0.25,
                'is_default' => true,
                'attributes' => [
                    'Largo' => '3 m',
                    'Color' => 'Cálido',
                ],
                'supplier' => [
                    'code' => 'BREVARE',
                    'sku' => 'BREVARE-GUIR-3M',
                    'cost_price' => 18,
                    'shipping_cost' => 0,
                    'stock' => 50,
                    'estimated_dispatch_days' => 1,
                ],
                'images' => [[
                    'file_name' => 'guirnalda.png',
                    'content_base64' => 'data:image/png;base64,'.self::PNG_BASE64,
                    'is_primary' => true,
                ]],
            ]],
        ];
    }

    private function mockCloudinaryUpload(): void
    {
        $cloudinary = Mockery::mock(CloudinaryImageService::class);
        $cloudinary->shouldReceive('upload')
            ->once()
            ->andReturn([
                'public_id' => 'productos/guirnalda-api',
                'url' => 'http://res.cloudinary.com/demo/image/upload/productos/guirnalda-api.png',
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/productos/guirnalda-api.png',
                'file_name' => 'guirnalda.png',
                'format' => 'png',
                'size' => 68,
                'width' => 1,
                'height' => 1,
            ]);
        $cloudinary->shouldReceive('delete')->zeroOrMoreTimes();

        $this->app->instance(CloudinaryImageService::class, $cloudinary);
    }
}
