<header x-data="{ navOpen: false }" class="sticky top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur-lg">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold tracking-tight text-gray-900">
                <span class="flex size-8 items-center justify-center rounded-lg bg-gray-900 text-white">
                    <flux:icon name="shopping-bag" class="size-4" />
                </span>
                Bravera
            </a>
        </div>

        <div class="hidden flex-1 justify-center px-4 lg:flex">
            <form class="w-full max-w-md" action="{{ route('store.search') }}" method="GET" role="search">
                <div class="relative">
                    <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar productos..."
                        class="w-full rounded-full border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10 focus:outline-none" aria-label="Buscar">
                </div>
            </form>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('store.search') }}" class="hidden rounded-full p-2.5 text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 sm:block lg:hidden" aria-label="Buscar">
                <flux:icon name="magnifying-glass" class="size-5" />
            </a>

            <livewire:store.components.cart-badge />

            @auth
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-full border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50" aria-expanded="false">
                        @if (Auth::user()->avatar)
                            <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}" class="size-5 rounded-full object-cover" loading="lazy">
                        @else
                            <flux:icon name="user-circle" class="size-5 text-gray-500" />
                        @endif
                        <span class="hidden max-w-32 truncate md:inline">{{ Auth::user()->name }}</span>
                    </button>

                    <div x-show="open" x-transition.opacity.scale.origin.top.right.duration.150ms x-cloak
                        class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-gray-200 bg-white py-2 shadow-xl shadow-gray-950/5">
                        <div class="border-b border-gray-100 px-4 py-2 text-sm text-gray-500">{{ Auth::user()->email }}</div>
                        <a href="{{ route('account.orders') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                            <flux:icon name="cube" class="size-4 text-gray-400" /> Mis pedidos
                        </a>
                        <a href="{{ route('account.addresses') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                            <flux:icon name="map-pin" class="size-4 text-gray-400" /> Direcciones
                        </a>
                        <a href="{{ route('account.profile') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                            <flux:icon name="user" class="size-4 text-gray-400" /> Mi perfil
                        </a>
                        <div class="mt-1 border-t border-gray-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-red-600 transition hover:bg-red-50">
                                    <flux:icon name="arrow-right-start-on-rectangle" class="size-4" /> Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="hidden rounded-full px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 sm:inline-flex">Entrar</a>
                <a href="{{ route('register') }}" class="rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">Crear cuenta</a>
            @endauth

            <button type="button" class="rounded-full p-2.5 text-gray-600 transition hover:bg-gray-100 lg:hidden" @click="navOpen = !navOpen" aria-label="Abrir menú">
                <flux:icon name="bars-3" class="size-5" />
            </button>
        </div>
    </div>

    <div x-cloak class="lg:hidden">
        <div x-show="navOpen" x-transition.opacity.duration.150ms class="border-t border-gray-200 bg-white px-4 py-4">
            <form class="mb-3" action="{{ route('store.search') }}" method="GET" role="search">
                <div class="relative">
                    <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar productos..."
                        class="w-full rounded-full border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-gray-900 focus:outline-none" aria-label="Buscar">
                </div>
            </form>
            <nav class="flex flex-col gap-1">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">Inicio</a>
                <a href="{{ route('store.search') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">Catálogo</a>
                @auth
                    <a href="{{ route('account.orders') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">Mis pedidos</a>
                    <a href="{{ route('account.addresses') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">Direcciones</a>
                @endauth
            </nav>
        </div>
    </div>

    @php
        $rootCategories = \App\Models\Category::query()
            ->where('is_visible', true)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->limit(8)
            ->get();
    @endphp
    <nav class="hidden border-t border-gray-100 bg-gray-50/60 lg:block" aria-label="Categorías">
        <div class="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}"
                class="whitespace-nowrap px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:text-gray-900 {{ request()->routeIs('home') ? 'text-gray-900' : '' }}">
                Inicio
            </a>
            @foreach ($rootCategories as $category)
                <a href="{{ route('store.category', ['path' => $category->path]) }}"
                    class="whitespace-nowrap px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:text-gray-900">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </nav>
</header>