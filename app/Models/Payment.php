<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'gateway',
        'method',
        'gateway_transaction_id',
        'gateway_charge_id',
        'source_id',
        'checkout_url',
        'amount',
        'currency',
        'status',
        'raw_response',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'raw_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Monto comprometido en devoluciones, incluidas las que aún esperan
     * validación o confirmación de la pasarela. Se excluyen solo las
     * rechazadas y las que fallaron.
     */
    public function refundedTotal(): float
    {
        return round((float) $this->refunds()->holdingFunds()->sum('amount'), 2);
    }

    /**
     * Saldo del pago que todavía puede devolverse.
     */
    public function refundableAmount(): float
    {
        return round(max(0, (float) $this->amount - $this->refundedTotal()), 2);
    }

    public function isRefundable(): bool
    {
        return in_array($this->status, [PaymentStatus::PAID, PaymentStatus::REFUNDED], true)
            && $this->refundableAmount() > 0;
    }

    public function markPaid(): void
    {
        $this->update([
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);
    }

    public function scopeGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}
