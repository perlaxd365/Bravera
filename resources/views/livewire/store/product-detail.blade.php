<div>
    @php
        $currentVariant = $variants->first(fn ($v) => (int) $v->id === (int) $selectedVariantId);
        $currentSuppliers = $currentVariant?->available_providers ?? collect();

        $galleryUrls = $currentVariant?->images
            ->map(fn ($image) => $image->secure_url ?? $image->url)
            ->values()
            ->all() ?? [];

        if ($currentVariant && count($galleryUrls) === 0 && $product->coverImage()) {
            $galleryUrls = [$product->coverImage()];
        }

        $crumbs = collect();
        $crumb = $product->category;
        while ($crumb) {
            $crumbs->prepend($crumb);
            $crumb = $crumb->parent;
        }
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav aria-label="breadcrumb" class="mb-4 text-sm">
            <ol class="flex flex-wrap items-center gap-1.5 text-gray-500">
                <li><a href="{{ route('home') }}" class="transition hover:text-gray-900">Inicio</a></li>
                @foreach ($crumbs as $crumbCategory)
                    <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                    <li>
                        <a href="{{ route('store.category', ['path' => $crumbCategory->path]) }}"
                            class="transition hover:text-gray-900">
                            {{ $crumbCategory->name }}
                        </a>
                    </li>
                @endforeach
                <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                <li class="font-medium text-gray-900" aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="grid gap-8 lg:grid-cols-2">

            {{-- Galería --}}
            <div>
                @if (count($galleryUrls) > 0)
                    <div x-data="{
                            images: @js($galleryUrls),
                            active: 0,
                            lightbox: false,
                            tx: 0,
                            ty: 0,
                            prev() { this.active = (this.active - 1 + this.images.length) % this.images.length; },
                            next() { this.active = (this.active + 1) % this.images.length; },
                            parallax(e) {
                                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                                const r = this.$refs.stage.getBoundingClientRect();
                                const x = (e.clientX - r.left) / r.width - 0.5;
                                const y = (e.clientY - r.top) / r.height - 0.5;
                                this.tx = x * 18;
                                this.ty = y * 18;
                            },
                            resetParallax() { this.tx = 0; this.ty = 0; },
                            flyToCart() {
                                const btn = document.querySelector('[data-add-to-cart]');
                                const badge = document.querySelector('[data-cart-badge]');
                                if (!btn || !badge || btn.disabled || !this.images.length) return;
                                const from = btn.getBoundingClientRect();
                                const to = badge.getBoundingClientRect();
                                const size = 60;
                                const ix = from.left + from.width / 2 - size / 2;
                                const iy = from.top - size - 8;
                                const img = document.createElement('img');
                                img.src = this.images[this.active];
                                img.alt = '';
                                img.className = 'pointer-events-none fixed z-[70] rounded-xl object-cover shadow-2xl';
                                img.style.width = size + 'px';
                                img.style.height = size + 'px';
                                img.style.left = ix + 'px';
                                img.style.top = iy + 'px';
                                document.body.appendChild(img);
                                const fx = to.left + to.width / 2 - size / 2;
                                const fy = to.top + to.height / 2 - size / 2;
                                img.animate([
                                    { transform: 'translate3d(0,0,0) scale(1)', opacity: 1 },
                                    { transform: 'translate3d(' + (fx - ix) + 'px, ' + (fy - iy) + 'px, 0) scale(0.2)', opacity: 0.55 },
                                ], { duration: 700, easing: 'cubic-bezier(0.6, -0.2, 0.28, 1)' }).onfinish = () => img.remove();
                            },
                        }"
                        wire:key="gallery-{{ $currentVariant?->id ?? 'default' }}"
                        @brevare-fly-to-cart.window="flyToCart()"
                        @keydown.arrow-right.window.prevent="if (lightbox) next()"
                        @keydown.arrow-left.window.prevent="if (lightbox) prev()"
                        @keydown.escape.window="lightbox = false">

                        <div class="group relative aspect-square overflow-hidden rounded-2xl border border-gray-200 bg-white"
                                x-ref="stage"
                                @mousemove="parallax($event)"
                                @mouseleave="resetParallax()">
                            <template x-for="(img, i) in images" :key="i">
                                <div x-show="i === active"
                                    x-transition.opacity.duration.300ms
                                    class="absolute inset-0 p-8 transition-transform duration-700 ease-out"
                                    :style="{ transform: 'translate3d(' + tx + 'px, ' + ty + 'px, 0)' }">
                                    <img :src="img" alt="{{ $product->name }}"
                                        class="h-full w-full animate-kenburns rounded-xl object-contain">
                                </div>
                            </template>

                            @if (count($galleryUrls) > 1)
                                <button type="button" @click="prev()" aria-label="Foto anterior"
                                    class="absolute left-3 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full border border-gray-200 bg-white/90 text-gray-700 opacity-0 shadow-sm backdrop-blur transition group-hover:opacity-100 hover:bg-white focus:opacity-100">
                                    <flux:icon name="chevron-left" class="size-5" />
                                </button>
                                <button type="button" @click="next()" aria-label="Foto siguiente"
                                    class="absolute right-3 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full border border-gray-200 bg-white/90 text-gray-700 opacity-0 shadow-sm backdrop-blur transition group-hover:opacity-100 hover:bg-white focus:opacity-100">
                                    <flux:icon name="chevron-right" class="size-5" />
                                </button>

                                <span class="absolute bottom-3 right-3 rounded-full bg-gray-950/60 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
                                    <span x-text="active + 1"></span> / <span x-text="images.length"></span>
                                </span>
                            @endif

                            <button type="button" @click="lightbox = true" aria-label="Ampliar imagen"
                                class="absolute bottom-3 left-3 flex size-9 items-center justify-center rounded-full border border-gray-200 bg-white/90 text-gray-700 shadow-sm backdrop-blur transition hover:bg-white">
                                <flux:icon name="magnifying-glass-plus" class="size-4" />
                            </button>
                        </div>

                        @if (count($galleryUrls) > 1)
                            <div class="mt-2.5 flex gap-2.5 overflow-x-auto pb-1">
                                <template x-for="(img, i) in images" :key="i">
                                    <button type="button" @click="active = i" :aria-label="'Ver foto ' + (i + 1)"
                                        :class="i === active ? 'border-gray-900 ring-2 ring-gray-900/20' : 'border-gray-200 opacity-70 hover:opacity-100'"
                                        class="size-16 shrink-0 overflow-hidden rounded-xl border bg-white">
                                        <img :src="img" class="h-full w-full object-cover" alt="">
                                    </button>
                                </template>
                            </div>
                        @endif

                        {{-- Lightbox --}}
                        <div x-show="lightbox" x-transition.opacity.duration.200ms x-cloak
                            @click.self="lightbox = false"
                            class="fixed inset-0 z-[60] flex flex-col items-center justify-center bg-gray-950/85 p-4 backdrop-blur-sm">
                            <div class="relative flex w-full max-w-4xl items-center justify-center">
                                <template x-for="(img, i) in images" :key="i">
                                    <img :src="img" x-show="i === active"
                                        x-transition.opacity.scale.origin.center.duration.250ms
                                        class="max-h-[75vh] w-full rounded-2xl bg-white object-contain p-4 shadow-2xl">
                                </template>

                                <button type="button" @click="prev()" aria-label="Anterior"
                                    class="absolute -left-4 flex size-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:-left-14">
                                    <flux:icon name="chevron-left" class="size-6" />
                                </button>
                                <button type="button" @click="next()" aria-label="Siguiente"
                                    class="absolute -right-4 flex size-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:-right-14">
                                    <flux:icon name="chevron-right" class="size-6" />
                                </button>
                            </div>

                            @if (count($galleryUrls) > 1)
                                <div class="mt-4 flex max-w-full gap-2 overflow-x-auto pb-1">
                                    <template x-for="(img, i) in images" :key="i">
                                        <button type="button" @click="active = i"
                                            :class="i === active ? 'border-white ring-2 ring-white/40' : 'border-white/20 opacity-60 hover:opacity-100'"
                                            class="size-14 shrink-0 overflow-hidden rounded-lg border">
                                            <img :src="img" class="h-full w-full object-cover" alt="">
                                        </button>
                                    </template>
                                </div>
                            @endif

                            <button type="button" @click="lightbox = false" aria-label="Cerrar"
                                class="absolute right-4 top-4 flex size-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
                                <flux:icon name="x-mark" class="size-6" />
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex aspect-square items-center justify-center rounded-2xl border border-gray-200 bg-white p-8">
                        <flux:icon name="photo" class="size-20 text-gray-300" />
                    </div>
                @endif
            </div>

            {{-- Información --}}
            <div>
                @if ($product->brand)
                    <span class="inline-block rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-600">
                        {{ $product->brand->name }}
                    </span>
                @endif

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">{{ $product->name }}</h1>

                @if ($product->short_description)
                    <p class="mt-2 text-gray-600">{{ $product->short_description }}</p>
                @endif

                {{-- Precio --}}
                <div class="mt-5 flex flex-wrap items-baseline gap-3">
                    @if ($currentVariant)
                        <span class="text-3xl font-extrabold tracking-tight text-gray-900">S/ {{ number_format((float) $currentVariant->sale_price, 2) }}</span>
                        @if ($currentVariant->compare_price && $currentVariant->compare_price > $currentVariant->sale_price)
                            <span class="text-lg text-gray-400 line-through">S/ {{ number_format((float) $currentVariant->compare_price, 2) }}</span>
                            <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700">
                                -{{ round((1 - $currentVariant->sale_price / $currentVariant->compare_price) * 100) }}%
                            </span>
                        @endif
                    @else
                        <span class="text-3xl font-extrabold tracking-tight text-gray-900">{{ $product->priceRange() }}</span>
                    @endif
                </div>

                <form wire:submit="addToCart"
                    x-on:submit="window.dispatchEvent(new CustomEvent('brevare-fly-to-cart'))"
                    class="mt-6 space-y-5">

                    {{-- Atributos (selector de variante) --}}
                    @foreach ($attributes as $attribute)
                        <div>
                            <p class="mb-2 text-sm font-semibold text-gray-900">{{ $attribute['name'] }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($attribute['values'] as $value)
                                    <button type="button"
                                        wire:click="$set('selectedAttributes.{{ $attribute['id'] }}', {{ $value['id'] }})"
                                        class="inline-flex items-center gap-1.5 rounded-full border px-4 py-2 text-sm font-medium transition {{ ($selectedAttributes[$attribute['id']] ?? null) == $value['id'] ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400' }}"
                                        @if ($value['color']) style="border-color:{{ $value['color'] }};" @endif>
                                        @if ($value['color'])<span class="inline-block size-3 rounded-full" style="background:{{ $value['color'] }};"></span>@endif
                                        {{ $value['value'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- Variantes disponibles (si no hay atributos) --}}
                    @if ($attributes && $variants->count() > 1)
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-900">Variante</label>
                            <div class="relative">
                                <select wire:model.live="selectedVariantId"
                                    class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                    <option value="">Selecciona una variante</option>
                                    @foreach ($variants as $variant)
                                        <option value="{{ $variant->id }}">
                                            {{ $variant->sku }} ({{ $variant->total_available }} disp.)
                                        </option>
                                    @endforeach
                                </select>
                                <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                            </div>
                        </div>
                    @endif

                    {{-- Proveedores disponibles para la variante --}}
                    @if ($currentVariant && $currentSuppliers->isNotEmpty())
                        <div>
                            <p class="mb-2 text-sm font-semibold text-gray-900">Proveedor</p>
                            @foreach ($currentSuppliers as $supplierVariant)
                                <div class="mb-2 flex cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-4 py-3 transition hover:border-gray-300">
                                    <input type="radio" name="supplier"
                                        wire:model="selectedSupplierVariantId" value="{{ $supplierVariant->id }}"
                                        id="supplier-{{ $supplierVariant->id }}"
                                        @if ($loop->first && !$selectedSupplierVariantId) checked @endif
                                        class="mt-1 size-4 shrink-0 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                                    <label class="ml-3 w-full cursor-pointer" for="supplier-{{ $supplierVariant->id }}">
                                        <span class="block text-sm font-semibold text-gray-900">{{ $supplierVariant->supplier?->business_name }}</span>
                                        <span class="block text-xs text-gray-500">
                                            Disponible: {{ $supplierVariant->availableStock() }} · Envío aprox.
                                            {{ $supplierVariant->estimated_dispatch_days }} día(s)
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Cantidad --}}
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-semibold text-gray-900">Cantidad</label>
                        <div class="flex items-center rounded-full border border-gray-300 bg-white">
                            <button type="button" wire:click="$set('quantity', {{ max(1, $quantity - 1) }})"
                                class="flex size-9 items-center justify-center rounded-l-full text-gray-600 transition hover:bg-gray-100">
                                <flux:icon name="minus" class="size-4" />
                            </button>
                            <input type="number" wire:model.live="quantity" min="1"
                                class="w-14 border-0 text-center text-sm font-medium text-gray-900 focus:outline-none focus:ring-0">
                            <button type="button" wire:click="$set('quantity', {{ $quantity + 1 }})"
                                class="flex size-9 items-center justify-center rounded-r-full text-gray-600 transition hover:bg-gray-100">
                                <flux:icon name="plus" class="size-4" />
                            </button>
                        </div>
                    </div>

                    @error('selectedSupplierVariantId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    @if ($currentInCart)
                        <p class="inline-flex items-center gap-1.5 text-sm font-medium text-green-700">
                            <flux:icon name="check-circle" class="size-4" />
                            Esta variante ya está en tu carrito
                        </p>
                    @endif

                    <button type="submit"
                        data-add-to-cart
                        @if (!$currentVariant || $currentSuppliers->isEmpty()) disabled @endif
                        class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-gray-900 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                        <flux:icon name="shopping-cart" class="size-4" />
                        {{ $currentInCart ? 'Agregar más al carrito' : 'Agregar al carrito' }}
                    </button>

                    @if ($currentVariant && $currentSuppliers->isEmpty())
                        <p class="text-center text-sm text-gray-500">Este producto no tiene stock disponible en este momento.</p>
                    @endif
                </form>

                {{-- Mini carrito --}}
                @if ($cartItems->isNotEmpty())
                    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" x-data="{ open: true }">
                        <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between gap-2 px-5 py-4 text-left">
                            <span class="flex items-center gap-2 text-sm font-bold text-gray-900">
                                <flux:icon name="shopping-cart" class="size-4" /> Tu carrito ({{ $cartCount }})
                            </span>
                            <span :class="open ? 'rotate-180' : ''" class="inline-flex text-gray-400">
                                <flux:icon name="chevron-down" class="size-4 transition" />
                            </span>
                        </button>

                        <div x-show="open" x-transition.opacity.duration.150ms x-cloak>
                            <ul class="divide-y divide-gray-100 border-t border-gray-100 px-5">
                                @foreach ($cartItems as $item)
                                    @php
                                        $cartVariant = $item->variant;
                                        $cartProduct = $cartVariant?->product;
                                        $cartImage = $cartVariant?->images->firstWhere('is_primary', true) ?? $cartVariant?->images->first();
                                    @endphp
                                    <li class="flex items-center gap-3 py-3">
                                        @if ($cartProduct && $cartImage)
                                            <img src="{{ $cartImage->secure_url ?? $cartImage->url }}" alt="{{ $cartProduct->name }}"
                                                class="size-12 shrink-0 rounded-lg border border-gray-200 bg-gray-50 object-cover">
                                        @else
                                            <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-300">
                                                <flux:icon name="cube" class="size-5" />
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('store.product', ['slug' => $cartProduct?->slug ?? '#']) }}"
                                                class="block truncate text-sm font-medium text-gray-900 transition hover:text-gray-600">
                                                {{ $cartProduct?->name ?? 'Producto' }}
                                            </a>
                                            <p class="text-xs text-gray-500">Cant. {{ $item->quantity }} × S/ {{ number_format((float) $item->unit_price, 2) }}</p>
                                        </div>
                                        <span class="shrink-0 text-sm font-semibold text-gray-900">S/ {{ number_format((float) $item->lineSubtotal(), 2) }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-4">
                                <span class="text-sm text-gray-500">Subtotal</span>
                                <span class="text-lg font-extrabold tracking-tight text-gray-900">S/ {{ number_format($cartSubtotal, 2) }}</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 px-5 pb-5">
                                <a href="{{ route('store.cart') }}"
                                    class="inline-flex items-center justify-center rounded-full border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                    Ver carrito
                                </a>
                                <a href="{{ route('checkout') }}"
                                    class="inline-flex items-center justify-center rounded-full bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                                    Finalizar compra
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Descripción --}}
        @if ($product->description)
            <div class="mt-12">
                <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                    <h2 class="mb-3 text-lg font-bold tracking-tight text-gray-900">Descripción</h2>
                    <div class="leading-relaxed text-gray-600">{!! nl2br(e($product->description)) !!}</div>
                </div>
            </div>
        @endif

        {{-- Productos relacionados --}}
        @if ($relatedProducts->isNotEmpty())
            <section class="mt-12">
                <h2 class="mb-5 text-lg font-bold tracking-tight text-gray-900">Productos relacionados</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($relatedProducts as $related)
                        @include('store.components.product-card', ['product' => $related])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Visto recientemente --}}
        @if ($recentlyViewed->isNotEmpty())
            <section class="mt-12">
                <h2 class="mb-5 text-lg font-bold tracking-tight text-gray-900">Visto recientemente</h2>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    @foreach ($recentlyViewed as $recent)
                        <div class="w-44 shrink-0 sm:w-52">
                            @include('store.components.product-card', ['product' => $recent])
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>