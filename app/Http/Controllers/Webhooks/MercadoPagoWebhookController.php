<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Gateways\MercadoPago\MercadoPagoOrderSync;
use App\Modules\Payments\Gateways\MercadoPago\MercadoPagoPaymentSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Webhook\WebhookSignatureValidator;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoOrderSync $orderSync,
        MercadoPagoPaymentSync $paymentSync,
    ): JsonResponse
    {
        $eventType = (string) ($request->query('type') ?? $request->query('topic') ?? $request->input('type', ''));
        $isOrderEvent = in_array($eventType, ['order', 'orders'], true);
        $isPaymentEvent = in_array($eventType, ['payment', 'payments'], true);

        if ($eventType !== '' && ! $isOrderEvent && ! $isPaymentEvent) {
            return response()->json(['ok' => true]);
        }

        // Mercado Pago firma el valor de data.id de la notificación, no un ID
        // tomado del cuerpo. Usamos su validador oficial para normalizar el HMAC.
        // PHP normaliza los puntos de nombres de query a guiones bajos en
        // algunos SAPI; soportamos ambas formas del mismo parámetro firmado.
        $remoteId = (string) (
            $request->query('data.id')
            ?? $request->query('data_id')
            ?? $request->input('data.id', '')
        );
        $secret = (string) config('payments.mercadopago.webhook_secret', '');

        try {
            WebhookSignatureValidator::validate(
                $request->header('x-signature'),
                $request->header('x-request-id'),
                $remoteId,
                $secret,
            );
        } catch (Throwable $e) {
            Log::warning('Firma de webhook de Mercado Pago inválida.', [
                'payment_id' => $remoteId,
                'reason' => $e::class,
            ]);

            return response()->json(['ok' => false], 401);
        }

        // Checkout API usa el tópico `orders` y su data.id es el ID de la
        // order. El tópico `payment` se conserva para pagos históricos de
        // Checkout Pro que todavía puedan notificar.
        $result = $isPaymentEvent
            ? $paymentSync->sync($remoteId)
            : $orderSync->sync($remoteId);

        if ($result === null) {
            return response()->json(['ok' => false], 503);
        }

        // No se confirma hasta contrastar monto, moneda, referencia y preferencia
        // contra la API. Un fallo temporal devuelve 503 para que MP reintente.
        return response()->json(['ok' => true]);
    }
}
