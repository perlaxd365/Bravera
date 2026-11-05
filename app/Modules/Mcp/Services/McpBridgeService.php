<?php

namespace App\Modules\Mcp\Services;

use App\Http\Controllers\Api\V1\CatalogOptionsController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Requests\Api\StoreProductRequest;
use App\Modules\Mcp\Exceptions\McpToolException;
use App\Modules\Product\Services\ProductPublicationApiService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Throwable;

class McpBridgeService
{
    public function __construct(
        private readonly ProductPublicationApiService $publication,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tools(): array
    {
        return [
            [
                'name' => 'consultar_catalogo',
                'title' => 'Consultar catálogo Brevare',
                'description' => 'Consulta categorías, marcas, proveedores, atributos y límites antes de publicar un producto.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'consultar_producto',
                'title' => 'Consultar producto Brevare',
                'description' => 'Consulta un producto publicado por ID o slug y devuelve su stock y estado de publicación.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id_o_slug' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 255,
                        ],
                    ],
                    'required' => ['id_o_slug'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'publicar_producto',
                'title' => 'Publicar producto en Brevare',
                'description' => 'Crea y publica un producto completo con variantes, proveedor, stock e imágenes Base64. No actualiza productos existentes ni reintenta una publicación fallida.',
                'inputSchema' => $this->publishProductSchema(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callTool(string $name, array $arguments): array
    {
        return match ($name) {
            'consultar_catalogo' => app(CatalogOptionsController::class)()->getData(true),
            'consultar_producto' => $this->consultProduct($arguments),
            'publicar_producto' => $this->publishProduct($arguments),
            default => throw new McpToolException('Herramienta MCP desconocida.'),
        };
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function consultProduct(array $arguments): array
    {
        $identifier = trim((string) ($arguments['id_o_slug'] ?? ''));

        if ($identifier === '') {
            throw new McpToolException('Debes indicar id_o_slug.');
        }

        try {
            return app(ProductController::class)->show($identifier)->getData(true);
        } catch (ModelNotFoundException) {
            throw new McpToolException('Producto no encontrado.');
        }
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function publishProduct(array $arguments): array
    {
        $request = StoreProductRequest::create('/api/v1/products', 'POST', $arguments);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        try {
            $request->validateResolved();
            $product = $this->publication->publish($request->validated());

            return app(ProductController::class)->show((string) $product->id)->getData(true);
        } catch (ValidationException $exception) {
            throw new McpToolException('La API Brevare rechazó la publicación.', [
                'status' => 422,
                'response' => [
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ],
            ]);
        } catch (McpToolException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            logger()->warning('Falló una publicación desde el conector MCP.', [
                'exception' => $exception::class,
            ]);

            throw new McpToolException('No se pudo publicar el producto.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function publishProductSchema(): array
    {
        $nullableString = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'product' => [
                    'type' => 'object',
                    'properties' => [
                        'category_path' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
                        'brand_slug' => ['type' => 'string', 'maxLength' => 255],
                        'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
                        'slug' => ['type' => 'string', 'maxLength' => 255],
                        'short_description' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 500],
                        'description' => ['type' => 'string', 'minLength' => 1],
                        'seo_title' => ['type' => 'string', 'maxLength' => 255],
                        'seo_description' => ['type' => 'string', 'maxLength' => 320],
                        'allows_shalom_cod' => ['type' => 'boolean'],
                        'is_featured' => ['type' => 'boolean'],
                        'translations' => [
                            'type' => 'object',
                            'additionalProperties' => true,
                        ],
                    ],
                    'required' => ['category_path', 'name', 'short_description', 'description'],
                    'additionalProperties' => false,
                ],
                'variants' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'maxItems' => 100,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'sku' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100],
                            'barcode' => $nullableString,
                            'sale_price' => ['type' => 'number', 'exclusiveMinimum' => 0],
                            'compare_price' => ['type' => 'number', 'exclusiveMinimum' => 0],
                            'discount_percent' => ['type' => 'number', 'minimum' => 0, 'maximum' => 90],
                            'wholesale_price' => ['type' => 'number', 'minimum' => 0],
                            'wholesale_min_quantity' => ['type' => 'integer', 'minimum' => 2],
                            'cost_price' => ['type' => 'number', 'minimum' => 0],
                            'weight' => ['type' => 'number', 'minimum' => 0],
                            'length' => ['type' => 'number', 'minimum' => 0],
                            'width' => ['type' => 'number', 'minimum' => 0],
                            'height' => ['type' => 'number', 'minimum' => 0],
                            'is_default' => ['type' => 'boolean'],
                            'attributes' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => ['string', 'number', 'boolean'],
                                ],
                            ],
                            'supplier' => [
                                'type' => 'object',
                                'properties' => [
                                    'code' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 20],
                                    'sku' => ['type' => 'string', 'maxLength' => 100],
                                    'cost_price' => ['type' => 'number', 'minimum' => 0],
                                    'shipping_cost' => ['type' => 'number', 'minimum' => 0],
                                    'sale_price' => ['type' => 'number', 'minimum' => 0],
                                    'stock' => ['type' => 'integer', 'minimum' => 1],
                                    'minimum_stock' => ['type' => 'integer', 'minimum' => 0],
                                    'estimated_dispatch_days' => ['type' => 'integer', 'minimum' => 0],
                                    'internal_notes' => $nullableString,
                                ],
                                'required' => ['code', 'stock'],
                                'additionalProperties' => false,
                            ],
                            'images' => [
                                'type' => 'array',
                                'minItems' => 1,
                                'maxItems' => 20,
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'file_name' => [
                                            'type' => 'string',
                                            'pattern' => '\\.(jpe?g|png|webp)$',
                                        ],
                                        'content_base64' => ['type' => 'string', 'minLength' => 20],
                                        'is_primary' => ['type' => 'boolean'],
                                        'sort_order' => ['type' => 'integer', 'minimum' => 0],
                                    ],
                                    'required' => ['file_name', 'content_base64'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['sku', 'sale_price', 'supplier', 'images'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['product', 'variants'],
            'additionalProperties' => false,
        ];
    }
}
