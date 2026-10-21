<div class="home-editorial">
    {{-- Credenciales de la cuenta de prueba para las validaciones de Culqi. --}}
    <div class="mx-auto mt-4 max-w-screen-2xl px-4 sm:px-6 lg:px-10">
        <x-brevare.review-credentials />
    </div>

    {{-- Franja promocional animada con fotografías reales del catálogo navideño. --}}
    @if ($promoTickerItems !== [])
        <section class="mx-auto mt-4 max-w-screen-2xl px-4 sm:px-6 lg:mt-5 lg:px-10"
            aria-label="Ideas navideñas de Brevare">
            <div class="home-promo-ticker">
                <div class="home-promo-track">
                    @foreach ([false, true] as $duplicate)
                        <div class="home-promo-group" @if ($duplicate) aria-hidden="true" @endif>
                            @foreach ($promoTickerItems as $item)
                                <a href="{{ $item['url'] }}" @if ($duplicate) tabindex="-1" @endif
                                    class="home-promo-card group">
                                    <span class="home-promo-media">
                                        <img src="{{ $item['image'] }}" alt="{{ $duplicate ? '' : $item['name'] }}"
                                            loading="lazy" decoding="async">
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[9px] font-bold uppercase tracking-[.17em] text-amber-800/75">{{ $item['eyebrow'] }}</span>
                                        <span class="mt-0.5 block truncate text-sm font-semibold text-stone-950 sm:text-[15px]">{{ $item['name'] }}</span>
                                    </span>
                                    <span class="home-promo-cta">Ver <span aria-hidden="true">↗</span></span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
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
            start() { this.stop(); if (!document.hidden && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && this.slides.length > 1) this.timer = setInterval(() => this.next(), 2000) },
            stop() { clearInterval(this.timer); this.timer = null }
        }"
            x-init="start()" @mouseenter="stop()" @mouseleave="start()" @focusin="stop()" @focusout="start()"
            @visibilitychange.window="document.hidden ? stop() : start()"
            class="mx-auto mt-5 max-w-screen-2xl px-4 sm:mt-7 sm:px-6 lg:px-10">
            <div class="home-hero relative overflow-hidden rounded-[1.75rem] bg-[#f5f2eb] sm:rounded-[2.25rem]">
                <div class="home-hero-grid grid min-h-[459px] lg:grid-cols-[.9fr_1.1fr]">
                    <div class="home-hero-copy order-2 flex flex-col justify-center px-6 pb-8 pt-5 sm:px-10 sm:pb-9 lg:order-1 lg:px-14 lg:py-10 xl:px-[4.5rem]">
                        <template x-for="(slide, i) in slides" :key="'copy-' + i">
                            <div x-show="i === active" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" x-cloak class="home-hero-slide">
                                <p class="text-[11px] font-bold uppercase tracking-[.2em] text-amber-900/70 sm:text-xs" x-text="slide.eyebrow"></p>
                                <h1 class="mt-3 max-w-xl text-[2.1rem] font-semibold leading-[1.04] tracking-[-.045em] text-[#171714] sm:text-[2.75rem] lg:text-[3.35rem] xl:text-[3.65rem]">
                                    <span class="sr-only">Brevare: </span>
                                    <span x-text="slide.title">Regalos que llegan</span>
                                    <span class="text-amber-700" x-text="slide.highlight">con ilusión</span>
                                </h1>
                                <p class="mt-4 max-w-md text-sm leading-6 text-gray-600 sm:text-base sm:leading-7" x-text="slide.subtitle"></p>
                                <a :href="slide.url" class="mt-5 inline-flex min-h-11 items-center gap-3 rounded-full bg-[#171714] px-6 text-sm font-semibold text-white transition hover:bg-amber-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900">
                                    <span x-text="slide.cta"></span><span aria-hidden="true">↗</span>
                                </a>
                            </div>
                        </template>

                        <div class="home-hero-controls mt-8 flex items-center gap-4 sm:mt-10">
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

                    <div class="home-hero-image relative order-1 h-[212px] overflow-hidden bg-[#e6e0d4] sm:h-[306px] lg:order-2 lg:h-full lg:min-h-[459px]">
                        <template x-for="(slide, i) in slides" :key="'image-' + i">
                            <div x-show="i === active" x-transition.opacity.duration.500ms x-cloak class="absolute inset-0">
                                <img :src="slide.image" :alt="slide.title + ' ' + slide.highlight" :fetchpriority="i === 0 ? 'high' : 'auto'" :loading="i === 0 ? 'eager' : 'lazy'" :class="i === active ? 'home-hero-photo-active' : ''" onerror="if (this.dataset.fallbackAttempted) { this.remove(); } else { this.dataset.fallbackAttempted = 'true'; this.src = '{{ $heroFallbackImage }}'; }" class="home-hero-photo h-full w-full object-cover">
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent lg:bg-gradient-to-l lg:from-transparent lg:to-black/[.04]"></div>
                            </div>
                        </template>
                        <span class="home-hero-snow" aria-hidden="true"></span>
                        <span class="absolute bottom-4 right-4 rounded-full border border-white/50 bg-white/75 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[.16em] text-gray-800 backdrop-blur-md sm:bottom-6 sm:right-6">Brevare · Perú</span>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Carrusel editorial con avance automático pausable en cualquier dispositivo. --}}
    @if ($allCategories->isNotEmpty())
        <section x-data="{
            timer: null,
            paused: false,
            userPaused: false,
            visible: false,
            observer: null,
            active: 0,
            total: @js($allCategories->count()),
            progress: 0,
            reduced() { return window.matchMedia('(prefers-reduced-motion: reduce)').matches },
            step() {
                const track = this.$refs.track;
                const first = track?.firstElementChild;
                if (!first) return 0;
                const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
                return first.getBoundingClientRect().width + gap;
            },
            sync() {
                const viewport = this.$refs.viewport;
                if (!viewport) return;
                const step = this.step();
                this.active = step > 0
                    ? Math.min(this.total - 1, Math.max(0, Math.round(viewport.scrollLeft / step)))
                    : 0;
                this.progress = viewport.scrollWidth > 0
                    ? Math.min(1, (viewport.scrollLeft + viewport.clientWidth) / viewport.scrollWidth)
                    : 1;
            },
            move() {
                const viewport = this.$refs.viewport;
                if (!viewport) return;
                const max = Math.max(0, viewport.scrollWidth - viewport.clientWidth);
                if (max < 4) return;
                let direction = viewport.dataset.direction === '-1' ? -1 : 1;
                if (direction > 0 && viewport.scrollLeft >= max - 4) direction = -1;
                if (direction < 0 && viewport.scrollLeft <= 4) direction = 1;
                viewport.dataset.direction = String(direction);
                viewport.scrollTo({
                    left: Math.max(0, Math.min(max, viewport.scrollLeft + direction * this.step())),
                    behavior: this.reduced() ? 'auto' : 'smooth',
                });
            },
            start() {
                this.stop();
                if (this.userPaused || !this.visible || this.reduced() || !this.$refs.viewport) return;
                this.timer = setInterval(() => { if (!this.paused) this.move() }, 3000);
            },
            stop() { clearInterval(this.timer); this.timer = null },
            observeVisibility() {
                if (!('IntersectionObserver' in window)) {
                    this.visible = true;
                    this.start();
                    return;
                }

                this.observer = new IntersectionObserver(([entry]) => {
                    this.visible = Boolean(entry?.isIntersecting);
                    this.visible ? this.start() : this.stop();
                }, { threshold: 0.2 });

                this.observer.observe(this.$el);
            },
            toggleAutoplay() {
                this.userPaused = !this.userPaused;
                if (this.userPaused) {
                    this.stop();
                    return;
                }
                this.paused = false;
                this.start();
            },
            interact() {
                this.userPaused = true;
                this.stop();
            },
            nudge(direction) {
                const viewport = this.$refs.viewport;
                if (!viewport) return;
                this.interact();
                viewport.scrollBy({
                    left: direction * viewport.clientWidth * 0.82,
                    behavior: this.reduced() ? 'auto' : 'smooth',
                });
            }
        }"
            x-init="$nextTick(() => { sync(); observeVisibility(); })"
            @focusin="paused = true" @focusout="paused = false"
            @visibilitychange.window="document.hidden ? stop() : start()" @resize.window.debounce.150ms="sync(); start()"
            class="home-category-section mt-8 py-12 sm:mt-12 sm:py-16 lg:mt-16 lg:py-20">
            <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-10">
                <div class="mb-7 flex items-end justify-between gap-5 sm:mb-9">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[.2em] text-amber-900/70">Hecho para encontrar</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-[-.035em] text-[#171714] sm:text-3xl lg:text-[2.5rem]">Un buen hallazgo empieza aquí.</h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600 sm:text-base">Explora colecciones elegidas para acompañar tus días.</p>
                    </div>
                    <div class="hidden items-center gap-3 sm:flex">
                        <div class="mr-2 text-right">
                            <p class="text-[10px] font-bold uppercase tracking-[.16em] text-stone-500">
                                <span x-text="String(active + 1).padStart(2, '0')">01</span>
                                <span class="text-stone-400">/ {{ str_pad((string) $allCategories->count(), 2, '0', STR_PAD_LEFT) }}</span>
                            </p>
                            <span class="mt-2 block h-px w-24 overflow-hidden bg-stone-900/10" aria-hidden="true">
                                <span class="block h-full origin-left bg-amber-700 transition-transform duration-500 ease-out"
                                    :style="`transform: scaleX(${progress})`"></span>
                            </span>
                        </div>
                        <button type="button" @click="nudge(-1)" aria-label="Categorías anteriores"
                            class="flex size-11 items-center justify-center rounded-full border border-stone-300 bg-white/70 text-stone-800 shadow-sm transition hover:border-stone-900 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700">
                            <flux:icon name="chevron-left" class="size-4" />
                        </button>
                        <button type="button" @click="nudge(1)" aria-label="Categorías siguientes"
                            class="flex size-11 items-center justify-center rounded-full border border-stone-300 bg-white/70 text-stone-800 shadow-sm transition hover:border-stone-900 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700">
                            <flux:icon name="chevron-right" class="size-4" />
                        </button>
                        <button type="button" @click="toggleAutoplay()" :aria-label="userPaused ? 'Reanudar rotación' : 'Pausar rotación'"
                            class="ml-1 min-h-11 rounded-full px-3 text-xs font-semibold text-stone-600 transition hover:text-stone-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700"
                            x-text="userPaused ? 'Reanudar' : 'Pausar'">Pausar</button>
                        <a href="{{ route('store.search') }}" class="ml-1 text-sm font-semibold text-gray-700 transition hover:text-amber-800">Ver todas <span class="ml-1" aria-hidden="true">↗</span></a>
                    </div>
                </div>

                <div x-ref="viewport" class="category-carousel-viewport" role="region"
                    aria-label="Explora categorías destacadas" tabindex="0"
                    @scroll.debounce.100ms="sync()"
                    @touchstart.passive="interact()"
                    @keydown.arrow-left.prevent="nudge(-1)"
                    @keydown.arrow-right.prevent="nudge(1)">
                    <div x-ref="track" class="category-carousel flex gap-4 pb-2 sm:gap-5 lg:gap-6" data-direction="1">
                    @foreach ($allCategories as $category)
                        <a href="{{ route('store.category', ['path' => $category->path]) }}" class="home-category-card group relative isolate flex min-h-[270px] shrink-0 items-end overflow-hidden rounded-[1.5rem] bg-[#e8e3da] text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-700 sm:min-h-[340px] sm:rounded-[1.8rem] lg:min-h-[390px]">
                            <img src="{{ $category->store_image }}" alt="" loading="lazy" decoding="async" onerror="if (this.dataset.fallbackAttempted) { this.remove(); } else { this.dataset.fallbackAttempted = 'true'; this.src = '{{ $category->store_image_fallback }}'; }" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.035]">
                            <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/65 via-black/5 to-transparent"></span>
                            <span class="relative flex w-full items-end justify-between gap-3 p-5 sm:p-6 lg:p-7">
                                <span class="min-w-0">
                                    <span class="mb-2 block text-[10px] font-semibold uppercase tracking-[.18em] text-white/80">Colección Brevare</span>
                                    <span class="block truncate text-2xl font-semibold tracking-[-.03em] sm:text-3xl">{{ $category->seo_label ?? $category->name }}</span>
                                </span>
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white/15 text-white backdrop-blur-md transition group-hover:border-white group-hover:bg-white group-hover:text-gray-900" aria-hidden="true"><span class="text-xl leading-none">↗</span></span>
                            </span>
                        </a>
                    @endforeach
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3 sm:hidden">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[.16em] text-stone-500">
                            <span x-text="String(active + 1).padStart(2, '0')">01</span>
                            <span class="text-stone-400">/ {{ str_pad((string) $allCategories->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        </p>
                        <span class="mt-2 block h-px w-20 overflow-hidden bg-stone-900/10" aria-hidden="true">
                            <span class="block h-full origin-left bg-amber-700 transition-transform duration-500 ease-out"
                                :style="`transform: scaleX(${progress})`"></span>
                        </span>
                    </div>
                    <div class="ml-auto flex gap-2">
                        <button type="button" @click="toggleAutoplay()" :aria-label="userPaused ? 'Reanudar rotación' : 'Pausar rotación'"
                            class="min-h-11 rounded-full border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 shadow-sm transition active:scale-95"
                            x-text="userPaused ? 'Reanudar' : 'Pausar'">Pausar</button>
                        <button type="button" @click="nudge(-1)" aria-label="Categorías anteriores"
                            class="flex size-11 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-800 shadow-sm transition active:scale-95">
                            <flux:icon name="chevron-left" class="size-4" />
                        </button>
                        <button type="button" @click="nudge(1)" aria-label="Categorías siguientes"
                            class="flex size-11 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-800 shadow-sm transition active:scale-95">
                            <flux:icon name="chevron-right" class="size-4" />
                        </button>
                    </div>
                    <a href="{{ route('store.search') }}" class="text-xs font-semibold text-gray-800">Ver todas ↗</a>
                </div>
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
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold">Explora {{ $discoveryCategory->seo_label ?? $discoveryCategory->name }} <span aria-hidden="true">↗</span></span>
                        </span>
                    </a>
                </div>
            @else
                <div class="home-product-grid grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
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
            <div class="home-product-grid grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($latest as $product)
                    <div class="home-product-item">@include('store.components.product-card', ['product' => $product])</div>
                @endforeach
            </div>
        </section>
    @endif
</div>
