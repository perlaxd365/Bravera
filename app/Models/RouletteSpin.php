<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouletteSpin extends Model
{
    protected $fillable = [
        'user_id',
        'coupon_id',
        'result',
        'discount_percent',
        'min_subtotal',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'min_subtotal' => 'decimal:2',
            'claimed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
