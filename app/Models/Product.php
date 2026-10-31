<?php

namespace App\Models;

use App\Enums\ProductType;
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
        'product_type',
        'course_price',
        'name',
        'slug',
        'short_description',
        'description',
        'video_url',
        'video_public_id',
        'meta_ad_video_url',
        'meta_ad_video_public_id',
        'status',
        'is_featured',
        'is_visible',
        'allows_shalom_cod',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_featured' => 'boolean',
        'is_visible' => 'boolean',
        'allows_shalom_cod' => 'boolean',
        'product_type' => ProductType::class,
        'course_price' => 'decimal:2',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function metaAds(): HasMany
    {
        return $this->hasMany(ProductMetaAd::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }

    public function courseAssets(): HasMany
    {
        return $this->hasMany(CourseAsset::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function localized(string $field, ?string $locale = null, ?string $fallback = null): ?string
    {
        $locale ??= app()->getLocale();
        $allowed = ['name', 'short_description', 'description', 'seo_title', 'seo_description'];

        if (! in_array($field, $allowed, true)) {
            return null;
        }

        if ($locale !== 'es') {
            $translation = $this->relationLoaded('translations')
                ? $this->translations->firstWhere('locale', $locale)
                : $this->translations()->where('locale', $locale)->first();

            if (filled($translation?->{$field})) {
                return (string) $translation->{$field};
            }
        }

        return $fallback ?? ($this->{$field} ?: null);
    }

    public function courseAssetsForLocale(?string $locale = null): \Illuminate\Support\Collection
    {
        $locale ??= app()->getLocale();
        $assets = $this->relationLoaded('courseAssets')
            ? $this->courseAssets
            : $this->courseAssets()->get();
        $localized = $assets->where('locale', $locale);

        return $localized->isNotEmpty()
            ? $localized->values()
            : $assets->where('locale', 'es')->values();
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

    public function isCourse(): bool
    {
        return $this->product_type === ProductType::COURSE;
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

        $context = \App\Support\Localization\StorefrontContext::current();

        if ($min === $max) {
            return $context->formatFromPen((float) $min);
        }

        return $context->formatFromPen((float) $min).' - '.$context->formatFromPen((float) $max);
    }

    public function hasStock(): bool
    {
        return $this->availableVariants()->exists();
    }

    public function isSoldByBrevare(): bool
    {
        if ($this->relationLoaded('variants')) {
            $variantsHaveSuppliersLoaded = $this->variants->every(
                fn ($variant) => $variant->relationLoaded('supplierVariants')
            );

            if ($variantsHaveSuppliersLoaded) {
                return $this->variants->contains(fn ($variant) => $variant->supplierVariants
                    ->contains(fn ($supplierVariant) => $supplierVariant->is_active
                        && $supplierVariant->availableStock() > 0
                        && $supplierVariant->supplier?->is_internal));
            }
        }

        return $this->variants()
            ->whereHas('supplierVariants', fn ($query) => $query
                ->active()
                ->whereColumn('stock', '>', 'reserved_stock')
                ->whereHas('supplier', fn ($supplier) => $supplier->where('is_internal', true)))
            ->exists();
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
