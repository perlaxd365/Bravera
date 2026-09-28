<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Brevare') }} · Acceso</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        @livewireStyles
    </head>
    <body class="min-h-screen bg-[#f9fafb] font-sans text-gray-900 antialiased">
        <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">

            {{-- Panel de marca (estilo Ripley / Falabella) --}}
            <aside class="relative hidden overflow-hidden bg-gray-950 lg:block" aria-hidden="true">
                <img
                    src="https://images.pexels.com/photos/16443132/pexels-photo-16443132.jpeg?auto=compress&cs=tinysrgb&w=1600&h=2000&fit=crop"
                    alt=""
                    onerror="this.style.display='none'"
                    class="absolute inset-0 h-full w-full animate-kenburns object-cover opacity-60">

                <div class="absolute inset-0 bg-gradient-to-br from-gray-950/90 via-gray-950/40 to-gray-900/80"></div>

                {{-- Orbes de luz sutiles --}}
                <div class="pointer-events-none absolute -left-24 top-1/4 size-72 rounded-full bg-emerald-400/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -right-16 bottom-24 size-80 rounded-full bg-sky-400/10 blur-3xl"></div>

                <div class="relative flex h-full flex-col justify-between p-10 xl:p-14">
                    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-white text-gray-950 shadow-lg">
                            <flux:icon name="shopping-bag" class="size-4" variant="solid" />
                        </span>
                        <span class="text-xl font-extrabold tracking-tight text-white">Brevare</span>
                    </a>

                    <div>
                        <p class="auth-rise text-xs font-bold uppercase tracking-[0.25em] text-emerald-300" style="animation-delay: 60ms">
                            Bienvenido a tu tienda online
                        </p>
                        <h1 class="auth-rise mt-3 max-w-md text-4xl font-extrabold leading-tight tracking-tight text-white xl:text-5xl" style="animation-delay: 140ms">
                            Compra fácil, <span class="text-emerald-300">rápido</span> y seguro.
                        </h1>
                        <p class="auth-rise mt-4 max-w-sm text-sm leading-relaxed text-gray-200" style="animation-delay: 220ms">
                            Tecnología, moda, hogar y mucho más con envíos a todo el Perú. Crea tu cuenta
                            o inicia sesión para seguir comprando desde donde lo dejaste.
                        </p>

                        <ul class="auth-rise mt-8 space-y-4" style="animation-delay: 300ms">
                            <li class="flex items-center gap-3 text-sm font-medium text-white">
                                <span class="flex size-8 items-center justify-center rounded-full bg-white/10 text-emerald-300 backdrop-blur">
                                    <flux:icon name="truck" class="size-4" />
                                </span>
                                Envío rápido a todo el Perú
                            </li>
                            <li class="flex items-center gap-3 text-sm font-medium text-white">
                                <span class="flex size-8 items-center justify-center rounded-full bg-white/10 text-emerald-300 backdrop-blur">
                                    <flux:icon name="shield-check" class="size-4" />
                                </span>
                                Compra protegida y pagos seguros
                            </li>
                            <li class="flex items-center gap-3 text-sm font-medium text-white">
                                <span class="flex size-8 items-center justify-center rounded-full bg-white/10 text-emerald-300 backdrop-blur">
                                    <flux:icon name="star" class="size-4" />
                                </span>
                                Los mejores precios del mercado
                            </li>
                        </ul>
                    </div>

                    <p class="text-xs text-gray-400">
                        &copy; {{ date('Y') }} Brevare &middot; Tu tienda online
                    </p>
                </div>
            </aside>

            {{-- Columna del formulario --}}
            <main class="relative flex flex-col">
                <div class="flex items-center justify-between px-6 pt-6 sm:px-10 lg:hidden">
                    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-gray-900 text-white">
                            <flux:icon name="shopping-bag" class="size-4" variant="solid" />
                        </span>
                        <span class="text-lg font-extrabold tracking-tight text-gray-900">Brevare</span>
                    </a>
                </div>

                <div class="flex flex-1 items-center justify-center px-4 py-10 sm:px-10">
                    {{ $slot }}
                </div>

                <p class="pb-6 text-center text-xs text-gray-400 lg:hidden">
                    &copy; {{ date('Y') }} Brevare &middot; Tu tienda online
                </p>
            </main>
        </div>

        @livewireScripts
        @fluxScripts
    </body>
</html>