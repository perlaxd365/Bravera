<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'cart_id',
        'status',
        'payment_status',
        'subtotal',
        'shipping_total',
        'discount_total',
        'total',
        'cost_total',
        'coupon_id',
        'coupon_code',
        'customer_snapshot',
        'address_snapshot',
        'currency',
        'notes',
        'paid_at',
        'gateway_order_id',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'customer_snapshot' => 'array',
            'address_snapshot' => 'array',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'processing_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Líneas del pedido que siguen vigentes (no canceladas).
     */
    public function activeItems(): HasMany
    {
        return $this->hasMany(OrderItem::class)
            ->where('status', OrderItemStatus::ACTIVE->value);
    }

    public function cancelledItems(): HasMany
    {
        return $this->hasMany(OrderItem::class)
            ->where('status', OrderItemStatus::CANCELLED->value);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Un pedido acumula varios pagos: el que falló, el pendiente del webhook y
     * el que sí se acreditó. El singular de arriba se queda con el primero, así
     * que para historial está es el que hay que usar.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function supplierOrders(): HasMany
    {
        return $this->hasMany(SupplierOrder::class);
    }

    public function margin(): float
    {
        return round($this->total - $this->cost_total, 2);
    }

    public function scopeStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Partida cancelada de un pedido, para notas de crédito futuras.
     */
    public function cancelledTotal(): float
    {
        return round($this->cancelledItems()->sum('line_subtotal'), 2);
    }

    public function hasCancelledItems(): bool
    {
        return $this->cancelledItems()->exists();
    }

    public function isFullyCancelled(): bool
    {
        return $this->items()->exists()
            && $this->activeItems()->doesntExist();
    }

    /**
     * Si el pedido sigue esperando un pago que el comprador puede terminar.
     *
     * Un pedido con el pago pendiente se puede reabrir con su modal mientras no
     * haya pasado el plazo de reserva: pasado ese plazo, el comando de
     * expiración lo cancela y ofrecer "pagar ahora" sería una promesa falsa.
     */
    public function isPayable(): bool
    {
        if ($this->status !== OrderStatus::PENDING || $this->payment_status !== PaymentStatus::PENDING) {
            return false;
        }

        $hours = (int) config('payments.pending_order_hours', 24);

        return $this->created_at->gte(now()->subHours(max(1, $hours)));
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Obtiene el historial de estados para la línea de tiempo.
     *
     * @return array<string, string|null>
     */
    public function getStatusHistory(): array
    {
        return [
            'confirmed' => $this->confirmed_at ?? $this->paid_at,
            'processing' => $this->processing_at,
            'shipped' => $this->shipped_at,
            'delivered' => $this->delivered_at,
        ];
    }

    /**
     * Marca el timestamp correspondiente al nuevo estado.
     */
    public function markStatusTimestamp(OrderStatus $newStatus): void
    {
        $column = match ($newStatus) {
            OrderStatus::CONFIRMED => 'confirmed_at',
            OrderStatus::PROCESSING => 'processing_at',
            OrderStatus::SHIPPED => 'shipped_at',
            OrderStatus::DELIVERED => 'delivered_at',
            default => null,
        };

        if ($column && ! $this->{$column}) {
            $this->{$column} = now();
            $this->saveQuietly();
        }
    }
}
