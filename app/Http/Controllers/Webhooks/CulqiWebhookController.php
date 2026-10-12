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
use App\Modules\Payments\Services\RefundService;
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
        protected RefundService $refundService,
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

        $this->recordChargeId($payment, $objectId, $remote);

        $this->paymentService->markAsPaid($payment, $remote);

        return $this->ack();
    }

    /**
     * Guarda el cargo de Culqi del pago cuando la API lo entrega.
     *
     * Los métodos asíncronos se cobran con una orden (`ord_...`), pero la
     * devolución exige un cargo (`chr_...`, POST /v2/refunds). El spec no
     * documenta cómo obtener el cargo de una orden, así que se aprovecha
     * cualquier respuesta que sí lo traiga.
     *
     * @param  array<string, mixed>  $remote
     */
    private function recordChargeId(Payment $payment, string $objectId, array $remote): void
    {
        $chargeId = is_string($remote['id'] ?? null) ? $remote['id'] : $objectId;

        if (! str_starts_with($chargeId, 'chr_') || $payment->gateway_charge_id === $chargeId) {
            return;
        }

        $payment->update(['gateway_charge_id' => $chargeId]);
    }

    /**
     * Devolución notificada por Culqi.
     *
     * Tampoco se confía en el cuerpo del webhook: cuando el evento trae el ID
     * de la devolución (`ref_...`) se consulta en la API
     * (GET /v2/refunds/{id}), que es la forma documentada de conocer su estado
     * real. El camino de "cargo reembolsado" se mantiene como respaldo y exige
     * que el cargo exista en Culqi.
     */
    private function refund(Payment $payment, string $objectId = ''): JsonResponse
    {
        if (str_starts_with($objectId, 'ref_')) {
            return $this->refundFromApi($payment, $objectId);
        }

        return $this->refundFromCharge($payment);
    }

    /**
     * Devolución consultada en la API de Culqi.
     */
    private function refundFromApi(Payment $payment, string $refundId): JsonResponse
    {
        $remote = $this->culqiGateway()?->verifyRefund($refundId);

        if ($remote === null) {
            Log::warning('Culqi notificó una devolución que no se pudo consultar en la API.', [
                'payment_id' => $payment->id,
                'refund_id' => $refundId,
            ]);

            return $this->ack();
        }

        $refund = $this->findRefundRecord($payment, $refundId);

        if ($refund !== null) {
            // RefundService interpreta el estado de Culqi y, cuando la
            // devolución queda confirmada, cierra también el pago.
            $this->refundService->applyGatewayStatus($refund, $remote);

            return $this->ack();
        }

        // Devolución hecha fuera de Brevare: el pago se toca solo cuando Culqi
        // confirma que el dinero ya volvió.
        if (CulqiStatus::resolveRefund($remote) === CulqiStatus::PAID) {
            $this->markPaymentRefunded($payment);
        }

        return $this->ack();
    }

    /**
     * Respaldo para eventos de "cargo reembolsado".
     *
     * Sin la evidencia de que el cargo exista en Culqi no se toca el estado
     * local: queda registrado para conciliación manual.
     */
    private function refundFromCharge(Payment $payment): JsonResponse
    {
        if ($payment->status === PaymentStatus::REFUNDED) {
            return $this->ack();
        }

        $chargeId = $this->chargeIdOf($payment);
        $gateway = $this->culqiGateway();
        $remote = $chargeId !== null && $gateway !== null
            ? $gateway->verifyCharge($chargeId)
            : null;

        if ($remote === null) {
            Log::warning('Culqi notificó un reembolso que no se pudo verificar en la API.', [
                'payment_id' => $payment->id,
                'charge_id' => $chargeId,
            ]);

            return $this->ack();
        }

        $this->markPaymentRefunded($payment);

        return $this->ack();
    }

    /**
     * Culqi avisó que la devolución no pudo procesarse.
     *
     * `refund.creation.failed` significa que la devolución no llegó a crearse,
     * así que la solicitud vuelve a quedar pendiente y puede reintentarse (por
     * ejemplo, después de vincular el cargo del pago). No se toca el pago en
     * ningún caso: el dinero no se devolvió.
     */
    private function refundFailed(Payment $payment, string $objectId, string $type): JsonResponse
    {
        $refund = $this->findRefundRecord($payment, $objectId);

        if ($refund === null) {
            Log::warning('Culqi notificó una devolución fallida sin registro local.', [
                'payment_id' => $payment->id,
                'refund_id' => $objectId,
                'type' => $type,
            ]);

            return $this->ack();
        }

        if ($type === 'refund.creation.failed') {
            $this->refundService->releaseForRetry(
                $refund,
                'Culqi notificó que la devolución no pudo crearse; la solicitud sigue pendiente.',
                'refund_creation_failed'
            );

            return $this->ack();
        }

        Log::warning('Culqi notificó una devolución fallida.', [
            'payment_id' => $payment->id,
            'refund_id' => $objectId,
            'type' => $type,
        ]);

        return $this->ack();
    }

    /**
     * Devolución local asociada al evento: por su ID de Culqi o, si el evento
     * no lo trae, la última que quedó en trámite.
     */
    private function findRefundRecord(Payment $payment, string $objectId): ?Refund
    {
        if (str_starts_with($objectId, 'ref_')) {
            $refund = Refund::query()->where('gateway_refund_id', $objectId)->first();

            if ($refund !== null) {
                return $refund;
            }
        }

        return $payment->refunds()
            ->where('status', RefundStatus::PROCESSING->value)
            ->latest('id')
            ->first();
    }

    /**
     * Cargo de Culqi del pago: el vinculado o el propio ID si ya es un cargo.
     */
    private function chargeIdOf(Payment $payment): ?string
    {
        foreach ([(string) $payment->gateway_charge_id, (string) $payment->gateway_transaction_id] as $candidate) {
            if (str_starts_with($candidate, 'chr_')) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Marca el pago como reembolsado.
     *
     * Una devolución parcial no convierte el pago en reembolsado: solo cuando
     * lo confirmado cubre el total cobrado. Sin devoluciones registradas en
     * Brevare se respeta el aviso de la pasarela.
     */
    private function markPaymentRefunded(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::REFUNDED) {
            return;
        }

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

            return;
        }

        $payment->update(['status' => PaymentStatus::REFUNDED]);

        $payment->order?->update([
            'payment_status' => PaymentStatus::REFUNDED,
        ]);

        Log::info('Reembolso de Culqi registrado.', [
            'payment_id' => $payment->id,
            'charge_id' => $this->chargeIdOf($payment),
        ]);
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
