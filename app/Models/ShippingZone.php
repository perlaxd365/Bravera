<?php

namespace App\Models;

use App\Modules\Shipping\Enums\ShippingZoneType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'type',
        'location_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => ShippingZoneType::class,
            'status' => 'boolean',
        ];
    }

    /**
     * Ubicación asociada a la zona.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(
            Location::class,
            'location_id'
        );
    }

    /**
     * Scope para zonas activas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Scope por tipo de zona.
     */
    public function scopeOfType(
        Builder $query,
        ShippingZoneType $type
    ): Builder {
        return $query->where('type', $type);
    }
}
