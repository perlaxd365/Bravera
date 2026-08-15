<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'shipping_zone_id',
        'supplier_id',
        'product_id',
        'product_variant_id',
        'price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    /**
     * Zona de envío.
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(
            ShippingZone::class,
            'shipping_zone_id'
        );
    }

    /**
     * Proveedor.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id'
        );
    }

    /**
     * Producto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }

    /**
     * Variante del producto.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }

    /**
     * Scope para tarifas activas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }
}
