<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_variant_id',
        'public_id',
        'file_name',
        'url',
        'secure_url',
        'format',
        'size',
        'width',
        'height',
        'is_primary',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * URL de entrega de la foto, priorizando la segura.
     */
    public function imageUrl(): ?string
    {
        return $this->secure_url ?: $this->url;
    }

    /**
     * URL de la foto recortada al cuadrado del tamaño indicado.
     *
     * Las fotos de productos se guardan en Cloudinary, así que en el sitio y
     * en los correos se sirven recortadas y comprimidas en lugar de cargar el
     * original. Si la URL no es de Cloudinary se devuelve tal cual.
     *
     * https://res.cloudinary.com/<cloud>/image/upload/v1234/productos/a.jpg
     *     -> https://res.cloudinary.com/<cloud>/image/upload/w_120,h_120,c_fill,g_auto,f_auto,q_auto/productos/a.jpg
     */
    public function sizedUrl(int $size, ?int $height = null): ?string
    {
        $url = $this->imageUrl();
        $marker = '/image/upload/';
        $position = $url ? strpos($url, $marker) : false;

        if ($position === false) {
            return $url;
        }

        $prefix = substr($url, 0, $position + strlen($marker));
        $asset = substr($url, $position + strlen($marker));

        // La versión de Cloudinary (v1234) no es una transformación: se descarta
        // para que la miniatura no dependa de una URL firmada por fecha.
        $asset = preg_replace('/^v\d+\//', '', $asset) ?? $asset;

        $transformation = sprintf('w_%d,h_%d,c_fill,g_auto,f_auto,q_auto/', $size, $height ?? $size);

        return $prefix.$transformation.$asset;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }
}
