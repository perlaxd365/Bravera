<?php

use App\Modules\Payments\Gateways\Culqi\CulqiGateway;
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
        'culqi' => CulqiGateway::class,
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
    | Culqi
    |--------------------------------------------------------------------------
    |
    | Credenciales de Culqi (ecosistema BCP). La clave pública (pk_) se usa en
    | el navegador para tokenizar, la secreta (sk_) solo en el servidor. Nunca
    | expongas la sk_ ni la RSA al frontend.
    |
    | Modo prueba: pk_test_ / sk_test_. Modo producción: pk_live_ / sk_live_.
    | Los hosts son los mismos en ambos casos; el prefijo de la clave decide.
    |
    */

    'culqi' => [
        'public_key' => env('CULQI_PUBLIC_KEY'),
        'secret_key' => env('CULQI_SECRET_KEY'),

        // Habilita cifrado RSA del payload en requests de tarjeta.
        'rsa_id' => env('CULQI_RSA_ID'),
        'rsa_public_key' => env('CULQI_RSA_PUBLIC_KEY'),

        'secure_url' => env('CULQI_SECURE_URL', 'https://secure.culqi.com/v2'),
        'api_url' => env('CULQI_API_URL', 'https://api.culqi.com/v2'),

        // URL pública que Culqi llamará para notificar eventos.
        'webhook_url' => env('CULQI_WEBHOOK_URL'),

        // Captura inmediata del cobro. false = solo autorización (hold).
        'capture' => env('CULQI_CAPTURE', true),

        // Timeout (segundos) para las llamadas a la API.
        'timeout' => env('CULQI_TIMEOUT', 20),

        // Vigencia de la orden de Yape/QR. Culqi exige expiration_date futura.
        'yape_expiration_minutes' => env('CULQI_YAPE_EXPIRATION_MINUTES', 30),

        /*
        |----------------------------------------------------------------------
        | Métodos del modal de Checkout Custom
        |----------------------------------------------------------------------
        |
        | El modal de Culqi es el que elige el método de pago, así que la tienda
        | no ofrece su propio selector. Esta lista se envía tal cual en
        | options.paymentMethods y Culqi oculta los que la cuenta no tenga
        | habilitados: no hace falta conocer la habilitación real para no
        | romper el checkout, y todo lo que sí esté disponible aparece.
        |
        | 'tarjeta' no necesita orden previa (se cobra con un cargo). El resto sí:
        | Culqi rechaza los métodos asíncronos si no se le pasa un settings.order
        | creado antes de abrir el checkout, por eso el pedido y su ord_ se crean
        | al pulsar "Pagar", antes de abrir el modal.
        |
        */

        'modal_methods' => ['tarjeta', 'yape', 'billetera', 'bancaMovil', 'agente', 'cuotealo'],

        // Métodos que se pagan contra la orden (ord_) en lugar de con un cargo.
        'gateway_methods' => [
            'culqi' => [
                'card',
                'yape',
                'billetera',
                'bancaMovil',
                'agente',
                'cuotealo',
            ],
            'demo' => ['card', 'yape', 'transferencia'],
            'manual' => ['card', 'yape', 'transferencia'],
        ],

        // Tipos declarados en la orden para que esos métodos puedan pagarse.
        // 'tarjeta' queda fuera a propósito: Culqi no la cobra contra una orden.
        'order_payment_methods' => [
            'yape'
        ],

        /*
        | Traducción del método de Culqi al método interno (payments.method).
        | Solo difiere en la tarjeta, que Culqi llama "tarjeta" y aquí es "card".
        | El resto se guarda con el nombre de Culqi para no perder de vista con
        | qué medio pagó el cliente.
        */

        'method_map' => [
            'tarjeta' => 'card',
            'yape' => 'yape',
            'billetera' => 'billetera',
            'bancaMovil' => 'bancaMovil',
            'agente' => 'agente',
            'cuotealo' => 'cuotealo',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Horas que un pedido espera el pago antes de cancelarse
    |--------------------------------------------------------------------------
    |
    | Al abrir el modal ya existe el pedido y su stock está reservado, aunque el
    | comprador cierre sin pagar. brevare:expire-pending-orders cancela los que
    | llevan demasiado tiempo en pendiente y devuelven el stock al inventario.
    |
    */

    'pending_order_hours' => env('PAYMENT_PENDING_ORDER_HOURS', 24),

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

    /*
    |--------------------------------------------------------------------------
    | Métodos que admite cada pasarela
    |--------------------------------------------------------------------------
    |
    | 'methods' es el catálogo que la tienda conoce; esta tabla dice qué admite
    | cada pasarela de verdad. El checkout solo ofrece los de la pasarela activa
    | y placeOrder() los vuelve a validar. Ofrecer un método que la pasarela
    | rechaza (por ejemplo 'transferencia' con Culqi) solo produce un pedido que
    | no puede cobrarse.
    |
    | Solo aplica a las pasarelas que cobran desde la tienda. Las de modal
    | (ver 'modal_gateways') no usan esta lista: el selector es el del
    | proveedor y los métodos asíncronos se declaran en la orden, no aquí.
    |
    | Culqi: 'card' usa token + charge (síncrono) y 'yape' el flujo de órdenes,
    | con redirección a url_pe y confirmación por webhook.
    |
    */

    'gateway_methods' => [
        'culqi' => ['card', 'yape'],
        'demo' => ['card', 'yape', 'transferencia'],
        'manual' => ['card', 'yape', 'transferencia'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pasarelas que cobran dentro de su propio modal
    |--------------------------------------------------------------------------
    |
    | En estas pasarelas el cliente elige el método de pago dentro del checkout
    | del proveedor, así que la tienda no dibuja su propio selector ni su propio
    | formulario: solo el botón que abre el modal.
    |
    | El botón crea el pedido y su orden en la pasarela ANTES de abrir el modal,
    | porque los métodos asíncronos no se pueden pagar sin una orden previa. Si
    | el comprador cierra sin pagar, el pedido queda pendiente y el comando
    | brevare:expire-pending-orders lo cancela devolviendo el stock.
    |
    */

    'modal_gateways' => ['culqi'],

    /*
    |--------------------------------------------------------------------------
    | Pasarelas que exigen token de tarjeta del navegador
    |--------------------------------------------------------------------------
    |
    | Estas pasarelas no aceptan el número de tarjeta: el navegador debe
    | tokenizarlo con su SDK y enviar solo el identificador opaco. Para las
    | demás (incluida la de demostración) el checkout no exige token.
    |
    */

    'card_token_gateways' => ['culqi'],

];
