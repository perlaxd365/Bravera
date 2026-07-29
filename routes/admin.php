<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('admin.dashboard.index');
})->name('dashboard');

Route::prefix('catalog')->group(function () {

    Route::get('/categories', \App\Livewire\Admin\Catalog\Categories\Index::class)
        ->name('admin.categories.index');
});
