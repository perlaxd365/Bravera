<div class="bg-[#f6f5f2] pb-10">
    {{-- Credenciales de la cuenta de prueba para las validaciones de Culqi. --}}
    @if (app()->getLocale() === 'es')
        <div class="mx-auto max-w-[1440px] px-4 pt-3 sm:px-6 lg:px-10">
            <x-brevare.review-credentials />
        </div>
    @endif

    {{-- Portada: cada slide es un producto real, con imagen grande y CTA de compra --}}
    @if ($heroSlides)
        <section x-data="{
            active: 0,
            frame: 0,
            slides: @js($heroSlides),
            timer: null,
            step: 1500,
            tick() {
                const images = this.slides[this.active]?.images || [];
                if (this.frame + 1 < images.length) { this.frame++; return; }
                this.frame = 0;
                this.active = (this.active + 1) % this.slides.length;
            },
            next() { this.go((this.active + 1) % this.slides.length) },
            prev() { this.go((this.active - 1 + this.slides.length) % this.slides.length) },
            go(i) { this.frame = 0; this.active = i; this.start() },
            start() {
                this.stop();
                const total = this.slides.reduce((sum, slide) => sum + Math.max(1, (slide.images || []).length), 0);
                if (!document.hidden && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && total > 1) this.timer = setInterval(() => this.tick(), this.step);
            },
            stop() { clearInterval(this.timer); this.timer = null }
        }"
            x-init="start()" @mouseenter="stop()" @mouseleave="start()" @focusin="stop()" @focusout="start()"
            @visibilitychange.window="document.hidden ? stop() : start()"
            class="mx-auto mt-3 max-w-[1440px] px-4 sm:px-6 lg:px-10">
            <div class="relative overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-[0_30px_60px_-46px_rgba(28,25,23,.45)]">
                {{-- Todas las portadas comparten la misma celda: el alto lo marca la más
                     alta, así al cambiar de slide nada de la página se mueve. --}}
                <div class="grid">
                    <template x-for="(slide, i) in slides" :key="'hero-' + i">
                        <div class="col-start-1 row-start-1 opacity-0 transition-opacity duration-700 ease-out"
                            :class="i === active ? 'z-10 opacity-100' : 'pointer-events-none opacity-0'"
                            :aria-hidden="i === active ? 'false' : 'true'">
                            <div class="grid lg:grid-cols-[1.15fr_1fr]">
                                {{-- Imagen principal: ocupa más espacio y alterna izquierda / derecha --}}
                                <div class="relative h-[260px] overflow-hidden bg-gradient-to-br from-[#f4efe6] via-[#faf8f3] to-[#efe7da] sm:h-[350px] lg:h-[500px]"
                                    :class="slide.side === 'left' ? 'lg:order-1' : 'lg:order-2'">
                                    <template x-for="(image, f) in (slide.images && slide.images.length ? slide.images : [slide.image])" :key="'img-' + i + '-' + f">
                                        <img :src="image" :alt="slide.title" :fetchpriority="i === 0 && f === 0 ? 'high' : 'auto'" :loading="i === 0 && f === 0 ? 'eager' : 'lazy'"
                                            onerror="if (this.dataset.fallbackAttempted) { this.remove(); } else { this.dataset.fallbackAttempted = 'true'; this.src = '{{ $heroFallbackImage }}'; }"
                                            class="absolute inset-0 h-full w-full object-contain p-8 drop-shadow-[0_26px_45px_rgba(28,25,23,.16)] transition-opacity duration-700 ease-out sm:p-12"
                                            :class="(i === active && f === frame) ? 'opacity-100 animate-kenburns' : 'opacity-0'">
                                    </template>
                                    <span x-show="slide.discount > 0" x-text="'-' + slide.discount + '%'" class="absolute left-5 top-5 rounded-full bg-rose-600 px-3 py-1 text-[11px] font-extrabold uppercase tracking-wide text-white shadow-md"></span>
                                    <div x-show="slide.thumbs.length" class="absolute bottom-5 left-5 flex items-end gap-2" aria-hidden="true">
                                        <template x-for="(thumb, t) in slide.thumbs" :key="'thumb-' + i + '-' + t">
                                            <button type="button" @click="frame = t" class="block size-11 overflow-hidden rounded-lg border-2 border-white bg-white shadow-md transition sm:size-14"
                                                :class="(i === active && t === frame) ? 'ring-2 ring-amber-400' : 'opacity-70 hover:opacity-100'">
                                                <img :src="thumb" alt="" loading="lazy" decoding="async" class="size-full object-cover">
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                {{-- Texto: toda la portada gira alrededor de este producto --}}
                                <div class="flex flex-col justify-center px-6 py-8 sm:px-10 sm:py-10 lg:px-14 lg:py-12"
                                    :class="slide.side === 'left' ? 'lg:order-2' : 'lg:order-1'">
                                    <p class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[.14em] text-amber-900" x-text="slide.eyebrow"></p>
                                    <h1 class="mt-4 line-clamp-3 max-w-xl text-[1.9rem] font-extrabold leading-[1.04] tracking-[-.04em] text-stone-950 sm:text-[2.4rem] lg:text-[2.75rem]" x-text="slide.title"></h1>
                                    <div class="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1" x-show="slide.price">
                                        <span class="text-[1.75rem] font-extrabold tracking-tight text-amber-700 sm:text-[2rem]" x-text="slide.price"></span>
                                        <span x-show="slide.compare" class="text-sm font-medium text-stone-400 line-through" x-text="slide.compare"></span>
                                    </div>
                                    <p class="mt-3 line-clamp-3 max-w-md text-sm leading-6 text-stone-600 sm:text-[15px] sm:leading-7" x-text="slide.subtitle"></p>
                                    <a :href="slide.url" class="fv-cta mt-6 w-fit text-[15px]">
                                        <span x-text="slide.cta"></span><span aria-hidden="true">→</span>
                                    </a>
                                    <p class="mt-4 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[.12em] text-stone-400">
                                        <span class="inline-block size-1.5 rounded-full bg-emerald-500"></span>{{ app()->getLocale() === 'en' ? 'In stock · Ships nationwide' : 'Disponible · Envío a todo el Perú' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Controles compartidos: posición y tamaño fijos --}}
                <div class="relative z-20 flex items-center gap-2 border-t border-stone-100 bg-white/90 px-4 py-3 backdrop-blur sm:px-6 lg:px-10">
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
