@php
    $company = config('brevare.company');
    $rootCategories = \App\Support\Seo\CatalogLinks::home(10);
    $items = [
        [
            'label' => __('storefront.common.account'),
            'url' => auth()->check()
                ? route('account.profile')
                : route('login', ['redirect' => route('account.profile')]),
            'icon' => 'user',
            'active' => request()->routeIs('account.profile', 'account.password', 'login', 'register'),
        ],
        [
            'label' => __('storefront.common.points'),
            'url' => auth()->check()
                ? route('account.points')
                : route('login', ['redirect' => route('account.points')]),
            'icon' => 'gift',
            'active' => request()->routeIs('account.points'),
        ],
        [
            'label' => __('storefront.common.offers'),
            'url' => route('store.offers'),
            'icon' => 'tag',
            'active' => request()->routeIs('store.offers'),
        ],
        [
            'label' => __('storefront.common.favorites'),
            'url' => auth()->check()
                ? route('account.favorites')
                : route('login', ['redirect' => route('account.favorites')]),
            'icon' => 'heart',
            'active' => request()->routeIs('account.favorites'),
        ],
    ];
@endphp

<div x-data="{ menuOpen: false }"
    @keydown.escape.window="menuOpen = false"
    @brevare-open-menu.window="menuOpen = true"
    class="lg:hidden">

    <div x-show="menuOpen" x-cloak x-transition.opacity.duration.200ms @click="menuOpen = false"
        class="fixed inset-0 z-[65] bg-gray-950/35 backdrop-blur-[2px]" aria-hidden="true"></div>

    <section x-show="menuOpen" x-cloak id="brevare-mobile-menu"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-6 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-4 opacity-0"
        class="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-[72] px-3"
        role="dialog" aria-modal="true" aria-label="{{ __('storefront.common.menu') }}">

        <div class="mx-auto max-h-[70vh] max-w-lg overflow-y-auto overscroll-contain rounded-3xl border border-amber-200 bg-white p-4 shadow-2xl shadow-gray-950/20">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-bold uppercase tracking-[.18em] text-amber-800">{{ app()->getLocale() === 'en' ? 'Brevare menu' : 'Menú Brevare Perú' }}</p>
                <button type="button" @click="menuOpen = false" aria-label="{{ __('storefront.common.close') }}"
                    class="flex size-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition hover:bg-gray-200 hover:text-gray-900">
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            </div>

            <nav class="mt-3 grid grid-cols-2 gap-2" aria-label="{{ app()->getLocale() === 'en' ? 'Main navigation' : 'Navegación principal móvil' }}">
                <a href="{{ route('home') }}" class="flex min-h-11 items-center rounded-xl bg-gray-50 px-3 text-sm font-semibold text-gray-900 transition hover:bg-amber-50">
                    {{ __('storefront.common.home') }}
                </a>
                <a href="{{ route('store.search') }}" class="flex min-h-11 items-center rounded-xl bg-gray-50 px-3 text-sm font-semibold text-gray-900 transition hover:bg-amber-50">
                    {{ __('storefront.common.catalog') }}
                </a>
                <a href="{{ route('about') }}" class="flex min-h-11 items-center rounded-xl bg-gray-50 px-3 text-sm font-semibold text-gray-900 transition hover:bg-amber-50">
                    {{ __('storefront.common.about') }}
                </a>
                <a href="{{ route('store.offers') }}" class="flex min-h-11 items-center rounded-xl bg-amber-50 px-3 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">
                    {{ __('storefront.common.offers') }}
                </a>
            </nav>

            @if ($rootCategories->isNotEmpty())
                <p class="mt-5 text-[11px] font-bold uppercase tracking-[.16em] text-gray-500">{{ __('storefront.catalog.categories') }}</p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach ($rootCategories as $category)
                        <a href="{{ route('store.category', ['path' => $category->path]) }}"
                            class="flex min-h-11 items-center rounded-xl border border-gray-200 px-3 text-sm font-medium text-gray-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-gray-950">
                            {{ $category->seo_label ?? $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            @auth
                <p class="mt-5 text-[11px] font-bold uppercase tracking-[.16em] text-gray-500">{{ __('storefront.common.account') }}</p>
                <div class="mt-2 grid gap-1">
                    <a href="{{ route('account.orders') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-950">
                        {{ __('storefront.common.my_orders') }}
                    </a>
                    <a href="{{ route('account.addresses') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-950">
                        {{ __('storefront.common.addresses') }}
                    </a>
                    <a href="{{ route('account.profile') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-950">
                        {{ __('storefront.common.profile') }}
                    </a>
                    <a href="{{ route('account.points') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-950">
                        {{ __('storefront.common.points') }}
                    </a>
                    <a href="{{ route('account.coupons') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-950">
                        {{ __('storefront.common.coupons') }}
                    </a>
                    <a href="{{ route('account.favorites') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-950">
                        {{ __('storefront.common.favorites') }}
                    </a>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="flex min-h-11 w-full items-center rounded-xl bg-red-50 px-3 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                        {{ __('storefront.common.logout') }}
                    </button>
                </form>
            @else
                <p class="mt-5 text-[11px] font-bold uppercase tracking-[.16em] text-gray-500">{{ __('storefront.common.account') }}</p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="flex min-h-11 items-center justify-center rounded-xl border border-gray-200 px-3 text-sm font-semibold text-gray-800 transition hover:bg-gray-50">
                        {{ __('storefront.common.sign_in') }}
                    </a>
                    <a href="{{ route('register', ['redirect' => request()->fullUrl()]) }}" class="flex min-h-11 items-center justify-center rounded-xl bg-gray-950 px-3 text-sm font-semibold text-white transition hover:bg-amber-800">
                        {{ __('storefront.common.create_account') }}
                    </a>
                </div>
            @endauth

            <a href="https://wa.me/{{ $company['whatsapp'] }}?text={{ rawurlencode(app()->getLocale() === 'en' ? 'Hello Brevare, I would like information about a product.' : 'Hola Brevare Perú, quiero información sobre un producto.') }}"
                target="_blank" rel="noopener noreferrer"
                class="mt-4 flex min-h-12 items-center justify-between gap-3 rounded-2xl bg-[#25D366] px-4 text-sm font-bold text-white shadow-sm transition hover:bg-[#1ebe5d]">
                <span>{{ app()->getLocale() === 'en' ? 'Message us on WhatsApp' : 'Escríbenos por WhatsApp' }}</span>
                <flux:icon name="arrow-up-right" class="size-4" />
            </a>
        </div>
    </section>

    <div class="fixed inset-x-0 bottom-0 z-[70] border-t border-amber-400/70 bg-[#FCD34D] pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_30px_rgba(69,26,3,.12)]">
        <nav class="mx-auto grid max-w-lg grid-cols-5" aria-label="{{ app()->getLocale() === 'en' ? 'Mobile navigation' : 'Navegación móvil' }}">
            @foreach ($items as $item)
                <a href="{{ $item['url'] }}"
                    class="flex min-h-16 flex-col items-center justify-center gap-1 px-1 text-[10px] font-semibold transition {{ $item['active'] ? 'text-gray-950' : 'text-amber-950/70 hover:text-gray-950' }}">
                    <span class="flex size-8 items-center justify-center rounded-full {{ $item['active'] ? 'bg-gray-950 text-white shadow-sm' : '' }}">
                        <flux:icon :name="$item['icon']" class="size-5" />
                    </span>
                    {{ $item['label'] }}
                </a>
            @endforeach

            <button type="button" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="brevare-mobile-menu"
                :class="menuOpen ? 'text-gray-950' : 'text-amber-950/70 hover:text-gray-950'"
                class="flex min-h-16 flex-col items-center justify-center gap-1 px-1 text-[10px] font-semibold transition">
                <span :class="menuOpen ? 'bg-gray-950 text-white shadow-sm' : ''" class="flex size-8 items-center justify-center rounded-full">
                    <flux:icon name="bars-3" class="size-5" />
                </span>
                {{ __('storefront.common.menu') }}
            </button>
        </nav>
    </div>

    <a x-show="!menuOpen" href="https://wa.me/{{ $company['whatsapp'] }}?text={{ rawurlencode('Hola Brevare Perú, quiero información sobre un producto.') }}"
        target="_blank" rel="noopener noreferrer" aria-label="Escríbenos por WhatsApp"
        class="fixed bottom-24 right-4 z-[75] flex size-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-2xl shadow-emerald-950/25 transition hover:scale-105 hover:bg-[#1ebe5d]">
        <svg viewBox="0 0 32 32" class="size-7" fill="currentColor" aria-hidden="true">
            <path d="M19.11 17.4c-.27-.14-1.6-.79-1.85-.88-.25-.09-.43-.14-.61.14-.18.27-.7.88-.86 1.06-.16.18-.32.2-.59.07-.27-.14-1.14-.42-2.17-1.34-.8-.71-1.34-1.59-1.5-1.86-.16-.27-.02-.42.12-.56.12-.12.27-.32.41-.48.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.48-.07-.14-.61-1.47-.84-2.01-.22-.53-.45-.46-.61-.47h-.52c-.18 0-.48.07-.73.34-.25.27-.96.94-.96 2.29 0 1.35.98 2.65 1.12 2.83.14.18 1.93 2.95 4.68 4.14.65.28 1.16.45 1.56.58.66.21 1.26.18 1.73.11.53-.08 1.6-.65 1.83-1.28.23-.63.23-1.17.16-1.28-.07-.11-.25-.18-.52-.32Z"/>
            <path d="M16.04 3.2c-7.08 0-12.84 5.75-12.84 12.83 0 2.26.59 4.47 1.71 6.41L3.2 28.8l6.52-1.7a12.8 12.8 0 0 0 6.32 1.66h.01c7.07 0 12.83-5.76 12.83-12.84 0-3.43-1.33-6.65-3.75-9.07a12.75 12.75 0 0 0-9.09-3.65Zm0 23.5h-.01a10.6 10.6 0 0 1-5.4-1.48l-.39-.23-4.01 1.05 1.07-3.91-.25-.4a10.6 10.6 0 0 1-1.63-5.7c0-5.88 4.78-10.66 10.67-10.66 2.85 0 5.52 1.11 7.53 3.12a10.6 10.6 0 0 1 3.12 7.54c0 5.88-4.79 10.67-10.7 10.67Z"/>
        </svg>
    </a>
</div>
