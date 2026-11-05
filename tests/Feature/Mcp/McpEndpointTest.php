<?php

namespace Tests\Feature\Mcp;

use App\Models\Category;
use App\Models\Supplier;
use App\Services\CloudinaryImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class McpEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'mcp-test-token-0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.mcp.access_token', self::TOKEN);
        config()->set('services.product_api.password', 'api-test-password');
    }

    public function test_health_no_expone_secretos(): void
    {
        $this->getJson('/mcp/health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('api_configured', true)
            ->assertJsonPath('mcp_auth_configured', true)
            ->assertJsonMissing(['access_token' => self::TOKEN]);
    }

    public function test_requiere_bearer_para_mcp(): void
    {
        $this->postJson('/mcp', $this->initializePayload())
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Token MCP inválido o ausente.');
    }

    public function test_inicializa_y_lista_las_herramientas(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/mcp', $this->initializePayload())
            ->assertOk()
            ->assertJsonPath('jsonrpc', '2.0')
            ->assertJsonPath('id', 1)
            ->assertJsonPath('result.protocolVersion', '2025-06-18')
            ->assertJsonPath('result.serverInfo.name', 'brevare-products');

        $this->withToken(self::TOKEN)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/list',
            ])
            ->assertOk()
            ->assertJsonCount(3, 'result.tools')
            ->assertJsonPath('result.tools.0.name', 'consultar_catalogo')
            ->assertJsonPath('result.tools.1.name', 'consultar_producto')
            ->assertJsonPath('result.tools.2.name', 'publicar_producto');
    }

    public function test_consulta_catalogo_a_traves_de_la_api_interna(): void
    {
        Category::create([
            'name' => 'Decoración',
            'slug' => 'decoracion-mcp',
            'path' => 'hogar/decoracion',
            'is_visible' => true,
        ]);
        Supplier::create([
            'code' => 'MCP-TEST',
            'business_name' => 'Proveedor MCP',
            'status' => 'active',
        ]);

        $response = $this->withToken(self::TOKEN)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 3,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'consultar_catalogo',
                    'arguments' => (object) [],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('result.isError', false)
            ->assertSee('hogar\\/decoracion', false);
    }

    public function test_error_de_publicacion_se_devuelve_como_error_de_herramienta(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 4,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'publicar_producto',
                    'arguments' => [
                        'product' => [],
                        'variants' => [],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('result.isError', true)
            ->assertSee('product.category_path', false);
    }

    public function test_publica_producto_sin_pasar_por_la_api_publica(): void
    {
        $category = Category::create([
            'name' => 'Hogar',
            'slug' => 'hogar-mcp',
            'path' => 'hogar',
            'is_visible' => true,
        ]);
        Supplier::create([
            'code' => 'MCP-PUBLISH',
            'business_name' => 'Proveedor de publicación MCP',
            'status' => 'active',
        ]);
        $this->mockCloudinaryUpload();

        $response = $this->withToken(self::TOKEN)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 5,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'publicar_producto',
                    'arguments' => $this->publishPayload($category),
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('result.isError', false)
            ->assertSee('producto-mcp', false);

        $this->assertDatabaseHas('products', [
            'slug' => 'producto-mcp',
            'status' => true,
            'is_visible' => true,
        ]);
    }

    public function test_rechaza_origen_no_autorizado(): void
    {
        $this->withToken(self::TOKEN)
            ->withHeader('Origin', 'https://example.com')
            ->postJson('/mcp', $this->initializePayload())
            ->assertForbidden()
            ->assertJsonPath('error', 'Origen no autorizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function initializePayload(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object) [],
                'clientInfo' => [
                    'name' => 'test',
                    'version' => '1.0.0',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publishPayload(Category $category): array
    {
        return [
            'product' => [
                'category_path' => $category->path,
                'name' => 'Producto MCP',
                'slug' => 'producto-mcp',
                'short_description' => 'Producto creado desde MCP.',
                'description' => 'Producto de prueba creado por el conector MCP.',
            ],
            'variants' => [[
                'sku' => 'MCP-PRODUCT-001',
                'sale_price' => 29.90,
                'is_default' => true,
                'supplier' => [
                    'code' => 'MCP-PUBLISH',
                    'stock' => 10,
                ],
                'images' => [[
                    'file_name' => 'producto.png',
                    'content_base64' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=',
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
                'public_id' => 'productos/producto-mcp',
                'url' => 'http://res.cloudinary.com/demo/image/upload/productos/producto-mcp.png',
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/productos/producto-mcp.png',
                'file_name' => 'producto.png',
                'format' => 'png',
                'size' => 68,
                'width' => 1,
                'height' => 1,
            ]);
        $cloudinary->shouldReceive('delete')->zeroOrMoreTimes();

        $this->app->instance(CloudinaryImageService::class, $cloudinary);
    }
}
