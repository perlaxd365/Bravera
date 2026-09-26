<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name') . ' | Tienda online')</title>

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
                window.dispatchEvent(new CustomEvent('bravera-cart-added', { detail: data }));
            });
        });
    </script>

    @stack('scripts')
</body>
</html>