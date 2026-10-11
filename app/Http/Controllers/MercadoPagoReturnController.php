<?php

namespace App\Http\Controllers;

use App\Modules\Payments\Gateways\MercadoPago\MercadoPagoPaymentSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MercadoPagoReturnController extends Controller
{
    public function __invoke(Request $request, MercadoPagoPaymentSync $sync): RedirectResponse
    {
        $externalReference = (string) $request->query('external_reference', '');
        $paymentId = (string) $request->query('payment_id', $request->query('collection_id', ''));

        if ($paymentId !== '') {
            $sync->sync($paymentId);
        }

        $order = $externalReference !== ''
            ? \App\Models\Order::where('order_number', $externalReference)->first()
            : null;

        if ($order && auth()->id() === $order->user_id) {
            return redirect()->route('store.order.placed', ['order' => $order->order_number]);
        }

        return redirect()->route('account.orders')->with('status', 'Si completaste el pago, estamos verificando la confirmación.');
    }
}
