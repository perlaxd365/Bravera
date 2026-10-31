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
     * Tramos de descuento por cantidad configurados para la tienda.
     *
     * @return array<int, array{quantity: int, percent: float, badge?: string}>
     */
    public function quantityDiscountTiers(): array
    {
        return collect(config('brevare.quantity_discounts', []))
            ->map(function ($tier): array {
                $mapped = [
                    'quantity' => (int) ($tier['quantity'] ?? 0),
                    'percent' => (float) ($tier['percent'] ?? 0),
                ];

                if (filled($tier['badge'] ?? null)) {
                    $mapped['badge'] = (string) $tier['badge'];
                }

                return $mapped;
            })
            ->filter(fn (array $tier) => $tier['quantity'] > 1 && $tier['percent'] > 0)
            ->sortBy('quantity')
            ->values()
            ->all();
    }

    public function quantityDiscountPercent(int $quantity): float
    {
        $percent = 0.0;

        foreach ($this->quantityDiscountTiers() as $tier) {
            if ($quantity >= $tier['quantity']) {
                $percent = $tier['percent'];
            }
        }

        return $percent;
    }

    /**
     * Precio unitario vigente para una cantidad concreta.
     *
     * El precio mayorista y el tramo por cantidad compiten por ofrecer el
     * mejor precio al cliente; nunca se aplica un importe superior.
     */
    public function unitPriceForQuantity(int $quantity): float
    {
        $quantityDiscount = $this->quantityDiscountPercent($quantity);
        $volumePrice = (float) $this->sale_price * (1 - ($quantityDiscount / 100));

        if ($this->appliesWholesalePrice($quantity)) {
            $volumePrice = min($volumePrice, (float) $this->wholesale_price);
        }

        return round($volumePrice, 2);
    }
}
