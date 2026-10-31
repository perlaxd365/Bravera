<?php

namespace App\Providers;

use App\Mail\Transport\BrevoApiTransport;
use App\Services\AppSettingsService;
use App\Services\FavoriteService;
use App\Support\Localization\StorefrontContext;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FavoriteService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            app(AppSettingsService::class)->applyToConfiguration();
        } catch (Throwable $exception) {
            report($exception);
        }

        Mail::extend('brevo-api', function (array $config = []): BrevoApiTransport {
            return new BrevoApiTransport(
                apiKey: (string) ($config['api_key'] ?? ''),
                timeout: (int) ($config['timeout'] ?? 10),
            );
        });

        View::prependNamespace('livewire', resource_path('views/vendor/livewire'));

        // Livewire component tests and internal renders do not always pass
        // through the web middleware; keep the same safe default context.
        $initialStorefront = StorefrontContext::current();
        app()->setLocale($initialStorefront->locale);
        View::share('storefront', $initialStorefront);
    }
}
