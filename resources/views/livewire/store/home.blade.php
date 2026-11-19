<div class="bg-[#f6f5f2] pb-10">
    {{-- Credenciales de la cuenta de prueba para las validaciones de Culqi. --}}
    @if (app()->getLocale() === 'es')
        <div class="mx-auto max-w-[1440px] px-4 pt-3 sm:px-6 lg:px-10">
            <x-brevare.review-credentials />
        </div>
    @endif

    {{-- Banner principal --}}
    @if ($heroSlides)
        <section x-data="{
            active: 0,
            slides: @js($heroSlides),
            timer: null,
            next() { this.active = (this.active + 1) % this.slides.length },
            prev() { this.active = (this.active - 1 + this.slides.length) % this.slides.length },
            go(i) { this.active = i },
            start() { this.stop(); if (!document.hidden && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && this.slides.length > 1) this.timer = setInterval(() => this.next(), 5000) },
            stop() { clearInterval(this.timer); this.timer = null }
        }"
            x-init="start()" @mouseenter="stop()" @mouseleave="start()" @focusin="stop()" @focusout="start()"
            @visibilitychange.window="document.hidden ? stop() : start()"
            class="mx-auto mt-3 max-w-[1440px] px-4 sm:px-6 lg:px-10">
            <div class="relative overflow-hidden rounded-2xl border border-stone-200 bg-white">
                <div class="grid lg:grid-cols-[1.05fr_1fr]">
                    <div class="relative order-2 flex flex-col justify-center px-6 pb-8 pt-6 sm:px-10 sm:py-10 lg:order-1 lg:px-14">
                        <template x-for="(slide, i) in slides" :key="'copy-' + i">
                            <div x-show="i === active" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
                                <p class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[.14em] text-amber-900" x-text="slide.eyebrow"></p>
                                <h1 class="mt-4 max-w-xl text-[2rem] font-extrabold leading-[1.05] tracking-[-.04em] text-stone-950 sm:text-[2.6rem] lg:text-[3rem]">
                                    <span class="sr-only">Brevare: </span>
                                    <span x-text="slide.title">Regalos que llegan</span>
                                    <span class="text-amber-700" x-text="slide.highlight">con ilusión</span>
                                </h1>
                                <p class="mt-3 max-w-md text-sm leading-6 text-stone-600 sm:text-[15px] sm:leading-7" x-text="slide.subtitle"></p>
                                <a :href="slide.url" class="fv-cta mt-6">
                                    <span x-text="slide.cta"></span><span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </template>

                        <div class="mt-7 flex items-center gap-2">
                            <template x-for="(slide, i) in slides" :key="'dot-' + i">
                                <button type="button" @click="go(i)" :aria-label="'{{ __('storefront.home.go_slide') }}'.replace(':number', i + 1)" :aria-current="i === active ? 'true' : 'false'" class="flex min-h-9 min-w-4 items-center justify-center">
                                    <span class="h-1.5 rounded-full transition-all duration-400" :class="i === active ? 'w-7 bg-amber-600' : 'w-4 bg-stone-300'"></span>
                                </button>
                            </template>
                            <div class="ml-auto flex gap-2">
                                <button type="button" @click="prev()" aria-label="{{ __('storefront.home.previous') }}" class="flex size-9 items-center justify-center rounded-full border border-stone-300 text-stone-700 transition hover:border-amber-400 hover:bg-amber-50">
                                    <flux:icon name="chevron-left" class="size-4" />
                                </button>
                                <button type="button" @click="next()" aria-label="{{ __('storefront.home.next') }}" class="flex size-9 items-center justify-center rounded-full border border-stone-300 text-stone-700 transition hover:border-amber-400 hover:bg-amber-50">
                                    <flux:icon name="chevron-right" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="relative order-1 min-h-[220px] overflow-hidden bg-[#e8e3da] sm:min-h-[300px] lg:order-2 lg:min-h-[400px]">
                        <template x-for="(slide, i) in slides" :key="'image-' + i">
                            <div x-show="i === active" x-transition.opacity.duration.500ms x-cloak class="absolute inset-0">
                                <img :src="slide.image" :alt="slide.title + ' ' + slide.highlight" :fetchpriority="i === 0 ? 'high' : 'auto'" :loading="i === 0 ? 'eager' : 'lazy'" onerror="if (this.dataset.fallbackAttempted) { this.remove(); } else { this.dataset.fallbackAttempted = 'true'; this.src = '{{ $heroFallbackImage }}'; }" class="h-full w-full object-cover">
                            </div>
                        </template>
                        <span class="absolute bottom-4 right-4 rounded-full bg-white/85 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[.16em] text-stone-800 backdrop-blur">Brevare · Perú</span>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Llamativos y económicos --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto mt-6 max-w-[1440px] px-4 sm:px-6 lg:px-10">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.16em] text-amber-800">{{ app()->getLocale() === 'en' ? 'Eye-catching & affordable' : 'Llamativos y económicos' }}</p>
                    <h2 class="mt-1 text-xl font-extrabold tracking-tight text-stone-950 sm:text-2xl">{{ app()->getLocale() === 'en' ? __('storefront.home.featured_title') : ($homeTexts['featured'] ?? 'Destacados') }}</h2>
                </div>
                <a href="{{ route('store.search') }}" class="shrink-0 text-[13px] font-semibold text-stone-700 transition hover:text-amber-800">{{ __('storefront.common.view_store') }} <span class="ml-1" aria-hidden="true">→</span></a>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($featured as $product)
                    @include('store.components.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Categorías --}}
    @if ($allCategories->isNotEmpty())
        <section class="mx-auto mt-6 max-w-[1440px] px-4 sm:px-6 lg:px-10" aria-labelledby="home-categories-title">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 id="home-categories-title" class="text-xl font-extrabold tracking-tight text-stone-950 sm:text-2xl">{{ __('storefront.home.categories_title') }}</h2>
                    <p class="mt-1 text-[13px] text-stone-500">{{ __('storefront.home.categories_copy') }}</p>
                </div>
                <a href="{{ route('store.search') }}" class="hidden shrink-0 text-[13px] font-semibold text-stone-700 transition hover:text-amber-800 sm:inline-flex">{{ __('storefront.common.view_all') }} <span class="ml-1" aria-hidden="true">→</span></a>
            </div>

            <div class="fv-noscroll flex gap-3 overflow-x-auto pb-1">
                @foreach ($allCategories as $category)
                    <a href="{{ route('store.category', ['path' => $category->path]) }}"
                        class="group flex w-[92px] shrink-0 flex-col items-center gap-2 text-center">
                        <span class="flex size-[76px] items-center justify-center overflow-hidden rounded-full border border-stone-200 bg-white p-1 shadow-sm transition group-hover:border-amber-300 group-hover:shadow-md sm:size-[88px]">
                            <img src="{{ $category->store_image }}" alt="" loading="lazy" decoding="async" onerror="if (this.dataset.fallbackAttempted) { this.remove(); } else { this.dataset.fallbackAttempted = 'true'; this.src = '{{ $category->store_image_fallback }}'; }" class="size-full rounded-full object-cover transition duration-500 group-hover:scale-105">
                        </span>
                        <span class="line-clamp-2 text-[12px] font-semibold leading-tight text-stone-700 group-hover:text-amber-800">{{ $category->seo_label ?? $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Novedades / agregados recientemente (deslizable + avance automático) --}}
    @if ($promoTickerItems !== [])
        <section class="mx-auto mt-6 max-w-[1440px] px-4 sm:px-6 lg:px-10" aria-label="{{ __('storefront.home.latest_added') }}">
            <div class="mb-2 flex items-center justify-between gap-3">
                <h2 class="text-sm font-extrabold uppercase tracking-[.12em] text-stone-900">{{ __('storefront.home.latest_added') }}</h2>
                <span class="text-[11px] font-medium text-stone-500">{{ app()->getLocale() === 'en' ? 'Swipe →' : 'Desliza →' }}</span>
            </div>

            <div
                x-data="{
                    timer: null,
                    paused: false,
                    down: false,
                    moved: false,
                    startX: 0,
                    startLeft: 0,
                    init() {
                        this.timer = setInterval(() => { if (!this.paused) this.advance(); }, 2800);
                    },
                    destroy() { clearInterval(this.timer); },
                    advance() {
                        const v = this.$refs.viewport;
                        if (!v) return;
                        const max = v.scrollWidth - v.clientWidth;
                        if (max <= 4) return;
                        const card = v.querySelector('[data-promo-card]');
                        const step = card ? Math.round(card.getBoundingClientRect().width) + 12 : 300;
                        if (v.scrollLeft >= max - 8) {
                            v.scrollTo({ left: 0, behavior: 'smooth' });
                        } else {
                            v.scrollTo({ left: Math.min(max, v.scrollLeft + step), behavior: 'smooth' });
                        }
                    },
                    start(e) {
                        if (e.pointerType && e.pointerType !== 'mouse') return;
                        this.down = true;
                        this.moved = false;
                        this.startX = e.clientX;
                        this.startLeft = this.$refs.viewport.scrollLeft;
                        this.paused = true;
                    },
                    move(e) {
                        if (!this.down) return;
                        const dx = e.clientX - this.startX;
                        if (Math.abs(dx) > 4) this.moved = true;
                        this.$refs.viewport.scrollLeft = this.startLeft - dx;
                    },
                    end() {
                        this.down = false;
                        this.paused = false;
                    },
                    guardClick(e) {
                        if (this.moved) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.moved = false;
                        }
                    }
                }"
                @mouseenter="paused = true" @mouseleave="paused = false"
                @touchstart.passive="paused = true" @touchend="paused = false"
                class="relative"
            >
                <div x-ref="viewport"
                    @pointerdown="start($event)" @pointermove="move($event)" @pointerup="end()" @pointerleave="end()"
                    @click.capture="guardClick($event)"
                    class="fv-noscroll flex cursor-grab snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth pb-1 select-none active:cursor-grabbing">
                    @foreach ($promoTickerItems as $item)
                        <a href="{{ $item['url'] }}" data-promo-card
                            class="group flex w-[272px] shrink-0 snap-start items-center gap-3 rounded-xl border border-stone-200 bg-white p-2.5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md sm:w-[320px]">
                            <span class="size-16 shrink-0 overflow-hidden rounded-lg border border-stone-100 bg-stone-50">
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" loading="lazy" decoding="async" width="72" height="72" class="size-full object-cover">
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[9px] font-bold uppercase tracking-[.16em] text-amber-800">{{ $item['eyebrow'] }}</span>
                                <span class="mt-0.5 line-clamp-2 text-[13px] font-semibold leading-tight text-stone-900 group-hover:text-amber-900">{{ $item['name'] }}</span>
                            </span>
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-stone-100 text-stone-500 transition group-hover:bg-amber-400 group-hover:text-amber-950" aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Lo más nuevo --}}
    @if ($latest->isNotEmpty())
        <section class="mx-auto mt-8 max-w-[1440px] px-4 sm:px-6 lg:px-10">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.16em] text-amber-800">{{ __('storefront.home.new_arrivals') }}</p>
                    <h2 class="mt-1 text-xl font-extrabold tracking-tight text-stone-950 sm:text-2xl">{{ app()->getLocale() === 'en' ? __('storefront.home.new_title') : ($homeTexts['latest'] ?? __('storefront.home.new_title')) }}</h2>
                </div>
                <a href="{{ route('store.search') }}" class="shrink-0 text-[13px] font-semibold text-stone-700 transition hover:text-amber-800">{{ __('storefront.home.discover_new') }} <span class="ml-1" aria-hidden="true">→</span></a>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($latest as $product)
                    @include('store.components.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif
</div>
