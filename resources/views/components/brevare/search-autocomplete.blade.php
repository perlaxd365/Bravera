@props(['mobile' => false])

@php
    $listId = $mobile ? 'mobile-search-suggestions' : 'desktop-search-suggestions';
@endphp

<form action="{{ route('store.search') }}" method="GET" role="search"
    x-data="{
        query: @js((string) request('q', '')),
        results: [],
        open: false,
        loading: false,
        active: -1,
        controller: null,
        async suggest() {
            const value = this.query.trim();

            if (value.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }

            this.controller?.abort();
            this.controller = new AbortController();
            this.loading = true;

            try {
                const response = await fetch(@js(route('store.search.suggestions')) + '?q=' + encodeURIComponent(value), {
                    headers: { Accept: 'application/json' },
                    signal: this.controller.signal,
                });
                this.results = response.ok ? await response.json() : [];
                this.open = this.results.length > 0;
                this.active = -1;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.results = [];
                    this.open = false;
                }
            } finally {
                this.loading = false;
            }
        },
        move(step) {
            if (!this.open || this.results.length === 0) return;
            this.active = (this.active + step + this.results.length) % this.results.length;
        },
        choose(index = this.active) {
            if (this.results[index]?.url) window.location.href = this.results[index].url;
        },
    }"
    @click.outside="open = false"
    @submit="open = false"
    class="w-full {{ $mobile ? '' : 'max-w-md' }}">
    <div class="relative">
        <flux:icon name="magnifying-glass" variant="mini"
            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-500" />
        <input type="search" name="q" x-model="query" @input.debounce.250ms="suggest()"
            @focus="query.trim().length >= 2 && suggest()"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="active >= 0 ? choose() : $el.form.requestSubmit()"
            @keydown.escape="open = false"
            role="combobox" aria-autocomplete="list"
            :aria-expanded="open.toString()" aria-controls="{{ $listId }}"
            placeholder="{{ $mobile ? '¿Qué estás buscando?' : 'Buscar productos...' }}"
            class="w-full rounded-full border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-10 text-sm text-gray-900 placeholder:text-gray-500 focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-400/20 focus:outline-none"
            aria-label="Buscar productos">
        <span x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
            <span class="block size-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-800 motion-reduce:animate-none"></span>
        </span>

        <div x-show="open" x-cloak x-transition.opacity.duration.150ms
            class="absolute left-0 right-0 top-full z-[90] mt-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/15">
            <ul id="{{ $listId }}" role="listbox" aria-label="Sugerencias de productos" class="divide-y divide-gray-100">
                <template x-for="(result, index) in results" :key="result.url">
                    <li role="option" :aria-selected="(active === index).toString()">
                        <button type="button" @click="choose(index)" @mouseenter="active = index"
                            class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition"
                            :class="active === index ? 'bg-amber-50' : 'hover:bg-gray-50'">
                            <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                                <img x-show="result.image" :src="result.image" alt="" class="size-full object-cover">
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-gray-900" x-text="result.name"></span>
                                <span class="mt-0.5 block text-xs text-gray-500" x-text="result.category || 'Brevare'"></span>
                            </span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</form>
