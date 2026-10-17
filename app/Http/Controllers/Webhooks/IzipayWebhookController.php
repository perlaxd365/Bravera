<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Gateways\Izipay\IzipayPaymentSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * IPN de Izipay: notificación server-to-server del resultado del pago.
 *
 * Es la fuente de verdad del cobro. Nunca se confía en el contenido sin
 * validar la firma kr-hash ni en el estado sin contrastar importe, moneda y
 * número de pedido contra el pedido local.
 */
class IzipayWebhookController extends Controller
{
    public function __invoke(Request $request, IzipayPaymentSync $sync): JsonResponse
    {
        $answer = $sync->answerFromRequest($request);

        if ($answer === null) {
            return response()->json(['ok' => false], 401);
        }

        if ($sync->confirm($answer) === null) {
            // Se responde 503 para que Izipay reintente la notificación cuando
            // el problema es temporal (pedido aún no registrado, base caída).
            return response()->json(['ok' => false], 503);
        }

        return response()->json(['ok' => true]);
    }
}
