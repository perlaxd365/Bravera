<?php

use App\Http\Controllers\Api\V1\CatalogOptionsController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Middleware\ValidateProductApiPassword;
use Illuminate\Support\Facades\Route;

Route::middleware([ValidateProductApiPassword::class, 'throttle:30,1'])
    ->prefix('v1')
    ->group(function (): void {
        Route::get('catalog/options', CatalogOptionsController::class);
        Route::post('suppliers', [SupplierController::class, 'store']);
        Route::post('products', [ProductController::class, 'store']);
        Route::get('products/{product}', [ProductController::class, 'show']);
    });
