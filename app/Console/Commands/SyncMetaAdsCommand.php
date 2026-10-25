<?php

namespace App\Console\Commands;

use App\Models\ProductMetaAd;
use App\Services\MetaAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMetaAdsCommand extends Command
{
    protected $signature = 'brevare:sync-meta-ads';

    protected $description = 'Actualiza el estado y las visitas de los anuncios de Meta';

    public function handle(MetaAdsService $metaAds): int
    {
        if (! $metaAds->isConfigured()) {
            $this->components->warn('Meta Ads no está configurado; no se sincronizaron métricas.');

            return self::SUCCESS;
        }

        $synced = 0;
        $failed = 0;

        ProductMetaAd::query()
            ->whereNotNull('ad_id')
            ->where(function ($query): void {
                $query->where('status', '!=', 'processing')
                    ->orWhere('updated_at', '<=', now()->subMinutes(10));
            })
            ->chunkById(50, function ($records) use ($metaAds, &$synced, &$failed): void {
                foreach ($records as $record) {
                    try {
                        $metaAds->refreshStatus($record);
                        $synced++;
                    } catch (Throwable $exception) {
                        $failed++;

                        Log::warning('No se pudieron sincronizar las métricas de un anuncio de Meta.', [
                            'product_meta_ad_id' => $record->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Anuncios sincronizados: {$synced}. Errores: {$failed}.");

        return self::SUCCESS;
    }
}
