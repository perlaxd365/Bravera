<?php

namespace App\Modules\Ordering\Services;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupplierVariant;
use App\Modules\Ordering\Events\OrderItemCancelled;
use App\Modules\Ordering\Events\OrderStatusChanged;
use Illuminate\Support\Facades\DB;

class OrderCancellationService
{
    /**
     * Cancela un único producto (línea) del pedido y recalcula los totales.
     */
    public function cancelItem(OrderItem $item, ?string $reason = null, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($item, $reason, $userId) {
            $order = $item->order;
            $previous = $item->status;

            if ($previous !== OrderItemStatus::ACTIVE) {
                return $order;
            }

            $item->update([
                'status' => OrderItemStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancellation_reason' => $reason ?: null,
            ]);

            $this->releaseStock($item);
            $this->syncSupplierOrder($item);

            $wholeOrder = $this->recalculateTotals($order);

            OrderItemCancelled::dispatch($order->fresh('items'), $item->fresh(), $previous, $wholeOrder);

            if ($wholeOrder && $order->status !== OrderStatus::CANCELLED) {
                $from = $order->status;

                $order->update([
                    'status' => OrderStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'cancelled_by' => $userId,
                    'cancellation_reason' => $reason ?: null,
                ]);

                OrderStatusChanged::dispatch($order->fresh(), $from, OrderStatus::CANCELLED);
            }

            return $order->fresh();
        });
    }

    /**
     * Cancela varios productos del pedido en una sola operación.
     *
     * @param  Collection<int, OrderItem>|array<int, OrderItem>  $items
     */
    public function cancelItems($items, ?string $reason = null, ?int $userId = null): Order
    {
        $order = null;

        DB::transaction(function () use ($items, $reason, $userId, &$order) {
            foreach ($items as $item) {
                $item = $item instanceof OrderItem ? $item : OrderItem::findOrFail($item);
                $order ??= $item->order;

                if ($item->status !== OrderItemStatus::ACTIVE) {
                    continue;
                }

                $previous = $item->status;

                $item->update([
                    'status' => OrderItemStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'cancelled_by' => $userId,
                    'cancellation_reason' => $reason ?: null,
                ]);

                $this->releaseStock($item);
                $this->syncSupplierOrder($item);

                OrderItemCancelled::dispatch($order->fresh('items'), $item->fresh(), $previous, false);
            }

            $this->recalculateTotals($order);
        });

        return $this->closeOrderIfFullyCancelled($order, $reason, $userId);
    }

    /**
     * Cancela el pedido completo: todas sus líneas activas.
     */
    public function cancelOrder(Order $order, ?string $reason = null, ?int $userId = null): Order
    {
        $items = $order->items()->where('status', OrderItemStatus::ACTIVE->value)->get();

        DB::transaction(function () use ($order, $items, $reason, $userId) {
            foreach ($items as $item) {
                $previous = $item->status;

                $item->update([
                    'status' => OrderItemStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'cancelled_by' => $userId,
                    'cancellation_reason' => $reason ?: null,
                ]);

                $this->releaseStock($item);
                $this->syncSupplierOrder($item);

                OrderItemCancelled::dispatch($order->fresh('items'), $item->fresh(), $previous, true);
            }

            $this->recalculateTotals($order);
        });

        return $this->closeOrderIfFullyCancelled($order, $reason, $userId);
    }

    /**
     * Revierte la cancelación de un producto y lo devuelve a activo.
     */
    public function restoreItem(OrderItem $item, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($item) {
            $order = $item->order;

            if ($item->status === OrderItemStatus::ACTIVE) {
                return $order;
            }

            $item->update([
                'status' => OrderItemStatus::ACTIVE,
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
            ]);

            $this->reserveStock($item);
            $this->reopenSupplierOrder($item);
            $this->recalculateTotals($order);

            if ($order->status === OrderStatus::CANCELLED && ! $order->fresh()->isFullyCancelled()) {
                $from = $order->status;

                $order->update([
                    'status' => OrderStatus::CONFIRMED,
                    'cancelled_at' => null,
                    'cancelled_by' => null,
                    'cancellation_reason' => null,
                ]);

                $order->markStatusTimestamp(OrderStatus::CONFIRMED);

                OrderStatusChanged::dispatch($order->fresh(), $from, OrderStatus::CONFIRMED);
            }

            return $order->fresh();
        });
    }

    /**
     * Recalcula los importes del pedido a partir de las líneas activas.
     *
     * @return bool true si el pedido quedó sin líneas activas
     */
    private function recalculateTotals(Order $order): bool
    {
        $active = $order->items()->where('status', OrderItemStatus::ACTIVE->value)->get();

        $subtotal = round($active->sum(fn (OrderItem $i) => (float) $i->line_subtotal), 2);
        $shipping = round($active->sum(fn (OrderItem $i) => (float) $i->shipping_price), 2);
        $cost = round($active->sum(fn (OrderItem $i) => (float) $i->line_cost_total), 2);
        $discount = $active->isEmpty() ? 0.0 : min((float) $order->discount_total, $subtotal);
        $total = round($subtotal + $shipping - $discount, 2);

        $order->update([
            'subtotal' => $subtotal,
            'shipping_total' => $shipping,
            'discount_total' => $discount,
            'cost_total' => $cost,
            'total' => $total,
        ]);

        return $active->isEmpty() && $order->items()->exists();
    }

    private function closeOrderIfFullyCancelled(Order $order, ?string $reason, ?int $userId): Order
    {
        $order->refresh();

        if ($order->isFullyCancelled() && $order->status !== OrderStatus::CANCELLED) {
            $from = $order->status;

            $order->update([
                'status' => OrderStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancellation_reason' => $reason ?: null,
            ]);

            $order->markStatusTimestamp(OrderStatus::CANCELLED);

            OrderStatusChanged::dispatch($order->fresh(), $from, OrderStatus::CANCELLED);
        }

        return $order->fresh();
    }

    /**
     * Devuelve la mercadería al inventario del proveedor.
     */
    private function releaseStock(OrderItem $item): void
    {
        $variantId = $item->supplier_variant_id;

        if (! $variantId) {
            return;
        }

        $variant = SupplierVariant::find($variantId);

        if (! $variant) {
            return;
        }

        $quantity = max(0, (int) $item->quantity);

        if ($quantity === 0) {
            return;
        }

        // Si el pedido se pagó, el stock ya se descontó: se devuelve.
        // Si no se pagó, la reserva sigue activa: se libera.
        if ($item->order?->payment_status === PaymentStatus::PAID) {
            $variant->increment('stock', $quantity);
        } else {
            $variant->decrement('reserved_stock', min($quantity, (int) $variant->reserved_stock));
        }
    }

    private function reserveStock(OrderItem $item): void
    {
        $variant = SupplierVariant::find($item->supplier_variant_id);

        if (! $variant) {
            return;
        }

        $quantity = max(0, (int) $item->quantity);

        if ($item->order?->payment_status === PaymentStatus::PAID) {
            $variant->decrement('stock', min($quantity, (int) $variant->stock));
        } else {
            $variant->increment('reserved_stock', $quantity);
        }
    }

    /**
     * Cancela la orden al proveedor cuando ya no le queda ninguna línea vigente.
     */
    private function syncSupplierOrder(OrderItem $item): void
    {
        foreach ($item->supplierOrderItems()->with('supplierOrder')->get() as $supplierOrderItem) {
            $supplierOrder = $supplierOrderItem->supplierOrder;

            if (! $supplierOrder || $supplierOrder->status === SupplierOrderStatus::CANCELLED) {
                continue;
            }

            $stillActive = $supplierOrder->items()
                ->whereHas('orderItem', fn ($q) => $q->where('status', OrderItemStatus::ACTIVE->value))
                ->exists();

            if (! $stillActive) {
                $supplierOrder->update(['status' => SupplierOrderStatus::CANCELLED]);
            }
        }
    }

    /**
     * Reactiva la orden al proveedor que se había cancelado al cancelar la línea.
     */
    private function reopenSupplierOrder(OrderItem $item): void
    {
        foreach ($item->supplierOrderItems()->with('supplierOrder')->get() as $supplierOrderItem) {
            $supplierOrder = $supplierOrderItem->supplierOrder;

            if (! $supplierOrder || $supplierOrder->status !== SupplierOrderStatus::CANCELLED) {
                continue;
            }

            $supplierOrder->update(['status' => SupplierOrderStatus::PENDING]);
        }
    }
}
