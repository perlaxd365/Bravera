<?php

namespace App\Livewire\Store;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Modules\Payments\Gateways\Izipay\IzipayGateway;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * Página donde el comprador completa el pago en el SmartForm de Izipay.
 *
 * Se llega aquí desde el checkout, después de crear el pedido y su formToken.
 * El formulario lo dibuja el SDK de Izipay con la clave pública, así que los
 * datos de la tarjeta nunca pasan por Brevare.
 */
#[Layout('store.layouts.app')]
class IzipayPayment extends Component
{
    public ?Order $order = null;

    public string $formToken = '';

    public string $publicKey = '';

    public string $jsUrl = '';

    public string $cssUrl = '';

    public string $themeUrl = '';

    public string $returnUrl = '';

    public function mount(?Order $order): void
    {
        $this->order = $order;

        abort_unless($this->order, 404);
        abort_unless(auth()->id() === $this->order->user_id, 403);

        if ($this->order->payment_status === PaymentStatus::PAID) {
            $this->redirect(route('store.order.placed', ['order' => $this->order->order_number]));

            return;
        }

        $gateway = app(IzipayGateway::class);

        if (! $gateway->isAvailable() || ! $this->order->isPayable()) {
            $this->redirect(route('checkout'));

            return;
        }

        $this->publicKey = $gateway->publicKey();
        $this->jsUrl = (string) config('payments.izipay.js_url');
        $this->cssUrl = (string) config('payments.izipay.css_url');
        $this->themeUrl = (string) config('payments.izipay.theme_url');
        $this->returnUrl = route('payments.izipay.return', [], true);

        $payment = $this->order->payments()->where('gateway', 'izipay')->latest('id')->first();
        $this->formToken = (string) data_get($payment?->raw_response, 'form_token', '');

        if ($this->formToken !== '') {
            return;
        }

        // El token pudo no guardarse (o el comprador vuelve más tarde): se pide
        // otro con el mismo pedido en lugar de mostrar un formulario vacío.
        try {
            $created = $gateway->createFormToken($this->order);
            $this->formToken = $created['form_token'];

            $payment?->update([
                'raw_response' => array_merge((array) $payment->raw_response, [
                    'form_token' => $this->formToken,
                ]),
            ]);
        } catch (Throwable $e) {
            report($e);

            session()->flash('status', 'No pudimos abrir el formulario de Izipay. Vuelve a intentarlo.');
            $this->redirect(route('checkout'));
        }
    }

    public function render()
    {
        return view('livewire.store.izipay-payment');
    }
}
