<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierOrderItem extends Model
{
    protected $fillable = [
        'supplier_order_id',
        'order_item_id',
        'supplier_variant_id',
        'quantity',
        'unit_cost',
        'supplier_shipping_cost',
        'line_cost_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'supplier_shipping_cost' => 'decimal:2',
            'line_cost_total' => 'decimal:2',
        ];
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function supplierVariant(): BelongsTo
    {
        return $this->belongsTo(SupplierVariant::class, 'supplier_variant_id');
    }
}
