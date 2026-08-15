<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Location;

class Supplier extends Model
{
    use SoftDeletes;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'business_name',
        'trade_name',
        'tax_id',
        'contact_name',
        'email',
        'phone',
        'whatsapp',
        'website',
        'address',
        'estimated_dispatch_days',
        'status',
        'internal_notes',
        'location_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Conversión automática de atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'estimated_dispatch_days' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Usuario que creó el proveedor.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que realizó la última actualización.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Filtra únicamente los proveedores activos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
    public function supplierVariants(): HasMany
    {
        return $this->hasMany(SupplierVariant::class);
    }

    /**
     * Ubicación del proveedor.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(
            Location::class,
            'location_id'
        );
    }
}
