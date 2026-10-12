<?php

namespace App\Models;

use App\Enums\RefundReason;
use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'order_id',
        'payment_id',
        'requested_by',
        'validated_by',
        'amount',
        'currency',
        'reason',
        'status',
        'notes',
        'resolution_notes',
        'gateway_refund_id',
        'gateway_response',
        'requested_at',
        'validated_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reason' => RefundReason::class,
            'status' => RefundStatus::class,
            'gateway_response' => 'array',
            'requested_at' => 'datetime',
            'validated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isPending(): bool
    {
        return $this->status === RefundStatus::PENDING;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function reasonLabel(): string
    {
        return $this->reason->label();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', RefundStatus::PENDING->value);
    }

    /**
     * Devoluciones que ya comprometen saldo del pago (no rechazadas ni fallidas).
     */
    public function scopeHoldingFunds(Builder $query): Builder
    {
        $statuses = collect(RefundStatus::cases())
            ->filter(fn (RefundStatus $status) => $status->holdsFunds())
            ->map(fn (RefundStatus $status) => $status->value)
            ->all();

        return $query->whereIn('status', $statuses);
    }
}
