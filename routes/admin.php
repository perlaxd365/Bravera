<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard\Index as DashboardIndex;
use App\Livewire\Admin\Catalog\Categories\Index as CategoryIndex;

Route::middleware('auth')->group(function () {

    Route::get('/', DashboardIndex::class)
        ->name('dashboard');

    Route::prefix('catalog')
        ->name('categories.')
        ->group(function () {

            Route::get('/categories', CategoryIndex::class)
                ->name('index');
        });
});
