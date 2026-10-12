<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Modules\Payments\Gateways\Culqi\CulqiGateway;
use App\Modules\Payments\Gateways\Culqi\CulqiStatus;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook de Culqi.
 *
 * No se confía en el cuerpo del webhook: antes de marcar un pago como pagado
 * se re-consulta a la API de Culqi (server-to-server). Así, un request
 * falsificado no puede confirmar un pedido, y un evento duplicado no genera
 * dos veces las órdenes al proveedor porque markAsPaid() es idempotente.
 */
class CulqiWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $type = (string) $request->input('type', '');
        $objectId = (string) $request->input('data.id', '');

        Log::info('Culqi webhook recibido.', ['type' => $type, 'id' => $objectId]);

        if ($objectId === '') {
            return $this->ack();
        }

        $payment = $this->findPayment($objectId);

        if ($payment === null) {
            Log::warning('Culqi webhook sin pago local.', ['id' => $objectId, 'type' => $type]);

            return $this->ack();
        }

        return match ($type) {
            'charge.paid',
            'order.paid',
            'order.status.changed' => $this->confirm($payment, $objectId),

            'charge.refunded',
            'order.refunded',
            'refund.paid',
            // Nombres que Culqi publica de verdad para devoluciones.
            'refund.creation.succeeded',
            'refund.update.succeeded' => $this->refund($payment, $objectId),

            'refund.creation.failed',
            'refund.update.failed' => $this->refundFailed($payment, $objectId, $type),

            default => $this->ack(),
        };
    }

    private function confirm(Payment $payment, string $objectId): JsonResponse
    {
        if ($payment->status === PaymentStatus::PAID) {
            return $this->ack();
        }

        $gateway = $this->culqiGateway();

        if ($gateway === null) {
            return $this->ack();
        }

        // Verificación real contra Culqi: el webhook solo dispara la consulta.
        $remote = str_starts_with($objectId, 'ord_')
            ? $gateway->verifyOrder($objectId)
            : $gateway->verifyCharge($objectId);

        if ($remote === null) {
            Log::warning('Culqi no devolvió el objeto en la verificación.', ['id' => $objectId]);

            return $this->ack();
        }

        // Culqi no usa un único campo para decir "cobrado": los cargos se leen
        // por outcome.code/outcome.type y las órdenes de Yape por status. La
        // misma regla usa CulqiGateway al cobrar, para que el pago no termine
        // pagado en un camino y pendiente en el otro.
        if (CulqiStatus::resolve($remote) !== CulqiStatus::PAID) {
            /*   Log::info('Webhook de Culqi con estado no pagado.', [
                'id' => $objectId,
                'resolved' => CulqiStatus::resolve($remote),
                'merchant_message' => CulqiStatus::merchantMessage($remote),
            ]); */

            Log::info('CULQI VERIFY ORDER - RESPUESTA COMPLETA', [
                'id' => $objectId,
                'remote' => $remote,
                'resolved' => CulqiStatus::resolve($remote),
            ]);

            return $this->ack();
        }

        $this->paymentService->markAsPaid($payment, $remote);

        return $this->ack();
    }

    /**
     * Reembolso notificado por Culqi.
     *
     * Culqi no expone (a día de hoy en esta integración) la lectura de un
     * refund, así que no se puede comprobar el estado del reembolso en la API.
     * Lo que sí se exige es que el cargo exista realmente en Culqi: sin eso no
     * se toca el estado local y queda registrado para conciliación manual.
     */
    private function refund(Payment $payment, string $objectId = ''): JsonResponse
    {
        if ($payment->status === PaymentStatus::REFUNDED) {
            return $this->ack();
        }

        $chargeId = (string) $payment->gateway_transaction_id;
        $gateway = $this->culqiGateway();
        $remote = $chargeId !== '' && $gateway !== null
            ? $gateway->verifyCharge($chargeId)
            : null;

        if ($remote === null) {
            Log::warning('Culqi notificó un reembolso que no se pudo verificar en la API.', [
                'payment_id' => $payment->id,
                'charge_id' => $chargeId,
            ]);

            return $this->ack();
        }

        // Culqi no permite consultar una devolución por API: este webhook es
        // la confirmación definitiva. Si la devolución se registró en Brevare,
        // se cierra aquí (el evento trae el ID `ref_...`).
        $refund = str_starts_with($objectId, 'ref_')
            ? Refund::query()->where('gateway_refund_id', $objectId)->first()
            : $payment->refunds()->where('status', RefundStatus::PROCESSING->value)->latest('id')->first();

        if ($refund !== null && $refund->status !== RefundStatus::COMPLETED) {
            $refund->update([
                'status' => RefundStatus::COMPLETED,
                'gateway_refund_id' => $objectId !== '' ? $objectId : $refund->gateway_refund_id,
                'completed_at' => now(),
            ]);
        }

        // Una devolución parcial no convierte el pago en reembolsado: solo
        // cuando lo confirmado cubre el total cobrado. Sin devoluciones
        // registradas en Brevare se respeta el aviso de la pasarela.
        $hasLocalRefunds = $payment->refunds()->exists();
        $completed = (float) $payment->refunds()
            ->where('status', RefundStatus::COMPLETED->value)
            ->sum('amount');

        if ($hasLocalRefunds && $completed + 0.005 < (float) $payment->amount) {
            Log::info('Devolución parcial de Culqi registrada.', [
                'payment_id' => $payment->id,
                'refunded' => $completed,
                'amount' => (float) $payment->amount,
            ]);

            return $this->ack();
        }

        $payment->update(['status' => PaymentStatus::REFUNDED]);

        $payment->order?->update([
            'payment_status' => PaymentStatus::REFUNDED,
        ]);

        Log::info('Reembolso de Culqi registrado.', [
            'payment_id' => $payment->id,
            'charge_id' => $chargeId,
        ]);

        return $this->ack();
    }

    /**
     * Culqi avisó que la devolución no pudo procesarse.
     *
     * No se toca el pago: el dinero no se devolvió. La devolución registrada
     * queda en `failed` para que el panel muestre el motivo.
     */
    private function refundFailed(Payment $payment, string $objectId, string $type): JsonResponse
    {
        $refund = str_starts_with($objectId, 'ref_')
            ? Refund::query()->where('gateway_refund_id', $objectId)->first()
            : $payment->refunds()->where('status', RefundStatus::PROCESSING->value)->latest('id')->first();

        if ($refund !== null && $refund->status !== RefundStatus::COMPLETED) {
            $refund->update([
                'status' => RefundStatus::FAILED,
                'gateway_refund_id' => $objectId !== '' ? $objectId : $refund->gateway_refund_id,
                'resolution_notes' => 'Culqi notificó que la devolución no pudo procesarse ('.$type.').',
            ]);
        }

        Log::warning('Culqi notificó una devolución fallida.', [
            'payment_id' => $payment->id,
            'refund_id' => $objectId,
            'type' => $type,
        ]);

        return $this->ack();
    }

    private function findPayment(string $objectId): ?Payment
    {
        $payment = Payment::query()
            ->where('gateway', 'culqi')
            ->where(function ($query) use ($objectId) {
                $query->where('gateway_transaction_id', $objectId)
                    ->orWhere('source_id', $objectId);
            })
            ->first();

        if ($payment !== null) {
            return $payment;
        }

        // Los webhooks de devolución traen el ID de la devolución (`ref_...`),
        // no el del cargo, así que el pago se ubica por la devolución
        // registrada en Brevare.
        $refundPayment = Refund::query()
            ->where('gateway_refund_id', $objectId)
            ->latest('id')
            ->first()
            ?->payment;

        return $refundPayment ?? $this->pendingPaymentForOrder($objectId);
    }

    /**
     * Pago pendiente de una orden de Culqi que todavía no tiene pago local.
     *
     * Con el checkout en modal, Culqi puede confirmar la orden antes de que el
     * navegador devuelva el evento de pago. Si este webhook llegara antes y no
     * reconociera la orden, se descartaría con un 200 y el pago se perdería: el
     * cliente habría pagado y el pedido terminaría cancelado por el comando de
     * expiración, con el stock ya devuelto a la venta. Por eso la orden, que
     * guarda su gateway_order_id desde antes de abrir el modal, sirve de ancla.
     *
     * El estado no se da por bueno aquí: confirm() lo contrasta contra la API
     * de Culqi igual que en cualquier otro caso.
     */
    private function pendingPaymentForOrder(string $objectId): ?Payment
    {
        if (! str_starts_with($objectId, 'ord_')) {
            return null;
        }

        $order = Order::query()
            ->where('gateway_order_id', $objectId)
            ->first();

        if ($order === null) {
            return null;
        }

        return $this->paymentService->recordPendingAsyncPayment(
            $order,
            'order',
            $objectId,
            'culqi',
        );
    }

    private function culqiGateway(): ?CulqiGateway
    {
        $class = config('payments.gateways.culqi');

        return is_string($class) && class_exists($class) ? app($class) : null;
    }

    /**
     * Siempre 200: si devolvemos error, Culqi insiste con reintentos.
     */
    private function ack(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }
}
