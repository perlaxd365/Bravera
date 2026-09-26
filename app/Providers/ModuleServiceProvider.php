<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $views = app_path('Modules/Product/Views');

        if (is_dir($views)) {
            $this->loadViewsFrom($views, 'product');
        }
    }
}
