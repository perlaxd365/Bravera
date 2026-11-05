<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Mcp\Exceptions\McpToolException;
use App\Modules\Mcp\Services\McpBridgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JsonException;
use stdClass;

class McpController extends Controller
{
    private const PROTOCOL_VERSION = '2025-06-18';

    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => 'brevare-mcp-bridge',
            'transport' => 'streamable-http',
            'api_configured' => filled(config('services.product_api.password')),
            'mcp_auth_configured' => strlen((string) config('services.mcp.access_token')) >= 32,
        ]);
    }

    public function methodNotAllowed(): JsonResponse
    {
        return response()
            ->json(['error' => 'Usa POST para el transporte MCP.'], 405)
            ->header('Allow', 'POST');
    }

    public function handle(Request $request, McpBridgeService $bridge): JsonResponse|Response
    {
        try {
            $payload = $request->json()->all();
        } catch (JsonException) {
            return $this->error(null, -32700, 'JSON inválido.');
        }

        if (array_is_list($payload)
            || ($payload['jsonrpc'] ?? null) !== '2.0'
            || ! is_string($payload['method'] ?? null)) {
            return $this->error(null, -32600, 'Solicitud JSON-RPC inválida.');
        }

        $id = $payload['id'] ?? null;
        $method = $payload['method'];
        $params = is_array($payload['params'] ?? null) ? $payload['params'] : [];

        if (! array_key_exists('id', $payload)) {
            return response('', 202);
        }

        return match ($method) {
            'initialize' => $this->result($id, $this->initializeResult($params)),
            'ping' => $this->result($id, new stdClass),
            'tools/list' => $this->result($id, [
                'tools' => $bridge->tools(),
            ]),
            'tools/call' => $this->callTool($id, $params, $bridge),
            default => $this->error($id, -32601, 'Método MCP no encontrado.'),
        };
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function callTool(mixed $id, array $params, McpBridgeService $bridge): JsonResponse
    {
        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (! is_string($name) || $name === '' || ! is_array($arguments)) {
            return $this->error($id, -32602, 'Parámetros de herramienta inválidos.');
        }

        try {
            return $this->result($id, [
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode(
                        $bridge->callTool($name, $arguments),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
                    ),
                ]],
                'isError' => false,
            ]);
        } catch (McpToolException $exception) {
            return $this->result($id, [
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'error' => $exception->getMessage(),
                        'details' => $exception->details,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                ]],
                'isError' => true,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function initializeResult(array $params): array
    {
        $requested = (string) ($params['protocolVersion'] ?? '');
        $supported = [self::PROTOCOL_VERSION, '2025-03-26', '2024-11-05'];

        return [
            'protocolVersion' => in_array($requested, $supported, true)
                ? $requested
                : self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => new stdClass,
            ],
            'serverInfo' => [
                'name' => 'brevare-products',
                'version' => '1.0.0',
            ],
            'instructions' => 'Consulta primero el catálogo. Para publicar, valida categoría, proveedor, stock, SKU, slug e imágenes Base64. No repitas una publicación que haya fallado sin corregir los errores.',
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function result(mixed $id, array|stdClass $result): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ]);
    }

    private function error(mixed $id, int $code, string $message): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ]);
    }
}
