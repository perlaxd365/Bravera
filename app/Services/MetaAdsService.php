<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductMetaAd;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Integración mínima con la Marketing API de Meta.
 *
 * Solo agrega anuncios activos a un conjunto de anuncios existente. No crea
 * campañas ni cambia presupuestos.
 */
class MetaAdsService
{
    public function isConfigured(): bool
    {
        return (bool) config('meta.ads.enabled', false)
            && $this->accessToken() !== ''
            && $this->adAccountId() !== 'act_'
            && $this->pageId() !== '';
    }

    /**
     * @return array<int, array{id: string, name: string, status: string, objective: string}>
     */
    public function campaigns(): array
    {
        $this->assertConfigured();

        $response = $this->graph()->get($this->adAccountId().'/campaigns', [
            'fields' => 'id,name,status,effective_status,objective',
            'limit' => 200,
        ]);

        $this->ensureOk($response, 'Meta no devolvió las campañas.');

        return collect($response->json('data', []))
            ->filter(fn ($campaign) => is_array($campaign) && filled($campaign['id'] ?? null))
            ->map(fn (array $campaign): array => [
                'id' => (string) $campaign['id'],
                'name' => (string) ($campaign['name'] ?? $campaign['id']),
                'status' => (string) ($campaign['effective_status'] ?? $campaign['status'] ?? ''),
                'objective' => (string) ($campaign['objective'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string, status: string, optimization_goal: string}>
     */
    public function adSets(string $campaignId): array
    {
        $this->assertConfigured();

        $campaignId = trim($campaignId);

        if ($campaignId === '') {
            return [];
        }

        $response = $this->graph()->get($campaignId.'/adsets', [
            'fields' => 'id,name,status,effective_status,optimization_goal,billing_event',
            'limit' => 200,
        ]);

        $this->ensureOk($response, 'Meta no devolvió los conjuntos de anuncios.');

        return collect($response->json('data', []))
            ->filter(fn ($adSet) => is_array($adSet) && filled($adSet['id'] ?? null))
            ->map(fn (array $adSet): array => [
                'id' => (string) $adSet['id'],
                'name' => (string) ($adSet['name'] ?? $adSet['id']),
                'status' => (string) ($adSet['effective_status'] ?? $adSet['status'] ?? ''),
                'optimization_goal' => (string) ($adSet['optimization_goal'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{
     *     campaign_id: string,
     *     campaign_name?: string,
     *     ad_set_id: string,
     *     ad_set_name?: string,
     *     media_type: string,
     *     primary_text: string,
     *     headline: string,
     *     description?: string,
     *     cta?: string,
     *     destination_url: string,
     *     ad_name?: string
     * }  $input
     */
    public function createAd(Product $product, array $input, ?int $userId = null): ProductMetaAd
    {
        $this->assertConfigured();
        $this->assertProductIsAdvertisable($product);

        $campaignId = trim((string) ($input['campaign_id'] ?? ''));
        $adSetId = trim((string) ($input['ad_set_id'] ?? ''));
        $mediaType = (string) ($input['media_type'] ?? 'image');
        $destinationUrl = trim((string) ($input['destination_url'] ?? ''));
        $primaryText = trim((string) ($input['primary_text'] ?? ''));
        $headline = trim((string) ($input['headline'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $cta = strtoupper(trim((string) ($input['cta'] ?? config('meta.ads.default_cta', 'SHOP_NOW'))));

        if ($campaignId === '' || $adSetId === '') {
            throw new MetaAdsException('Selecciona una campaña y un conjunto de anuncios.');
        }

        if (! in_array($mediaType, ['image', 'video'], true)) {
            throw new MetaAdsException('El tipo de creatividad debe ser imagen o video.');
        }

        if ($primaryText === '' || $headline === '') {
            throw new MetaAdsException('El texto principal y el titular son obligatorios.');
        }

        if (! filter_var($destinationUrl, FILTER_VALIDATE_URL)) {
            throw new MetaAdsException('La URL de destino no es válida.');
        }

        $existing = ProductMetaAd::query()
            ->where('product_id', $product->id)
            ->where('ad_set_id', $adSetId)
            ->first();

        if ($existing?->ad_id && $existing->status !== 'failed') {
            throw new MetaAdsException(
                'Este producto ya tiene un anuncio en ese conjunto de anuncios ('.$existing->ad_id.').'
            );
        }

        if ($existing?->status === 'processing'
            && $existing->updated_at?->gt(now()->subMinutes(10))) {
            throw new MetaAdsException('Ya hay una creación de anuncio en curso para este producto.');
        }

        try {
            return Cache::lock('meta-ad:'.$product->id.':'.$adSetId, 30)->block(
                10,
                fn (): ProductMetaAd => $this->performCreate(
                    $product,
                    [
                        'campaignId' => $campaignId,
                        'adSetId' => $adSetId,
                        'mediaType' => $mediaType,
                        'destinationUrl' => $destinationUrl,
                        'primaryText' => $primaryText,
                        'headline' => $headline,
                        'description' => $description,
                        'cta' => $cta,
                        'input' => $input,
                    ],
                    $userId,
                )
            );
        } catch (LockTimeoutException) {
            throw new MetaAdsException('Ya hay otra creación de anuncio en curso. Inténtalo de nuevo.');
        }
    }

    public function refreshStatus(ProductMetaAd $record): ProductMetaAd
    {
        $this->assertConfigured();

        if (blank($record->ad_id)) {
            return $record;
        }

        $response = $this->graph()->get($record->ad_id, [
            'fields' => 'id,name,status,effective_status,adset_id,campaign_id',
        ]);

        $this->ensureOk($response, 'Meta no devolvió el estado del anuncio.');
        $payload = $response->json();
        $effectiveStatus = (string) ($payload['effective_status'] ?? $payload['status'] ?? 'unknown');
        $status = Str::lower($effectiveStatus);

        $record->update([
            'status' => $status,
            'review_status' => null,
            'response_payload' => is_array($payload) ? $payload : [],
            'last_synced_at' => now(),
            'error_message' => null,
        ]);

        $linkClicks = $status === 'active'
            ? $this->linkClicksLast30Days($record->ad_id)
            : 0;

        $record->update([
            'link_clicks_30d' => $linkClicks,
            'metrics_synced_at' => now(),
        ]);

        return $record->refresh();
    }

    private function linkClicksLast30Days(string $adId): int
    {
        $response = $this->graph()->get($adId.'/insights', [
            'date_preset' => 'last_30d',
            'fields' => 'inline_link_clicks',
        ]);

        $this->ensureOk($response, 'Meta no devolvió las visitas del anuncio.');

        return collect($response->json('data', []))
            ->sum(fn ($row): int => max(0, (int) ($row['inline_link_clicks'] ?? 0)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function performCreate(
        Product $product,
        array $data,
        ?int $userId,
    ): ProductMetaAd {
        $record = ProductMetaAd::updateOrCreate(
            [
                'product_id' => $product->id,
                'ad_set_id' => $data['adSetId'],
            ],
            [
                'created_by' => $userId,
                'ad_account_id' => $this->adAccountId(),
                'campaign_id' => $data['campaignId'],
                'campaign_name' => $data['input']['campaign_name'] ?? null,
                'ad_set_name' => $data['input']['ad_set_name'] ?? null,
                'media_type' => $data['mediaType'],
                'status' => 'processing',
                'review_status' => null,
                'error_message' => null,
                'ad_id' => null,
                'creative_id' => null,
            ]
        );

        $product->loadMissing(['variants.images']);
        $imageUrl = $product->coverImage();
        $mediaUrl = $data['mediaType'] === 'video'
            ? (string) ($product->meta_ad_video_url ?: $product->video_url ?: '')
            : (string) ($imageUrl ?? '');

        $requestPayload = [
            'ad_account_id' => $this->adAccountId(),
            'campaign_id' => $data['campaignId'],
            'campaign_name' => $data['input']['campaign_name'] ?? null,
            'ad_set_id' => $data['adSetId'],
            'ad_set_name' => $data['input']['ad_set_name'] ?? null,
            'media_type' => $data['mediaType'],
            'media_url' => $mediaUrl,
            'primary_text' => $data['primaryText'],
            'headline' => $data['headline'],
            'description' => $data['description'],
            'cta' => $data['cta'],
            'destination_url' => $data['destinationUrl'],
        ];

        $record->update(['request_payload' => $requestPayload]);

        $creativeId = null;
        $responsePayload = [];

        try {
            if ($data['mediaType'] === 'video') {
                if ($mediaUrl === '') {
                    throw new MetaAdsException('El producto no tiene un video para usar en el anuncio.');
                }

                $videoId = $this->uploadVideo($mediaUrl, $product);
                $creativeId = $this->createVideoCreative($product, $videoId, $imageUrl, $data);
            } else {
                if ($mediaUrl === '') {
                    throw new MetaAdsException('El producto no tiene una imagen principal para usar en el anuncio.');
                }

                $imageHash = $this->uploadImage($mediaUrl);
                $creativeId = $this->createImageCreative($product, $imageHash, $data);
            }

            $responsePayload['creative'] = ['id' => $creativeId];

            $adResponse = $this->graph()->post($this->adAccountId().'/ads', [
                'name' => $data['input']['ad_name']
                    ?? Str::limit($product->name.' — '.now()->format('Y-m-d H:i'), 100),
                'adset_id' => $data['adSetId'],
                'creative' => json_encode(['creative_id' => $creativeId], JSON_UNESCAPED_SLASHES),
                'status' => 'ACTIVE',
            ]);

            $this->ensureOk($adResponse, 'Meta no pudo crear el anuncio.');
            $adPayload = $adResponse->json();
            $adId = (string) ($adPayload['id'] ?? '');
            $responsePayload['ad'] = is_array($adPayload) ? $adPayload : [];

            if ($adId === '') {
                throw new MetaAdsException(
                    'Meta aceptó la solicitud, pero no devolvió el ID del anuncio.',
                    $adResponse->status(),
                    $responsePayload['ad'],
                );
            }

            $record->update([
                'ad_id' => $adId,
                'creative_id' => $creativeId,
                'status' => 'active',
                'review_status' => null,
                'response_payload' => $responsePayload,
                'last_synced_at' => now(),
                'error_message' => null,
            ]);

            return $record->refresh();
        } catch (Throwable $exception) {
            $message = $exception instanceof MetaAdsException
                ? $exception->getMessage()
                : 'No se pudo crear el anuncio en Meta.';

            if ($exception instanceof MetaAdsException) {
                $responsePayload['error'] = $exception->payload;
            }

            $record->update([
                'status' => 'failed',
                'error_message' => mb_substr($message, 0, 2000),
                'response_payload' => $responsePayload,
                'last_synced_at' => now(),
            ]);

            if ($exception instanceof MetaAdsException) {
                throw $exception;
            }

            throw new MetaAdsException($message, 0, ['exception' => $exception::class]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createImageCreative(Product $product, string $imageHash, array $data): string
    {
        $linkData = [
            'message' => $data['primaryText'],
            'link' => $data['destinationUrl'],
            'name' => Str::limit($data['headline'], 40, ''),
            'description' => Str::limit($data['description'], 200, ''),
            'image_hash' => $imageHash,
            'call_to_action' => [
                'type' => $data['cta'],
                'value' => ['link' => $data['destinationUrl']],
            ],
        ];

        $response = $this->graph()->post($this->adAccountId().'/adcreatives', [
            'name' => Str::limit($product->name.' — creativo', 100),
            'object_story_spec' => json_encode(
                $this->storySpec(['link_data' => $linkData]),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
        ]);

        $this->ensureOk($response, 'Meta no pudo crear el creativo con la imagen.');
        $creativeId = (string) ($response->json('id') ?? '');

        if ($creativeId === '') {
            throw new MetaAdsException('Meta creó el creativo sin devolver un ID.');
        }

        return $creativeId;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createVideoCreative(
        Product $product,
        string $videoId,
        ?string $imageUrl,
        array $data,
    ): string {
        $videoData = [
            'video_id' => $videoId,
            'message' => $data['primaryText'],
            'title' => Str::limit($data['headline'], 40, ''),
            'link_description' => Str::limit($data['description'], 200, ''),
            'call_to_action' => [
                'type' => $data['cta'],
                'value' => ['link' => $data['destinationUrl']],
            ],
        ];

        if (filled($imageUrl)) {
            $videoData['image_url'] = $imageUrl;
        }

        $response = $this->graph()->post($this->adAccountId().'/adcreatives', [
            'name' => Str::limit($product->name.' — video', 100),
            'object_story_spec' => json_encode(
                $this->storySpec(['video_data' => $videoData]),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
        ]);

        $this->ensureOk($response, 'Meta no pudo crear el creativo con el video.');
        $creativeId = (string) ($response->json('id') ?? '');

        if ($creativeId === '') {
            throw new MetaAdsException('Meta creó el creativo sin devolver un ID.');
        }

        return $creativeId;
    }

    /**
     * @param  array<string, mixed>  $story
     * @return array<string, mixed>
     */
    private function storySpec(array $story): array
    {
        $spec = [
            'page_id' => $this->pageId(),
            ...$story,
        ];

        $instagramId = trim((string) config('meta.ads.instagram_actor_id'));

        if ($instagramId !== '') {
            $spec['instagram_user_id'] = $instagramId;
        }

        return $spec;
    }

    private function uploadImage(string $url): string
    {
        $contents = $this->downloadMedia($url, 'imagen');
        $path = $this->temporaryMediaPath($contents, $this->extensionFromUrl($url, 'jpg'));

        try {
            $response = $this->graph()
                ->attach('filename', file_get_contents($path), basename($path))
                ->post($this->adAccountId().'/adimages');

            $this->ensureOk($response, 'Meta no pudo subir la imagen del producto.');
            $images = $response->json('images', []);
            $first = is_array($images) ? reset($images) : null;
            $hash = is_array($first) ? (string) ($first['hash'] ?? '') : '';

            if ($hash === '') {
                throw new MetaAdsException('Meta subió la imagen sin devolver su hash.');
            }

            return $hash;
        } finally {
            $this->deleteTemporaryMedia($path);
        }
    }

    private function uploadVideo(string $url, Product $product): string
    {
        $contents = $this->downloadMedia($url, 'video');
        $path = $this->temporaryMediaPath($contents, $this->extensionFromUrl($url, 'mp4'));

        try {
            $response = $this->graph()
                ->attach('source', file_get_contents($path), basename($path))
                ->post($this->adAccountId().'/advideos', [
                    'title' => Str::limit($product->name, 100),
                    'description' => 'Video de producto Brevare',
                ]);

            $this->ensureOk($response, 'Meta no pudo subir el video del producto.');
            $videoId = (string) ($response->json('id') ?? '');

            if ($videoId === '') {
                throw new MetaAdsException('Meta subió el video sin devolver un ID.');
            }

            return $videoId;
        } finally {
            $this->deleteTemporaryMedia($path);
        }
    }

    private function downloadMedia(string $url, string $what): string
    {
        $response = Http::timeout(60)->get($url);

        if (! $response->successful() || $response->body() === '') {
            throw new MetaAdsException('No se pudo descargar la '.$what.' del producto para enviarla a Meta.');
        }

        return $response->body();
    }

    private function temporaryMediaPath(string $contents, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'brevare_meta_');

        if ($path === false) {
            throw new MetaAdsException('No se pudo preparar el archivo temporal para Meta.');
        }

        $withExtension = $path.'.'.$extension;

        if (file_put_contents($withExtension, $contents) === false) {
            @unlink($path);

            throw new MetaAdsException('No se pudo guardar el archivo temporal para Meta.');
        }

        @unlink($path);

        return $withExtension;
    }

    private function deleteTemporaryMedia(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function extensionFromUrl(string $url, string $fallback): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov', 'webm'], true)
            ? $extension
            : $fallback;
    }

    private function graph(): PendingRequest
    {
        $request = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(60)
            ->connectTimeout(10)
            ->baseUrl('https://graph.facebook.com/'.$this->apiVersion());

        $secret = trim((string) config('meta.ads.app_secret'));

        if ($secret !== '') {
            $request = $request->withQueryParameters([
                'appsecret_proof' => hash_hmac('sha256', $this->accessToken(), $secret),
            ]);
        }

        return $request;
    }

    private function ensureOk(Response $response, string $fallback): void
    {
        $error = $response->json('error');
        $payload = is_array($error) ? $error : [];

        if ($response->successful() && $payload === []) {
            return;
        }

        $traceId = trim((string) ($payload['fbtrace_id'] ?? $response->header('X-FB-Trace-ID')));

        if ($traceId !== '' && ! isset($payload['_trace_id'])) {
            $payload['_trace_id'] = $traceId;
        }

        if ($payload === [] && $response->body() !== '') {
            $payload['_raw_body'] = mb_substr($response->body(), 0, 3000);
        }

        $message = filled($payload['error_user_msg'] ?? null)
            ? (string) $payload['error_user_msg']
            : (string) ($payload['message'] ?? $fallback);

        throw new MetaAdsException(
            $message,
            $response->status(),
            $payload,
        );
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new MetaAdsException(
                'Meta Ads no está configurado. Completa META_ADS_ENABLED, META_AD_ACCOUNT_ID, META_PAGE_ID y META_ADS_ACCESS_TOKEN.'
            );
        }
    }

    private function assertProductIsAdvertisable(Product $product): void
    {
        if (! $product->status || ! $product->is_visible) {
            throw new MetaAdsException('Publica el producto antes de crear su anuncio.');
        }

        if (! $product->hasStock()) {
            throw new MetaAdsException('El producto debe tener stock disponible para anunciarlo.');
        }

        $product->loadMissing(['variants.images']);

        if (blank($product->coverImage())) {
            throw new MetaAdsException('El producto necesita una imagen principal antes de anunciarlo.');
        }
    }

    private function accessToken(): string
    {
        return trim((string) config('meta.ads.access_token'));
    }

    private function adAccountId(): string
    {
        $value = trim((string) config('meta.ads.ad_account_id'));

        if ($value === '') {
            return 'act_';
        }

        return str_starts_with($value, 'act_') ? $value : 'act_'.$value;
    }

    private function pageId(): string
    {
        return trim((string) config('meta.ads.page_id'));
    }

    private function apiVersion(): string
    {
        $version = trim((string) config('meta.graph_version', 'v23.0'), '/');

        return $version !== '' ? $version : 'v23.0';
    }
}
