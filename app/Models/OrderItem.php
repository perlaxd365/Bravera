<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'supplier_id',
        'supplier_variant_id',
        'product_name',
        'variant_sku',
        'variant_attributes',
        'supplier_name',
        'supplier_sku',
        'quantity',
        'status',
        'unit_price',
        'unit_cost',
        'supplier_shipping_cost',
        'shipping_price',
        'line_subtotal',
        'line_cost_total',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => OrderItemStatus::class,
            'variant_attributes' => 'array',
            'unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'supplier_shipping_cost' => 'decimal:2',
            'shipping_price' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'line_cost_total' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierVariant(): BelongsTo
    {
        return $this->belongsTo(SupplierVariant::class, 'supplier_variant_id');
    }

    public function supplierOrderItems(): HasMany
    {
        return $this->hasMany(SupplierOrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OrderItemStatus::ACTIVE->value);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', OrderItemStatus::CANCELLED->value);
    }

    public function isActive(): bool
    {
        return $this->status === OrderItemStatus::ACTIVE;
    }

    public function attributesLabel(): string
    {
        $attrs = $this->variant_attributes ?? [];

        return collect($attrs)->map(fn ($value, $key) => ucfirst($key).': '.$value)->implode(', ');
    }

    /**
     * Foto del producto pedido para mostrarlo en el detalle del pedido y en los
     * correos: primero la de la variante que se compró y, si esa variante no
     * tiene foto, la portada del producto.
     *
     * @param  int|null  $size  Lado en píxeles de la miniatura servida por Cloudinary.
     */
    public function imageUrl(?int $size = null): ?string
    {
        $image = $this->image();

        if (! $image) {
            return null;
        }

        return $size ? $image->sizedUrl($size) : $image->imageUrl();
    }

    /**
     * Imagen de la variante pedida, o la portada del producto como respaldo.
     */
    public function image(): ?ProductImage
    {
        $image = $this->variant?->images->firstWhere('is_primary', true)
            ?? $this->variant?->images->first();

        return $image ?? $this->product?->coverImageModel();
    }
}
