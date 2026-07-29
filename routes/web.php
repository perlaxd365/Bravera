<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::prefix('admin')
    ->middleware(['auth'])
    ->as('admin.')
    ->group(base_path('routes/admin.php'));

require __DIR__.'/auth.php';