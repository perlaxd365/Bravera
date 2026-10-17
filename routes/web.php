<?php

use App\Http\Controllers\Webhooks\CulqiWebhookController;
use App\Http\Controllers\Izipay\IzipayReturnController;
use App\Http\Controllers\Webhooks\IzipayWebhookController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;
use App\Http\Controllers\MercadoPagoConfigController;
use App\Http\Controllers\MercadoPagoReturnController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\Seo\GoogleMerchantFeedController;
use App\Livewire\Account\Addresses;
use App\Livewire\Account\OrderDetail;
use App\Livewire\Account\Orders;
use App\Livewire\Account\PasswordPanel;
use App\Livewire\Account\Profile;
use App\Livewire\Store\Cart\Index as CartIndex;
use App\Livewire\Store\Catalog;
use App\Livewire\Store\Checkout;
use App\Livewire\Store\Home;
use App\Livewire\Store\MercadoPagoPayment;
use App\Livewire\Store\OrderPlaced;
use App\Livewire\Store\ProductDetail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks de pasarelas
|--------------------------------------------------------------------------
|
| Rutas server-to-server. La seguridad no depende del cuerpo del request:
| el controlador re-consulta a la API de Culqi antes de confirmar un pago.
| Están exentas de CSRF en bootstrap/app.php.
|
*/

Route::post('webhooks/culqi', CulqiWebhookController::class)
    ->name('webhooks.culqi');

Route::post('webhooks/izipay', IzipayWebhookController::class)
    ->name('webhooks.izipay');

Route::post('webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->name('webhooks.mercadopago');

Route::get('pagos/mercadopago/configuracion', MercadoPagoConfigController::class)
    ->name('payments.mercadopago.config');

Route::get('pagos/mercadopago/retorno', MercadoPagoReturnController::class)
    ->name('payments.mercadopago.return');

/*
 * Retorno del navegador desde el formulario de Izipay: llega por POST, sin
 * cookie de sesión y sin token CSRF (ver bootstrap/app.php).
 */
Route::post('pagos/izipay/retorno', IzipayReturnController::class)
    ->name('payments.izipay.return');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('feeds/google-shopping.xml', GoogleMerchantFeedController::class)->name('google.merchant-feed');

/*
|--------------------------------------------------------------------------
| Tienda pública
|--------------------------------------------------------------------------
*/

Route::get('/', Home::class)
    ->name('home');

Route::get('buscar', Catalog::class)
    ->name('store.search');

Route::get('categoria/{path}', Catalog::class)
    ->where('path', '.*')
    ->name('store.category');

Route::get('producto/{slug}', ProductDetail::class)
    ->name('store.product');

Route::get('carrito', CartIndex::class)
    ->name('store.cart');

Route::get('checkout', Checkout::class)
    ->middleware('verified.if.authenticated')
    ->name('checkout');

Route::get('pedido/{order:order_number}/confirmado', OrderPlaced::class)
    ->middleware('verified.if.authenticated')
    ->name('store.order.placed');

Route::get('pago/izipay/{order:order_number}', \App\Livewire\Store\IzipayPayment::class)
    ->middleware(['auth', 'verified'])
    ->name('payments.izipay.pay');

Route::get('pago/mercadopago/{order:order_number}', MercadoPagoPayment::class)
    ->middleware(['auth', 'verified'])
    ->name('payments.mercadopago.pay');

/*
|--------------------------------------------------------------------------
| Cuenta del cliente
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('mi-cuenta')->name('account.')->group(function () {
    Route::get('perfil', Profile::class)
        ->name('profile');

    Route::get('contrasena', PasswordPanel::class)
        ->name('password');

    Route::get('pedidos', Orders::class)
        ->name('orders');

    Route::get('pedido/{order:order_number}', OrderDetail::class)
        ->name('orders.show');

    Route::get('direcciones', Addresses::class)
        ->name('addresses');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::redirect('profile', '/mi-cuenta/perfil')
    ->middleware(['auth', 'verified'])
    ->name('profile');

require __DIR__.'/auth.php';



Route::get('terminos-y-condiciones', App\Livewire\Pages\Legal\Terms::class)->name('terms');

Route::get('politica-de-cambios-y-devoluciones', App\Livewire\Pages\Legal\Returns::class)->name('returns');
Route::get('politica-de-privacidad', App\Livewire\Pages\Legal\Privacy::class)->name('privacy');
Route::get('libro-de-reclamaciones', App\Livewire\Pages\Legal\Claims::class)->name('claims');
