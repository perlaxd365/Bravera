<?php

return [
    'canonical_url' => 'https://brevare.com',

    /*
    |--------------------------------------------------------------------------
    | Entidad de marca
    |--------------------------------------------------------------------------
    |
    | Estos datos alimentan el JSON-LD de Organization/WebSite. Google puede
    | usar esta información para entender la entidad, pero el panel de
    | conocimiento y los sitelinks siguen siendo decisiones automáticas de
    | Google.
    |
    */

    'founders' => [
        [
            'name' => 'Raúl Baca',
            'job_title' => 'Fundador',
        ],
        [
            'name' => 'Perla García',
            'job_title' => 'Cofundadora',
        ],
    ],

    'founding_date' => '2026-10-17',

    'founding_location' => 'Lima, Perú',

    'slogan' => 'Todo lo que buscas, en un solo lugar.',

    'description' => 'Brevare es una tienda online peruana de moda, tecnología, hogar y regalos, con envíos a todo el Perú.',

    'organization_image' => env('SEO_ORGANIZATION_IMAGE'),

    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),

    'social_links' => [
        [
            'network' => 'instagram',
            'name' => 'Instagram',
            'url' => env('SEO_INSTAGRAM_URL'),
        ],
        [
            'network' => 'facebook',
            'name' => 'Facebook',
            'url' => env('SEO_FACEBOOK_URL'),
        ],
        [
            'network' => 'tiktok',
            'name' => 'TikTok',
            'url' => env('SEO_TIKTOK_URL'),
        ],
        [
            'network' => 'linkedin',
            'name' => 'LinkedIn',
            'url' => env('SEO_LINKEDIN_URL'),
        ],
        [
            'network' => 'youtube',
            'name' => 'YouTube',
            'url' => env('SEO_YOUTUBE_URL'),
        ],
    ],

    'same_as' => array_values(array_filter([
        env('SEO_INSTAGRAM_URL'),
        env('SEO_FACEBOOK_URL'),
        env('SEO_TIKTOK_URL'),
        env('SEO_LINKEDIN_URL'),
        env('SEO_YOUTUBE_URL'),
    ])),

    /*
    |--------------------------------------------------------------------------
    | Orden editorial de categorías
    |--------------------------------------------------------------------------
    |
    | Se usa en el home, la navegación, el footer, el sitemap y el ItemList.
    | Google decide qué sitelinks muestra y en qué orden, pero esta jerarquía
    | deja clara la prioridad editorial del sitio.
    |
    */

    'home_category_order' => [
        ['path' => 'navidad', 'label' => 'Navidad'],
        ['path' => 'tecnologia', 'label' => 'Tecnología'],
        ['path' => 'hogar', 'label' => 'Hogar'],
        ['path' => 'moda/mujer', 'label' => 'Ropa Mujer'],
        ['path' => 'moda/hombre', 'label' => 'Ropa Hombre'],
        ['path' => 'nino', 'label' => 'Ropa Niño'],
    ],

    'category_labels' => [
        'navidad' => 'Navidad',
        'tecnologia' => 'Tecnología',
        'hogar' => 'Hogar',
        'moda/mujer' => 'Ropa Mujer',
        'moda/hombre' => 'Ropa Hombre',
        'nino' => 'Ropa Niño',
    ],

    /*
    |--------------------------------------------------------------------------
    | Categorías de Google Merchant Center
    |--------------------------------------------------------------------------
    |
    | Facilitan la clasificación del feed de Shopping. No sustituyen la
    | revisión de cada producto en Merchant Center.
    |
    */

    'google_product_category' => [
        'navidad' => 'Holiday & Seasonal',
        'tecnologia' => 'Electronics',
        'hogar' => 'Home & Garden',
        'moda/mujer' => 'Apparel & Accessories > Clothing',
        'moda/hombre' => 'Apparel & Accessories > Clothing',
        'nino' => 'Apparel & Accessories > Clothing > Kids',
        'calzado' => 'Apparel & Accessories > Shoes',
        'accesorios' => 'Apparel & Accessories > Clothing Accessories',
        'juguetes' => 'Toys & Games',
    ],
];
