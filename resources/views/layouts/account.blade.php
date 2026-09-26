<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mi cuenta') · {{ config('app.name') }}</title>

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

    @php
        $user = Auth::user();
        $initials = collect(preg_split('/[\s,]+/', trim((string) $user->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('');
    @endphp

    <main class="flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Mi cuenta</h1>
                    <p class="mt-1 text-sm text-gray-500">Administra tu perfil, tus direcciones y tus pedidos.</p>
                </div>
                <span class="hidden items-center gap-2 text-sm text-gray-500 sm:flex">
                    <span class="flex size-8 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600 ring-1 ring-inset ring-gray-200">{{ $initials }}</span>
                    {{ $user->name }}
                </span>
            </div>

            <div class="mt-8 grid items-start gap-8 lg:grid-cols-[260px_1fr]">

                {{-- Sidebar escritorio --}}
                <aside class="hidden lg:block">
                    @include('livewire.account.partials.nav', ['orientation' => 'vertical'])
                </aside>

                <div class="min-w-0">
                    {{-- Navegación móvil --}}
                    @include('livewire.account.partials.nav', ['orientation' => 'horizontal'])

                    {{ $slot }}
                </div>
            </div>
        </div>
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
        });
    </script>

    @stack('scripts')
</body>
</html>