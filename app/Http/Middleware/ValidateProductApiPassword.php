<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateProductApiPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredPassword = trim((string) config('services.product_api.password'));

        if ($configuredPassword === '') {
            return response()->json([
                'message' => 'La API de productos no está configurada.',
            ], 503);
        }

        $providedPassword = $request->query('password');

        if (! is_string($providedPassword)
            || $providedPassword === ''
            || ! hash_equals($configuredPassword, $providedPassword)) {
            return response()->json([
                'message' => 'Contraseña de API inválida o ausente.',
            ], 401);
        }

        return $next($request);
    }
}
