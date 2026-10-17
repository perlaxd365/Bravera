<?php

namespace App\Modules\Payments\Gateways\Izipay;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment as LocalPayment;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Confirma un pedido a partir del kr-answer de Izipay.
 *
 * Lo usan los dos caminos que traen el resultado del pago: el IPN
 * (server-to-server) y el retorno del navegador a la tienda. Antes de
 * confirmar nada se verifica la firma kr-hash y se contrastan importe,
 * moneda y número de pedido contra el pedido local, porque el kr-answer
 * viaja por el navegador y no es confiable por sí solo.
 */
class IzipayPaymentSync
{
    /**
     * Devuelve el kr-answer ya decodificado si la firma es válida.
     *
     * @return array<string, mixed>|null
     */
    public function answerFromRequest(Request $request): ?array
    {
        $payload = $request->all();

        if (! IzipaySignature::verify($payload)) {
            Log::warning('Firma kr-hash inválida en una notificación de Izipay.', [
                'has_answer' => is_string($payload['kr-answer'] ?? null),
                'key' => $payload['kr-hash-key'] ?? null,
            ]);

            return null;
        }

        $answer = json_decode((string) $payload['kr-answer'], true);

        return is_array($answer) ? $answer : null;
    }

    /**
     * Aplica el resultado del pago al pedido.
     *
     * @param  array<string, mixed>  $answer
     */
    public function confirm(array $answer): ?LocalPayment
    {
        $orderNumber = trim((string) data_get($answer, 'orderDetails.orderId', ''));
        $order = $orderNumber !== '' ? Order::where('order_number', $orderNumber)->first() : null;

        if ($order === null) {
            Log::warning('Notificación de Izipay sin pedido local.', ['order_number' => $orderNumber]);

            return null;
        }

        $amount = (int) data_get($answer, 'orderDetails.amount', 0);
        $currency = strtoupper((string) data_get($answer, 'orderDetails.currency', ''));
        $expected = (int) round((float) $order->total * 100);

        if ($currency !== strtoupper((string) $order->currency) || $amount !== $expected) {
            Log::warning('Pago de Izipay no coincide con el pedido local.', [
                'order_number' => $orderNumber,
                'amount' => $amount,
                'expected_amount' => $expected,
                'currency' => $currency,
            ]);

            return null;
        }

        $uuid = (string) data_get($answer, 'transactions.0.uuid', '');
        $orderStatus = strtoupper((string) data_get($answer, 'orderStatus', ''));

        $payment = ($uuid !== ''
                ? LocalPayment::query()->where('gateway', 'izipay')->where('gateway_transaction_id', $uuid)->first()
                : null)
            ?? LocalPayment::query()
                ->where('order_id', $order->id)
                ->where('gateway', 'izipay')
                ->where('status', PaymentStatus::PENDING)
                ->latest('id')
                ->first();

        if ($payment === null) {
            Log::warning('Notificación de Izipay sin intento de pago local pendiente.', [
                'order_number' => $orderNumber,
                'uuid' => $uuid,
            ]);

            return null;
        }

        if ((int) $payment->order_id !== (int) $order->id) {
            Log::critical('Transacción de Izipay vinculada a otro pedido.', [
                'uuid' => $uuid,
                'local_payment_id' => $payment->id,
                'order_number' => $orderNumber,
            ]);

            return null;
        }

        $raw = [
            'uuid' => $uuid,
            'order_status' => $orderStatus,
            'amount' => $amount,
            'currency' => $currency,
            'payment_method_type' => (string) data_get($answer, 'transactions.0.paymentMethodType', ''),
            'transaction_status' => (string) data_get($answer, 'transactions.0.status', ''),
        ];

        if ($orderStatus === 'PAID') {
            if ($payment->status === PaymentStatus::PAID && $payment->gateway_transaction_id === $uuid) {
                return $payment;
            }

            if ($order->status !== OrderStatus::PENDING || ! $order->isPayable()) {
                Log::critical('Izipay aprobó un pago para un pedido cancelado o vencido. Requiere conciliación.', [
                    'order_id' => $order->id,
                    'uuid' => $uuid,
                ]);

                return null;
            }

            $payment->update(['gateway_transaction_id' => $uuid]);

            return app(PaymentService::class)->markAsPaid($payment->fresh(), $raw);
        }

        $mappedStatus = match ($orderStatus) {
            // El pago existe pero el dinero todavía no está capturado: no se
            // confirma el pedido ni se despacha nada.
            'AUTHORISED', 'WAITING_AUTHORISATION' => PaymentStatus::AUTHORIZED,
            'UNPAID', 'RUNNING' => PaymentStatus::PENDING,
            'REFUNDED' => PaymentStatus::REFUNDED,
            default => PaymentStatus::FAILED,
        };

        if ($mappedStatus === PaymentStatus::AUTHORIZED) {
            Log::warning('Izipay autorizó un pago sin capturar. Requiere captura manual.', [
                'order_id' => $order->id,
                'uuid' => $uuid,
            ]);
        }

        $payment->update([
            'gateway_transaction_id' => $uuid !== '' ? $uuid : $payment->gateway_transaction_id,
            'status' => $mappedStatus,
            'raw_response' => $raw,
        ]);

        if ($mappedStatus === PaymentStatus::REFUNDED) {
            $order->update(['payment_status' => PaymentStatus::REFUNDED]);
        }

        // Un rechazo no destruye la reserva: el comprador puede reintentar
        // hasta que el vencimiento libere el stock.
        return $payment->fresh();
    }
}
