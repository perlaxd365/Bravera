<?php

namespace App\Modules\Payments\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Modules\Dropshipping\Services\SupplierOrderService;
use App\Modules\Ordering\Events\OrderPaid;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected SupplierOrderService $supplierOrderService,
    ) {}

    /**
     * Procesa el pago de un pedido a través de la pasarela configurada.
     */
    public function charge(Order $order, string $method = 'card', ?string $gatewayName = null): Payment
    {
        [$gateway, $gatewayName] = $this->resolveGateway($gatewayName);

        return DB::transaction(function () use ($order, $method, $gateway, $gatewayName) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'gateway' => $gatewayName,
                'method' => $method,
                'amount' => (float) $order->total,
                'currency' => $order->currency,
                'status' => PaymentStatus::PENDING,
            ]);

            $result = $gateway->charge($payment);

            $payment->update([
                'status' => $result['status'],
                'raw_response' => $result['raw'],
                'gateway_transaction_id' => $result['transaction_id'],
            ]);

            if ($result['success']) {
                $payment->markPaid();

                $order->update([
                    'status' => OrderStatus::CONFIRMED,
                    'payment_status' => PaymentStatus::PAID,
                    'paid_at' => now(),
                ]);

                // Generar órdenes de compra a proveedores (dropshipping).
                $orders = $this->supplierOrderService->createForPaidOrder($order);

                event(new OrderPaid($order));

                return $payment->fresh();
            }

            $order->update([
                'status' => OrderStatus::PENDING,
                'payment_status' => PaymentStatus::FAILED,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Resuelve la instancia del gateway y su nombre.
     *
     * Falla de forma cerrada: si la pasarela no está registrada, o si es una
     * pasarela de demostración y no está habilitada explícitamente, lanza una
     * excepción en lugar de aceptar el cobro.
     *
     * @return array{0: PaymentGateway, 1: string}
     */
    private function resolveGateway(?string $gatewayName): array
    {
        $name = $gatewayName ?? config('payments.default_gateway');

        if (! is_string($name) || trim($name) === '') {
            throw new PaymentGatewayNotConfiguredException(
                'No hay pasarela de pago configurada. Define PAYMENT_GATEWAY en el entorno.'
            );
        }

        $class = config('payments.gateways.'.$name);

        if (! is_string($class) || ! class_exists($class)) {
            throw new PaymentGatewayNotConfiguredException(
                "La pasarela de pago [{$name}] no está registrada en config/payments.php."
            );
        }

        if (in_array($name, (array) config('payments.demo_gateways', []), true)
            && ! config('payments.allow_demo_gateway')) {
            throw new PaymentGatewayNotConfiguredException(
                "La pasarela [{$name}] es de demostración y está deshabilitada. "
                .'Registra una pasarela real en config/payments.php antes de operar.'
            );
        }

        $gateway = app($class);

        return [$gateway, $gateway->name()];
    }
}
