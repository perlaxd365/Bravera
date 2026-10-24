<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMetaAd extends Model
{
    protected $fillable = [
        'product_id',
        'created_by',
        'ad_account_id',
        'campaign_id',
        'campaign_name',
        'ad_set_id',
        'ad_set_name',
        'ad_id',
        'creative_id',
        'media_type',
        'status',
        'review_status',
        'link_clicks_30d',
        'metrics_synced_at',
        'error_message',
        'request_payload',
        'response_payload',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'link_clicks_30d' => 'integer',
            'metrics_synced_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'processing' => 'Creando',
            'paused' => 'En pausa',
            'active' => 'Activo',
            'in_process' => 'Activo · procesando',
            'failed' => 'Falló',
            'disapproved' => 'Rechazado',
            'pending_review' => 'En revisión',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'paused', 'pending_review', 'processing', 'in_process' => 'warning',
            'failed', 'disapproved' => 'danger',
            default => 'neutral',
        };
    }
}
