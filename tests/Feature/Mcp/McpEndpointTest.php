<?php

namespace Tests\Feature\Mcp;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
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
        config()->set('services.mcp.api_base', 'https://brevare.com/api/v1');
        config()->set('services.mcp.timeout', 30);
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
        Http::fake([
            'brevare.com/api/v1/catalog/options?*' => Http::response([
                'categories' => [['path' => 'hogar/decoracion']],
                'suppliers' => [['code' => 'BREVARE']],
            ]),
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

        Http::assertSent(fn (Request $request) => str_starts_with(
            $request->url(),
            'https://brevare.com/api/v1/catalog/options?password=api-test-password',
        ));
    }

    public function test_error_de_publicacion_se_devuelve_como_error_de_herramienta(): void
    {
        Http::fake([
            'brevare.com/api/v1/products?*' => Http::response([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'variants.0.supplier.stock' => ['El stock es obligatorio.'],
                ],
            ], 422),
        ]);

        $this->withToken(self::TOKEN)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 4,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'publicar_producto',
                    'arguments' => [
                        'product' => ['name' => 'Prueba'],
                        'variants' => [],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('result.isError', true)
            ->assertSee('stock es obligatorio', false);
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
}
