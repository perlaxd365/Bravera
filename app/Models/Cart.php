<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)
            ->with(['variant.product', 'variant.images', 'supplierVariant']);
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(CartItem::class)
            ->with(['variant.product', 'variant.images', 'supplierVariant'])
            ->whereHas('variant');
    }

    public function count(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Subtotal del carrito (sin envío ni descuentos).
     */
    public function subtotal(): float
    {
        return $this->items->sum(fn($item) => $item->unit_price * $item->quantity);
    }

    /**
     * Costo total de los items (para proveedores).
     */
    public function costTotal(): float
    {
        return $this->items->sum(fn($item) => ($item->unit_cost + $item->supplier_shipping_cost) * $item->quantity);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }


    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
