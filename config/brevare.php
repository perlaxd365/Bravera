<?php

return [
    'modules_path' => app_path('Modules'),
    'stubs_path' => base_path('stubs/brevare'),
    'default_namespace' => 'App\\Modules',

    /*
    |--------------------------------------------------------------------------
    | Datos legales de la empresa
    |--------------------------------------------------------------------------
    |
    | Fuente única de verdad para datos de contacto y fiscales. Se usan en las
    | páginas legales, en el footer y en los datos estructurados (JSON-LD).
    |
    */

    'company' => [
        'name' => 'Brevare Perú',
        'legal_name' => env('COMPANY_LEGAL_NAME', 'Inversiones La Breña S.A.C.'),
        'ruc' => env('COMPANY_RUC', '10738883123'),
        'email' => env('COMPANY_EMAIL', 'administracion@brevare.com'),
        'support_email' => env('COMPANY_SUPPORT_EMAIL', 'administracion@brevare.com'),
        'phone' => env('COMPANY_PHONE', '+51 902 517 849'),
        'phone_e164' => env('COMPANY_PHONE_E164', '+51902517849'),
        'whatsapp' => env('COMPANY_WHATSAPP', '51902517849'),
        'address' => env('COMPANY_ADDRESS', 'Avenida Camino Real 456'),
        'city' => env('COMPANY_CITY', 'San Isidro'),
        'region' => env('COMPANY_REGION', 'Lima'),
        'country' => env('COMPANY_COUNTRY', 'PE'),
        'country_name' => env('COMPANY_COUNTRY_NAME', 'Perú'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cuenta de pruebas (validaciones de Culqi)
    |--------------------------------------------------------------------------
    |
    | Publica en el login las credenciales de la cuenta de prueba que usan los
    | revisores del procesador de pagos para completar el proceso de compra.
    |
    | Cuando terminen las validaciones, pon REVIEW_LOGIN_ENABLED=false en el
    | .env (y ejecuta `php artisan config:clear`), y elimina o cambia la
    | contraseña de la cuenta desde el panel.
    |
    */

    'review_login' => [
        'enabled' => env('REVIEW_LOGIN_ENABLED', true),
        'email' => env('REVIEW_LOGIN_EMAIL', 'revision@brevare.com'),
        'password' => env('REVIEW_LOGIN_PASSWORD', 'Brevare2026Culqi'),
    ],
];
