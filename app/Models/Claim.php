<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    public const TYPE_RECLAMO = 'reclamo';

    public const TYPE_QUEJA = 'queja';

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'code',
        'name',
        'document_type',
        'document_number',
        'email',
        'phone',
        'address',
        'is_minor',
        'guardian_name',
        'order_number',
        'claim_type',
        'claimed_good',
        'description',
        'request',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_minor' => 'boolean',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_RECLAMO => 'Reclamo',
            self::TYPE_QUEJA => 'Queja',
        ];
    }

    /**
     * Genera el siguiente código correlativo del libro de reclamaciones.
     */
    public static function nextCode(): string
    {
        $year = now()->format('Y');
        $prefix = "REC-{$year}-";

        $last = static::query()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('code');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
