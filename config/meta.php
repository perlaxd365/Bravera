<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta Pixel y Conversions API
    |--------------------------------------------------------------------------
    |
    | El Pixel se carga solo después del consentimiento de marketing. La CAPI
    | usa el mismo event_id para que Meta deduplique los eventos del navegador
    | y del servidor. Sin pixel_id/token configurados no se envía nada.
    |
    */

    'pixel_id' => env('META_PIXEL_ID'),

    'pixel_enabled' => (bool) env('META_PIXEL_ENABLED', false),

    'capi_enabled' => (bool) env('META_CAPI_ENABLED', false),

    'access_token' => env('META_CAPI_ACCESS_TOKEN'),

    'graph_version' => env('META_GRAPH_VERSION', 'v23.0'),

    'test_event_code' => env('META_TEST_EVENT_CODE'),

    'domain_verification' => env('META_DOMAIN_VERIFICATION'),

    'consent_cookie' => 'brevare_consent',

    /*
    |--------------------------------------------------------------------------
    | Meta Marketing API (crear anuncios desde la ficha de producto)
    |--------------------------------------------------------------------------
    |
    | El token debe ser de un usuario del sistema con acceso a la cuenta
    | publicitaria, la página y, si se usa, la cuenta de Instagram. Los
    | anuncios se crean siempre en pausa.
    |
    */

    'ads' => [
        'enabled' => (bool) env('META_ADS_ENABLED', false),
        'app_id' => env('META_APP_ID'),
        'business_id' => env('META_BUSINESS_ID'),
        'ad_account_id' => env('META_AD_ACCOUNT_ID'),
        'page_id' => env('META_PAGE_ID'),
        'instagram_actor_id' => env('META_INSTAGRAM_ACTOR_ID'),
        'access_token' => env('META_ADS_ACCESS_TOKEN'),
        'app_secret' => env('META_APP_SECRET'),
        'default_cta' => env('META_ADS_DEFAULT_CTA', 'SHOP_NOW'),
    ],
];
