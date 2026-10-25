<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Products\Form as ProductFormComponent;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductMetaAd;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Services\MetaAdsException;
use App\Services\MetaAdsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MetaAdsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'meta.ads.enabled' => true,
            'meta.ads.ad_account_id' => 'act_123',
            'meta.ads.page_id' => 'page_123',
            'meta.ads.access_token' => 'token_123',
            'meta.ads.app_secret' => null,
            'meta.graph_version' => 'v23.0',
        ]);
    }

    public function test_crea_un_anuncio_activo_con_la_imagen_principal(): void
    {
        Http::fake([
            'https://res.cloudinary.com/*' => Http::response('image-bytes', 200, ['Content-Type' => 'image/jpeg']),
            'https://graph.facebook.com/v23.0/act_123/adimages' => Http::response([
                'images' => ['producto.jpg' => ['hash' => 'hash_123']],
            ]),
            'https://graph.facebook.com/v23.0/act_123/adcreatives' => Http::response(['id' => 'creative_123']),
            'https://graph.facebook.com/v23.0/act_123/ads' => Http::response(['id' => 'ad_123']),
        ]);

        $product = $this->productWithMedia();
        $record = app(MetaAdsService::class)->createAd($product, $this->payload());

        $this->assertSame('active', $record->status);
        $this->assertSame('ad_123', $record->ad_id);
        $this->assertSame('creative_123', $record->creative_id);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://graph.facebook.com/v23.0/act_123/ads'
            && $request->method() === 'POST'
            && $request['adset_id'] === 'adset_123'
            && $request['status'] === 'ACTIVE');

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/adcreatives')
            && str_contains((string) $request['object_story_spec'], 'hash_123')
            && str_contains((string) $request['object_story_spec'], 'page_123'));
    }

    public function test_crea_un_anuncio_activo_con_el_video_del_producto(): void
    {
        Http::fake([
            'https://res.cloudinary.com/*' => Http::response('video-bytes', 200, ['Content-Type' => 'video/mp4']),
            'https://graph.facebook.com/v23.0/act_123/advideos' => Http::response(['id' => 'video_123']),
            'https://graph.facebook.com/v23.0/act_123/adcreatives' => Http::response(['id' => 'creative_video_123']),
            'https://graph.facebook.com/v23.0/act_123/ads' => Http::response(['id' => 'ad_video_123']),
        ]);

        $product = $this->productWithMedia();
        $product->update(['meta_ad_video_url' => 'https://res.cloudinary.com/demo/video/upload/v1/producto.mp4']);

        $record = app(MetaAdsService::class)->createAd(
            $product->fresh(['variants.images']),
            $this->payload(['media_type' => 'video'])
        );

        $this->assertSame('active', $record->status);
        $this->assertSame('ad_video_123', $record->ad_id);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/advideos'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/adcreatives')
            && str_contains((string) $request['object_story_spec'], '"video_id":"video_123"'));
    }

    public function test_reemplaza_un_anuncio_de_imagen_por_video_en_el_mismo_conjunto(): void
    {
        Http::fake([
            'https://res.cloudinary.com/*' => Http::response('video-bytes', 200, ['Content-Type' => 'video/mp4']),
            'https://graph.facebook.com/v23.0/act_123/advideos' => Http::response(['id' => 'video_replacement']),
            'https://graph.facebook.com/v23.0/act_123/adcreatives' => Http::response(['id' => 'creative_replacement']),
            'https://graph.facebook.com/v23.0/act_123/ads' => Http::response(['id' => 'ad_replacement']),
            'https://graph.facebook.com/v23.0/ad_image_old' => Http::response(['success' => true]),
        ]);

        $product = $this->productWithMedia();
        $product->update(['meta_ad_video_url' => 'https://res.cloudinary.com/demo/video/upload/v1/replacement.mp4']);
        $record = ProductMetaAd::create([
            'product_id' => $product->id,
            'ad_account_id' => 'act_123',
            'campaign_id' => 'campaign_123',
            'ad_set_id' => 'adset_123',
            'ad_id' => 'ad_image_old',
            'creative_id' => 'creative_image_old',
            'media_type' => 'image',
            'status' => 'active',
        ]);

        $replacement = app(MetaAdsService::class)->createAd(
            $product->fresh(),
            $this->payload(['media_type' => 'video']),
        );

        $this->assertSame('ad_replacement', $replacement->ad_id);
        $this->assertSame('creative_replacement', $replacement->creative_id);
        $this->assertSame('video', $replacement->media_type);
        $this->assertSame($record->id, $replacement->id);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://graph.facebook.com/v23.0/ad_image_old'
            && $request->method() === 'POST'
            && $request['status'] === 'PAUSED');
    }

    public function test_envia_instagram_user_id_con_el_nombre_actual_de_la_api(): void
    {
        config(['meta.ads.instagram_actor_id' => '17841400000000000']);
        Http::fake([
            'https://res.cloudinary.com/*' => Http::response('image-bytes', 200, ['Content-Type' => 'image/jpeg']),
            'https://graph.facebook.com/v23.0/act_123/adimages' => Http::response([
                'images' => ['producto.jpg' => ['hash' => 'hash_instagram']],
            ]),
            'https://graph.facebook.com/v23.0/act_123/adcreatives' => Http::response(['id' => 'creative_instagram']),
            'https://graph.facebook.com/v23.0/act_123/ads' => Http::response(['id' => 'ad_instagram']),
        ]);

        app(MetaAdsService::class)->createAd(
            $this->productWithMedia(),
            $this->payload(),
        );

        Http::assertSent(function (Request $request): bool {
            $story = (string) ($request['object_story_spec'] ?? '');

            return str_ends_with($request->url(), '/adcreatives')
                && str_contains($story, '"instagram_user_id":"17841400000000000"')
                && ! str_contains($story, '"instagram_actor_id"');
        });
    }

    public function test_no_anuncia_un_producto_oculto(): void
    {
        $product = $this->productWithMedia();
        $product->update(['is_visible' => false]);

        $this->expectException(MetaAdsException::class);
        $this->expectExceptionMessage('Publica el producto');

        app(MetaAdsService::class)->createAd($product, $this->payload());
    }

    public function test_lista_campanas_y_conjuntos_de_anuncios(): void
    {
        Http::fake([
            'https://graph.facebook.com/v23.0/act_123/campaigns*' => Http::response([
                'data' => [[
                    'id' => 'campaign_123',
                    'name' => 'Campaña Navidad',
                    'status' => 'PAUSED',
                    'effective_status' => 'PAUSED',
                    'objective' => 'OUTCOME_SALES',
                ]],
            ]),
            'https://graph.facebook.com/v23.0/campaign_123/adsets*' => Http::response([
                'data' => [[
                    'id' => 'adset_123',
                    'name' => 'Conjunto principal',
                    'status' => 'PAUSED',
                    'effective_status' => 'PAUSED',
                    'optimization_goal' => 'OFFSITE_CONVERSIONS',
                ]],
            ]),
        ]);

        $service = app(MetaAdsService::class);

        $this->assertSame('Campaña Navidad', $service->campaigns()[0]['name']);
        $this->assertSame('Conjunto principal', $service->adSets('campaign_123')[0]['name']);
    }

    public function test_detecta_el_error_real_de_meta_aunque_la_respuesta_http_sea_200(): void
    {
        Http::fake([
            'https://res.cloudinary.com/*' => Http::response('image-bytes', 200, ['Content-Type' => 'image/jpeg']),
            'https://graph.facebook.com/v23.0/act_123/adimages' => Http::response([
                'images' => ['producto.jpg' => ['hash' => 'hash_123']],
            ]),
            'https://graph.facebook.com/v23.0/act_123/adcreatives' => Http::response(['id' => 'creative_123']),
            'https://graph.facebook.com/v23.0/act_123/ads' => Http::response([
                'error' => [
                    'message' => 'This request requires the user to take a pending action',
                    'type' => 'OAuthException',
                    'code' => 31,
                    'error_subcode' => 3858385,
                    'error_user_msg' => 'Autentica tu cuenta en el administrador de anuncios.',
                    'fbtrace_id' => 'trace_123',
                ],
            ], 200),
        ]);

        try {
            app(MetaAdsService::class)->createAd(
                $this->productWithMedia(),
                $this->payload(),
            );

            $this->fail('Se esperaba que MetaAdsService rechazara la respuesta de error.');
        } catch (MetaAdsException $exception) {
            $this->assertSame(
                'Autentica tu cuenta en el administrador de anuncios.',
                $exception->getMessage(),
            );
            $this->assertSame(31, $exception->payload['code'] ?? null);
            $this->assertSame('trace_123', $exception->payload['_trace_id'] ?? null);
        }

        $record = ProductMetaAd::query()->firstOrFail();

        $this->assertSame('failed', $record->status);
        $this->assertSame(
            'Autentica tu cuenta en el administrador de anuncios.',
            $record->error_message,
        );
        $this->assertSame('creative_123', data_get($record->response_payload, 'creative.id'));
        $this->assertSame(31, data_get($record->response_payload, 'error.code'));
        $this->assertSame('trace_123', data_get($record->response_payload, 'error._trace_id'));
    }

    public function test_la_ficha_muestra_la_seccion_de_meta_sin_configuracion(): void
    {
        config(['meta.ads.enabled' => false]);

        $product = $this->productWithMedia();

        Livewire::test(ProductFormComponent::class)
            ->call('edit', $product->id)
            ->assertSee('Publicidad en Meta')
            ->assertSee('Meta Ads todavía no está configurado.');
    }

    public function test_la_carga_del_video_de_anuncio_muestra_progreso_y_errores(): void
    {
        $product = $this->productWithMedia();

        Livewire::test(ProductFormComponent::class)
            ->call('edit', $product->id)
            ->call('goToStep', 5)
            ->assertSee('livewire-upload-progress', false)
            ->assertSee('Guardando en Cloudinary…')
            ->assertSee('No se pudo transferir el video');
    }

    public function test_sincroniza_las_visitas_del_enlace_en_los_ultimos_treinta_dias(): void
    {
        Http::fake([
            'https://graph.facebook.com/v23.0/ad_123/insights*' => Http::response([
                'data' => [
                    ['inline_link_clicks' => '18'],
                    ['inline_link_clicks' => '7'],
                ],
            ]),
            'https://graph.facebook.com/v23.0/ad_123*' => Http::response([
                'id' => 'ad_123',
                'status' => 'ACTIVE',
                'effective_status' => 'ACTIVE',
            ]),
        ]);

        $product = $this->productWithMedia();
        $record = ProductMetaAd::create([
            'product_id' => $product->id,
            'ad_account_id' => 'act_123',
            'campaign_id' => 'campaign_123',
            'ad_set_id' => 'adset_123',
            'ad_id' => 'ad_123',
            'media_type' => 'image',
            'status' => 'paused',
        ]);

        $record = app(MetaAdsService::class)->refreshStatus($record);

        $this->assertSame('active', $record->status);
        $this->assertSame(25, $record->link_clicks_30d);
        $this->assertNotNull($record->metrics_synced_at);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/ad_123/insights')
            && $request['date_preset'] === 'last_30d'
            && $request['fields'] === 'inline_link_clicks');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'campaign_id' => 'campaign_123',
            'campaign_name' => 'Campaña Navidad',
            'ad_set_id' => 'adset_123',
            'ad_set_name' => 'Conjunto principal',
            'media_type' => 'image',
            'primary_text' => 'Compra este producto en Brevare.',
            'headline' => 'Producto Brevare',
            'description' => 'Envíos a todo el Perú.',
            'cta' => 'SHOP_NOW',
            'destination_url' => 'https://brevare.com/producto/producto-test',
        ], $overrides);
    }

    private function productWithMedia(): Product
    {
        $category = Category::create([
            'name' => 'Pruebas',
            'slug' => 'pruebas',
            'path' => 'pruebas',
            'is_visible' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto de prueba',
            'slug' => 'producto-test',
            'short_description' => 'Producto para probar anuncios.',
            'status' => true,
            'is_visible' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-META-TEST',
            'sale_price' => 99,
            'cost_price' => 50,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductImage::create([
            'product_variant_id' => $variant->id,
            'public_id' => 'productos/producto-test',
            'file_name' => 'producto-test.jpg',
            'url' => 'https://res.cloudinary.com/demo/image/upload/v1/producto-test.jpg',
            'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/producto-test.jpg',
            'width' => 1200,
            'height' => 1200,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-META',
            'business_name' => 'Brevare',
            'status' => 'active',
            'is_internal' => true,
        ]);

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'supplier_sku' => 'SUP-META-SKU',
            'cost_price' => 50,
            'shipping_cost' => 0,
            'stock' => 10,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        return $product->fresh(['variants.images']);
    }
}
