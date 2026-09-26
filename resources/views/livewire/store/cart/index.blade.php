<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-6 flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900">
            <flux:icon name="shopping-cart" class="size-6" /> Mi carrito
        </h1>

        @if ($items->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white py-16 text-center">
                <flux:icon name="shopping-cart" class="mx-auto mb-3 size-12 text-gray-300" />
                <p class="text-sm font-medium text-gray-700">Tu carrito está vacío.</p>
                <a href="{{ route('store.search') }}"
                    class="mt-4 inline-flex items-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                    Ir a la tienda
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
                                <p class="font-semibold text-gray-900">{{ $item->variant?->product?->name ?? 'Producto' }}</p>
                                <p class="mb-3 text-xs text-gray-500">
                                    SKU: {{ $item->variant?->sku }}
                                    · Proveedor: {{ $item->supplierVariant?->supplier?->business_name }}
                                </p>

                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center rounded-full border border-gray-300 bg-white">
                                        <button type="button"
                                            wire:click="$set('quantities.{{ $item->id }}', {{ max(1, (int) ($quantities[$item->id] ?? 1) - 1) }})"
                                            class="flex size-8 items-center justify-center rounded-l-full text-gray-600 transition hover:bg-gray-100">
                                            <flux:icon name="minus" class="size-3.5" />
                                        </button>
                                        <input type="number"
                                            wire:model.live.debounce.500ms="quantities.{{ $item->id }}"
                                            class="w-12 border-0 text-center text-sm font-medium text-gray-900 focus:outline-none focus:ring-0">
                                        <button type="button"
                                            wire:click="$set('quantities.{{ $item->id }}', {{ (int) ($quantities[$item->id] ?? 1) + 1 }})"
                                            class="flex size-8 items-center justify-center rounded-r-full text-gray-600 transition hover:bg-gray-100">
                                            <flux:icon name="plus" class="size-3.5" />
                                        </button>
                                    </div>

                                    <button class="rounded-full px-4 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                                        wire:click="updateQuantity({{ $item->id }})">
                                        Actualizar
                                    </button>

                                    <button wire:click="removeItem({{ $item->id }})"
                                        wire:confirm="¿Quitar este producto del carrito?"
                                        class="ml-auto inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50"
                                        aria-label="Quitar producto">
                                        <flux:icon name="trash" class="size-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="text-right">
                                <span class="text-base font-bold text-gray-900">S/ {{ number_format($item->lineSubtotal(), 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Resumen --}}
                <div class="h-fit rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:sticky lg:top-24">
                    <h2 class="font-bold text-gray-900">Resumen</h2>

                    <dl class="mt-4 space-y-3 border-t border-gray-100 pt-4 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Subtotal</dt>
                            <dd class="font-semibold text-gray-900">S/ {{ number_format($subtotal, 2) }}</dd>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <dt>Envío y cupones</dt>
                            <dd>Se calculan al pagar</dd>
                        </div>
                    </dl>

                    <a href="{{ route('checkout') }}"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                        Ir a pagar <flux:icon name="arrow-right" class="size-4" />
                    </a>
                    <a href="{{ route('store.search') }}"
                        class="mt-2 block w-full rounded-full px-6 py-2.5 text-center text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                        Seguir comprando
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>