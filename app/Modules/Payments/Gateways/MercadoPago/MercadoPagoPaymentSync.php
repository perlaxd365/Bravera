<?php

namespace App\Modules\Payments\Gateways\MercadoPago;

use App\Enums\PaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment as LocalPayment;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use Throwable;

/** Resolves a browser return or signed notification against the MP API. */
class MercadoPagoPaymentSync
{
    public function sync(string $remoteId): ?LocalPayment
    {
        if (! ctype_digit($remoteId) || ! (new MercadoPagoGateway)->hasApiCredentials()) {
            return null;
        }

        $options = new RequestOptions;
        $options->setAccessToken((string) config('payments.mercadopago.access_token'));
        $options->setConnectionTimeout(max(1, (int) config('payments.mercadopago.timeout', 15)) * 1000);
        $options->setMaxRetries(0);

        try {
            $remote = (new PaymentClient)->get((int) $remoteId, $options);
        } catch (Throwable $e) {
            report($e);
            Log::warning('No se pudo consultar el pago de Mercado Pago.', ['payment_id' => $remoteId]);

            return null;
        }

        $orderNumber = (string) ($remote->external_reference ?? '');
        $order = $orderNumber !== '' ? Order::where('order_number', $orderNumber)->first() : null;

        if ($order === null
            || strtoupper((string) ($remote->currency_id ?? '')) !== strtoupper((string) $order->currency)
            || abs((float) ($remote->transaction_amount ?? 0) - (float) $order->total) > 0.01) {
            Log::warning('Pago de Mercado Pago no coincide con el pedido local.', [
                'payment_id' => $remoteId,
                'order_number' => $orderNumber,
                'order_exists' => $order !== null,
            ]);

            return null;
        }

        $existing = LocalPayment::query()
            ->where('gateway', 'mercadopago')
            ->where('gateway_transaction_id', $remoteId)
            ->first();

        if ($existing !== null && $existing->order_id !== $order->id) {
            Log::critical('ID de pago de Mercado Pago vinculado a otro pedido.', [
                'payment_id' => $remoteId,
                'local_payment_id' => $existing->id,
                'external_reference' => $orderNumber,
            ]);

            return null;
        }

        $payment = $existing ?? LocalPayment::query()
            ->where('order_id', $order->id)
            ->where('gateway', 'mercadopago')
            ->where('status', PaymentStatus::PENDING)
            ->latest('id')
            ->first();

        if ($payment === null) {
            Log::warning('Notificación de Mercado Pago sin intento de pago local pendiente.', [
                'payment_id' => $remoteId,
                'order_number' => $orderNumber,
            ]);

            return null;
        }

        $preferenceId = (string) data_get($payment->raw_response, 'preference_id', '');
        $remotePreferenceId = (string) ($remote->preference_id ?? '');

        if ($preferenceId === '' || $remotePreferenceId === '' || ! hash_equals($preferenceId, $remotePreferenceId)) {
            Log::warning('El pago notificado no pertenece a la preferencia guardada.', [
                'payment_id' => $remoteId,
                'local_payment_id' => $payment->id,
            ]);

            return null;
        }

        $raw = [
            'id' => (string) ($remote->id ?? $remoteId),
            'status' => (string) ($remote->status ?? ''),
            'status_detail' => (string) ($remote->status_detail ?? ''),
            'external_reference' => $orderNumber,
            'preference_id' => $remotePreferenceId ?: $preferenceId,
            'transaction_amount' => (float) $remote->transaction_amount,
            'currency_id' => (string) $remote->currency_id,
        ];

        $status = strtolower((string) ($remote->status ?? ''));

        if ($status === 'approved') {
            if ($payment->status === PaymentStatus::PAID
                && $payment->gateway_transaction_id === $remoteId) {
                return $payment;
            }

            if ($order->status !== OrderStatus::PENDING || ! $order->isPayable()) {
                Log::critical('Mercado Pago aprobó un pago para un pedido cancelado o vencido. Requiere conciliación.', [
                    'payment_id' => $remoteId,
                    'order_id' => $order->id,
                    'order_status' => $order->status->value,
                ]);

                return null;
            }

            if ($order->payment_status === PaymentStatus::PAID
                && ($payment->status !== PaymentStatus::PAID || $payment->gateway_transaction_id !== $remoteId)) {
                Log::critical('Mercado Pago aprobó un segundo pago para un pedido ya pagado. Requiere conciliación.', [
                    'payment_id' => $remoteId,
                    'order_id' => $order->id,
                    'local_payment_id' => $payment->id,
                ]);

                return null;
            }

            $payment->update(['gateway_transaction_id' => $remoteId]);

            return app(PaymentService::class)->markAsPaid($payment->fresh(), $raw);
        }

        $mappedStatus = match ($status) {
            'pending', 'in_process', 'authorized' => PaymentStatus::PENDING,
            'refunded' => PaymentStatus::REFUNDED,
            default => PaymentStatus::FAILED,
        };

        $payment->update([
            'gateway_transaction_id' => $remoteId,
            'status' => $mappedStatus,
            'raw_response' => $raw,
        ]);

        if ($mappedStatus === PaymentStatus::REFUNDED) {
            $order->update(['payment_status' => PaymentStatus::REFUNDED]);
        }

        // Rechazo, proceso y cancelación no destruyen la reserva. La orden
        // puede reintentarse hasta que el vencimiento libere stock.
        return $payment->fresh();
    }
}
