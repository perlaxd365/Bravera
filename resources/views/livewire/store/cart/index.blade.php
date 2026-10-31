<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-6 flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900">
            <flux:icon name="shopping-cart" class="size-6" /> {{ __('storefront.cart.title') }}
        </h1>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($items->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white py-16 text-center">
                <flux:icon name="shopping-cart" class="mx-auto mb-3 size-12 text-gray-300" />
                <p class="text-sm font-medium text-gray-700">{{ __('storefront.cart.empty') }}</p>
                <a href="{{ route('store.search') }}"
                    class="mt-4 inline-flex items-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                    {{ __('storefront.cart.go_store') }}
                </a>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Ítems --}}
                <div class="space-y-3 lg:col-span-2">
                    @foreach ($items as $item)
                        <div class="flex gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm" wire:key="cart-item-{{ $item->id }}">
                            @php
                                $primary = $item->variant?->images->firstWhere('is_primary', true) ?? $item->variant?->images->first();
                            @endphp
                            <div class="shrink-0">
                                @if ($primary)
                                    <img src="{{ $primary->secure_url ?? $primary->url }}" class="size-24 rounded-xl object-cover" alt="">
                                @else
                                    <div class="flex size-24 items-center justify-center rounded-xl bg-gray-100">
                                        <flux:icon name="photo" class="size-8 text-gray-300" />
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900">{{ $item->variant?->product?->localized('name') ?? $item->variant?->product?->name ?? __('storefront.common.product') }}</p>
                                <p class="mb-3 text-xs text-gray-500">
                                    SKU: {{ $item->variant?->sku }}
                                </p>

                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center rounded-full border border-gray-300 bg-white">
                                        <button type="button"
                                            wire:click="adjustQuantity({{ $item->id }}, -1)"
                                            class="flex size-8 items-center justify-center rounded-l-full text-gray-600 transition hover:bg-gray-100">
                                            <flux:icon name="minus" class="size-3.5" />
                                        </button>
                                        <input type="number"
                                            min="1"
                                            wire:model="quantities.{{ $item->id }}"
                                            wire:change="updateQuantity({{ $item->id }})"
                                            class="w-12 border-0 text-center text-sm font-medium text-gray-900 focus:outline-none focus:ring-0">
                                        <button type="button"
                                            wire:click="adjustQuantity({{ $item->id }}, 1)"
                                            class="flex size-8 items-center justify-center rounded-r-full text-gray-600 transition hover:bg-gray-100">
                                            <flux:icon name="plus" class="size-3.5" />
                                        </button>
                                    </div>

                                    <button wire:click="removeItem({{ $item->id }})"
                                        wire:confirm="{{ __('storefront.cart.remove_confirm') }}"
                                        class="ml-auto inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50"
                                        aria-label="{{ __('storefront.cart.remove') }}">
                                        <flux:icon name="trash" class="size-4" />
                                    </button>
                                </div>
                                @if ($item->variant?->hasWholesalePrice() && ! $item->variant->appliesWholesalePrice((int) $item->quantity))
                                    <p class="mt-2 text-xs font-medium text-emerald-700">
                                        {{ __('storefront.cart.add_more_wholesale', ['count' => max(0, (int) $item->variant->wholesale_min_quantity - (int) $item->quantity)]) }}
                                    </p>
                                @endif
                            </div>

                            <div class="text-right">
                                @php
                                    $wholesaleActive = $item->variant?->appliesWholesalePrice((int) $item->quantity) ?? false;
                                    $retailUnitPrice = (float) ($item->variant?->sale_price ?? $item->unit_price);
                                    $regularUnitPrice = $wholesaleActive
                                        ? $retailUnitPrice
                                        : max((float) ($item->variant?->compare_price ?? $retailUnitPrice), $retailUnitPrice);
                                    $lineRegularPrice = $regularUnitPrice * (int) $item->quantity;
                                    $discountPercent = (1 - ((float) $item->unit_price / max(0.01, $regularUnitPrice))) * 100;
                                @endphp
                                @if ($wholesaleActive)
                                    <span class="mb-1 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                                        {{ __('storefront.cart.wholesale') }}
                                    </span>
                                @endif
                                @if ($regularUnitPrice > (float) $item->unit_price)
                                    <span class="block text-xs text-gray-400 line-through"><x-brevare.price :amount="$lineRegularPrice" size="xs" class="font-normal text-gray-400 line-through" :show-approximation="false" /></span>
                                    <span class="mb-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                        -{{ number_format($discountPercent, 0) }}%
                                    </span>
                                @endif
                                <span class="block text-xs text-gray-500">{{ __('storefront.cart.each', ['price' => \App\Support\Localization\StorefrontContext::current()->formatFromPen((float) $item->unit_price)]) }}</span>
                                <span class="block text-base font-bold text-gray-900"><x-brevare.price :amount="$item->lineSubtotal()" size="sm" class="font-bold text-gray-900" /></span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Resumen --}}
                <div class="h-fit rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:sticky lg:top-24">
                    <h2 class="font-bold text-gray-900">{{ __('storefront.cart.summary') }}</h2>

                    <dl class="mt-4 space-y-3 border-t border-gray-100 pt-4 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('storefront.cart.subtotal_discounts') }}</dt>
                            <dd class="font-semibold text-gray-900"><x-brevare.price :amount="$subtotal" size="sm" class="font-semibold text-gray-900" /></dd>
                        </div>
                        @if ($productDiscount > 0)
                            <div class="flex justify-between text-emerald-700">
                                <dt>{{ __('storefront.cart.product_discounts') }}</dt>
                                <dd class="font-semibold">- <x-brevare.price :amount="$productDiscount" size="xs" class="font-semibold text-emerald-700" /></dd>
                            </div>
                        @endif
                        <div class="flex justify-between text-gray-500">
                            <dt>{{ __('storefront.cart.shipping_coupons') }}</dt>
                            <dd>{{ __('storefront.cart.calculated_checkout') }}</dd>
                        </div>
                    </dl>

                    @if ($storefront->isInternational())
                        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-3 py-3 text-xs leading-5 text-sky-900" role="note">
                            <p class="font-semibold">{{ __('storefront.prices.charged_in_soles', ['amount' => $storefront->chargedFromPen($subtotal)]) }}</p>
                            <p class="mt-1 text-sky-700">{{ __('storefront.prices.exchange_rate', ['rate' => number_format($storefront->exchangeRate, 2)]) }}</p>
                        </div>
                    @endif

                    <a href="{{ route('checkout') }}"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                        {{ __('storefront.common.go_to_checkout') }} <flux:icon name="arrow-right" class="size-4" />
                    </a>
                    <a href="{{ route('store.search') }}"
                        class="mt-2 block w-full rounded-full px-6 py-2.5 text-center text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                        {{ __('storefront.common.continue_shopping') }}
                    </a>
                </div>
            </div>

            @if ($recommendations->isNotEmpty())
                <section class="mt-10 border-t border-gray-200 pt-8" aria-labelledby="complete-purchase-title">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[.18em] text-amber-800/75">{{ __('storefront.cart.recommendation_eyebrow') }}</p>
                            <h2 id="complete-purchase-title" class="mt-1 text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">
                                {{ __('storefront.cart.complete_title') }}
                            </h2>
                            <p class="mt-1 text-sm leading-6 text-gray-600">{{ __('storefront.cart.complete_copy') }}</p>
                        </div>
                        <a href="{{ route('store.search') }}" class="text-sm font-semibold text-gray-700 transition hover:text-amber-800">
                            {{ __('storefront.cart.view_more') }} <span aria-hidden="true">↗</span>
                        </a>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($recommendations as $recommendation)
                            @php
                                $product = $recommendation['product'];
                                $variant = $recommendation['variant'];
                                $price = (float) $variant->unitPriceForQuantity(1);
                                $comparePrice = (float) ($variant->compare_price ?? 0);
                            @endphp
                            <article wire:key="recommended-{{ $product->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
                                <a href="{{ route('store.product', ['slug' => $product->slug]) }}" class="block">
                                    <div class="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-[#f7f5ef] p-3">
                                        @if ($recommendation['image'])
                                            <img src="{{ $recommendation['image'] }}" alt="{{ $product->name }}"
                                                loading="lazy" decoding="async"
                                                class="h-full w-full object-contain transition duration-500 hover:scale-[1.03]">
                                        @else
                                            <flux:icon name="photo" class="size-10 text-gray-300" />
                                        @endif
                                        @if ($comparePrice > $price && $price > 0)
                                            <span class="absolute left-3 top-3 rounded-full bg-rose-600 px-2.5 py-1 text-[10px] font-bold text-white">
                                                -{{ round((1 - ($price / $comparePrice)) * 100) }}%
                                            </span>
                                        @endif
                                    </div>
                                </a>
                                <div class="flex flex-1 flex-col p-4">
                                    <p class="text-[10px] font-bold uppercase tracking-[.12em] text-gray-400">
                                        {{ $product->category?->name }}
                                    </p>
                                    <a href="{{ route('store.product', ['slug' => $product->slug]) }}"
                                        class="mt-1 line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-gray-900 hover:text-amber-900">
                                        {{ $product->localized('name') ?? $product->name }}
                                    </a>
                                    <div class="mt-3 flex flex-wrap items-baseline gap-2">
                                        <x-brevare.price :amount="$price" size="md" class="font-bold text-gray-950" />
                                        @if ($comparePrice > $price)
                                            <x-brevare.price :amount="$comparePrice" size="xs" class="font-normal text-gray-400 line-through" :show-approximation="false" />
                                        @endif
                                    </div>
                                    <button type="button" wire:click="addRecommendedProduct({{ $variant->id }})"
                                        wire:loading.attr="disabled" wire:target="addRecommendedProduct"
                                        class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-gray-950 px-4 text-sm font-semibold text-white transition hover:bg-amber-800 disabled:cursor-wait disabled:opacity-60">
                                        <flux:icon name="plus" class="size-4" />
                                        <span wire:loading.remove wire:target="addRecommendedProduct">{{ app()->getLocale() === 'en' ? 'Add' : 'Agregar' }}</span>
                                        <span wire:loading wire:target="addRecommendedProduct">{{ app()->getLocale() === 'en' ? 'Adding…' : 'Agregando…' }}</span>
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
</div>
