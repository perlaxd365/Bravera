<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-6 flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900">
            <flux:icon name="credit-card" class="size-6" /> Finalizar compra
        </h1>

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- Columna principal --}}
            <div class="space-y-4 lg:col-span-2">

                {{-- 1. Dirección de entrega --}}
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                        <span class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">1</span>
                        Dirección de entrega
                    </h2>

                    @if ($addresses->isNotEmpty())
                        @foreach ($addresses as $address)
                            <label for="addr-{{ $address->id }}"
                                class="mb-2 flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 transition hover:border-gray-300">
                                <input type="radio" name="address"
                                    value="{{ $address->id }}" id="addr-{{ $address->id }}"
                                    wire:model="selectedAddressId"
                                    wire:change="selectAddress({{ $address->id }})"
                                    @if ($address->is_default) checked @endif
                                    class="mt-1 size-4 shrink-0 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                                <span class="w-full">
                                    <span class="flex items-center justify-between gap-2">
                                        <strong class="text-sm text-gray-900">{{ $address->full_name }}</strong>
                                        @if ($address->is_default)
                                            <span class="rounded-full border border-gray-300 bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-600">Principal</span>
                                        @endif
                                    </span>
                                    <span class="mt-0.5 block text-xs leading-relaxed text-gray-500">
                                        {{ $address->address }}
                                        @if ($address->reference) ({{ $address->reference }}) @endif
                                        · {{ $address->locationLabel() }}
                                        · {{ $address->phone }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    @else
                        <div class="mb-4 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-800">
                            Aún no tienes direcciones. Agrega una para continuar.
                        </div>
                    @endif

                    @if (!$showNewAddress)
                        <button class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-900 transition hover:bg-gray-50"
                            wire:click="openNewAddress">
                            <flux:icon name="plus" class="size-4" /> Nueva dirección
                        </button>
                    @endif

                    @if ($showNewAddress)
                        <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-5" wire:key="new-address">
                            <h3 class="mb-4 font-bold text-gray-900">Nueva dirección</h3>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-700">Nombre completo</label>
                                    <input type="text" wire:model="newFullName"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                    @error('newFullName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-700">Teléfono</label>
                                    <input type="text" wire:model="newPhone"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                    @error('newPhone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-700">Departamento</label>
                                    <div class="relative">
                                        <select wire:model.live="newDepartmentId"
                                            class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                            <option value="">Seleccionar</option>
                                            @foreach ($departments as $dept)
                                                <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-700">Provincia</label>
                                    <div class="relative">
                                        <select wire:model.live="newProvinceId"
                                            class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                            <option value="">Seleccionar</option>
                                            @foreach ($provinces as $prov)
                                                <option value="{{ $prov['id'] }}">{{ $prov['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-700">Distrito</label>
                                    <div class="relative">
                                        <select wire:model.live="newDistrictId"
                                            class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                            <option value="">Seleccionar</option>
                                            @foreach ($districts as $dist)
                                                <option value="{{ $dist['id'] }}">{{ $dist['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                                    </div>
                                    @error('newDistrictId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-700">Dirección</label>
                                    <input type="text" wire:model="newAddress" placeholder="Av., Calle, Jr. y número"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                    @error('newAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-700">Referencia</label>
                                    <input type="text" wire:model="newReference" placeholder="Opcional"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                </div>
                                <div class="flex gap-2 sm:col-span-2">
                                    <button wire:click="saveNewAddress"
                                        class="rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                                        Guardar y usar
                                    </button>
                                    <button wire:click="$set('showNewAddress', false)"
                                        class="rounded-full border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @error('selectedAddressId')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </section>

                {{-- 2. Envío --}}
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                        <span class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">2</span>
                        Envío
                    </h2>
                    @if ($quote)
                        <div class="flex items-center gap-2 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-700">
                            <flux:icon name="truck" class="size-5 text-gray-500" />
                            Costo de envío estimado: <strong>S/ {{ number_format($shippingTotal, 2) }}</strong>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Selecciona una dirección para calcular el envío.</p>
                    @endif
                </section>

                {{-- 3. Cupón de descuento --}}
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                        <span class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">3</span>
                        Cupón de descuento
                    </h2>
                    @if ($couponApplied)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-green-50 px-4 py-3">
                            <div>
                                <p class="flex items-center gap-1.5 text-sm font-semibold text-green-800">
                                    <flux:icon name="tag" class="size-4" /> {{ $couponApplied['coupon']->code }}
                                    — Descuento: S/ {{ number_format($discount, 2) }}
                                </p>
                                <p class="text-xs text-green-700">{{ $couponApplied['description'] }}</p>
                            </div>
                            <button wire:click="removeCoupon"
                                class="shrink-0 rounded-full border border-red-300 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                Quitar
                            </button>
                        </div>
                    @else
                        <div class="flex gap-2">
                            <input type="text" wire:model="couponCode" placeholder="Ingresa tu código (ej: BRAVERA10)"
                                class="w-full rounded-full border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <button wire:click="applyCoupon"
                                class="shrink-0 rounded-full border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-900 transition hover:bg-gray-50">
                                Aplicar
                            </button>
                        </div>
                        @if ($couponError)
                            <p class="mt-2 flex items-center gap-1.5 text-sm text-red-600">
                                <flux:icon name="exclamation-circle" class="size-4" /> {{ $couponError }}
                            </p>
                        @endif
                    @endif
                </section>

                {{-- 4. Pago --}}
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                        <span class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">4</span>
                        Método de pago
                    </h2>
                    @foreach ($paymentMethods as $key => $label)
                        <label for="pm-{{ $key }}"
                            class="mb-2 flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 transition hover:border-gray-300">
                            <input type="radio" name="payment_method"
                                id="pm-{{ $key }}" value="{{ $key }}"
                                wire:model="paymentMethod"
                                class="size-4 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            <span class="text-sm font-medium text-gray-900">{{ $label }}</span>
                        </label>
                    @endforeach
                    <div class="mt-2 flex items-center gap-2 rounded-xl bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        <flux:icon name="information-circle" class="size-5" />
                        Modo demostración: no se realizará un cargo real.
                    </div>
                </section>
            </div>

            {{-- Resumen --}}
            <aside class="h-fit space-y-4 lg:sticky lg:top-24">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-gray-900">Resumen del pedido</h2>

                    <div class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
                        @foreach ($items as $item)
                            <div class="flex justify-between gap-3">
                                <span class="min-w-0 truncate text-gray-700">
                                    {{ $item->quantity }} × {{ $item->variant?->product?->name }}
                                </span>
                                <strong class="shrink-0 text-gray-900">S/ {{ number_format($item->lineSubtotal(), 2) }}</strong>
                            </div>
                        @endforeach
                    </div>

                    <dl class="mt-4 space-y-1.5 border-t border-gray-100 pt-4 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Subtotal</dt>
                            <dd class="text-gray-900">S/ {{ number_format($subtotal, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Envío</dt>
                            <dd class="text-gray-900">S/ {{ number_format($shippingTotal, 2) }}</dd>
                        </div>
                        @if ($discount > 0)
                            <div class="flex justify-between text-green-700">
                                <dt>Cupón</dt>
                                <dd>- S/ {{ number_format($discount, 2) }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                        <span class="font-bold text-gray-900">Total</span>
                        <span class="text-2xl font-extrabold tracking-tight text-gray-900">S/ {{ number_format($total, 2) }}</span>
                    </div>

                    <div class="mt-4">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">Notas (opcional)</label>
                        <textarea wire:model="notes" rows="2"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10"></textarea>
                    </div>

                    <button wire:click="placeOrder"
                        wire:loading.attr="disabled" @if (!$selectedAddressId) disabled @endif
                        class="mt-4 inline-flex w-full items-center justify-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                        <span wire:loading.remove>Realizar pedido y pagar</span>
                        <span wire:loading>Procesando...</span>
                    </button>
                    <a href="{{ route('store.cart') }}"
                        class="mt-2 block w-full rounded-full px-6 py-2.5 text-center text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                        Volver al carrito
                    </a>
                </div>
            </aside>
        </div>
    </div>
</div>