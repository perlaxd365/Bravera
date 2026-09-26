<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'supplier_variant_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'supplier_shipping_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'supplier_shipping_cost' => 'decimal:2',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function supplierVariant(): BelongsTo
    {
        return $this->belongsTo(SupplierVariant::class, 'supplier_variant_id');
    }

    public function lineSubtotal(): float
    {
        return round($this->unit_price * $this->quantity, 2);
    }

    public function lineCost(): float
    {
        return round(($this->unit_cost + $this->supplier_shipping_cost) * $this->quantity, 2);
    }
}
