<div>
    {{-- Descubrimiento de productos: la pequeña recomendación cambia cada 3 segundos. --}}
    @if ($rotatingProducts->isNotEmpty())
        <section x-data="{ items: @js($rotatingProducts), active: 0, timer: null, paused: false, next() { this.active = (this.active + 1) % this.items.length }, start() { this.stop(); if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && this.items.length > 1) this.timer = setInterval(() => { if (!this.paused) this.next() }, 3000) }, stop() { clearInterval(this.timer); this.timer = null } }"
            x-init="start()" @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false"
            class="mx-auto mt-5 max-w-7xl px-4 sm:px-6 lg:px-8">
            <a :href="items[active].url" class="group flex min-h-16 items-center justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 shadow-sm transition hover:border-amber-300 hover:bg-amber-100 sm:px-5">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-300 text-amber-950"><flux:icon name="sparkles" class="size-4" /></span>
                    <span class="min-w-0"><span class="block text-[10px] font-bold uppercase tracking-[.16em] text-amber-900/70" x-text="items[active].phrase"></span><span class="block truncate text-sm font-semibold text-gray-900" x-text="items[active].name"></span></span>
                </span>
                <span class="flex shrink-0 items-center gap-3"><img :src="items[active].image" alt="" class="size-11 rounded-xl bg-white object-cover shadow-sm" x-show="items[active].image"><span class="hidden text-sm font-semibold text-amber-950 sm:inline">Descubrir <span aria-hidden="true">→</span></span></span>
            </a>
        </section>
    @endif

    {{-- Portadas de temporada y categorías --}}
    @if ($heroSlides)
        <section x-data="{
            active: 0,
            slides: @js($heroSlides),
            timer: null,
            next() { this.active = (this.active + 1) % this.slides.length; },
            prev() { this.active = (this.active - 1 + this.slides.length) % this.slides.length; },
            go(i) { this.active = i; },
            start() { this.stop(); if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && this.slides.length > 1) this.timer = setInterval(() => this.next(), 5000); },
            stop() { if (this.timer) clearInterval(this.timer); this.timer = null; }
        }"
            x-init="start()"
            @mouseenter="stop()"
            @mouseleave="start()"
            class="mx-auto mt-4 w-full max-w-7xl overflow-hidden px-4 sm:px-6 lg:px-8">

            <div class="relative h-[400px] w-full overflow-hidden rounded-[1.5rem] border border-amber-100 bg-gray-900 shadow-xl shadow-amber-950/10 sm:h-[460px] sm:rounded-[2rem] lg:h-[510px]">
                <template x-for="(slide, i) in slides" :key="i">
                    <div x-show="i === active" x-transition.opacity.duration.700ms x-cloak class="absolute inset-0">
                        <img :src="slide.image" :alt="slide.title"
                            class="absolute inset-0 h-full w-full animate-hero-pan object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r from-gray-950/85 via-gray-950/45 to-gray-950/5"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-950/40 via-transparent to-transparent"></div>

                        <div class="absolute inset-x-0 bottom-0 px-5 pb-16 sm:px-10 sm:pb-20 lg:px-16">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-300 sm:text-sm" x-text="slide.eyebrow"></p>
                            <h2 class="mt-2 max-w-xl text-2xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-[2.6rem]">
                                <span x-text="slide.title"></span>
                                <span class="text-amber-300" x-text="slide.highlight"></span>
                            </h2>
                            <p class="mt-2 max-w-lg text-sm leading-relaxed text-white/85 sm:text-base" x-text="slide.subtitle"></p>
                            <a :href="slide.url"
                                class="mt-5 inline-flex min-h-12 items-center gap-2 rounded-full bg-amber-300 px-6 py-3 text-sm font-bold text-gray-950 shadow-lg shadow-black/15 transition hover:bg-amber-200">
                                <span x-text="slide.cta"></span>
                                <flux:icon name="arrow-right" class="size-4" />
                            </a>
                        </div>
                    </div>
                </template>

                <div>
                        <button type="button" @click="prev()" aria-label="Anterior"
                            class="absolute left-4 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur transition hover:bg-white/20 sm:flex">
                            <flux:icon name="chevron-left" class="size-5" />
                        </button>
                        <button type="button" @click="next()" aria-label="Siguiente"
                            class="absolute right-4 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur transition hover:bg-white/20 sm:flex">
                            <flux:icon name="chevron-right" class="size-5" />
                        </button>
                </div>

                <div class="absolute bottom-5 left-1/2 flex -translate-x-1/2 items-center justify-center gap-1 rounded-full border border-white/15 bg-gray-950/35 px-2 py-1 backdrop-blur-md" role="group" aria-label="Seleccionar portada">
                    <template x-for="(slide, i) in slides" :key="'dot-' + i">
                        <button type="button" @click="go(i)" :aria-label="'Ir al slide ' + (i + 1)"
                            :aria-current="i === active ? 'true' : 'false'"
                            class="flex size-8 items-center justify-center rounded-full transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <span class="block h-1.5 rounded-full transition-all duration-300" :class="i === active ? 'w-5 bg-amber-300' : 'w-1.5 bg-white/65'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </section>
    @endif

    {{-- Categorías principales --}}
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <div class="reveal mb-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-800">Encuentra tu estilo</p>
                <h2 class="mt-1 text-xl font-bold tracking-tight text-gray-950 sm:text-2xl">{{ $homeTexts['primary_categories'] ?? 'Compra por categoría' }}</h2>
            </div>
            <a href="{{ route('store.search') }}" class="text-sm font-semibold text-gray-600 transition hover:text-amber-800">Ver tienda <span aria-hidden="true">→</span></a>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            @foreach ($primaryCategories as $category)
                <a href="{{ route('store.category', ['path' => $category->path]) }}"
                    class="reveal group relative isolate flex aspect-[4/5] min-h-[250px] items-end overflow-hidden rounded-[1.35rem] bg-gray-900 text-left shadow-md shadow-gray-900/10 transition duration-500 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-amber-950/20 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-500 sm:min-h-[350px] sm:rounded-[1.75rem] lg:min-h-[420px]"
                    style="transition-delay: {{ $loop->index * 70 }}ms">
                    @if ($category->image)
                        <img src="{{ $category->image }}" alt="{{ $category->name }}" loading="lazy" class="absolute inset-0 z-0 h-full w-full object-cover transition duration-1000 ease-out group-hover:scale-110">
                    @else
                        <div class="absolute inset-0 z-0 bg-gradient-to-br from-amber-700 via-orange-900 to-gray-950"></div>
                    @endif
                    <span class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-t from-gray-950/90 via-gray-950/10 to-gray-950/5 transition duration-500 group-hover:from-gray-950/95"></span>
                    <span class="relative z-20 flex w-full items-end justify-between gap-2 p-3.5 sm:p-5 lg:p-6">
                        <span class="min-w-0 translate-y-1 transition duration-300 group-hover:translate-y-0">
                            <span class="block text-xl font-extrabold tracking-tight text-white drop-shadow-md sm:text-2xl lg:text-3xl">{{ $category->name }}</span>
                            <span class="mt-1 block text-xs font-medium text-white/90 sm:text-sm">Explora la colección</span>
                        </span>
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-300 text-gray-950 shadow-lg transition duration-300 group-hover:translate-x-1 group-hover:bg-white sm:size-12"><flux:icon name="arrow-right" class="size-5 pl-2" /></span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Otras categorías --}}
    <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <div class="reveal mb-5 flex items-center justify-between">
            <h2 class="text-lg font-bold tracking-tight text-gray-900">{{ $homeTexts['categories'] ?? 'Explora todo' }}</h2>
            <a href="{{ route('store.search') }}" class="text-sm font-medium text-gray-600 transition hover:text-gray-900">Ver todo</a>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @forelse ($categories as $category)
                <a href="{{ route('store.category', ['path' => $category->path]) }}"
                    class="reveal group relative isolate flex min-h-36 items-end overflow-hidden rounded-2xl border border-amber-100 bg-gray-900 text-left shadow-sm transition duration-300 hover:-translate-y-1 hover:border-amber-300 hover:shadow-lg hover:shadow-amber-950/10 sm:min-h-44"
                    style="transition-delay: {{ ($loop->index % 4) * 70 }}ms">
                    <img src="{{ $category->store_image }}" alt="" loading="lazy" class="absolute inset-0 -z-10 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    <span class="absolute inset-0 -z-10 bg-gradient-to-t from-gray-950/85 via-gray-950/25 to-transparent"></span>
                    <span class="m-3 flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-300 text-amber-950 shadow-sm transition group-hover:rotate-3 sm:m-4 sm:size-10">
                        <flux:icon name="{{ $category->icon && in_array($category->icon, ['box', 'tag', 'sparkles', 'wrench-screwdriver', 'home-modern', 'gift', 'funnel', 'cube']) ? $category->icon : 'cube' }}" class="size-5" />
                    </span>
                    <p class="mb-5 mr-3 text-sm font-bold text-white drop-shadow sm:mb-6 sm:text-base">{{ $category->name }}</p>
                </a>
            @empty
                <div class="col-span-2 py-10 text-center text-sm text-gray-500 sm:col-span-4">Aún no hay categorías disponibles.</div>
            @endforelse
        </div>
    </section>

    {{-- Destacados --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
            <h2 class="reveal mb-5 text-lg font-bold tracking-tight text-gray-900">{{ $homeTexts['featured'] ?? 'Productos destacados' }}</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($featured as $product)
                    <div class="reveal" style="transition-delay: {{ ($loop->index % 4) * 70 }}ms">
                        @include('store.components.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Recién llegados --}}
    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <h2 class="reveal mb-5 text-lg font-bold tracking-tight text-gray-900">{{ $homeTexts['latest'] ?? 'Lo nuevo' }}</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($latest as $product)
                <div class="reveal" style="transition-delay: {{ ($loop->index % 4) * 70 }}ms">
                    @include('store.components.product-card', ['product' => $product])
                </div>
            @empty
                <div class="col-span-2 py-10 text-center text-sm text-gray-500 sm:col-span-4">
                    <flux:icon name="cube" class="mx-auto mb-2 size-8 text-gray-300" />
                    Aún no hay productos disponibles.
                </div>
            @endforelse
        </div>
    </section>
</div>
