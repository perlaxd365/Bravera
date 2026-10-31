<?php

namespace App\Http\Controllers\Store;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;

class CartRecoveryController
{
    public function __invoke(string $token, CartService $cart): RedirectResponse
    {
        $recovered = Cart::query()
            ->where('recovery_token', $token)
            ->with('user')
            ->firstOrFail();

        if ($recovered->status !== 'active' || $recovered->activeItems()->doesntExist()) {
            return redirect()
                ->route('store.cart')
                ->with('status', 'Este carrito ya fue procesado o está vacío.');
        }

        // Los usuarios registrados deben autenticarse. Un invitado no tiene
        // contraseña: el token enviado a su correo es la prueba de acceso y
        // el checkout le pedirá sus datos nuevamente.
        if ($recovered->user_id && ! $recovered->user?->is_guest) {
            if (! auth()->check()) {
                session(['url.intended' => request()->fullUrl()]);

                return redirect()
                    ->route('login')
                    ->with('status', 'Inicia sesión para recuperar tu carrito.');
            }

            abort_unless(
                (int) auth()->id() === (int) $recovered->user_id,
                403,
            );
        }

        $recovered->forceFill(['last_activity_at' => now()])->save();
        session([CartService::SESSION_KEY => $recovered->id]);

        return redirect()->route('checkout');
    }
}
