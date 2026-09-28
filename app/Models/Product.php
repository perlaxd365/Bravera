<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'short_description',
        'description',
        'status',
        'is_featured',
        'is_visible',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_featured' => 'boolean',
        'is_visible' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers para tienda
    |--------------------------------------------------------------------------
    */

    /**
     * Variantes activas que tienen al menos un proveedor con stock.
     */
    public function availableVariants()
    {
        return $this->variants()
            ->where('is_active', true)
            ->whereHas('supplierVariants', function ($query) {
                $query->active()->whereColumn('stock', '>', 'reserved_stock');
            });
    }

    public function minPrice(): ?float
    {
        return $this->availableVariants()->get()->min('sale_price');
    }

    public function maxPrice(): ?float
    {
        return $this->availableVariants()->get()->max('sale_price');
    }

    public function priceRange(): string
    {
        $min = $this->minPrice();
        $max = $this->maxPrice();

        if ($min === null) {
            return 'Consultar';
        }

        if ($min === $max) {
            return 'S/ '.number_format((float) $min, 2);
        }

        return 'S/ '.number_format((float) $min, 2).' - '.number_format((float) $max, 2);
    }

    public function hasStock(): bool
    {
        return $this->availableVariants()->exists();
    }

    public function coverImage(): ?string
    {
        return $this->coverImageModel()?->imageUrl();
    }

    /**
     * Foto que representa al producto en listados y correos: la principal de la
     * variante predeterminada o, si no tiene, la primera que exista.
     */
    public function coverImageModel(): ?ProductImage
    {
        $variant = $this->variants
            ->sortByDesc('is_default')
            ->first(function ($variant) {
                return ! $variant->images->isEmpty();
            });

        if (! $variant) {
            return null;
        }

        return $variant->images->firstWhere('is_primary', true)
            ?? $variant->images->first();
    }
}
