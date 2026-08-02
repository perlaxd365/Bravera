<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard\Index as DashboardIndex;
use App\Livewire\Admin\Catalog\Categories\Index as CategoryIndex;
use App\Livewire\Admin\Catalog\Brands\Index as BrandIndex;
use App\Livewire\Admin\Catalog\Attributes\Index as AttributeIndex;
use App\Livewire\Admin\Catalog\AttributeValues\Index as AttributeValueIndex;
use App\Livewire\Admin\Catalog\Suppliers\Index as SupplierIndex;
use App\Livewire\Admin\Catalog\Products\Index as ProductIndex;

Route::middleware('auth')->group(function () {

    Route::get('/', DashboardIndex::class)
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Catálogo
    |--------------------------------------------------------------------------
    */

    Route::prefix('catalog')->name('catalog.')->group(function () {

        Route::get('/categories', CategoryIndex::class)
            ->name('categories.index');

        Route::get('/brands', BrandIndex::class)
            ->name('brands.index');

        Route::get('/attributes', AttributeIndex::class)
            ->name('attributes.index');

        Route::get('/attribute-values', AttributeValueIndex::class)
            ->name('attribute-values.index');

        Route::get('/suppliers', SupplierIndex::class)
            ->name('suppliers.index');

        Route::get('/products', ProductIndex::class)
            ->name('products.index');
    });
});
