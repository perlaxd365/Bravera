<?php

namespace App\Livewire\Admin\Refunds;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use App\Modules\Payments\Services\RefundService;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Sección de devoluciones del panel: aquí se validan.
 *
 * Las solicitudes nacen en el detalle del pedido y quedan en `pending`. Desde
 * esta pantalla un administrador aprueba (y recién ahí se envía el reembolso a
 * Culqi) o rechaza, siempre dejando registrado quién decidió.
 */
#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $status = 'pending';

    /** @var array<int, string> refundId => motivo del rechazo */
    public array $rejectReason = [];

    /** @var array<int, bool> refundId => formulario de rechazo abierto */
    public array $showRejectFor = [];

    /** @var array<int, string> refundId => cargo de Culqi (chr_...) por vincular */
    public array $chargeId = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function toggleRejectForm(int $refundId): void
    {
        if ($this->showRejectFor[$refundId] ?? false) {
            unset($this->showRejectFor[$refundId]);

            return;
        }

        $this->showRejectFor[$refundId] = true;
        $this->rejectReason[$refundId] = $this->rejectReason[$refundId] ?? '';
    }

    /**
     * Aprueba la devolución y la ejecuta en la pasarela.
     */
    public function approve(int $refundId): void
    {
        $refund = Refund::query()->findOrFail($refundId);
        $amount = 'S/ '.number_format((float) $refund->amount, 2);

        try {
            $refund = app(RefundService::class)->approve($refund, auth()->id());
        } catch (PaymentGatewayNotConfiguredException $e) {
            // La pasarela no está configurada: la solicitud sigue pendiente.
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        [$type, $message] = match ($refund->status) {
            RefundStatus::COMPLETED => [
                'success',
                'Devolución aprobada. '.$amount.' devueltos al cliente.',
            ],
            RefundStatus::PROCESSING => [
                'warning',
                'Culqi recibió la devolución de '.$amount.' y la mantiene en proceso.',
            ],
            RefundStatus::FAILED => [
                'error',
                $refund->resolution_notes ?: 'La pasarela rechazó la devolución.',
            ],
            default => [
                'warning',
                'La devolución ya había sido validada antes.',
            ],
        };

        $this->dispatch('notify', ['type' => $type, 'message' => $message]);
    }

    /**
     * Rechaza la solicitud. No se envía nada a la pasarela.
     */
    public function reject(int $refundId): void
    {
        $refund = Refund::query()->findOrFail($refundId);
        $reason = trim((string) ($this->rejectReason[$refundId] ?? ''));

        app(RefundService::class)->reject($refund, auth()->id(), $reason ?: null);

        unset($this->showRejectFor[$refundId], $this->rejectReason[$refundId]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Devolución rechazada. No se envió ningún reembolso.',
        ]);
    }

    /**
     * Consulta en Culqi el estado real de una devolución en trámite.
     *
     * Culqi no necesita webhook para esto: GET /v2/refunds/{id} es la forma
     * documentada de conocer el estado.
     */
    public function refresh(int $refundId): void
    {
        $refund = Refund::query()->findOrFail($refundId);

        try {
            $refund = app(RefundService::class)->refreshWithGateway($refund);
        } catch (PaymentGatewayNotConfiguredException|InvalidArgumentException $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->dispatch('notify', [
            'type' => $refund->status === RefundStatus::COMPLETED ? 'success' : 'warning',
            'message' => 'Culqi reporta la devolución como "'.$refund->statusLabel().'".',
        ]);
    }

    /**
     * Vincula el cargo de Culqi a un pago cobrado con una orden (`ord_...`).
     *
     * Se valida contra la API antes de guardarlo, así que un cargo de otra
     * cuenta o de otro entorno no llega a persistirse.
     */
    public function linkCharge(int $refundId): void
    {
        $refund = Refund::query()->findOrFail($refundId);
        $payment = $refund->payment;

        if ($payment === null) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'La devolución no tiene un pago asociado.',
            ]);

            return;
        }

        try {
            app(RefundService::class)->linkCharge($payment, (string) ($this->chargeId[$refundId] ?? ''));
        } catch (PaymentGatewayNotConfiguredException|InvalidArgumentException $e) {
            $this->addError('chargeId.'.$refundId, $e->getMessage());

            return;
        }

        unset($this->chargeId[$refundId]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Cargo vinculado. Ya puedes aprobar la devolución.',
        ]);
    }

    public function render()
    {
        $counts = collect(RefundStatus::cases())
            ->mapWithKeys(fn (RefundStatus $status) => [
                $status->value => Refund::query()->where('status', $status->value)->count(),
            ])
            ->all();

        $pendingTotal = (float) Refund::query()
            ->where('status', RefundStatus::PENDING->value)
            ->sum('amount');

        $refunds = Refund::query()
            ->with([
                'order:id,order_number,total,currency,payment_status,customer_snapshot',
                'payment:id,gateway,method,gateway_transaction_id,gateway_charge_id,status',
                'requestedBy:id,name',
                'validatedBy:id,name',
            ])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.addcslashes(trim($this->search), '%_\\').'%';

                $query->where(function ($query) use ($term): void {
                    $query->whereHas('order', fn ($order) => $order->where('order_number', 'like', $term))
                        ->orWhereHas('order.user', fn ($user) => $user->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term))
                        ->orWhereHas('requestedBy', fn ($user) => $user->where('name', 'like', $term));
                });
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('id')
            ->paginate(15);

        return view('livewire.admin.refunds.index', compact('refunds', 'counts', 'pendingTotal'))
            ->title('Devoluciones | Brevare');
    }
}
