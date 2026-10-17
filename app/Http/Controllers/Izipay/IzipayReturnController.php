<?php

namespace App\Http\Controllers\Izipay;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Modules\Payments\Gateways\Izipay\IzipayPaymentSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Retorno del navegador desde el formulario de Izipay.
 *
 * Izipay hace un POST con kr-answer y kr-hash (por eso la ruta está exenta de
 * CSRF en bootstrap/app.php). El POST llega sin la cookie de sesión, así que
 * la confirmación no depende de quién esté conectado: se valida la firma y se
 * contrasta contra el pedido. Después se redirige a una página propia, que sí
 * viaja con sesión.
 */
class IzipayReturnController extends Controller
{
    public function __invoke(Request $request, IzipayPaymentSync $sync): RedirectResponse
    {
        $answer = $sync->answerFromRequest($request);

        if ($answer === null) {
            return redirect()->route('checkout')
                ->with('status', 'No pudimos verificar el resultado del pago. Si el cargo aparece en tu tarjeta, escríbenos.');
        }

        $sync->confirm($answer);

        $orderNumber = trim((string) data_get($answer, 'orderDetails.orderId', ''));
        $order = $orderNumber !== '' ? Order::where('order_number', $orderNumber)->first() : null;

        if ($order === null) {
            return redirect()->route('home');
        }

        if ($order->payment_status === PaymentStatus::PAID) {
            // La confirmación definitiva la da el IPN; el navegador puede
            // llegar antes que la notificación, por eso se confirma aquí
            // también con la firma verificada.
            return auth()->check() && auth()->id() === $order->user_id
                ? redirect()->route('store.order.placed', ['order' => $order->order_number])
                : redirect()->route('login')->with('status', 'Tu pago fue registrado. Inicia sesión para ver tu pedido.');
        }

        return redirect()->route('checkout')
            ->with('status', 'El pago no se completó. Tu pedido sigue reservado y puedes intentarlo otra vez.');
    }
}
