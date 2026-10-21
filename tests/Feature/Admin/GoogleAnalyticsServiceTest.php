<?php

namespace Tests\Feature\Admin;

use App\Services\GoogleAnalyticsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAnalyticsServiceTest extends TestCase
{
    private ?string $credentialsPath = null;

    protected function tearDown(): void
    {
        if ($this->credentialsPath !== null && is_file($this->credentialsPath)) {
            unlink($this->credentialsPath);
        }

        parent::tearDown();
    }

    public function test_ga4_report_returns_today_and_yesterday_traffic(): void
    {
        $this->travelTo(Carbon::parse('2026-10-21 12:00:00'));
        Cache::flush();
        $this->configureCredentials();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ga-test-token',
                'expires_in' => 3600,
            ]),
            'https://analyticsdata.googleapis.com/v1beta/properties/123456789:runReport' => Http::response([
                'rows' => [
                    [
                        'dimensionValues' => [['value' => '20261021']],
                        'metricValues' => [
                            ['value' => '42'],
                            ['value' => '87'],
                        ],
                    ],
                    [
                        'dimensionValues' => [['value' => '20261020']],
                        'metricValues' => [
                            ['value' => '35'],
                            ['value' => '64'],
                        ],
                    ],
                ],
            ]),
        ]);

        $service = new class extends GoogleAnalyticsService
        {
            protected function accessToken(array $credentials): string
            {
                return 'ga-test-token';
            }

            protected function credentials(): array
            {
                return [
                    'client_email' => 'analytics-test@example.iam.gserviceaccount.com',
                    'private_key' => 'not-used-in-this-test',
                ];
            }
        };

        $traffic = $service->traffic();

        $this->assertSame(42, $traffic['visitorsToday']);
        $this->assertSame(35, $traffic['visitorsYesterday']);
        $this->assertSame(87, $traffic['pageViewsToday']);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), ':runReport')
            && $request->hasHeader('Authorization', 'Bearer ga-test-token')
            && data_get($request->data(), 'metrics.0.name') === 'activeUsers'
            && data_get($request->data(), 'metrics.1.name') === 'screenPageViews');
    }

    public function test_ga4_service_is_not_configured_without_property_and_credentials(): void
    {
        config([
            'services.google_analytics.property_id' => null,
            'services.google_analytics.credentials_path' => null,
        ]);

        $this->assertFalse(app(GoogleAnalyticsService::class)->isConfigured());
    }

    private function configureCredentials(): void
    {
        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'ga4-test-');
        $this->assertNotFalse($this->credentialsPath);
        file_put_contents($this->credentialsPath, json_encode([
            'type' => 'service_account',
            'client_email' => 'analytics-test@example.iam.gserviceaccount.com',
            'private_key' => 'not-used-in-this-test',
        ], JSON_THROW_ON_ERROR));

        config([
            'services.google_analytics.property_id' => '123456789',
            'services.google_analytics.credentials_path' => $this->credentialsPath,
        ]);
    }
}
