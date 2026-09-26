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
        'order_number', 'user_id', 'status', 'payment_status',
        'subtotal', 'shipping_total', 'discount_total', 'total', 'cost_total',
        'coupon_id', 'coupon_code', 'customer_snapshot', 'address_snapshot',
        'currency', 'notes', 'paid_at',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
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
}
