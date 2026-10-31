<header class="relative z-40 border-b border-gray-200 bg-white/90 backdrop-blur-lg lg:sticky lg:top-0">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:gap-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-extrabold tracking-tight text-gray-950">
                <span class="flex size-9 items-center justify-center rounded-xl bg-amber-300 text-amber-950 shadow-sm">
                    <flux:icon name="shopping-bag" class="size-4" />
                </span>
                {{ app()->getLocale() === 'en' ? 'Brevare' : 'Brevare Perú' }}
            </a>
        </div>

        <div class="hidden flex-1 justify-center px-4 sm:flex lg:flex">
            <x-brevare.search-autocomplete />
        </div>

        <div class="flex items-center gap-2">
            <a href="https://wa.me/{{ config('brevare.company.whatsapp') }}?text={{ rawurlencode(app()->getLocale() === 'en' ? 'Hello Brevare, I would like information about a product.' : 'Hola Brevare Perú, quiero información sobre un producto.') }}"
                target="_blank" rel="noopener noreferrer" aria-label="{{ app()->getLocale() === 'en' ? 'Contact us on WhatsApp' : 'Escríbenos por WhatsApp' }}"
                data-desktop-whatsapp
                class="hidden shrink-0 items-center gap-2 rounded-full bg-[#25D366] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#1ebe5d] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 md:inline-flex">
                <flux:icon name="chat-bubble-left-right" class="size-4" />
                <span class="hidden lg:inline">WhatsApp</span>
            </a>

            <livewire:store.components.cart-badge />

            <a href="{{ auth()->check() ? route('account.orders') : route('login', ['redirect' => route('account.orders')]) }}"
                class="hidden items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-100 md:inline-flex">
                <flux:icon name="cube" class="size-4" /> {{ __('storefront.common.my_orders') }}
            </a>

            @auth
                @if (Auth::user()->hasVerifiedEmail())
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
                            class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-gray-200 bg-white py-2 shadow-xl shadow-gray-950/5">
                            <div class="border-b border-gray-100 px-4 py-2 text-sm text-gray-500">{{ Auth::user()->email }}</div>
                            <a href="{{ route('account.orders') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                                <flux:icon name="cube" class="size-4 text-gray-400" /> {{ __('storefront.common.my_orders') }}
                            </a>
                            <a href="{{ route('account.addresses') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                                <flux:icon name="map-pin" class="size-4 text-gray-400" /> {{ __('storefront.common.addresses') }}
                            </a>
                            <a href="{{ route('account.points') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                                <flux:icon name="gift" class="size-4 text-gray-400" /> {{ __('storefront.common.points') }}
                            </a>
                            <a href="{{ route('account.favorites') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                                <flux:icon name="heart" class="size-4 text-gray-400" /> {{ __('storefront.common.favorites') }}
                            </a>
                            <a href="{{ route('account.coupons') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                                <flux:icon name="ticket" class="size-4 text-gray-400" /> {{ __('storefront.common.coupons') }}
                            </a>
                            <a href="{{ route('account.profile') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                                <flux:icon name="user" class="size-4 text-gray-400" /> {{ __('storefront.common.profile') }}
                            </a>
                            <div class="mt-1 border-t border-gray-100">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-red-600 transition hover:bg-red-50">
                                        <flux:icon name="arrow-right-start-on-rectangle" class="size-4" /> {{ __('storefront.common.logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('verification.notice') }}" class="rounded-full bg-amber-300 px-3 py-2 text-xs font-semibold text-amber-950 sm:px-4 sm:text-sm">{{ app()->getLocale() === 'en' ? 'Verify email' : 'Verificar correo' }}</a>
                @endif
            @else
                <a href="{{ route('login') }}" class="hidden rounded-full px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 sm:inline-flex">{{ __('storefront.common.sign_in') }}</a>
                <a href="{{ route('register') }}" class="rounded-full bg-gray-950 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-800 sm:px-4 sm:text-sm">{{ __('storefront.common.create_account') }}</a>
            @endauth

        </div>
    </div>

    <div class="border-t border-gray-100 bg-white px-4 py-2 sm:hidden">
        <x-brevare.search-autocomplete mobile />
    </div>

    @php($rootCategories = \App\Support\Seo\CatalogLinks::home(10))
    <nav class="hidden border-t border-gray-100 bg-gray-50/60 lg:block" aria-label="{{ app()->getLocale() === 'en' ? 'Categories' : 'Categorías' }}">
        <div class="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}"
                class="whitespace-nowrap px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:text-gray-900 {{ request()->routeIs('home') ? 'text-gray-900' : '' }}">
                {{ __('storefront.common.home') }}
            </a>
            @foreach ($rootCategories as $category)
                <a href="{{ route('store.category', ['path' => $category->path]) }}"
                    class="whitespace-nowrap px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:text-gray-900">
                    {{ $category->seo_label ?? $category->name }}
                </a>
            @endforeach
            <a href="{{ route('about') }}"
                class="whitespace-nowrap px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:text-gray-900">
                {{ __('storefront.common.about') }}
            </a>
            <a href="{{ route('store.offers') }}"
                class="whitespace-nowrap rounded-full bg-amber-100 px-3 py-1.5 text-sm font-semibold text-amber-900 transition hover:bg-amber-200">
                {{ __('storefront.common.offers') }}
            </a>
        </div>
    </nav>
</header>
