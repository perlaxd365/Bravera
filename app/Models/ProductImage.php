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
     * URL de la foto reducida al ancho indicado, conservando su proporción.
     *
     * Las fotos de productos se guardan en Cloudinary, así que en el sitio y
     * en los correos se sirven recortadas y comprimidas en lugar de cargar el
     * original. Si la URL no es de Cloudinary se devuelve tal cual.
     *
     * https://res.cloudinary.com/<cloud>/image/upload/v1234/productos/a.jpg
     *     -> https://res.cloudinary.com/<cloud>/image/upload/w_120,c_limit,f_jpg,q_auto/productos/a.jpg
     */
    public function sizedUrl(int $size): ?string
    {
        $url = $this->imageUrl();
        $marker = '/image/upload/';
        $position = $url ? strpos($url, $marker) : false;

        if ($position === false) {
            return $url;
        }

        $prefix = substr($url, 0, $position + strlen($marker));
        $asset = substr($url, $position + strlen($marker));

        // Email clients often do not send a browser user agent that Cloudinary
        // can use for f_auto, and several still cannot display WebP/AVIF.
        // Use a predictable JPEG format for order email thumbnails.
        $prefix = preg_replace('#^http://#i', 'https://', $prefix) ?? $prefix;

        // La versión de Cloudinary (v1234) no es una transformación: se descarta
        // para que la miniatura no dependa de una URL firmada por fecha.
        $asset = preg_replace('/^v\d+\//', '', $asset) ?? $asset;

        $transformation = sprintf('w_%d,c_limit,f_jpg,q_auto/', $size);

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
