<?php

use App\Modules\Payments\Gateways\DemoGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Pasarela por defecto
    |--------------------------------------------------------------------------
    |
    | Debe coincidir con una clave de 'gateways'. Si se deja vacío el cobro
    | falla de forma cerrada: no se procesa ningún pedido sin pasarela real.
    |
    */

    'default_gateway' => env('PAYMENT_GATEWAY'),

    /*
    |--------------------------------------------------------------------------
    | Pasarelas registradas
    |--------------------------------------------------------------------------
    |
    | Cada clave es el nombre interno (se guarda en payments.gateway) y el
    | valor es la clase que implementa App\Modules\Payments\Contracts\
    | PaymentGateway. Para operar de verdad hay que registrar aquí una
    | pasarela con credenciales reales; 'demo'/'manual' solo cobra en
    | desarrollo y está bloqueada mientras 'allow_demo_gateway' sea false.
    |
    */

    'gateways' => [
        'demo' => DemoGateway::class,
        'manual' => DemoGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pasarelas de demostración
    |--------------------------------------------------------------------------
    |
    | Nombres que nunca deben procesar un cobro real. Se rechazan en
    | producción aunque estén registradas arriba.
    |
    */

    'demo_gateways' => ['demo', 'manual'],

    /*
    |--------------------------------------------------------------------------
    | Habilitar la pasarela de demostración
    |--------------------------------------------------------------------------
    |
    | Solo para desarrollo local. En producción debe ser false: la pasarela
    | demo aprueba cualquier monto, así que habilitarla en producción
    | despacharía pedidos sin cobrar nada.
    |
    */

    'allow_demo_gateway' => env('PAYMENT_ALLOW_DEMO', false),

    /*
    |--------------------------------------------------------------------------
    | Métodos de pago disponibles en la tienda
    |--------------------------------------------------------------------------
    */

    'methods' => [
        'card' => 'Tarjeta de débito/crédito',
        'yape' => 'Yape',
        'transferencia' => 'Transferencia bancaria',
    ],

];
