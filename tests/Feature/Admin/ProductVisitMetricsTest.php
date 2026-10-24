<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Products\Index as ProductIndex;
use App\Livewire\Store\Catalog as StoreCatalog;
use App\Models\Category;
use App\Models\PageView;
use App\Models\Product;
use App\Models\ProductMetaAd;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\User;
use App\Modules\Product\Repositories\StoreCatalogRepository;
use Carbon\CarbonInterface;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductVisitMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_admin_shows_web_and_active_meta_visits_and_can_sort_by_each(): void
    {
        $high = $this->product('Visto alto', 'visto-alto');
        $low = $this->product('Visto bajo', 'visto-bajo');
        $paused = $this->product('Anuncio pausado', 'anuncio-pausado');

        $this->webViews($high, 8);
        $this->webViews($low, 3);

        $this->metaAd($high, 'active', 120, 'adset-high-1');
        $this->metaAd($high, 'active', 30, 'adset-high-2');
        $this->metaAd($low, 'active', 40, 'adset-low');
        $this->metaAd($paused, 'paused', 999, 'adset-paused');

        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail());

        Livewire::test(ProductIndex::class)
            ->assertSee('Visitas web · 30 d')
            ->assertSee('Visitas Meta · 30 d')
            ->assertSee('Sin anuncio activo en Meta')
            ->set('sort', 'web_visits_desc')
            ->assertSeeInOrder(['Visto alto', 'Visto bajo', 'Anuncio pausado'])
            ->set('sort', 'meta_visits_desc')
            ->assertSeeInOrder(['Visto alto', 'Visto bajo', 'Anuncio pausado']);

        $this->assertSame(8, $high->fresh()->pageViews()->where('viewed_at', '>=', now()->subDays(30))->count());
    }

    public function test_store_catalog_can_sort_by_each_visit_source(): void
    {
        $high = $this->product('Trafico alto', 'trafico-alto');
        $low = $this->product('Trafico bajo', 'trafico-bajo');

        $this->webViews($high, 5);
        $this->webViews($low, 2);
        $this->webViews($high, 100, now()->subDays(31));

        $this->metaAd($high, 'active', 70, 'store-adset-high');
        $this->metaAd($low, 'active', 10, 'store-adset-low');

        Livewire::test(StoreCatalog::class)
            ->assertSee('Más visitas web')
            ->assertSee('Más visitas desde Meta')
            ->set('sort', 'web_visits_desc')
            ->assertSeeInOrder(['Trafico alto', 'Trafico bajo'])
            ->set('sort', 'meta_visits_desc')
            ->assertSeeInOrder(['Trafico alto', 'Trafico bajo']);

        $products = app(StoreCatalogRepository::class)
            ->paginate(sort: 'web_visits_desc')
            ->getCollection()
            ->keyBy('id');

        $this->assertSame(5, (int) $products[$high->id]->web_visits_30d);
        $this->assertSame(2, (int) $products[$low->id]->web_visits_30d);
    }

    private function product(string $name, string $slug): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'metricas-visitas'],
            [
                'name' => 'Métricas de visitas',
                'path' => 'metricas-visitas',
                'is_visible' => true,
            ],
        );

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'status' => true,
            'is_visible' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VISIT-'.strtoupper($slug),
            'sale_price' => 99,
            'cost_price' => 50,
            'is_active' => true,
            'is_default' => true,
        ]);

        $supplier = Supplier::query()->firstOrCreate(
            ['code' => 'SUP-VISITS'],
            [
                'business_name' => 'Brevare',
                'status' => 'active',
                'is_internal' => true,
            ],
        );

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'supplier_sku' => 'SUP-'.strtoupper($slug),
            'cost_price' => 50,
            'shipping_cost' => 0,
            'stock' => 10,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        return $product;
    }

    private function webViews(Product $product, int $count, ?CarbonInterface $viewedAt = null): void
    {
        $viewedAt ??= now();

        for ($index = 0; $index < $count; $index++) {
            PageView::create([
                'product_id' => $product->id,
                'visitor_token' => 'visitor-'.$product->id.'-'.$index.'-'.$viewedAt->timestamp,
                'path' => '/producto/'.$product->slug,
                'viewed_at' => $viewedAt,
            ]);
        }
    }

    private function metaAd(Product $product, string $status, int $clicks, string $adSetId): ProductMetaAd
    {
        return ProductMetaAd::create([
            'product_id' => $product->id,
            'ad_account_id' => 'act_123',
            'campaign_id' => 'campaign_123',
            'ad_set_id' => $adSetId,
            'ad_id' => 'ad-'.$adSetId,
            'media_type' => 'image',
            'status' => $status,
            'link_clicks_30d' => $clicks,
            'metrics_synced_at' => now(),
        ]);
    }
}
