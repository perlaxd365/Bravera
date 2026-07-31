<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{

    use HasFactory;
    use SoftDeletes;

    /**
     * Atributos asignables masivamente.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    /**
     * Conversión de atributos.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Productos de la marca.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Usuario que creó el registro.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que actualizó el registro.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
