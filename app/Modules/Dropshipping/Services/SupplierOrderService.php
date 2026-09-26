<?php

namespace App\Modules\Dropshipping\Services;

use App\Enums\SupplierOrderStatus;
use App\Models\Order;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierOrderService
{
    /**
     * Genera una orden de compra por proveedor cuando el pedido
     * del cliente queda pagado, y descuenta el stock reservado.
     *
     * @return Collection<int, SupplierOrder>
     */
    public function createForPaidOrder(Order $order)
    {
        return DB::transaction(function () use ($order) {
            $grouped = $order->items
                ->filter(fn ($item) => $item->supplier_id !== null)
                ->groupBy('supplier_id');

            $created = collect();

            foreach ($grouped as $supplierId => $items) {
                $supplierOrder = $this->createSupplyOrder($order, (int) $supplierId, $items);

                $this->decrementStock($items);

                $created->push($supplierOrder);
            }

            return $created;
        });
    }

    private function createSupplyOrder(Order $order, int $supplierId, $items): SupplierOrder
    {
        $totalCost = round(
            $items->sum(fn ($item) => (float) $item->line_cost_total),
            2
        );

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'supplier_id' => $supplierId,
            'supplier_order_number' => $this->nextNumber(),
            'status' => SupplierOrderStatus::PENDING,
            'total_cost' => $totalCost,
            'submitted_at' => now(),
        ]);

        foreach ($items as $item) {
            SupplierOrderItem::create([
                'supplier_order_id' => $supplierOrder->id,
                'order_item_id' => $item->id,
                'supplier_variant_id' => $item->supplier_variant_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'supplier_shipping_cost' => $item->supplier_shipping_cost,
                'line_cost_total' => $item->line_cost_total,
            ]);
        }

        return $supplierOrder;
    }

    /**
     * Descarta la mercadería reservada: stock -= qty; reserved_stock -= qty.
     */
    private function decrementStock($items): void
    {
        foreach ($items as $item) {
            $supplierVariant = $item->supplierVariant;

            if (! $supplierVariant) {
                continue;
            }

            $qty = min($item->quantity, $supplierVariant->reserved_stock);

            $supplierVariant->decrement('stock', $qty);
            $supplierVariant->decrement('reserved_stock', $qty);
        }
    }

    private function nextNumber(): string
    {
        $today = now()->format('Ymd');

        $prefix = 'SOP-'.$today.'-';

        $last = DB::table('supplier_orders')
            ->where('supplier_order_number', 'like', $prefix.'%')
            ->orderByDesc('supplier_order_number')
            ->value('supplier_order_number');

        $sequence = $last
            ? ((int) substr($last, -4)) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
