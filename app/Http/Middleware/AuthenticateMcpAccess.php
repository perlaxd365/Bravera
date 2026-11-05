<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcpAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = trim((string) config('services.mcp.access_token'));

        if (strlen($configuredToken) < 32) {
            return response()->json([
                'error' => 'El conector MCP no está configurado.',
            ], 503);
        }

        $origin = $request->headers->get('Origin');
        $allowedOrigins = (array) config('services.mcp.allowed_origins', []);

        if ($origin !== null && $origin !== '' && ! in_array($origin, $allowedOrigins, true)) {
            return response()->json([
                'error' => 'Origen no autorizado.',
            ], 403);
        }

        $authorization = (string) $request->headers->get('Authorization', '');
        $providedToken = str_starts_with($authorization, 'Bearer ')
            ? substr($authorization, 7)
            : '';

        if ($providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'error' => 'Token MCP inválido o ausente.',
            ], 401);
        }

        return $next($request);
    }
}
