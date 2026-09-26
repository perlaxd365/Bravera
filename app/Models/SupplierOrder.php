<?php

namespace App\Models;

use App\Enums\SupplierOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupplierOrder extends Model
{
    protected $fillable = [
        'order_id',
        'supplier_id',
        'supplier_order_number',
        'status',
        'total_cost',
        'tracking_code',
        'tracking_url',
        'notes',
        'sent_at',
        'delivered_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupplierOrderStatus::class,
            'total_cost' => 'decimal:2',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(SupplierPayment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierOrderItem::class);
    }

    /**
     * Datos de envío del cliente para compartir con el proveedor.
     */
    public function shippingDetails(): array
    {
        $customer = $this->order->customer_snapshot ?? [];
        $address = $this->order->address_snapshot ?? [];

        return [
            'customer_name' => $address['full_name'] ?? $customer['name'] ?? '',
            'phone' => $address['phone'] ?? '',
            'address' => $address['address'] ?? '',
            'reference' => $address['reference'] ?? '',
            'location' => $address['location_label'] ?? '',
        ];
    }
}
