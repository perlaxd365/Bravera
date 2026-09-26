<?php

namespace App\Models;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_subtotal',
        'max_discount',
        'starts_at',
        'ends_at',
        'usage_limit',
        'per_user_limit',
        'applies_to',
        'applies_to_id',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'applies_to' => CouponAppliesTo::class,
            'value' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit' => 'integer',
            'per_user_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->whereRaw('lower(code) = ?', [mb_strtolower($code)]);
    }

    /**
     * Usos totales registrados.
     */
    public function usedCount(): int
    {
        return $this->usages()->count();
    }

    /**
     * ¿El cupón sigue disponible globalmente?
     */
    public function hasReachedGlobalLimit(): bool
    {
        return $this->usage_limit !== null
            && $this->usedCount() >= $this->usage_limit;
    }

    /**
     * Usos de un usuario específico.
     */
    public function usedCountByUser(int $userId): int
    {
        return $this->usages()->where('user_id', $userId)->count();
    }

    public function hasReachedUserLimit(int $userId): bool
    {
        return $this->usedCountByUser($userId) >= $this->per_user_limit;
    }

    /**
     * El cupón aplica a un item dado su proveedor/producto/categoría.
     */
    public function appliesToItem(OrderItem|CartItem $item): bool
    {
        if ($this->applies_to === CouponAppliesTo::ALL) {
            return true;
        }

        return match ($this->applies_to) {
            CouponAppliesTo::SUPPLIER => $item->supplier_id === $this->applies_to_id,
            CouponAppliesTo::PRODUCT => $item->product_id === $this->applies_to_id,
            CouponAppliesTo::CATEGORY => $item->product?->category_id === $this->applies_to_id,
            default => true,
        };
    }
}
