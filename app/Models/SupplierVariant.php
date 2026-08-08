<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierVariant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'product_variant_id',
        'supplier_sku',
        'supplier_product_url',
        'cost_price',
        'shipping_cost',
        'supplier_sale_price',
        'stock',
        'reserved_stock',
        'minimum_stock',
        'extra_data',
        'estimated_dispatch_days',
        'last_sync_at',
        'is_default',
        'is_active',
        'internal_notes',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'supplier_sale_price' => 'decimal:2',
        'stock' => 'integer',
        'reserved_stock' => 'integer',
        'minimum_stock' => 'integer',
        'extra_data' => 'array',
        'estimated_dispatch_days' => 'integer',
        'last_sync_at' => 'datetime',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
