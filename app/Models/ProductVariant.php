<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'cost_price',
        'sale_price',
        'wholesale_price',
        'wholesale_min_quantity',
        'compare_price',
        'discount_percent',
        'weight',
        'length',
        'width',
        'height',
        'sync_enabled',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'wholesale_min_quantity' => 'integer',
        'compare_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'sync_enabled' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(
            ProductVariantAttributeValue::class,
            'product_variant_id'
        );
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order');
    }

    public function supplierVariants(): HasMany
    {
        return $this->hasMany(SupplierVariant::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Indica si la variante tiene configurado un precio al por mayor válido.
     */
    public function hasWholesalePrice(): bool
    {
        return $this->wholesale_price !== null
            && (int) $this->wholesale_min_quantity > 1;
    }

    /**
     * Indica si la cantidad alcanza el mínimo para aplicar el precio mayorista.
     */
    public function appliesWholesalePrice(int $quantity): bool
    {
        return $this->hasWholesalePrice()
            && $quantity >= (int) $this->wholesale_min_quantity;
    }

    /**
     * Precio unitario vigente para una cantidad concreta.
     *
     * El precio mayorista se aplica a toda la línea cuando se alcanza el
     * mínimo; por debajo del mínimo se mantiene el precio minorista.
     */
    public function unitPriceForQuantity(int $quantity): float
    {
        $price = $this->appliesWholesalePrice($quantity)
            ? $this->wholesale_price
            : $this->sale_price;

        return round((float) $price, 2);
    }
}
