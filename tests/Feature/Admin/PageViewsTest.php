<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard\Index as DashboardIndex;
use App\Models\Category;
use App\Models\PageView;
use App\Models\Product;
use App\Models\User;
use App\Services\GoogleAnalyticsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PageViewsTest extends TestCase
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

    public function test_public_page_view_is_recorded_only_after_analytics_consent(): void
    {
        $this->get(route('about'))->assertOk();

        $this->assertDatabaseCount('page_views', 0);

        $this->withCookie('brevare_consent', 'all')
            ->get(route('about'))
            ->assertOk()
            ->assertCookie('brevare_visitor');

        $this->assertDatabaseCount('page_views', 1);
        $this->assertDatabaseHas('page_views', [
            'path' => '/nosotros',
        ]);
    }

    public function test_dashboard_shows_unique_visitors_and_page_views_for_today(): void
    {
        $this->travelTo(Carbon::parse('2026-10-21 12:00:00'));

        $this->pageView('11111111-1111-4111-8111-111111111111', '/');
        $this->pageView('11111111-1111-4111-8111-111111111111', '/producto/demo');
        $this->pageView('22222222-2222-4222-8222-222222222222', '/nosotros');
        $this->pageView(
            '33333333-3333-4333-8333-333333333333',
            '/',
            today()->subDay(),
        );

        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail());

        Livewire::test(DashboardIndex::class)
            ->assertSee('Personas que vieron la página')
            ->assertSee('2')
            ->assertSee('3 páginas vistas')
            ->assertViewHas('traffic', fn (array $traffic): bool => $traffic['visitorsToday'] === 2
                && $traffic['visitorsYesterday'] === 1
                && $traffic['pageViewsToday'] === 3
                && $traffic['activeNow'] === 0);
    }

    public function test_product_page_views_are_linked_to_the_product(): void
    {
        $category = Category::create([
            'name' => 'Pruebas',
            'slug' => 'pruebas',
            'path' => 'pruebas',
            'is_visible' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto visitado',
            'slug' => 'producto-visitado',
            'status' => true,
            'is_visible' => true,
        ]);

        $this->withCookie('brevare_consent', 'all')
            ->get(route('store.product', ['slug' => $product->slug]))
            ->assertOk();

        $this->assertDatabaseHas('page_views', [
            'product_id' => $product->id,
            'path' => '/producto/producto-visitado',
        ]);
    }

    public function test_dashboard_prefers_ga4_when_the_data_api_is_configured(): void
    {
        $this->app->instance(GoogleAnalyticsService::class, new class extends GoogleAnalyticsService
        {
            public function isConfigured(): bool
            {
                return true;
            }

            public function traffic(): array
            {
                return [
                    'visitorsToday' => 18,
                    'visitorsYesterday' => 12,
                    'pageViewsToday' => 31,
                    'activeNow' => 2,
                ];
            }
        });

        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail());

        Livewire::test(DashboardIndex::class)
            ->assertSee('Google Analytics 4')
            ->assertSee('2 personas activas ahora')
            ->assertViewHas('traffic', fn (array $traffic): bool => $traffic['visitorsToday'] === 18
                && $traffic['visitorsYesterday'] === 12
                && $traffic['pageViewsToday'] === 31
                && $traffic['activeNow'] === 2)
            ->assertViewHas('trafficSource', 'ga4');
    }

    private function pageView(
        string $visitorToken,
        string $path,
        ?Carbon $viewedAt = null,
    ): void {
        PageView::create([
            'visitor_token' => $visitorToken,
            'path' => $path,
            'viewed_at' => $viewedAt ?? today()->addHours(12),
        ]);
    }
}
