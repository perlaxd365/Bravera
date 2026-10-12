<div class="home-editorial">
    {{-- Descubrimiento destacado: una pausa editorial entre navegación y campaña. --}}
    @if ($rotatingProducts->isNotEmpty())
        <section x-data="{ items: @js($rotatingProducts), active: 0, timer: null, paused: false, next() { this.active = (this.active + 1) % this.items.length }, start() { this.stop(); if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && this.items.length > 1) this.timer = setInterval(() => { if (!this.paused) this.next() }, 4800) }, stop() { clearInterval(this.timer); this.timer = null } }"
            x-init="start()" @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false"
            class="mx-auto mt-4 max-w-screen-2xl px-4 sm:px-6 lg:px-10">
            <a :href="items[active].url" class="group flex min-h-[58px] items-center justify-between gap-4 rounded-2xl bg-[#f8f5ed] px-4 py-2.5 transition-colors hover:bg-amber-50 sm:px-5">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white text-amber-800"><flux:icon name="sparkles" class="size-4" /></span>
                    <span class="min-w-0"><span class="block text-[10px] font-bold uppercase tracking-[.16em] text-amber-900/65" x-text="items[active].phrase"></span><span class="block truncate text-sm font-semibold text-gray-900" x-text="items[active].name"></span></span>
                </span>
                <span class="flex shrink-0 items-center gap-3"><img :src="items[active].image" alt="" class="size-10 rounded-xl object-cover" x-show="items[active].image" onerror="this.remove()"><span class="hidden text-sm font-semibold text-gray-800 sm:inline">Descubrir <span aria-hidden="true">↗</span></span></span>
            </a>
        </section>
    @endif

    {{-- Portada de campaña: fotografía protagonista y texto con espacio propio. --}}
    @if ($heroSlides)
        <section x-data="{
            active: 0,
            slides: @js($heroSlides),
            timer: null,
            next() { this.active = (this.active + 1) % this.slides.length },
            prev() { this.active = (this.active - 1 + this.slides.length) % this.slides.length },
            go(i) { this.active = i },
            start() { this.stop(); if (!document.hidden && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && this.slides.length > 1) this.timer = setInterval(() => this.next(), 4000) },
            stop() { clearInterval(this.timer); this.timer = null }
        }"
            x-init="start()" @mouseenter="stop()" @mouseleave="start()" @focusin="stop()" @focusout="start()"
            @visibilitychange.window="document.hidden ? stop() : start()"
            class="mx-auto mt-5 max-w-screen-2xl px-4 sm:mt-7 sm:px-6 lg:px-10">
            <div class="home-hero relative overflow-hidden rounded-[1.75rem] bg-[#f5f2eb] sm:rounded-[2.25rem]">
                <div class="home-hero-grid grid min-h-[540px] lg:grid-cols-[.9fr_1.1fr]">
                    <div class="home-hero-copy order-2 flex flex-col justify-center px-6 pb-8 pt-5 sm:px-10 sm:pb-10 lg:order-1 lg:px-14 lg:py-14 xl:px-[4.5rem]">
                        <template x-for="(slide, i) in slides" :key="'copy-' + i">
                            <div x-show="i === active" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-350" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" x-cloak class="home-hero-slide">
                                <p class="text-[11px] font-bold uppercase tracking-[.2em] text-amber-900/70 sm:text-xs" x-text="slide.eyebrow"></p>
                                <h1 class="mt-4 max-w-xl text-[2.35rem] font-semibold leading-[1.04] tracking-[-.045em] text-[#171714] sm:text-5xl lg:text-[3.8rem] xl:text-[4.2rem]">
                                    <span x-text="slide.title"></span>
                                    <span class="text-amber-700" x-text="slide.highlight"></span>
                                </h1>
                                <p class="mt-5 max-w-md text-base leading-7 text-gray-600 sm:text-lg sm:leading-8" x-text="slide.subtitle"></p>
                                <a :href="slide.url" class="mt-7 inline-flex min-h-12 items-center gap-3 rounded-full bg-[#171714] px-6 text-sm font-semibold text-white transition hover:bg-amber-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900">
                                    <span x-text="slide.cta"></span><span aria-hidden="true">↗</span>
                                </a>
                            </div>
                        </template>

                        <div class="home-hero-controls mt-10 flex items-center gap-4 sm:mt-14">
                            <button type="button" @click="prev()" aria-label="Portada anterior" class="flex size-11 items-center justify-center rounded-full border border-gray-900/15 text-gray-800 transition hover:border-gray-900 hover:bg-white focus-visible:outline-2 focus-visible:outline-amber-700">
                                <flux:icon name="chevron-left" class="size-5" />
                            </button>
                            <div class="flex items-center gap-1.5" role="group" aria-label="Seleccionar portada">
                                <template x-for="(slide, i) in slides" :key="'dot-' + i">
                                    <button type="button" @click="go(i)" :aria-label="'Ir a portada ' + (i + 1)" :aria-current="i === active ? 'true' : 'false'" class="flex min-h-11 min-w-5 items-center justify-center focus-visible:outline-2 focus-visible:outline-amber-700">
                                        <span class="h-1.5 rounded-full transition-all duration-500" :class="i === active ? 'w-7 bg-amber-700' : 'w-1.5 bg-gray-400/65'"></span>
                                    </button>
                                </template>
                            </div>
                            <button type="button" @click="next()" aria-label="Siguiente portada" class="flex size-11 items-center justify-center rounded-full border border-gray-900/15 text-gray-800 transition hover:border-gray-900 hover:bg-white focus-visible:outline-2 focus-visible:outline-amber-700">
                                <flux:icon name="chevron-right" class="size-5" />
                            </button>
                            <span class="ml-auto hidden items-center gap-2 text-xs font-medium text-gray-500 sm:inline-flex"><span class="size-1.5 rounded-full bg-emerald-600"></span>Ideas para cada temporada</span>
                        </div>
                    </div>

                    <div class="home-hero-image relative order-1 h-[250px] overflow-hidden bg-[#e6e0d4] sm:h-[360px] lg:order-2 lg:h-full lg:min-h-[540px]">
                        <template x-for="(slide, i) in slides" :key="'image-' + i">
                            <div x-show="i === active" x-transition.opacity.duration.700ms x-cloak class="absolute inset-0">
                                <img :src="slide.image" :alt="slide.title + ' ' + slide.highlight" :fetchpriority="i === 0 ? 'high' : 'auto'" :loading="i === 0 ? 'eager' : 'lazy'" :class="i === active ? 'home-hero-photo-active' : ''" onerror="if (this.dataset.fallbackAttempted) { this.remove(); } else { this.dataset.fallbackAttempted = 'true'; this.src = '{{ $heroFallbackImage }}'; }" class="home-hero-photo h-full w-full object-cover">
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent lg:bg-gradient-to-l lg:from-transparent lg:to-black/[.04]"></div>
                            </div>
                        </template>
                        <span class="absolute bottom-4 right-4 rounded-full border border-white/50 bg-white/75 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[.16em] text-gray-800 backdrop-blur-md sm:bottom-6 sm:right-6">Brevare · Perú</span>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Categorías en una vitrina de avance automático, sin controles ni scroll manual. --}}
    @if ($allCategories->isNotEmpty())
        <section x-data="{
            offset: 0,
            direction: 1,
            timer: null,
            paused: false,
            get cards() { return this.$refs.track?.children ?? [] },
            step() {
                const track = this.$refs.track;
                if (!track || !this.cards.length) return 0;
                const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
                return this.cards[0].getBoundingClientRect().width + gap;
            },
            move() {
                const track = this.$refs.track;
                const viewport = this.$refs.viewport;
                if (!track || !viewport || this.cards.length < 2) return;
                const max = Math.max(0, track.scrollWidth - viewport.clientWidth);
                const destination = this.offset + this.direction * this.step();
                if (this.direction > 0 && destination >= max - 4) {
                    this.offset = max;
                    this.direction = -1;
                } else if (this.direction < 0 && destination <= 4) {
                    this.offset = 0;
                    this.direction = 1;
                } else {
                    this.offset = Math.max(0, Math.min(max, destination));
                }
            },
            start() { this.stop(); if (this.cards.length > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.timer = setInterval(() => { if (!this.paused) this.move() }, 5200) },
            stop() { clearInterval(this.timer); this.timer = null }
        }"
            x-init="start()" @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false"
            @visibilitychange.window="document.hidden ? stop() : start()" @resize.window="offset = Math.min(offset, Math.max(0, $refs.track.scrollWidth - $refs.viewport.clientWidth))"
            class="home-category-section mt-8 py-12 sm:mt-12 sm:py-16 lg:mt-16 lg:py-20">
            <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-10">
                <div class="mb-7 flex items-end justify-between gap-5 sm:mb-9">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[.2em] text-amber-900/70">Hecho para encontrar</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-[-.035em] text-[#171714] sm:text-3xl lg:text-[2.5rem]">Un buen hallazgo empieza aquí.</h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600 sm:text-base">Explora colecciones elegidas para acompañar tus días.</p>
                    </div>
                    <a href="{{ route('store.search') }}" class="mb-1 hidden text-sm font-semibold text-gray-700 transition hover:text-amber-800 sm:inline-flex">Ver todas <span class="ml-1" aria-hidden="true">↗</span></a>
                </div>

                <div x-ref="viewport" class="category-carousel-viewport" role="region" aria-label="Explora categorías">
                    <div x-ref="track" class="category-carousel flex gap-4 pb-2 sm:gap-5 lg:gap-6" :style="'transform: translate3d(-' + offset + 'px, 0, 0)'">
                    @foreach ($allCategories as $category)
                        <a href="{{ route('store.category', ['path' => $category->path]) }}" class="home-category-card group relative isolate flex min-h-[270px] shrink-0 items-end overflow-hidden rounded-[1.5rem] bg-[#e8e3da] text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-700 sm:min-h-[340px] sm:rounded-[1.8rem] lg:min-h-[390px]">
                            <img src="{{ $category->store_image }}" alt="" loading="lazy" decoding="async" onerror="if (this.dataset.fallbackAttempted) { this.remove(); } else { this.dataset.fallbackAttempted = 'true'; this.src = '{{ $category->store_image_fallback }}'; }" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.035]">
                            <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/65 via-black/5 to-transparent"></span>
                            <span class="relative flex w-full items-end justify-between gap-3 p-5 sm:p-6 lg:p-7">
                                <span class="min-w-0">
                                    <span class="mb-2 block text-[10px] font-semibold uppercase tracking-[.18em] text-white/80">Colección Brevare</span>
                                    <span class="block truncate text-2xl font-semibold tracking-[-.03em] sm:text-3xl">{{ $category->name }}</span>
                                </span>
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white/15 text-white backdrop-blur-md transition group-hover:border-white group-hover:bg-white group-hover:text-gray-900" aria-hidden="true"><span class="text-xl leading-none">↗</span></span>
                            </span>
                        </a>
                    @endforeach
                    </div>
                </div>
                <div class="mt-3 flex justify-end text-xs text-gray-500 sm:hidden"><a href="{{ route('store.search') }}" class="font-semibold text-gray-800">Ver todas ↗</a></div>
            </div>
        </section>
    @endif

    {{-- Productos reales; el módulo editorial llena el espacio si hay poca selección. --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-screen-2xl px-4 pb-12 pt-10 sm:px-6 sm:pb-16 sm:pt-14 lg:px-10">
            <div class="mb-7 flex items-end justify-between gap-4 sm:mb-9">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.2em] text-amber-900/70">Selección Brevare</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-[-.035em] text-[#171714] sm:text-3xl">{{ $homeTexts['featured'] ?? 'Productos destacados' }}</h2>
                </div>
                <a href="{{ route('store.search') }}" class="hidden text-sm font-semibold text-gray-700 transition hover:text-amber-800 sm:inline-flex">Ver la tienda <span class="ml-1" aria-hidden="true">↗</span></a>
            </div>
            @if ($featured->count() <= 2 && $allCategories->isNotEmpty())
                <div class="home-featured-layout grid gap-5 lg:grid-cols-[minmax(260px,.72fr)_minmax(0,1.28fr)] lg:gap-7">
                    <div class="home-featured-card">
                        @include('store.components.product-card', ['product' => $featured->first()])
                    </div>
                    @php($discoveryCategory = $allCategories->first())
                    <a href="{{ route('store.category', ['path' => $discoveryCategory->path]) }}" class="home-discovery-card group relative isolate flex min-h-[390px] items-end overflow-hidden rounded-[1.75rem] bg-[#e8e3da] sm:min-h-[440px]">
                        <img src="{{ $discoveryCategory->store_image }}" alt="" loading="lazy" decoding="async" onerror="if (this.dataset.fallbackAttempted) { this.remove(); } else { this.dataset.fallbackAttempted = 'true'; this.src = '{{ $discoveryCategory->store_image_fallback }}'; }" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.025]">
                        <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/65 via-black/5 to-transparent"></span>
                        <span class="relative max-w-lg p-6 text-white sm:p-9 lg:p-11">
                            <span class="block text-[10px] font-semibold uppercase tracking-[.2em] text-white/80">Más para descubrir</span>
                            <span class="mt-2 block text-3xl font-semibold leading-tight tracking-[-.035em] sm:text-4xl">Pequeños detalles. Grandes momentos.</span>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold">Explora {{ $discoveryCategory->name }} <span aria-hidden="true">↗</span></span>
                        </span>
                    </a>
                </div>
            @else
                <div class="home-product-grid grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                    @foreach ($featured as $product)
                        <div class="home-product-item">@include('store.components.product-card', ['product' => $product])</div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if ($latest->isNotEmpty())
        <section class="mx-auto max-w-screen-2xl px-4 pb-16 sm:px-6 sm:pb-20 lg:px-10">
            <div class="mb-7 flex items-end justify-between gap-4 sm:mb-9">
                <div><p class="text-[11px] font-bold uppercase tracking-[.2em] text-amber-900/70">Recién llegados</p><h2 class="mt-2 text-2xl font-semibold tracking-[-.035em] text-[#171714] sm:text-3xl">{{ $homeTexts['latest'] ?? 'Lo nuevo' }}</h2></div>
                <a href="{{ route('store.search') }}" class="text-sm font-semibold text-gray-700 transition hover:text-amber-800">Descubrir novedades ↗</a>
            </div>
            <div class="home-product-grid grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($latest as $product)
                    <div class="home-product-item">@include('store.components.product-card', ['product' => $product])</div>
                @endforeach
            </div>
        </section>
    @endif
</div>
