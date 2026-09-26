<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'location_id',
        'address',
        'reference',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Cadena completa: Departamento > Provincia > Distrito.
     */
    public function locationLabel(): string
    {
        return $this->locationLabelHelper($this->location);
    }

    private function locationLabelHelper(?Location $location): string
    {
        if (! $location) {
            return '';
        }

        $parts = [$location->name];

        $parent = $location->parent;

        while ($parent) {
            $parts[] = $parent->name;
            $parent = $parent->parent;
        }

        return implode(' > ', array_reverse($parts));
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
