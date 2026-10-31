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

    /*
    | Country lookup is cached per IP. It is only used when the hosting or CDN
    | does not provide a country header. Private/reserved IPs are never sent.
    */
    'geoip' => [
        'enabled' => env('STOREFRONT_GEOIP_ENABLED', true),
        'endpoint' => env('STOREFRONT_GEOIP_ENDPOINT', 'https://ipwho.is/{ip}'),
        'timeout' => env('STOREFRONT_GEOIP_TIMEOUT', 2),
        'cache_hours' => env('STOREFRONT_GEOIP_CACHE_HOURS', 24),
    ],

    'cookies' => [
        'locale' => 'brevare_locale',
        'currency' => 'brevare_currency',
    ],
];
