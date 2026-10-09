<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\SupplierPaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupplierOrder;
use App\Models\SupplierPayment;
use App\Modules\Ordering\Events\OrderStatusChanged;
use App\Modules\Ordering\Services\OrderCancellationService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin.layouts.app')]
class Show extends Component
{
    public Order $order;

    public string $currentStatus = '';

    public bool $showPayments = false;

    public array $supplierStatus = [];

    public array $trackingCode = [];

    public array $paymentMethod = [];

    public array $paymentReference = [];

    /** @var array<int, string> orderItemId => motivo de cancelación */
    public array $cancelReason = [];

    /** @var array<int, bool> orderItemId => mostrar el formulario de cancelación */
    public array $showCancelFor = [];

    public bool $showCancelWholeOrder = false;

    public string $cancelWholeOrderReason = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'user',
            'items.variant.images',
            'items.product.variants.images',
            'coupon',
            'payment',
            'supplierOrders.supplier',
            'supplierOrders.items',
            'supplierOrders.payment',
        ]);

        $this->currentStatus = $order->status->value;

        foreach ($this->order->supplierOrders as $so) {
            $this->supplierStatus[$so->id] = $so->status->value;
            $this->trackingCode[$so->id] = $so->tracking_code ?? '';
            $this->paymentMethod[$so->id] = 'transferencia';
        }
    }

    public function updateStatus(): void
    {
        $to = OrderStatus::tryFrom($this->currentStatus);

        if (! $to) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El estado seleccionado no es válido.',
            ]);

            return;
        }

        $from = $this->order->status;

        if ($from === $to) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'El pedido ya está en ese estado.',
            ]);

            return;
        }

        $this->order->update(['status' => $to]);

        // Marcar timestamp del nuevo estado
        $this->order->markStatusTimestamp($to);

        OrderStatusChanged::dispatch($this->order->fresh(), $from, $to);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Estado actualizado a "'.$to->label().'". Se notificó al cliente por correo.',
        ]);
    }

    /**
     * Muestra u oculta el formulario de cancelación de un producto.
     */
    public function toggleCancelForm(int $orderItemId): void
    {
        if ($this->showCancelFor[$orderItemId] ?? false) {
            unset($this->showCancelFor[$orderItemId]);

            return;
        }

        $this->showCancelFor[$orderItemId] = true;
        $this->cancelReason[$orderItemId] = $this->cancelReason[$orderItemId] ?? '';
    }

    /**
     * Cancela un solo producto del pedido.
     */
    public function cancelItem(int $orderItemId): void
    {
        $item = $this->order->items->firstWhere('id', $orderItemId);

        if (! $item) {
            return;
        }

        $reason = trim((string) ($this->cancelReason[$orderItemId] ?? ''));

        app(OrderCancellationService::class)->cancelItem(
            $item,
            $reason ?: null,
            auth()->id(),
        );

        unset($this->showCancelFor[$orderItemId], $this->cancelReason[$orderItemId]);

        $this->order->refresh();
        $this->currentStatus = $this->order->status->value;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Se canceló "'.$item->product_name.'". Se notificó al cliente por correo.',
        ]);
    }

    /**
     * Reactiva un producto cancelado.
     */
    public function restoreItem(int $orderItemId): void
    {
        $item = $this->order->items->firstWhere('id', $orderItemId);

        if (! $item) {
            return;
        }

        app(OrderCancellationService::class)->restoreItem($item, auth()->id());

        $this->order->refresh();
        $this->currentStatus = $this->order->status->value;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Se reactivó "'.$item->product_name.'".',
        ]);
    }

    /**
     * Cancela todos los productos vivos del pedido.
     */
    public function cancelWholeOrder(): void
    {
        $reason = trim($this->cancelWholeOrderReason);

        $order = app(OrderCancellationService::class)->cancelOrder(
            $this->order,
            $reason ?: null,
            auth()->id(),
        );

        $this->showCancelWholeOrder = false;
        $this->cancelWholeOrderReason = '';

        $this->order->refresh();
        $this->currentStatus = $this->order->status->value;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Se canceló el pedido '.$order->order_number.'. Se notificó al cliente por correo.',
        ]);
    }

    public function updateSupplierStatus(SupplierOrder $supplierOrder): void
    {
        $status = $this->supplierStatus[$supplierOrder->id] ?? $supplierOrder->status->value;
        $to = SupplierOrderStatus::tryFrom($status);

        if (! $to) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El estado seleccionado no es válido.',
            ]);

            return;
        }

        $supplierOrder->update([
            'status' => $to,
            'tracking_code' => $this->trackingCode[$supplierOrder->id] ?? null,
            'sent_at' => $to === SupplierOrderStatus::SENT ? ($supplierOrder->sent_at ?? now()) : $supplierOrder->sent_at,
            'delivered_at' => $to === SupplierOrderStatus::DELIVERED ? now() : $supplierOrder->delivered_at,
        ]);

        if ($to === SupplierOrderStatus::DELIVERED) {
            $this->order->refresh();

            $this->maybeCompleteOrder();
        }

        $this->order->refresh();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Estado de la orden a proveedor actualizado.',
        ]);
    }

    private function maybeCompleteOrder(): void
    {
        $supplierOrders = $this->order->supplierOrders;

        $allDelivered = $supplierOrders->isNotEmpty()
            && $supplierOrders->every(fn ($so) => $so->status === SupplierOrderStatus::DELIVERED);

        if (! $allDelivered || ! $this->order->status->isActive()) {
            return;
        }

        $to = OrderStatus::DELIVERED;
        $from = $this->order->status;

        $this->order->update(['status' => $to]);
        $this->currentStatus = $to->value;

        OrderStatusChanged::dispatch($this->order->fresh(), $from, $to);
    }

    public function registerPayment(SupplierOrder $supplierOrder): void
    {
        if ($supplierOrder->payment) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Esta orden ya tiene un pago registrado.',
            ]);

            return;
        }

        $supplierOrder->loadMissing('items.orderItem');

        $method = $this->paymentMethod[$supplierOrder->id] ?? 'transferencia';
        $reference = trim((string) ($this->paymentReference[$supplierOrder->id] ?? ''));

        $margin = $this->marginFor($supplierOrder);

        $payment = SupplierPayment::create([
            'supplier_id' => $supplierOrder->supplier_id,
            'supplier_order_id' => $supplierOrder->id,
            'reference' => $reference ?: null,
            'amount' => (float) $supplierOrder->total_cost,
            'margin_amount' => $margin,
            'method' => $method,
            'status' => SupplierPaymentStatus::PAID,
            'paid_at' => now(),
            'created_by' => auth()->id(),
        ]);

        $supplierOrder->update(['status' => SupplierOrderStatus::ACCEPTED]);
        $this->supplierStatus[$supplierOrder->id] = SupplierOrderStatus::ACCEPTED->value;

        $this->order->refresh();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Pago a proveedor registrado por S/ '.number_format((float) $payment->amount, 2)
                .' · margen S/ '.number_format($margin, 2).'.',
        ]);
    }

    /**
     * Margen de Brevare correspondiente a la orden del proveedor:
     * lo que el cliente pagó por sus productos menos el costo del proveedor.
     */
    private function marginFor(SupplierOrder $supplierOrder): float
    {
        $itemIds = $supplierOrder->items->pluck('order_item_id')->filter()->all();

        if ($itemIds === []) {
            return 0.0;
        }

        $items = OrderItem::whereIn('id', $itemIds)->get();

        $revenue = round($items->sum(fn ($i) => (float) $i->line_subtotal), 2);
        $shippingCharged = round($items->sum(fn ($i) => (float) $i->shipping_price), 2);
        $cost = (float) $supplierOrder->total_cost;

        // El descuento del cupón se prorratea entre los productos del pedido.
        $orderGross = (float) $this->order->subtotal + (float) $this->order->shipping_total;
        $share = $orderGross > 0 ? ($revenue + $shippingCharged) / $orderGross : 0.0;
        $discountShare = round((float) $this->order->discount_total * $share, 2);

        return round(max(0, $revenue + $shippingCharged - $discountShare - $cost), 2);
    }

    public function render()
    {
        return view('livewire.admin.orders.show');
    }
}
