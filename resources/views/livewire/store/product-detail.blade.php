<div>
    @php
        $isEnglish = app()->getLocale() === 'en';
        $productName = $product->localized('name') ?? $product->name;
        $productShortDescription = $product->localized('short_description');
        $productDescription = $product->localized('description');
        $selectedVariant = $variants->first(fn ($v) => (int) $v->id === (int) $selectedVariantId);

        // La variante real (la que eligió el cliente) manda; si todavía no
        // eligió, se muestra una de vista previa solo para galería y precio.
        $currentVariant = $selectedVariant ?? $previewVariant;

        $currentSuppliers = $selectedVariant?->available_providers ?? collect();

        $wholesaleActive = $currentVariant?->appliesWholesalePrice((int) $quantity) ?? false;

        $currentUnitPrice = $currentVariant?->unitPriceForQuantity((int) $quantity);

        $dispatchDays = (int) ($currentSuppliers->firstWhere('id', (int) $selectedSupplierVariantId)?->estimated_dispatch_days ?? 0);

        $quantityDiscountTiers = collect($currentVariant?->quantityDiscountTiers() ?? []);

        $pointsEarned = max(0, (int) floor((float) $currentUnitPrice * max(1, (int) $quantity)));

        $maxQuantity = $product->isCourse()
            ? 1
            : max(1, (int) ($currentVariant?->total_available ?? 1));

        $shippingBadge = $product->isCourse()
            ? ($isEnglish ? 'Lifetime access' : 'Acceso de por vida')
            : ($dispatchDays === 1
                ? ($isEnglish ? 'Arrives tomorrow' : 'Llega mañana')
                : ($dispatchDays > 1
                    ? ($isEnglish ? 'Arrives in '.$dispatchDays.' days' : 'Llega en '.$dispatchDays.' días')
                    : ($isEnglish ? 'Shipping throughout Peru' : 'Envío a todo el Perú')));

        $company = config('brevare.company');

        $isFavorite = in_array(
            $product->id,
            app(\App\Services\FavoriteService::class)->productIdsFor(auth()->id()),
            true
        );

        $missingSelection = $requiresVariantSelection && ! $selectedVariant;

        // La imagen principal elegida en el panel debe ser la primera de la
        // galería, aunque tenga un sort_order posterior a las demás.
        $galleryUrls = $currentVariant?->images
            ->sortByDesc('is_primary')
            ->map(fn ($image) => $image->imageUrl())
            ->filter(fn ($url) => filled($url))
            ->values()
            ->all() ?? [];

        if ($currentVariant && count($galleryUrls) === 0 && $product->coverImageModel()?->imageUrl()) {
            $galleryUrls = [$product->coverImageModel()->imageUrl()];
        }

        $crumbs = collect();
        $crumb = $product->category;
        while ($crumb) {
            $crumbs->prepend($crumb);
            $crumb = $crumb->parent;
        }
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <style>
        @media (min-width: 1024px) {
            .product-gallery-grid {
                grid-template-columns: 76px minmax(0, 1fr);
                align-items: start;
            }

            .product-gallery-stage {
                grid-column: 2;
                grid-row: 1;
                width: 100%;
            }

            .product-gallery-thumbs {
                grid-column: 1;
                grid-row: 1;
                max-height: min(70vh, 620px);
                flex-direction: column;
                overflow-x: hidden;
                overflow-y: auto;
                padding-right: 4px;
            }

            .product-detail-info {
                max-height: calc(100dvh - 13rem);
                overflow-y: auto;
                overscroll-behavior-y: auto;
                scrollbar-color: rgba(154, 52, 18, .38) transparent;
                scrollbar-gutter: stable;
                scrollbar-width: thin;
                padding-right: .55rem;
            }

            .product-detail-info::-webkit-scrollbar {
                width: 8px;
            }

            .product-detail-info::-webkit-scrollbar-track {
                background: transparent;
            }

            .product-detail-info::-webkit-scrollbar-thumb {
                border: 2px solid transparent;
                border-radius: 9999px;
                background: rgba(154, 52, 18, .32);
                background-clip: padding-box;
            }

            .product-detail-info::-webkit-scrollbar-thumb:hover {
                background: rgba(154, 52, 18, .5);
                background-clip: padding-box;
            }
        }
    </style>

        <nav aria-label="breadcrumb" class="mb-4 text-sm">
            <ol class="flex flex-wrap items-center gap-1.5 text-gray-500">
                <li><a href="{{ route('home') }}" class="transition hover:text-gray-900">{{ __('storefront.common.home') }}</a></li>
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
                <li class="font-medium text-gray-900" aria-current="page">{{ $productName }}</li>
            </ol>
        </nav>

        <div class="grid min-w-0 items-start gap-6 lg:grid-cols-2 lg:gap-8">

            {{-- Galería --}}
            <div class="min-w-0">
                @if (count($galleryUrls) > 0)
                    <div x-data="{
                            images: @js($galleryUrls),
                            active: 0,
                            lightbox: false,
                            tx: 0,
                            ty: 0,
                            prev() { this.active = (this.active - 1 + this.images.length) % this.images.length; },
                            next() { this.active = (this.active + 1) % this.images.length; },
                            touchStartX: null,
                            touchStartY: null,
                            startSwipe(event) {
                                const touch = event.changedTouches?.[0];
                                if (!touch) return;
                                this.touchStartX = touch.clientX;
                                this.touchStartY = touch.clientY;
                            },
                            endSwipe(event) {
                                const touch = event.changedTouches?.[0];
                                if (!touch || this.touchStartX === null) return;
                                const dx = touch.clientX - this.touchStartX;
                                const dy = touch.clientY - this.touchStartY;
                                this.touchStartX = null;
                                this.touchStartY = null;
                                if (Math.abs(dx) < 40 || Math.abs(dx) <= Math.abs(dy)) return;
                                dx < 0 ? this.next() : this.prev();
                            },
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

                        <div class="product-gallery-grid grid gap-2.5">
                        <div class="product-gallery-stage group relative aspect-square touch-pan-y overflow-hidden rounded-2xl border border-gray-200 bg-white"
                                x-ref="stage"
                                @mousemove="parallax($event)"
                                @mouseleave="resetParallax()"
                                @touchstart.passive="startSwipe($event)"
                                @touchend="endSwipe($event)">
                            <div class="absolute inset-0 p-8 transition-transform duration-700 ease-out"
                                :style="{ transform: 'translate3d(' + tx + 'px, ' + ty + 'px, 0)' }">
                                <img src="{{ $galleryUrls[0] }}" :src="images[active]" alt="{{ $productName }}"
                                    fetchpriority="high" decoding="async"
                                    class="h-full w-full animate-kenburns rounded-xl object-contain">
                            </div>

                            @if (count($galleryUrls) > 1)
                                <button type="button" @click="prev()" aria-label="Foto anterior"
                                    class="absolute left-3 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full border border-gray-200 bg-white/90 text-gray-700 shadow-sm backdrop-blur transition hover:bg-white">
                                    <flux:icon name="chevron-left" class="size-5" />
                                </button>
                                <button type="button" @click="next()" aria-label="Foto siguiente"
                                    class="absolute right-3 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full border border-gray-200 bg-white/90 text-gray-700 shadow-sm backdrop-blur transition hover:bg-white">
                                    <flux:icon name="chevron-right" class="size-5" />
                                </button>

                                <span class="absolute bottom-3 right-3 rounded-full bg-gray-950/60 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
                                    <span x-text="active + 1"></span> / <span x-text="images.length"></span>
                                </span>
                            @endif

                            <button type="button" @click="lightbox = true" aria-label="Ampliar imagen"
                                class="absolute bottom-3 left-3 flex size-11 items-center justify-center rounded-full border border-gray-200 bg-white/90 text-gray-700 shadow-sm backdrop-blur transition hover:bg-white">
                                <flux:icon name="magnifying-glass-plus" class="size-4" />
                            </button>
                        </div>

                        @if (count($galleryUrls) > 1)
                            <div class="product-gallery-thumbs flex gap-2.5 overflow-x-auto pb-1">
                                <template x-for="(img, i) in images" :key="i">
                                    <button type="button" @click="active = i" :aria-label="'Ver foto ' + (i + 1)"
                                        :class="i === active ? 'border-gray-900 ring-2 ring-gray-900/20' : 'border-gray-200 opacity-70 hover:opacity-100'"
                                        class="size-16 shrink-0 overflow-hidden rounded-xl border bg-white">
                                        <img :src="img" class="h-full w-full object-cover" alt="">
                                    </button>
                                </template>
                            </div>
                            <p class="text-center text-[11px] text-gray-400 sm:hidden lg:hidden">Desliza la imagen para ver más fotos</p>
                        @endif
                        </div>

                        {{-- Lightbox --}}
                        <div x-show="lightbox" x-transition.opacity.duration.200ms x-cloak
                            @click.self="lightbox = false"
                            class="fixed inset-0 z-[60] flex flex-col items-center justify-center bg-gray-950/85 p-4 backdrop-blur-sm">
                            <div class="relative flex w-full max-w-4xl touch-pan-y items-center justify-center"
                                @touchstart.passive="startSwipe($event)"
                                @touchend="endSwipe($event)">
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
            <div class="product-detail-info min-w-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2"
                tabindex="0" aria-label="Información y opciones de compra">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-800">
                        <span class="size-1.5 rounded-full bg-emerald-600" aria-hidden="true"></span>
                        {{ $shippingBadge }}
                    </span>
                    @if ($product->brand)
                        <span class="text-[11px] font-semibold uppercase tracking-[.14em] text-gray-500">
                            {{ $product->brand->name }}
                        </span>
                    @endif
                </div>

                <h1 class="mt-4 text-[1.75rem] font-bold leading-[1.12] tracking-[-.025em] text-stone-800 sm:text-[2rem]">{{ $productName }}</h1>

                @if ($productShortDescription)
                    <p class="mt-3 text-sm leading-6 text-gray-600">{{ $productShortDescription }}</p>
                @endif

                {{-- Precio --}}
                <div @class([
                    'mt-5 flex flex-wrap items-center gap-x-3 gap-y-2',
                    'rounded-2xl border border-amber-200/70 bg-gradient-to-br from-[#fff9e9] via-white to-[#faf6ee] p-4 shadow-[0_18px_36px_-32px_rgba(120,53,15,.45)]' => $product->isCourse(),
                ])>
                    @if ($currentVariant)
                        @if ($product->isCourse())
                            <span class="w-full text-[10px] font-bold uppercase tracking-[.18em] text-stone-500">{{ __('storefront.product.course_price') }}</span>
                        @endif
                        <x-brevare.price :amount="(float) $currentVariant->sale_price" size="lg" class="text-[1.65rem] font-extrabold tracking-[-.025em] text-[#c73b3b]" />
                        @if ($currentVariant->compare_price && $currentVariant->compare_price > $currentVariant->sale_price)
                            <x-brevare.price :amount="(float) $currentVariant->compare_price" size="sm" class="text-base font-normal text-gray-500 line-through" :show-approximation="false" />
                            <span class="inline-flex items-center overflow-hidden rounded bg-[#c73b3b] text-[11px] font-bold text-white shadow-sm">
                                <span class="px-2 py-1.5">-{{ round((1 - $currentVariant->sale_price / $currentVariant->compare_price) * 100) }}%</span>
                                <span class="h-4 w-px bg-white/55" aria-hidden="true"></span>
                                <span class="px-2 py-1.5">{{ __('storefront.product.sale') }}</span>
                            </span>
                        @endif
                        @if ($product->isCourse() && $currentVariant->compare_price && $currentVariant->compare_price > $currentVariant->sale_price)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                                <flux:icon name="sparkles" class="size-3" />
                                {{ __('storefront.product.launch_price') }}
                            </span>
                        @endif
                    @else
                        <span class="text-3xl font-extrabold tracking-tight text-gray-900">{{ $product->priceRange() }}</span>
                    @endif
                </div>

                @if ($storefront->isInternational() && $currentVariant)
                    <div class="mt-2 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs leading-5 text-sky-900" role="note">
                        <p class="font-semibold">{{ __('storefront.prices.charged_in_soles', ['amount' => $storefront->chargedFromPen((float) $currentVariant->sale_price)]) }}</p>
                        <p class="mt-1 text-sky-700">{{ __('storefront.prices.exchange_rate', ['rate' => number_format($storefront->exchangeRate, 2)]) }}</p>
                    </div>
                @endif

                @if ($product->isCourse())
                    <div class="relative mt-3 overflow-hidden rounded-xl border border-amber-200/20 bg-[#1b1713] px-4 py-3 text-white shadow-[0_16px_34px_-28px_rgba(41,37,36,.75)]">
                        <div class="flex items-center gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/10 text-amber-200">
                                <flux:icon name="academic-cap" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <span class="block text-[9px] font-bold uppercase tracking-[.2em] text-amber-200/85">{{ __('storefront.product.digital_experience') }}</span>
                                <p class="mt-0.5 text-sm font-semibold leading-5 tracking-[-.015em]">{{ __('storefront.product.lifetime_access') }}</p>
                            </div>
                        </div>
                        @php
                            $courseLocales = $product->courseAssets->pluck('locale')->filter()->unique();
                        @endphp
                        @if ($courseLocales->isNotEmpty())
                            <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-white/10 pt-3">
                                <span class="text-[10px] font-bold uppercase tracking-[.16em] text-white/50">{{ __('storefront.product.language') }}</span>
                                @if ($courseLocales->contains('es'))
                                    <span class="rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-semibold text-white">Español</span>
                                @endif
                                @if ($courseLocales->contains('en'))
                                    <span class="rounded-full bg-amber-200 px-2.5 py-1 text-[11px] font-semibold text-amber-950">English</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                @if ($currentVariant && $currentVariant->total_available > 0)
                    @if ($product->isCourse())
                        <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-800">
                            <flux:icon name="document-arrow-down" class="size-3.5" />
                            {{ __('storefront.product.digital_product') }}
                        </p>
                    @else
                        <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">
                            <span class="size-2 rounded-full bg-emerald-600"></span>
                            {{ __('storefront.product.in_stock') }} · {{ $currentVariant->total_available === 1
                                ? __('storefront.product.one_unit_available')
                                : __('storefront.product.units_available', ['count' => $currentVariant->total_available]) }}
                        </p>
                    @endif
                @endif

                @if ($product->allows_shalom_cod)
                    <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">
                        <flux:icon name="truck" class="size-3.5" />
                        {{ $isEnglish ? 'Shalom cash-on-delivery is available' : 'Envío con pago contra entrega en Shalom disponible' }}
                    </p>
                @endif

                @auth
                    <form method="POST" action="{{ route('favorites.toggle', $product) }}" class="mt-4">
                        @csrf
                        <button type="submit"
                            class="inline-flex min-h-10 items-center gap-2 rounded-full border px-4 text-sm font-medium transition {{ $isFavorite ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'border-gray-300 bg-white text-gray-700 hover:border-rose-200 hover:text-rose-600' }}">
                            <flux:icon name="heart" class="size-4 {{ $isFavorite ? 'fill-current' : '' }}" />
                            {{ $isFavorite ? ($isEnglish ? 'In your favorites' : 'En tus favoritos') : ($isEnglish ? 'Save to favorites' : 'Guardar en favoritos') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}"
                        class="mt-4 inline-flex min-h-10 items-center gap-2 rounded-full border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-rose-200 hover:text-rose-600">
                        <flux:icon name="heart" class="size-4" />
                        {{ $isEnglish ? 'Save to favorites' : 'Guardar en favoritos' }}
                    </a>
                @endauth

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
                                        class="inline-flex min-h-11 items-center gap-1.5 rounded-full border px-4 py-2 text-sm font-medium transition {{ ($selectedAttributes[$attribute['id']] ?? null) == $value['id'] ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400' }}"
                                        @if ($value['color']) style="border-color:{{ $value['color'] }};" @endif>
                                        @if ($value['color'])<span class="inline-block size-3 rounded-full" style="background:{{ $value['color'] }};"></span>@endif
                                        {{ $value['value'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- Variantes disponibles (si no hay atributos) --}}
                    @if (empty($attributes) && $variants->count() > 1)
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-900">{{ $isEnglish ? 'Option' : 'Variante' }}</label>
                            <div class="relative">
                                <select wire:model.live="selectedVariantId"
                                    class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                    <option value="">{{ __('storefront.product.choose_variant') }}</option>
                                    @foreach ($variants as $variant)
                                        <option value="{{ $variant->id }}">
                                            {{ $variant->sku }}
                                            @if (! $product->isCourse())
                                                ({{ $variant->total_available }} {{ $isEnglish ? 'available' : 'disp.' }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                            </div>
                        </div>
                    @endif

                    {{-- Cantidad y descuentos --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-[0_10px_30px_-26px_rgba(41,37,36,.55)] sm:p-5">
                        @if ($product->isCourse())
                            <div class="flex items-start gap-3">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-800 ring-1 ring-amber-200">
                                    <flux:icon name="bolt" class="size-5" />
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-stone-900">{{ $isEnglish ? 'Lifetime digital access' : 'Acceso digital de por vida' }}</p>
                                    <p class="mt-1 text-xs leading-5 text-stone-500">{{ $isEnglish ? 'After your payment is confirmed, the course will be available in your account. No physical shipping is required.' : 'Después de confirmar tu pago, el curso estará disponible en tu cuenta. No se realizan envíos físicos.' }}</p>
                                </div>
                            </div>
                        @else
                        @if ($quantityDiscountTiers->isNotEmpty())
                            <fieldset>
                                <legend class="flex w-full items-center gap-2 text-base font-medium tracking-[-.01em] text-stone-800">
                                    <flux:icon name="fire" class="size-6 shrink-0 text-orange-500" />
                                    {{ $isEnglish ? 'Discount from the second item' : 'Descuento desde la 2da unidad' }}
                                </legend>

                                <div class="mt-4 space-y-2">
                                    @foreach ($quantityDiscountTiers as $tier)
                                        @php
                                            $tierQuantity = (int) $tier['quantity'];
                                            $tierPercent = (float) $tier['percent'];
                                            $tierUnitPrice = (float) $currentVariant?->unitPriceForQuantity($tierQuantity);
                                            $tierEffectivePercent = $currentVariant && (float) $currentVariant->sale_price > 0
                                                ? round((1 - ($tierUnitPrice / (float) $currentVariant->sale_price)) * 100, 1)
                                                : $tierPercent;
                                            $tierTotal = round($tierUnitPrice * $tierQuantity, 2);
                                            $tierOriginalTotal = round((float) $currentVariant?->sale_price * $tierQuantity, 2);
                                            $tierUnavailable = $currentVariant && $tierQuantity > (int) $currentVariant->total_available;
                                            $tierSelected = (int) $quantity === $tierQuantity;
                                        @endphp

                                        <label
                                            class="flex min-h-[54px] items-center gap-3 rounded-lg border px-3 py-2.5 transition focus-within:ring-2 focus-within:ring-orange-300 focus-within:ring-offset-1 sm:px-4 {{ $tierSelected ? 'border-orange-300 bg-[#fffaf1] ring-1 ring-orange-200' : 'border-gray-200 bg-white hover:border-gray-300' }} {{ $tierUnavailable ? 'pointer-events-none cursor-not-allowed opacity-45' : 'cursor-pointer' }}">
                                            <input type="radio" name="product_quantity_tier" value="{{ $tierQuantity }}"
                                                wire:model.live="quantity" @disabled($tierUnavailable)
                                                class="sr-only">
                                            <span class="flex size-5 shrink-0 items-center justify-center rounded-full border {{ $tierSelected ? 'border-orange-400' : 'border-gray-400' }}" aria-hidden="true">
                                                <span class="size-2.5 rounded-full {{ $tierSelected ? 'bg-orange-400' : 'bg-transparent' }}"></span>
                                            </span>
                                            <span class="min-w-0 text-sm font-medium leading-5 text-stone-800 sm:text-[15px]">
                                                {{ $isEnglish ? 'Buy '.$tierQuantity.' items' : 'Lleva '.$tierQuantity.' unidades' }}
                                            </span>
                                            <span class="shrink-0 rounded bg-amber-50 px-1.5 py-1 text-[10px] font-bold text-orange-500 sm:text-xs">
                                                {{ rtrim(rtrim(number_format($tierEffectivePercent, 1, '.', ''), '0'), '.') }}% OFF
                                            </span>
                                            @if (! empty($tier['badge']))
                                                <span class="hidden shrink-0 text-[11px] font-semibold text-orange-400 md:inline">{{ $tier['badge'] }}</span>
                                            @endif
                                            <span class="ml-auto shrink-0 text-right">
                                                <span class="block text-sm font-extrabold tabular-nums text-stone-800 sm:text-[15px]"><x-brevare.price :amount="$tierTotal" size="xs" class="font-extrabold text-stone-800" /></span>
                                                @if ($tierOriginalTotal > $tierTotal)
                                                    <span class="mt-0.5 block text-[11px] tabular-nums text-gray-400 line-through"><x-brevare.price :amount="$tierOriginalTotal" size="xs" class="font-normal text-gray-400 line-through" :show-approximation="false" /></span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3">
                            <label class="text-xs font-medium text-gray-500">{{ $isEnglish ? 'Exact quantity' : 'Cantidad exacta' }}</label>
                            <div class="flex items-center rounded-full border border-gray-300 bg-white">
                                <button type="button" wire:click="$set('quantity', {{ max(1, $quantity - 1) }})"
                                    @disabled($quantity <= 1)
                                    class="flex size-9 items-center justify-center rounded-l-full text-gray-600 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-35">
                                    <flux:icon name="minus" class="size-3.5" />
                                </button>
                                <input type="number" wire:model.live="quantity" min="1" max="{{ $maxQuantity }}"
                                    class="w-11 border-0 text-center text-xs font-semibold text-gray-900 focus:outline-none focus:ring-0">
                                <button type="button" wire:click="$set('quantity', {{ min($maxQuantity, $quantity + 1) }})"
                                    @disabled($quantity >= $maxQuantity)
                                    class="flex size-9 items-center justify-center rounded-r-full text-gray-600 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-35">
                                    <flux:icon name="plus" class="size-3.5" />
                                </button>
                            </div>
                        </div>

                        @if ($currentVariant?->hasWholesalePrice())
                            <p class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium leading-5 text-emerald-800">
                                @if ($wholesaleActive)
                                    {{ $isEnglish ? 'You are buying '.$quantity.' items at the wholesale price.' : 'Estás comprando '.$quantity.' unidades con precio al por mayor.' }}
                                @else
                                    {{ $isEnglish ? 'Wholesale price' : 'Precio al por mayor' }}: <x-brevare.price :amount="(float) $currentVariant->wholesale_price" size="xs" class="font-semibold text-emerald-800" /> {{ $isEnglish ? 'from '.$currentVariant->wholesale_min_quantity.' units' : 'desde '.$currentVariant->wholesale_min_quantity.' unidades' }}
                                @endif
                            </p>
                        @endif
                        @endif
                    </div>

                    @if ($missingAttributes->isNotEmpty())
                        <p class="text-sm text-amber-600">{{ $isEnglish ? 'Select:' : 'Falta seleccionar:' }} {{ $missingAttributes->join(', ') }}</p>
                    @endif

                    @error('selectedVariantId')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @error('selectedSupplierVariantId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    @if ($currentInCart)
                        <p class="inline-flex items-center gap-1.5 text-sm font-medium text-green-700">
                            <flux:icon name="check-circle" class="size-4" />
                            {{ $isEnglish ? 'This option is already in your cart' : 'Esta variante ya está en tu carrito' }}
                        </p>
                    @endif

                    @if ($currentInCart)
                        <a href="{{ route('checkout') }}" wire:navigate
                            class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-700 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                            <flux:icon name="credit-card" class="size-4" />
                            {{ $isEnglish ? 'Go to checkout and pay' : 'Ir al Checkout (Pagar ahora)' }}
                        </a>
                    @else
                        <button type="submit"
                            data-add-to-cart
                            @disabled(!$selectionComplete || !$selectedVariant || $currentSuppliers->isEmpty())
                            class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-gray-900 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                            <flux:icon :name="$product->isCourse() ? 'academic-cap' : 'shopping-cart'" class="size-4" />
                            {{ $product->isCourse() ? ($isEnglish ? 'Add course to cart' : 'Agregar curso al carrito') : __('storefront.product.add_to_cart') }}
                        </button>
                    @endif

                    @if ($selectionComplete && $selectedVariant && $currentSuppliers->isEmpty())
                        <p class="text-center text-sm text-gray-500">{{ $isEnglish ? 'That combination is not available.' : 'Esa combinación no está disponible.' }}</p>
                    @endif
                </form>

                <div class="mt-4 flex items-center justify-between gap-4 border-y border-gray-100 py-3">
                    <p class="text-sm text-gray-600">
                        @if ($currentVariant)
                            {{ $isEnglish ? 'Earn' : 'Gana' }} <strong class="font-semibold text-gray-800">{{ number_format($pointsEarned) }} {{ $isEnglish ? 'points' : 'puntos' }}</strong> {{ $isEnglish ? 'with this purchase.' : 'con esta compra.' }}
                        @else
                            {{ $isEnglish ? 'Earn Brevare Rewards points with every purchase.' : 'Gana Brevare Puntos con cada compra.' }}
                        @endif
                    </p>
                    <a href="{{ auth()->check() ? route('account.points') : route('login', ['redirect' => request()->fullUrl()]) }}"
                        class="shrink-0 text-xs font-medium text-gray-700 underline decoration-gray-400 underline-offset-4 transition hover:text-amber-800">
                        {{ $isEnglish ? 'Learn more' : 'Aprende más' }}
                    </a>
                </div>

                {{-- Métodos de pago --}}
                <x-brevare.payment-methods presentation="product" class="mt-5" />

                {{-- Envío y retiro --}}
                <section id="envios" class="mt-5 scroll-mt-28" aria-labelledby="shipping-options-title">
                    <h2 id="shipping-options-title" class="sr-only">{{ $isEnglish ? 'Shipping and pickup options' : 'Opciones de envío y retiro' }}</h2>

                    @if ($product->isCourse())
                        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-sky-700 shadow-sm">
                                    <flux:icon name="document-arrow-down" class="size-5" />
                                </span>
                                <div>
                                    <p class="font-semibold">{{ $isEnglish ? 'Lifetime access' : 'Acceso de por vida' }}</p>
                                    <p class="mt-1 leading-5 text-sky-800">{{ $isEnglish ? 'The content will be available in your account after payment is confirmed.' : 'El contenido quedará disponible en tu cuenta después de confirmar el pago.' }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="grid gap-3 sm:grid-cols-2">
                            <article class="flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50/80 p-4">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-sky-700 shadow-sm">
                                    <flux:icon name="truck" class="size-5" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-semibold text-sky-800">{{ $isEnglish ? 'Fast delivery in Metro Lima' : 'Envíos rápidos a Lima Metropolitana' }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-sky-700">{{ $isEnglish ? 'Receive your order within 24 hours.' : 'Recibe tu pedido en 24 horas.' }}</p>
                                </div>
                            </article>
                            <article class="flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50/80 p-4">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-sky-700 shadow-sm">
                                    <flux:icon name="cube" class="size-5" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-semibold text-sky-800">{{ $isEnglish ? 'Shipping throughout Peru' : 'Envíos a todo el Perú' }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-sky-700">{{ $isEnglish ? 'Receive your order in 2 to 4 business days.' : 'Recibe tu pedido de 2 a 4 días hábiles.' }}</p>
                                </div>
                            </article>
                        </div>

                        @php
                            $pickupMessage = rawurlencode('Hola Brevare Perú, quiero coordinar un Retiro Express del producto '.$product->name.'.');
                            $pickupUrl = 'https://wa.me/'.$company['whatsapp'].'?text='.$pickupMessage;
                        @endphp
                        <div class="mt-3 rounded-xl border border-sky-200 bg-sky-50 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-sky-700 shadow-sm">
                                        <flux:icon name="map-pin" class="size-5" />
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-semibold text-sky-800">{{ $isEnglish ? 'Express pickup in 2 to 6 hours' : 'Retiro Express en 2 a 6 horas' }}</h3>
                                        <p class="mt-1 text-xs leading-5 text-sky-700">{{ $isEnglish ? 'Available at our 2 stores.' : 'Disponible en nuestras 2 tiendas.' }}</p>
                                    </div>
                                </div>
                                <a href="{{ $pickupUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex min-h-9 shrink-0 items-center justify-center gap-1.5 self-start rounded-full bg-[#ff9b31] px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-[#e88720]">
                                    <flux:icon name="clock" class="size-4" />
                                    {{ $isEnglish ? 'Express pickup' : 'Retiro Express' }}
                                </a>
                            </div>

                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                @foreach (['San Isidro, Lima', 'Chimbote, Áncash'] as $store)
                                    <div class="flex items-center gap-2 rounded-lg bg-white/75 px-3 py-2.5 text-xs font-medium text-sky-800">
                                        <flux:icon name="map-pin" class="size-4 shrink-0 text-sky-600" />
                                        {{ $store }}
                                    </div>
                                @endforeach
                            </div>

                            <a href="{{ $pickupUrl }}" target="_blank" rel="noopener noreferrer"
                                class="mt-3 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-full bg-sky-800 px-4 text-xs font-semibold text-white transition hover:bg-sky-900 sm:max-w-sm">
                                {{ $isEnglish ? 'More store information' : 'Ver más información de las tiendas' }}
                                <flux:icon name="chevron-right" class="size-4" />
                            </a>
                        </div>
                    @endif
                </section>

                {{-- Mini carrito --}}
                @if ($cartItems->isNotEmpty())
                    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" x-data="{ open: true }">
                        <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between gap-2 px-5 py-4 text-left">
                            <span class="flex items-center gap-2 text-sm font-bold text-gray-900">
                                <flux:icon name="shopping-cart" class="size-4" /> {{ __('storefront.product.your_cart') }} ({{ $cartCount }})
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
                                            <p class="text-xs text-gray-500">{{ __('storefront.common.quantity') }} {{ $item->quantity }} × <x-brevare.price :amount="(float) $item->unit_price" size="xs" class="font-normal text-gray-500" /></p>
                                        </div>
                                        <span class="shrink-0 text-sm font-semibold text-gray-900"><x-brevare.price :amount="$item->lineSubtotal()" size="xs" class="font-semibold text-gray-900" /></span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-4">
                                <span class="text-sm text-gray-500">{{ __('storefront.common.subtotal') }}</span>
                                <span class="text-lg font-extrabold tracking-tight text-gray-900"><x-brevare.price :amount="$cartSubtotal" size="xs" class="font-extrabold text-gray-900" /></span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 px-5 pb-5">
                                <a href="{{ route('store.cart') }}"
                                    class="inline-flex items-center justify-center rounded-full border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                    {{ __('storefront.product.view_cart') }}
                                </a>
                                <a href="{{ route('checkout') }}"
                                    class="inline-flex items-center justify-center rounded-full bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                                    {{ __('storefront.product.finish_purchase') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Descripción --}}
        @if ($productDescription)
            <section class="mt-10 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="product-description-title">
                <h2 id="product-description-title" class="mb-3 text-xl font-bold tracking-tight text-gray-900">{{ __('storefront.product.description') }}</h2>
                <div class="leading-relaxed text-gray-600">{!! nl2br(e($productDescription)) !!}</div>
            </section>
        @endif

        {{-- Video y especificaciones --}}
        @php
            $specifications = collect([
                'Marca' => $product->brand?->name,
                'Categoría' => $product->category?->name,
                'SKU' => $currentVariant?->sku,
                'Peso' => $currentVariant?->weight ? number_format((float) $currentVariant->weight, 2, '.', '').' kg' : null,
                'Dimensiones' => $currentVariant && ($currentVariant->length || $currentVariant->width || $currentVariant->height)
                    ? collect([$currentVariant->length, $currentVariant->width, $currentVariant->height])->filter()->implode(' × ').' cm'
                    : null,
            ]);

            if ($currentVariant) {
                foreach ($currentVariant->attributeValues as $attributeValue) {
                    $name = $attributeValue->attribute?->name;
                    $value = $attributeValue->value?->value;

                    if (filled($name) && filled($value)) {
                        $specifications->put($name, $value);
                    }
                }
            }

            $specifications = $specifications->filter(fn ($value) => filled($value));
        @endphp
        @if ($product->video_url || $specifications->isNotEmpty())
            <div class="mt-6 grid gap-6 {{ $product->video_url && $specifications->isNotEmpty() ? 'lg:grid-cols-2 lg:items-stretch' : '' }}">
                @if ($product->video_url)
                    <section class="h-full overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="product-video-title">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 id="product-video-title" class="text-xl font-bold tracking-tight text-gray-900">{{ $isEnglish ? 'Product video' : 'Video del producto' }}</h2>
                            <span class="text-[10px] font-semibold uppercase tracking-[.12em] text-gray-400">Brevare</span>
                        </div>
                        <video src="{{ $product->video_url }}" controls controlsList="nodownload noremoteplayback" disablepictureinpicture autoplay muted preload="auto" playsinline oncontextmenu="return false" class="aspect-video w-full rounded-xl bg-gray-950 object-contain" aria-label="{{ $isEnglish ? 'Video of' : 'Video de' }} {{ $productName }}">
                            {{ $isEnglish ? 'Your browser cannot play this video.' : 'Tu navegador no puede reproducir este video.' }}
                        </video>
                    </section>
                @endif

                @if ($specifications->isNotEmpty())
                    <section class="h-full rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="product-specifications-title">
                        <h2 id="product-specifications-title" class="text-xl font-bold tracking-tight text-gray-900">{{ __('storefront.product.specifications') }}</h2>
                        <dl class="mt-4 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                            @foreach ($specifications as $label => $value)
                                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                                    <dt class="text-gray-600">{{ $label }}</dt>
                                    <dd class="text-right font-semibold text-gray-900">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif
            </div>
        @endif

        {{-- Opiniones y calificaciones --}}
        <section id="opiniones" class="mt-10 scroll-mt-28 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-gray-100 pb-5">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-900">{{ $isEnglish ? 'Customer reviews' : 'Opiniones de clientes' }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $isEnglish ? 'Your experience helps others choose.' : 'Tu experiencia ayuda a otras personas a elegir.' }}</p>
                </div>
                @if ($reviewCount > 0)
                    <div class="flex items-center gap-2" aria-label="Promedio de {{ number_format((float) $reviewAverage, 1) }} sobre 5, basado en {{ $reviewCount }} opiniones">
                        @php($filledReviewStars = min(5, max(0, (int) round((float) $reviewAverage))))
                        <span class="text-xl tracking-wide text-amber-500" aria-hidden="true">{{ str_repeat('★', $filledReviewStars).str_repeat('☆', 5 - $filledReviewStars) }}</span>
                        <span class="text-lg font-bold text-gray-900">{{ number_format((float) $reviewAverage, 1) }}</span>
                        <span class="text-sm text-gray-500">({{ $reviewCount }})</span>
                    </div>
                @endif
            </div>

            @auth
                @if ($hasReviewed)
                    <p class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">Gracias por compartir tu opinión sobre este producto.</p>
                @else
                    <form wire:submit="submitReview" class="mt-5 rounded-xl bg-amber-50/70 p-4 sm:p-5">
                        <label class="block text-sm font-semibold text-gray-900">Tu calificación</label>
                        <div class="mt-2 flex gap-1" role="group" aria-label="Elige de una a cinco estrellas">
                            @foreach (range(1, 5) as $star)
                                <button type="button" wire:click="$set('reviewRating', {{ $star }})"
                                    aria-label="{{ $star }} {{ $star === 1 ? 'estrella' : 'estrellas' }}"
                                    aria-pressed="{{ $reviewRating === $star ? 'true' : 'false' }}"
                                    class="rounded-md px-1 text-3xl leading-none transition hover:scale-110 focus-visible:outline-2 focus-visible:outline-amber-700 {{ $reviewRating >= $star ? 'text-amber-500' : 'text-gray-300' }}">★</button>
                            @endforeach
                        </div>
                        @error('reviewRating') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                        <label for="review-comment" class="mt-4 block text-sm font-semibold text-gray-900">Tu comentario</label>
                        <textarea id="review-comment" wire:model="reviewComment" rows="4" maxlength="1500" required
                            placeholder="Cuéntanos qué te pareció este producto…"
                            class="mt-2 w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm placeholder:text-gray-400 focus:border-amber-500 focus:ring-amber-500"></textarea>
                        @error('reviewComment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-gray-500">Publicarás con el nombre de tu cuenta.</p>
                            <button type="submit" wire:loading.attr="disabled" wire:target="submitReview"
                                class="inline-flex min-h-11 items-center justify-center rounded-full bg-gray-950 px-5 text-sm font-semibold text-white transition hover:bg-amber-800 disabled:cursor-wait disabled:opacity-60">
                                <span wire:loading.remove wire:target="submitReview">Publicar opinión</span>
                                <span wire:loading wire:target="submitReview">Publicando…</span>
                            </button>
                        </div>
                    </form>
                @endif
            @else
                <div class="mt-5 rounded-xl bg-amber-50/70 p-4 sm:flex sm:items-center sm:justify-between sm:gap-4">
                    <p class="text-sm text-gray-700">Inicia sesión para calificar y comentar este producto.</p>
                    <a href="{{ route('login') }}" class="mt-3 inline-flex min-h-10 items-center justify-center rounded-full bg-gray-950 px-4 text-sm font-semibold text-white transition hover:bg-amber-800 sm:mt-0">Iniciar sesión</a>
                </div>
            @endauth

            <div class="mt-5 divide-y divide-gray-100">
                @forelse ($reviews as $review)
                    <article class="py-5 first:pt-0 last:pb-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">{{ $review->user->name }}</h3>
                                <p class="mt-0.5 text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</p>
                            </div>
                            <span class="text-base tracking-wide text-amber-500" aria-label="{{ $review->rating }} de 5 estrellas">{{ str_repeat('★', $review->rating).str_repeat('☆', 5 - $review->rating) }}</span>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $review->comment }}</p>
                    </article>
                @empty
                    <p class="py-5 text-sm text-gray-500">Aún no hay opiniones. Sé la primera persona en compartir su experiencia.</p>
                @endforelse
            </div>
        </section>

        {{-- Productos relacionados --}}
        @if ($relatedProducts->isNotEmpty())
            <section class="mt-12">
                <h2 class="mb-5 text-lg font-bold tracking-tight text-gray-900">{{ __('storefront.product.related') }}</h2>
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
                <h2 class="mb-5 text-lg font-bold tracking-tight text-gray-900">{{ $isEnglish ? 'Recently viewed' : 'Visto recientemente' }}</h2>
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
