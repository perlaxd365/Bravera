<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attribute_id',
        'value',
        'slug',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'attribute_id' => 'integer',
        'sort_order'   => 'integer',
        'is_active'    => 'boolean',
    ];

    /**
     * Relación con el atributo.
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Scope: solo activos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordenados.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')
            ->orderBy('value');
    }
    public function variantValues()
    {
        return $this->hasMany(ProductVariantAttributeValue::class);
    }
}
