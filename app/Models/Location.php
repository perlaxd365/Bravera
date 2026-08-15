<?php

namespace App\Models;

use App\Modules\Location\Enums\LocationLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /**
     * Campos asignables.
     */
    protected $fillable = [
        'ubigeo',
        'name',
        'level',
        'parent_id',
    ];

    /**
     * Conversiones de atributos.
     */
    protected function casts(): array
    {
        return [
            'level' => LocationLevel::class,
        ];
    }

    /**
     * Ubicación padre.
     *
     * Provincia → Departamento
     * Distrito → Provincia
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_id'
        );
    }

    /**
     * Ubicaciones hijas.
     *
     * Departamento → Provincias
     * Provincia → Distritos
     */
    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_id'
        );
    }

    /**
     * Scope para departamentos.
     */
    public function scopeDepartments(Builder $query): Builder
    {
        return $query->where(
            'level',
            LocationLevel::DEPARTMENT
        );
    }

    /**
     * Scope para provincias.
     */
    public function scopeProvinces(Builder $query): Builder
    {
        return $query->where(
            'level',
            LocationLevel::PROVINCE
        );
    }

    /**
     * Scope para distritos.
     */
    public function scopeDistricts(Builder $query): Builder
    {
        return $query->where(
            'level',
            LocationLevel::DISTRICT
        );
    }
}
