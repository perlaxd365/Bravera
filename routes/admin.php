<?php

use App\Livewire\Admin\Catalog\Attributes\Index as AttributeIndex;
use App\Livewire\Admin\Catalog\AttributeValues\Index as AttributeValueIndex;
use App\Livewire\Admin\Catalog\Brands\Index as BrandIndex;
use App\Livewire\Admin\Catalog\Categories\Index as CategoryIndex;
use App\Livewire\Admin\Catalog\Products\Index as ProductIndex;
use App\Livewire\Admin\Catalog\Suppliers\Index as SupplierIndex;
use App\Livewire\Admin\Dashboard\Index as DashboardIndex;
use App\Livewire\Admin\Discounts\Coupons\Index as CouponIndex;
use App\Livewire\Admin\Dropshipping\Orders\Index as SupplierOrderIndex;
use App\Livewire\Admin\Orders\Index as OrderIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Livewire\Admin\Shipping\Rates\Index as ShippingRateIndex;
use App\Livewire\Admin\Shipping\Zones\Index as ShippingZoneIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::get('/', DashboardIndex::class)
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Ventas
    |--------------------------------------------------------------------------
    */

    Route::prefix('orders')->name('orders.')->group(function () {

        Route::get('/', OrderIndex::class)
            ->name('index');

        Route::get('/{order}', OrderShow::class)
            ->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Dropshipping
    |--------------------------------------------------------------------------
    */

    Route::prefix('dropshipping')->name('dropshipping.')->group(function () {

        Route::get('/orders', SupplierOrderIndex::class)
            ->name('orders.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Descuentos
    |--------------------------------------------------------------------------
    */

    Route::prefix('discounts')->name('discounts.')->group(function () {

        Route::get('/coupons', CouponIndex::class)
            ->name('coupons.index');
    });

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

    /*
    |--------------------------------------------------------------------------
    | Envíos
    |--------------------------------------------------------------------------
    */

    Route::prefix('shipping')->name('shipping.')->group(function () {

        Route::get('/zones', ShippingZoneIndex::class)
            ->name('zones.index');

        Route::get('/rates', ShippingRateIndex::class)
            ->name('rates.index');
    });
});
