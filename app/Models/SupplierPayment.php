<?php

namespace App\Models;

use App\Enums\SupplierPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    protected $fillable = [
        'supplier_id',
        'supplier_order_id',
        'reference',
        'amount',
        'margin_amount',
        'method',
        'status',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'margin_amount' => 'decimal:2',
            'status' => SupplierPaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function markPaid(): void
    {
        $this->update([
            'status' => SupplierPaymentStatus::PAID,
            'paid_at' => now(),
        ]);
    }
}
