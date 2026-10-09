<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = $title ?? (config('app.name').' | Tienda online');
        $pageDescription = $seoDescription ?? 'Descubre moda, tecnología, hogar y más en Brevare. Compra online con atención cercana y seguimiento de tus pedidos.';
        $canonical = $canonicalUrl ?? request()->url();
        $noIndexRoute = request()->routeIs('store.search', 'store.cart', 'checkout', 'account.*', 'dashboard', 'login', 'register', 'verification.*', 'password.*');
        $robots = app()->environment('production') && ! $noIndexRoute ? 'index,follow,max-image-preview:large' : 'noindex,nofollow';
        $company = config('brevare.company');
        $organizationStructuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $company['name'] ?? 'Brevare',
            'legalName' => $company['legal_name'] ?? null,
            'url' => rtrim(config('app.url'), '/'),
            'email' => $company['email'] ?? null,
            'telephone' => $company['phone_e164'] ?? null,
            'taxID' => $company['ruc'] ?? null,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $company['address'] ?? null,
                'addressLocality' => $company['city'] ?? null,
                'addressRegion' => $company['region'] ?? null,
                'addressCountry' => $company['country'] ?? 'PE',
            ],
        ];
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:site_name" content="Brevare">
    <meta property="og:type" content="{{ ! empty($productStructuredData) ? 'product' : 'website' }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if (! empty($seoImage))
        <meta property="og:image" content="{{ $seoImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $seoImage }}">
    @endif
    <script type="application/ld+json">@json($organizationStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    @if (! empty($productStructuredData))
        <script type="application/ld+json">@json($productStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">

    @include('store.layouts.partials.header')

    <main class="flex-1">
        {{ $slot }}
    </main>

    @include('store.layouts.partials.footer')

    <flux:toast position="bottom right" />

    @livewireScripts
    @fluxScripts

    @include('store.components.cart-toast')

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
