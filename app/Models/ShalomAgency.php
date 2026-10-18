<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShalomAgency extends Model
{
    protected $fillable = [
        'code',
        'department',
        'province',
        'district',
        'address',
        'modality',
        'latitude',
        'longitude',
        'geocode_precision',
        'geocoded_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'geocoded_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeInDepartment(Builder $query, ?string $department): Builder
    {
        return $department
            ? $query->whereRaw('UPPER(department) = ?', [mb_strtoupper(trim($department))])
            : $query;
    }

    public function scopeInDistrict(Builder $query, ?string $district): Builder
    {
        return $district
            ? $query->whereRaw('UPPER(district) = ?', [mb_strtoupper(trim($district))])
            : $query;
    }

    public function displayName(): string
    {
        return $this->address !== '' ? $this->address : $this->district;
    }
}
