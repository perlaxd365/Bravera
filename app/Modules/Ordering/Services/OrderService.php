<?php

namespace App\Modules\Ordering\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Modules\Ordering\Data\CheckoutData;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected OrderNumberGenerator $orderNumberGenerator,
    ) {}

    /**
     * Crea el pedido a partir del carrito confirmado,
     * reserva stock y registra el uso del cupón.
     */
    public function createFromCheckout(CheckoutData $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'cart_id' => $data->cart->id,
                'order_number' => $this->orderNumberGenerator->next(),
                'user_id' => $data->user->id,
                'status' => OrderStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'subtotal' => $data->subtotal,
                'shipping_total' => $data->shippingTotal,
                'discount_total' => $data->discountTotal,
                'total' => $data->total,
                'cost_total' => $data->costTotal,
                'coupon_id' => $data->coupon?->id,
                'coupon_code' => $data->coupon?->code,
                'customer_snapshot' => [
                    'name' => $data->user->name,
                    'email' => $data->user->email,
                ],
                'address_snapshot' => [
                    'full_name' => $data->address->full_name,
                    'phone' => $data->address->phone,
                    'address' => $data->address->address,
                    'reference' => $data->address->reference,
                    'location_label' => $data->address->locationLabel(),
                ],
                'currency' => 'PEN',
                'notes' => $data->notes,
            ]);

            $this->createItems($order, $data);

            $this->reserveStock($data);

            $this->registerCouponUsage($order, $data);

            // $data->cart->update(['status' => 'converted']);

            return $order->fresh();
        });
    }

    private function createItems(Order $order, CheckoutData $data): void
    {
        foreach ($data->items as $item) {
            $variant = $item->variant;
            $supplierVariant = $item->supplierVariant;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $variant?->product_id,
                'product_variant_id' => $variant?->id,
                'supplier_id' => $supplierVariant?->supplier_id,
                'supplier_variant_id' => $supplierVariant?->id,
                'product_name' => $variant?->product?->name ?? 'Producto',
                'variant_sku' => $variant?->sku,
                'variant_attributes' => $this->variantAttributes($variant),
                'supplier_name' => $supplierVariant?->supplier?->business_name,
                'supplier_sku' => $supplierVariant?->supplier_sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'unit_cost' => $item->unit_cost,
                'supplier_shipping_cost' => $item->supplier_shipping_cost,
                'shipping_price' => $data->shippingPerItem[$item->id] ?? $item->supplier_shipping_cost,
                'line_subtotal' => $item->lineSubtotal(),
                'line_cost_total' => $item->lineCost(),
            ]);
        }
    }

    private function variantAttributes($variant): ?array
    {
        if (! $variant || $variant->attributeValues->isEmpty()) {
            return null;
        }

        return $variant->attributeValues
            ->map(fn ($pivot) => ($pivot->value?->value ?? $pivot->attribute?->name ?? ''))
            ->values()
            ->all();
    }

    /**
     * Reserva stock incrementando reserved_stock en el proveedor.
     */
    private function reserveStock(CheckoutData $data): void
    {
        foreach ($data->items as $item) {
            $supplierVariant = $item->supplierVariant;

            if (! $supplierVariant) {
                continue;
            }

            $supplierVariant->increment('reserved_stock', $item->quantity);
        }
    }

    /**
     * Libera el stock reservado de un pedido.
     *
     * Se invoca cuando el pago falla de forma definitiva: el pedido existe,
     * pero nunca se despacha, así que la reserva no debe seguir bloqueando
     * unidades. No se llama cuando el pago queda pendiente (Yape/QR), porque
     * ahí la reserva debe mantenerse mientras el cliente paga.
     */
    public function releaseStock(Order $order): void
    {
        foreach ($order->items()->with('supplierVariant')->get() as $item) {
            $supplierVariant = $item->supplierVariant;

            if (! $supplierVariant) {
                continue;
            }

            // Nunca dejar el contador en negativo.
            $reserved = (int) $supplierVariant->reserved_stock;
            $quantity = min($reserved, (int) $item->quantity);

            if ($quantity > 0) {
                $supplierVariant->decrement('reserved_stock', $quantity);
            }
        }
    }

    /**
     * Cancela un pedido que nadie llegó a pagar y devuelve su stock.
     *
     * El checkout en modal crea el pedido y reserva el stock antes de abrir el
     * pago, porque Culqi exige una orden previa para los métodos asíncronos. Si
     * el comprador cierra sin pagar, esa reserva quedaría bloqueada para
     * siempre: este método es el que la devuelve.
     *
     * Solo toca pedidos que siguen en pendiente. Si ya se pagaron, cancelaron o
     * caducaron, no hace nada, así que se puede repetir cada hora sin riesgo de
     * liberar dos veces el mismo stock.
     */
    public function abandonPendingOrder(Order $order, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($order, $reason) {
            $pending = Order::query()
                ->whereKey($order->getKey())
                ->where('status', OrderStatus::PENDING->value)
                ->where('payment_status', PaymentStatus::PENDING->value)
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                return false;
            }

            $pending->update([
                'status' => OrderStatus::CANCELLED,
                'payment_status' => PaymentStatus::EXPIRED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason
                    ?? 'El pago no se completó dentro del plazo establecido.',
            ]);

            // Los pagos que esperaban confirmación ya no pueden cobrar: la orden
            // de Culqi venció junto con el plazo.
            Payment::query()
                ->where('order_id', $pending->id)
                ->where('status', PaymentStatus::PENDING->value)
                ->update(['status' => PaymentStatus::EXPIRED->value]);

            // El uso del cupón se cuenta por filas en coupon_usages, así que un
            // pedido que nunca se pagó no debe seguir consumiendo un uso.
            CouponUsage::query()
                ->where('order_id', $pending->id)
                ->delete();

            $this->releaseStock($pending);

            return true;
        });
    }

    private function registerCouponUsage(Order $order, CheckoutData $data): void
    {
        if (! $data->coupon || $data->discountTotal <= 0) {
            return;
        }

        CouponUsage::create([
            'coupon_id' => $data->coupon->id,
            'user_id' => $data->user->id,
            'order_id' => $order->id,
            'subtotal' => $data->subtotal,
            'discount_amount' => $data->discountTotal,
        ]);
    }
}
