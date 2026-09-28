<aside
    x-data="{
        open: false,
        message: '',
        thumb: '',
        count: 0,
        timer: null,
        show(detail) {
            this.message = detail?.product ?? 'Producto agregado al carrito';
            this.thumb = detail?.image ?? '';
            this.count = Number(detail?.count ?? 0);
            this.open = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => (this.open = false), 3500);
        },
        close() { this.open = false; },
    }"
    @brevare-cart-added.window="show($event.detail)"
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
    class="fixed bottom-5 left-4 z-[80] sm:left-6"
    role="status"
    aria-live="polite">
    <div class="flex items-center gap-3 rounded-2xl border border-gray-700/60 bg-gray-950/90 px-4 py-3 text-white shadow-2xl backdrop-blur-md">
        <img x-show="thumb" x-bind:src="thumb" x-transition.opacity.duration.300ms
            class="size-11 shrink-0 rounded-lg object-cover ring-1 ring-white/20" alt="">

        <div class="min-w-0 max-w-[9rem] sm:max-w-xs">
            <p class="flex items-start gap-1.5 text-sm font-semibold leading-snug">
                <span class="mt-0.5 shrink-0 text-emerald-400">
                    <flux:icon name="check-circle" class="size-4" />
                </span>
                <span x-text="message" class="truncate font-medium"></span>
            </p>
            <p x-show="count > 0" class="mt-0.5 pl-6 text-xs text-gray-400">
                Ya tienes <span class="font-semibold text-white" x-text="count"></span>
                <span x-text="count === 1 ? 'artículo' : 'artículos'"></span>
                en tu carrito
            </p>
        </div>

        <a href="{{ route('store.cart') }}"
            class="ml-1 shrink-0 rounded-full bg-white px-3.5 py-2 text-xs font-semibold text-gray-900 shadow-sm transition hover:bg-gray-100">
            Ver carrito
        </a>

        <button type="button" @click="close()" aria-label="Cerrar notificación"
            class="-mr-1 shrink-0 rounded-full p-1.5 text-gray-400 transition hover:bg-white/10 hover:text-white">
            <flux:icon name="x-mark" class="size-4" />
        </button>
    </div>
</aside>