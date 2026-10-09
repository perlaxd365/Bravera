<?php

namespace App\Modules\Payments\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Modules\Dropshipping\Services\SupplierOrderService;
use App\Modules\Ordering\Events\OrderPaid;
use App\Modules\Ordering\Services\OrderService;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentService
{
    public function __construct(
        protected SupplierOrderService $supplierOrderService,
    ) {}

    /**
     * Verifica que exista una pasarela operativa y devuelve su nombre.
     *
     * Se invoca antes de crear el pedido. Así, un problema de configuración no
     * deja pedidos huérfanos: el cliente no debería ver un pedido creado si la
     * pasarela no puede cobrar. Los declines del banco, en cambio, sí dejan
     * pedido con estado fallido, porque son un hecho real de la compra.
     */
    public function assertGatewayUsable(?string $gatewayName = null): string
    {
        [, $name] = $this->resolveGateway($gatewayName);

        return $name;
    }

    /**
     * Procesa el pago de un pedido a través de la pasarela configurada.
     *
     * El cobro se hace FUERA de cualquier transacción de base de datos, y a
     * propósito. Culqi no ofrece idempotency key, así que si la transacción
     * quedara abierta durante el round trip y luego revirtiera, el cliente
     * habría pagado y no tendríamos ni pago ni pedido. Además mantiene bloqueos
     * de stock durante los segundos que tarda la API.
     *
     * El orden es: registrar el intento, cobrar, y solo después persistir el
     * resultado. Si el cobro sale pero el registro falla, el log crítico deja
     * el transaction_id para conciliar.
     */
    public function charge(
        Order $order,
        string $method = 'card',
        ?string $gatewayName = null,
        ?string $sourceId = null,
    ): Payment {
        [$gateway, $gatewayName] = $this->resolveGateway($gatewayName);

        // 1. Constancia del intento, confirmada de inmediato.
        $payment = DB::transaction(fn (): Payment => Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => $gatewayName,
            'method' => $method,
            'source_id' => $sourceId,
            'amount' => (float) $order->total,
            'currency' => $order->currency,
            'status' => PaymentStatus::PENDING,
        ]));

        // 2. Movimiento de dinero real. Sin transacción abierta.
        $result = $this->captureCharge($gateway, $payment);

        // 3. Resultado y confirmación.
        return DB::transaction(function () use ($payment, $result, $order, $gatewayName) {
            $payment->update([
                'status' => $result['status'],
                'raw_response' => $result['raw'],
                'gateway_transaction_id' => $result['transaction_id'],
                'source_id' => $result['source_id'] ?? $payment->source_id,
                'checkout_url' => $result['checkout_url'] ?? $payment->checkout_url,
            ]);

            if ($result['success']) {
                return $this->markAsPaid($payment);
            }

            // Un pago asíncrono (Yape/QR) sigue pendiente hasta que la
            // pasarela notifique por webhook: no es un fallo.
            $pending = $result['status'] === PaymentStatus::PENDING->value;
            $retryableModal = ! $pending
                && $order->gateway_order_id !== null
                && in_array(
                    $gatewayName,
                    (array) config('payments.modal_gateways', []),
                    true,
                );

            $order->update([
                'status' => OrderStatus::PENDING,
                // Un rechazo de tarjeta/Yape dentro del checkout modal no
                // cancela la orden. El comprador puede corregir los datos y
                // volver a intentar con la misma orden y la reserva vigente.
                'payment_status' => ($pending || $retryableModal)
                    ? PaymentStatus::PENDING
                    : PaymentStatus::FAILED,
            ]);

            // Fuera del checkout modal, un fallo sí es definitivo y libera stock.
            if (! $pending && ! $retryableModal) {
                app(OrderService::class)->releaseStock($order);
            }

            return $payment->fresh();
        });
    }

    /**
     * Registra el intento de pago de un método asíncrono cuya orden en la
     * pasarela ya existe.
     *
     * El flujo del modal no puede pasar por charge(): el pedido y su orden
     * (ord_...) se crean antes de abrir el checkout de Culqi, y cuando el
     * comprador elige Yape, billetera, banca móvil, agente o Cuotéalo ya no
     * queda nada que cobrar contra la API: solo queda dejar constancia de que
     * se está esperando la confirmación.
     *
     * Es idempotente por orden de la pasarela: el modal puede volver a llamar
     * al callback si el comprador reintenta dentro de la misma sesión, y no
     * queremos dos pagos apuntando al mismo ord_.
     *
     * @param  array<string, mixed>|null  $raw
     */
    public function recordPendingAsyncPayment(
        Order $order,
        string $method,
        string $gatewayTransactionId,
        ?string $gatewayName = null,
        ?string $checkoutUrl = null,
        ?array $raw = null,
    ): Payment {
        $name = $gatewayName ?? config('payments.default_gateway');

        if (! is_string($name) || $name === '') {
            throw new PaymentGatewayNotConfiguredException(
                'No hay pasarela de pago configurada. Define PAYMENT_GATEWAY en el entorno.'
            );
        }

        $existing = Payment::query()
            ->where('gateway', $name)
            ->where('gateway_transaction_id', $gatewayTransactionId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(fn (): Payment => Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => $name,
            'method' => $method,
            'gateway_transaction_id' => $gatewayTransactionId,
            'source_id' => $gatewayTransactionId,
            'checkout_url' => $checkoutUrl,
            'amount' => (float) $order->total,
            'currency' => $order->currency,
            'status' => PaymentStatus::PENDING,
            'raw_response' => $raw,
        ]));
    }

    /**
     * Ejecuta el cobro y traduce cualquier fallo en un resultado uniforme.
     *
     * Los errores de configuración se propagan: no son un pago rechazado y
     * silenciarlos dejaría al comprador viendo "intenta de nuevo" para siempre.
     *
     * @return array<string, mixed>
     */
    private function captureCharge(PaymentGateway $gateway, Payment $payment): array
    {
        try {
            return $gateway->charge($payment->fresh());
        } catch (PaymentGatewayNotConfiguredException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $this->logUnrecordedCharge($payment, $e);

            return [
                'success' => false,
                'transaction_id' => null,
                'status' => PaymentStatus::FAILED->value,
                'message' => 'No pudimos procesar el pago. Inténtalo de nuevo.',
                'raw' => ['code' => 'gateway_exception', 'exception' => $e::class],
            ];
        }
    }

    /**
     * Si el cobro pudo completarse pero el registro falló, la transacción
     * queda huérfana en Culqi. Sin idempotency key no podemos recuperarla sola,
     * así que se deja el rastro completo para conciliar a mano.
     */
    private function logUnrecordedCharge(Payment $payment, Throwable $e): void
    {
        Log::critical('No se pudo confirmar el cobro en la pasarela. Revisar y conciliar.', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'gateway' => $payment->gateway,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'exception' => $e::class,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Confirma un pago ya cobrado y dispara el resto del flujo.
     *
     * Es idempotente: si el pedido ya figuraba como pagado no vuelve a generar
     * órdenes de proveedor ni a emitir el evento, porque el webhook de la
     * pasarela puede llegar más de una vez. La guarda mira el pedido y no el
     * pago, porque el flujo de charge() ya marca el pago como pagado antes de
     * llegar aquí.
     */
    public function markAsPaid(Payment $payment, ?array $raw = null): Payment
    {
        $order = $payment->order;

        if ($order !== null && $order->payment_status === PaymentStatus::PAID) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $order, $raw) {
            $payment->update(array_filter([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
                'raw_response' => $raw,
            ], fn ($value) => $value !== null));

            $order->update([
                'status' => OrderStatus::CONFIRMED,
                'payment_status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ]);
            $cart = $order->cart;
            if ($cart !== null) {
                $cart->update(['status' => 'converted']);
                // El pedido ya conserva sus propias líneas y precios. Vaciar
                // el carrito evita que siga apareciendo con los productos
                // comprados al volver a la tienda.
                $cart->items()->delete();
            }

            // Generar órdenes de compra a proveedores (dropshipping).
            $this->supplierOrderService->createForPaidOrder($order);

            event(new OrderPaid($order));

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

        if (
            in_array($name, (array) config('payments.demo_gateways', []), true)
            && ! config('payments.allow_demo_gateway')
        ) {
            throw new PaymentGatewayNotConfiguredException(
                "La pasarela [{$name}] es de demostración y está deshabilitada. "
                    .'Registra una pasarela real en config/payments.php antes de operar.'
            );
        }

        $gateway = app($class);

        return [$gateway, $gateway->name()];
    }
}
