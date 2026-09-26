<div>
    {{-- Portadas rotativas (estilo Falabella) --}}
    @if ($heroSlides)
        <section x-data="{
            active: 0,
            slides: @js($heroSlides),
            timer: null,
            next() { this.active = (this.active + 1) % this.slides.length; },
            prev() { this.active = (this.active - 1 + this.slides.length) % this.slides.length; },
            go(i) { this.active = i; },
            start() { this.stop(); if (this.slides.length > 1) this.timer = setInterval(() => this.next(), 5500); },
            stop() { if (this.timer) clearInterval(this.timer); this.timer = null; }
        }"
            x-init="start()"
            @mouseenter="stop()"
            @mouseleave="start()"
            class="mx-auto mt-4 w-full max-w-7xl overflow-hidden rounded-3xl px-4 sm:px-6 lg:px-8">

            <div class="relative h-[440px] w-full overflow-hidden rounded-3xl bg-gray-900 border border-gray-200 sm:h-[500px] lg:h-[540px]">
                <template x-for="(slide, i) in slides" :key="i">
                    <div x-show="i === active" x-transition.opacity.duration.700ms x-cloak class="absolute inset-0">
                        <img :src="slide.image" :alt="slide.title"
                            class="absolute inset-0 h-full w-full animate-hero-pan object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-950/85 via-gray-950/25 to-transparent"></div>

                        <div class="absolute inset-x-0 bottom-0 px-6 pb-20 sm:px-10 sm:pb-24 lg:px-14">
                            <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-300" x-text="slide.eyebrow"></p>
                            <h2 class="mt-2 max-w-xl text-2xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-[2.6rem]">
                                <span x-text="slide.title"></span>
                                <span class="text-emerald-300" x-text="slide.highlight"></span>
                            </h2>
                            <p class="mt-2 max-w-lg text-sm leading-relaxed text-gray-200 sm:text-base" x-text="slide.subtitle"></p>
                            <a :href="slide.url"
                                class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-gray-900 shadow-sm transition hover:bg-gray-100">
                                <span x-text="slide.cta"></span>
                                <flux:icon name="arrow-right" class="size-4" />
                            </a>
                        </div>
                    </div>
                </template>

                <template x-for="(slide, i) in slides" :key="'nav-' + i">
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
                </template>

                <div class="absolute bottom-5 left-0 right-0 flex items-center justify-center gap-2">
                    <template x-for="(slide, i) in slides" :key="'dot-' + i">
                        <button type="button" @click="go(i)" :aria-label="'Ir al slide ' + (i + 1)"
                            :class="i === active ? 'w-8 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"
                            class="h-2 rounded-full transition-all duration-300"></button>
                    </template>
                </div>
            </div>
        </section>
    @endif

    {{-- Categorías --}}
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="reveal mb-5 flex items-center justify-between">
            <h2 class="text-lg font-bold tracking-tight text-gray-900">Categorías</h2>
            <a href="{{ route('store.search') }}" class="text-sm font-medium text-gray-600 transition hover:text-gray-900">Ver todo</a>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @forelse ($categories as $category)
                <a href="{{ route('store.category', ['path' => $category->path]) }}"
                    class="reveal group rounded-2xl border border-gray-200 bg-white px-4 py-8 text-center shadow-sm transition hover:border-gray-300 hover:shadow-md"
                    style="transition-delay: {{ ($loop->index % 4) * 70 }}ms">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-900 transition group-hover:scale-110 group-hover:bg-gray-900 group-hover:text-white">
                        <flux:icon name="{{ $category->icon && in_array($category->icon, ['box', 'tag', 'sparkles', 'wrench-screwdriver', 'home-modern', 'gift', 'funnel', 'cube']) ? $category->icon : 'cube' }}" class="size-6" />
                    </span>
                    <p class="mt-3 text-sm font-semibold text-gray-900">{{ $category->name }}</p>
                </a>
            @empty
                <div class="col-span-2 py-10 text-center text-sm text-gray-500 sm:col-span-4">Aún no hay categorías disponibles.</div>
            @endforelse
        </div>
    </section>

    {{-- Destacados --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
            <h2 class="reveal mb-5 text-lg font-bold tracking-tight text-gray-900">Productos destacados</h2>
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
        <h2 class="reveal mb-5 text-lg font-bold tracking-tight text-gray-900">Lo nuevo</h2>
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