<?php

namespace App\Livewire\Store\Concerns;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Throwable;

trait ProcessesMercadoPagoPayment
{
    /**
     * Procesa un intento de tarjeta de Checkout API para un pedido ya creado.
     *
     * El monto y la identidad salen del pedido local; el navegador solo aporta
     * el token opaco de la tarjeta y los datos del titular necesarios.
     *
     * @return array{status: string, message?: string, url?: string}
     */
    protected function processMercadoPagoPayment(
        ?Order $order,
        string $token,
        string $paymentMethodId,
        string $identificationNumber,
        ?string $issuerId = null,
    ): array {
        $order = $order?->fresh(['items', 'user']);

        if ($order === null || auth()->id() !== $order->user_id) {
            return ['status' => 'error', 'message' => 'No encontramos el pedido para procesar el pago.'];
        }

        if ($order->payment_status === PaymentStatus::PAID) {
            return [
                'status' => 'paid',
                'url' => route('store.order.placed', ['order' => $order->order_number]),
            ];
        }

        if (! $order->isPayable()) {
            return [
                'status' => 'error',
                'message' => 'El pedido ya no está disponible para pago. Vuelve al checkout.',
            ];
        }

        $validator = Validator::make([
            'token' => $token,
            'payment_method_id' => $paymentMethodId,
            'identification_number' => preg_replace('/\D+/', '', $identificationNumber),
        ], [
            'token' => ['required', 'string', 'max:255'],
            'payment_method_id' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/i'],
            'identification_number' => ['required', 'string', 'regex:/^\d{8,12}$/'],
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => 'Revisa el número de documento e inténtalo otra vez.',
            ];
        }

        $data = $validator->validated();
        $lock = Cache::lock('mercadopago:pay:'.$order->id, 45);

        if (! $lock->get()) {
            return [
                'status' => 'error',
                'message' => 'Ya estamos procesando este pago. Espera unos segundos antes de reintentar.',
            ];
        }

        try {
            $payment = app(PaymentService::class)->charge(
                order: $order,
                method: 'card',
                gatewayName: 'mercadopago',
                sourceId: $data['token'],
                gatewayContext: [
                    'payment_method_id' => $data['payment_method_id'],
                    'identification_number' => $data['identification_number'],
                    'issuer_id' => $issuerId,
                ],
            );

            if ($payment->status === PaymentStatus::PAID) {
                session()->forget('mercadopago_pending_order');

                return [
                    'status' => 'paid',
                    'url' => route('store.order.placed', ['order' => $order->order_number]),
                ];
            }

            if ($payment->status === PaymentStatus::PENDING) {
                return [
                    'status' => 'pending',
                    'url' => route('store.order.placed', ['order' => $order->order_number]),
                ];
            }

            return [
                'status' => 'error',
                'message' => (string) data_get(
                    $payment->raw_response,
                    'message',
                    'No se pudo procesar el pago. Revisa los datos de tu tarjeta e inténtalo otra vez.'
                ),
            ];
        } catch (PaymentGatewayNotConfiguredException $e) {
            report($e);

            return [
                'status' => 'error',
                'message' => 'Mercado Pago no está configurado correctamente. Escríbenos para completar tu compra.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'status' => 'error',
                'message' => 'No pudimos procesar el pago. Inténtalo de nuevo o escríbenos.',
            ];
        } finally {
            $lock->release();
        }
    }
}
