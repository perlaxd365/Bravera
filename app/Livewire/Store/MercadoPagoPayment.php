<?php

namespace App\Livewire\Store;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use App\Modules\Payments\Gateways\MercadoPago\MercadoPagoGateway;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Pantalla de pago de Checkout API.
 *
 * La tarjeta se tokeniza en el navegador con MercadoPago.js. El servidor
 * recibe únicamente el token y crea la order con el monto congelado del
 * pedido; nunca acepta importes ni datos completos de tarjeta desde la vista.
 */
#[Layout('store.layouts.app')]
class MercadoPagoPayment extends Component
{
    #[Locked]
    public ?Order $order = null;

    public bool $processing = false;

    public function mount(?Order $order): void
    {
        $this->order = $order;

        abort_unless($this->order, 404);
        abort_unless(auth()->check() && auth()->id() === $this->order->user_id, 403);

        if ($this->order->payment_status === PaymentStatus::PAID) {
            $this->redirect(route('store.order.placed', ['order' => $this->order->order_number]));

            return;
        }

        if (! app(MercadoPagoGateway::class)->isAvailable() || ! $this->order->isPayable()) {
            session()->flash('status', 'Mercado Pago no está disponible para este pedido o la reserva venció.');
            $this->redirect(route('checkout'));

            return;
        }

        $this->order->loadMissing(['items', 'user']);
    }

    public function pay(
        string $token,
        string $paymentMethodId,
        string $identificationNumber,
        ?string $issuerId = null,
    ): void {
        $order = $this->order?->fresh(['items', 'user']);

        if ($order === null || auth()->id() !== $order->user_id) {
            abort(403);
        }

        if ($order->payment_status === PaymentStatus::PAID) {
            $this->redirect(route('store.order.placed', ['order' => $order->order_number]));

            return;
        }

        if (! $order->isPayable()) {
            $this->dispatch(
                'mercadopago:result',
                status: 'error',
                message: 'El pedido ya no está disponible para pago. Vuelve al checkout.',
            );

            return;
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
            $this->dispatch(
                'mercadopago:result',
                status: 'error',
                message: 'Revisa el número de documento e inténtalo otra vez.',
            );

            return;
        }

        $data = $validator->validated();
        $lock = Cache::lock('mercadopago:pay:'.$order->id, 45);

        if (! $lock->get()) {
            $this->dispatch(
                'mercadopago:result',
                status: 'error',
                message: 'Ya estamos procesando este pago. Espera unos segundos antes de reintentar.',
            );

            return;
        }

        $this->processing = true;

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
                $this->dispatch(
                    'mercadopago:result',
                    status: 'paid',
                    url: route('store.order.placed', ['order' => $order->order_number]),
                );

                return;
            }

            if ($payment->status === PaymentStatus::PENDING) {
                $this->dispatch(
                    'mercadopago:result',
                    status: 'pending',
                    url: route('store.order.placed', ['order' => $order->order_number]),
                );

                return;
            }

            $this->dispatch(
                'mercadopago:result',
                status: 'error',
                message: (string) data_get(
                    $payment->raw_response,
                    'message',
                    'No se pudo procesar el pago. Revisa los datos de tu tarjeta e inténtalo otra vez.'
                ),
            );
        } catch (PaymentGatewayNotConfiguredException $e) {
            report($e);
            $this->dispatch(
                'mercadopago:result',
                status: 'error',
                message: 'Mercado Pago no está configurado correctamente. Escríbenos para completar tu compra.',
            );
        } catch (Throwable $e) {
            report($e);
            $this->dispatch(
                'mercadopago:result',
                status: 'error',
                message: 'No pudimos procesar el pago. Inténtalo de nuevo o escríbenos.',
            );
        } finally {
            $this->processing = false;
            $lock->release();
        }
    }

    public function render()
    {
        return view('livewire.store.mercadopago-payment');
    }
}
