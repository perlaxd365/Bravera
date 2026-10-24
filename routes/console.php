<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('brevare:prune-cloudinary')->daily();

/*
| Los pedidos en pendiente nacen con el stock ya reservado (el modal de Culqi
| exige crear la orden antes de abrir el pago), así que hay que devolverlo
| cuando el comprador no completa la compra. A cada hora: un comprador que
| abandona a media sesión libera el inventario en un plazo razonable.
*/

Schedule::command('brevare:expire-pending-orders')->hourly();

/*
| Las métricas de Meta se guardan localmente para que los listados no esperen
| por la API externa. Quince minutos mantiene el dato útil sin saturar la API.
*/

Schedule::command('brevare:sync-meta-ads')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

/*
| El comando se ejecuta a diario, pero cada carrito recibe como máximo un
| recordatorio cada `CART_RECOVERY_DAYS` días. Así el correo sale cada 3 días
| sin necesidad de crear un cron distinto por carrito.
*/

Schedule::command('carts:send-recovery')
    ->dailyAt('10:00')
    ->timezone('America/Lima')
    ->withoutOverlapping();
