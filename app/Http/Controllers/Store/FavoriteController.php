<?php

namespace App\Http\Controllers\Store;

use App\Models\Product;
use App\Services\FavoriteService;
use Illuminate\Http\RedirectResponse;

class FavoriteController
{
    public function toggle(Product $product, FavoriteService $favorites): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $isFavorite = $favorites->toggle($user, $product);
        $message = $isFavorite
            ? 'Producto guardado en favoritos.'
            : 'Producto eliminado de favoritos.';

        return back()->with('status', $message);
    }
}
