<?php

namespace App\Modules\Payments\Gateways\MercadoPago;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment as LocalPayment;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Order\OrderClient;
use Throwable;

/**
 * Confirma una order de Checkout API consultando la Orders API.
 *
 * El webhook de Mercado Pago es solo una señal: el estado real se vuelve a
 * consultar con el Access Token, se contrasta contra el pedido local y recién
 * entonces se confirma el pago.
 */
class MercadoPagoOrderSync
{
    public function sync(string $remoteId): ?LocalPayment
    {
        $remoteId = trim($remoteId);

        if ($remoteId === '' || ! (new MercadoPagoGateway)->hasApiCredentials()) {
            return null;
        }

        $options = new RequestOptions;
        $options->setAccessToken((string) config('payments.mercadopago.access_token'));
        $options->setConnectionTimeout(max(1, (int) config('payments.mercadopago.timeout', 15)) * 1000);
        $options->setMaxRetries(0);

        try {
            $remote = (new OrderClient)->get($remoteId, $options);
        } catch (Throwable $e) {
            report($e);
            Log::warning('No se pudo consultar la order de Mercado Pago.', ['order_id' => $remoteId]);

            return null;
        }

        $orderNumber = trim((string) ($remote->external_reference ?? ''));
        $order = $orderNumber !== '' ? Order::where('order_number', $orderNumber)->first() : null;
        $remotePayment = data_get($remote, 'transactions.payments.0');
        $remoteAmount = (float) ($remote->total_amount ?? data_get($remotePayment, 'amount', 0));
        $remoteCurrency = strtoupper((string) ($remote->currency ?: $order?->currency ?: 'PEN'));

        if ($order === null
            || abs($remoteAmount - (float) $order->total) > 0.01
            || $remoteCurrency !== strtoupper((string) $order->currency)) {
            Log::warning('Order de Mercado Pago no coincide con el pedido local.', [
                'order_id' => $remoteId,
                'external_reference' => $orderNumber,
                'order_exists' => $order !== null,
                'remote_amount' => $remoteAmount,
                'local_total' => $order?->total,
                'remote_currency' => $remoteCurrency,
                'local_currency' => $order?->currency,
            ]);

            return null;
        }

        $existing = LocalPayment::query()
            ->where('gateway', 'mercadopago')
            ->where('gateway_transaction_id', $remoteId)
            ->first();

        if ($existing !== null && $existing->order_id !== $order->id) {
            Log::critical('Order de Mercado Pago vinculada a otro pedido.', [
                'order_id' => $remoteId,
                'local_payment_id' => $existing->id,
                'external_reference' => $orderNumber,
            ]);

            return null;
        }

        $payment = $existing ?? LocalPayment::query()
            ->where('order_id', $order->id)
            ->where('gateway', 'mercadopago')
            ->whereIn('status', [PaymentStatus::PENDING->value, PaymentStatus::FAILED->value])
            ->latest('id')
            ->first();

        if ($payment === null) {
            Log::warning('Notificación de Mercado Pago sin intento de pago local.', [
                'order_id' => $remoteId,
                'order_number' => $orderNumber,
            ]);

            return null;
        }

        $status = strtolower((string) ($remote->status ?? ''));
        $detail = strtolower((string) ($remote->status_detail ?? ''));
        $remotePaymentStatus = strtolower((string) data_get($remotePayment, 'status', ''));
        $remotePaymentDetail = strtolower((string) data_get($remotePayment, 'status_detail', ''));

        $raw = [
            'order_id' => $remoteId,
            'order_status' => $status,
            'order_status_detail' => $detail,
            'payment_id' => (string) data_get($remotePayment, 'id', ''),
            'payment_status' => $remotePaymentStatus,
            'payment_status_detail' => $remotePaymentDetail,
            'payment_method_id' => (string) data_get($remotePayment, 'payment_method.id', ''),
            'transaction_amount' => (string) ($remote->total_amount ?? ''),
            'currency' => $remoteCurrency,
        ];

        if ($status === 'processed' && $detail === 'accredited') {
            if ($payment->status === PaymentStatus::PAID
                && $payment->gateway_transaction_id === $remoteId) {
                return $payment;
            }

            if ($order->status !== OrderStatus::PENDING || ! $order->isPayable()) {
                Log::critical('Mercado Pago aprobó un pago para un pedido cancelado o vencido. Requiere conciliación.', [
                    'order_id' => $remoteId,
                    'local_order_id' => $order->id,
                    'order_status' => $order->status->value,
                ]);

                return null;
            }

            if ($order->payment_status === PaymentStatus::PAID
                && ($payment->status !== PaymentStatus::PAID || $payment->gateway_transaction_id !== $remoteId)) {
                Log::critical('Mercado Pago aprobó un segundo pago para un pedido ya pagado. Requiere conciliación.', [
                    'order_id' => $remoteId,
                    'local_order_id' => $order->id,
                    'local_payment_id' => $payment->id,
                ]);

                return null;
            }

            $payment->update(['gateway_transaction_id' => $remoteId]);

            return app(PaymentService::class)->markAsPaid($payment->fresh(), $raw);
        }

        if ($status === 'refunded' || ($status === 'processed' && $detail === 'refunded')) {
            $payment->update([
                'gateway_transaction_id' => $remoteId,
                'status' => PaymentStatus::REFUNDED,
                'raw_response' => $raw,
            ]);
            $order->update(['payment_status' => PaymentStatus::REFUNDED]);

            return $payment->fresh();
        }

        if ($status === 'processed' && $detail === 'partially_refunded') {
            $payment->update([
                'gateway_transaction_id' => $remoteId,
                'raw_response' => $raw,
            ]);

            return $payment->fresh();
        }

        $mappedStatus = match (true) {
            $status === 'action_required' && $detail === 'waiting_capture' => PaymentStatus::AUTHORIZED,
            in_array($status, ['created', 'processing', 'in_review', 'action_required'], true) => PaymentStatus::PENDING,
            $status === 'canceled' => PaymentStatus::CANCELLED,
            default => PaymentStatus::FAILED,
        };

        $payment->update([
            'gateway_transaction_id' => $remoteId,
            'status' => $mappedStatus,
            'raw_response' => $raw,
        ]);

        // Un rechazo, una cancelación o una revisión no destruyen la reserva.
        // El comprador puede reintentar hasta que venza el pedido.
        return $payment->fresh();
    }
}
