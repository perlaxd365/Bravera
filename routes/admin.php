<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Catalog\Categories\Index;

Route::get('/', function () {
    return view('admin.dashboard.index');
})->name('dashboard');


Route::middleware(['auth'])->group(function () {

    Route::prefix('catalog')->group(function () {

        Route::get('/categories', Index::class)
            ->name('admin.categories.index');
    });
});
