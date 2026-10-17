<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Entrega la Public Key de Mercado Pago al navegador.
 *
 * La Public Key es un dato público; el Access Token y el secreto del webhook
 * nunca salen del servidor. La respuesta se marca como no cacheable para que
 * una página antigua no conserve una configuración vencida.
 *
 * GET /pagos/mercadopago/configuracion
 */
class MercadoPagoConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $publicKey = trim((string) config('payments.mercadopago.public_key'));

        // Falla explícita: status(503) cuando MP_PUBLIC_KEY no está configurada.
        if ($publicKey === '' || ! config('payments.mercadopago.enabled')) {
            return response()
                ->json(['error' => 'MP_PUBLIC_KEY no está configurada en el servidor.'], 503)
                ->header('Cache-Control', 'no-store, max-age=0');
        }

        return response()
            ->json(['publicKey' => $publicKey])
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
