<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $googleTagManagerId = trim((string) config('services.google_tag_manager.id'));
    @endphp
    @if ($googleTagManagerId !== '')
        <script>
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{{ $googleTagManagerId }}');
        </script>
    @endif
    @php
        $googleAnalyticsId = trim((string) config('services.google_analytics.id'));
    @endphp
    @if ($googleAnalyticsId !== '')
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $googleAnalyticsId }}');
        </script>
    @endif
    @if (! empty(config('seo.google_site_verification')))
        <meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
    @endif
    @if (! empty(config('meta.domain_verification')))
        <meta name="facebook-domain-verification" content="{{ config('meta.domain_verification') }}">
    @endif
    @include('partials.favicon')
    @php
        $brandName = 'Brevare Perú';
        $pageTitle = $title ?? ($brandName.' | Tienda online');
        $pageDescription = $seoDescription ?? 'Descubre moda, tecnología, hogar y más en Brevare Perú. Compra online con envíos garantizados a todo el Perú.';
        $canonical = $canonicalUrl ?? request()->url();
        $noIndexRoute = request()->routeIs('store.search', 'store.cart', 'cart.recover', 'cart.recovery.*', 'checkout', 'account.*', 'dashboard', 'login', 'register', 'verification.*', 'password.*');
        $noIndex = $noIndex ?? $noIndexRoute;
        $robots = app()->environment('production') && ! $noIndex ? 'index,follow,max-image-preview:large' : 'noindex,nofollow';
        $organizationStructuredData = \App\Support\Seo\StructuredData::organization();
        $websiteStructuredData = \App\Support\Seo\StructuredData::website();
        $pageStructuredData = $pageStructuredData ?? [];
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="alternate" hreflang="es-pe" href="{{ $canonical }}">
    <link rel="alternate" hreflang="x-default" href="{{ $canonical }}">
    <meta property="og:site_name" content="Brevare Perú">
    <meta property="og:type" content="{{ $ogType ?? (! empty($productStructuredData) ? 'product' : 'website') }}">
    <meta property="og:locale" content="es_PE">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if (! empty($seoImage))
        <meta property="og:image" content="{{ $seoImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $seoImage }}">
    @endif
    <script type="application/ld+json">@json($organizationStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    <script type="application/ld+json">@json($websiteStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    @if (! empty($breadcrumbStructuredData))
        <script type="application/ld+json">@json($breadcrumbStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    @endif
    @foreach ($pageStructuredData as $structuredDataItem)
        <script type="application/ld+json">@json($structuredDataItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    @endforeach
    @if (! empty($productStructuredData))
        <script type="application/ld+json">@json($productStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    @endif
    @php
        $metaPixelConfig = [
            'enabled' => (bool) config('meta.pixel_enabled'),
            'pixelId' => (string) config('meta.pixel_id'),
            'consentCookie' => config('meta.consent_cookie', 'brevare_consent'),
        ];
    @endphp
    <script>
        window.BrevareMeta = @json($metaPixelConfig);
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @if (request()->routeIs('checkout'))
        {{-- Culqi se carga con la página para que su modal no dependa de
             inyectar el SDK justo al pulsar pagar. --}}
        <script src="https://js.culqi.com/checkout-js" async></script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
    @stack('styles')
    @stack('head')
</head>
<body class="min-h-screen bg-gray-50 pb-20 font-sans text-gray-900 antialiased lg:pb-0">
    @if ($googleTagManagerId !== '')
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{ $googleTagManagerId }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
    @endif

    @include('store.layouts.partials.top-banner')
    @include('store.layouts.partials.header')

    <main class="flex-1">
        {{ $slot }}
    </main>

    @include('store.layouts.partials.footer')

    @unless (request()->routeIs('checkout', 'store.order.placed', 'payments.*'))
        @include('store.layouts.partials.mobile-nav')
    @endunless

    <flux:toast position="bottom right" />

    <x-brevare.cookie-consent />

    @livewireScripts
    @fluxScripts

    @include('store.components.cart-toast')
    @include('partials.meta-pixel')

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const variant = {
                    error: 'danger',
                    danger: 'danger',
                    warning: 'warning',
                    info: 'info',
                    success: 'success',
                }[data.type] ?? 'neutral';

                window.Flux.toast(data.message, {
                    variant,
                    duration: 3000,
                });
            });

            Livewire.on('cart-updated', () => {
                Livewire.dispatch('refresh-cart');
            });

            Livewire.on('cart-added', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                window.dispatchEvent(new CustomEvent('brevare-cart-added', { detail: data }));
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
