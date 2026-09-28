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
