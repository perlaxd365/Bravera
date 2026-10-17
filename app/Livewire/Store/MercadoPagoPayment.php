<?php

namespace App\Livewire\Store;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Livewire\Store\Concerns\ProcessesMercadoPagoPayment;
use App\Modules\Payments\Gateways\MercadoPago\MercadoPagoGateway;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pantalla directa de pago de Checkout API.
 *
 * El flujo normal del checkout abre el modal; esta página queda disponible
 * para enlaces directos y para el historial de pedidos.
 */
#[Layout('store.layouts.app')]
class MercadoPagoPayment extends Component
{
    use ProcessesMercadoPagoPayment;

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

        $this->processing = true;

        try {
            $result = $this->processMercadoPagoPayment(
                $order,
                $token,
                $identificationNumber,
            );

            if (in_array($result['status'], ['paid', 'pending'], true)) {
                $this->dispatch(
                    'mercadopago:result',
                    status: $result['status'],
                    url: $result['url'],
                );

                return;
            }

            $this->dispatch(
                'mercadopago:result',
                status: 'error',
                message: $result['message'],
            );
        } finally {
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.store.mercadopago-payment');
    }
}
