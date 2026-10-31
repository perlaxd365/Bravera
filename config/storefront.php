<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storefront locale and currency
    |--------------------------------------------------------------------------
    |
    | All orders and payment gateways continue to charge in PEN. International
    | prices are an approximate USD conversion for display only, using the
    | approved fixed rate below (PEN per 1 USD).
    |
    */

    'default_country' => 'PE',
    'default_locale' => 'es',
    'supported_locales' => ['es', 'en'],
    'base_currency' => 'PEN',
    'international_currency' => 'USD',
    'usd_rate' => (float) env('STOREFRONT_USD_RATE', 3.49),

    'language_names' => [
        'es' => 'Español',
        'en' => 'English',
    ],

    'country_headers' => [
        'CF-IPCountry',
        'X-Country-Code',
        'X-Vercel-IP-Country',
    ],

    'cookies' => [
        'locale' => 'brevare_locale',
        'currency' => 'brevare_currency',
    ],
];
