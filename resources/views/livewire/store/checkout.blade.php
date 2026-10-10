<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
    data-culqi-public-key="{{ $culqiPublicKey ?? '' }}"
    data-order-status-url-template="{{ route('store.order.placed', ['order' => '__ORDER__']) }}"
    data-orders-url="{{ route('account.orders') }}">
    <h1 class="mb-6 flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900">
        <flux:icon name="credit-card" class="size-6" /> Finalizar compra
    </h1>
    <div id="culqi-processing-overlay"
        style="
        display: none;
        position: fixed;
        inset: 0;
        z-index: 999999;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(5px);
        align-items: center;
        justify-content: center;
    ">
        <div
            style="
            width: min(90%, 420px);
            background: white;
            border-radius: 16px;
            padding: 35px 30px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
        ">
            <div id="culqi-processing-spinner"
                style="
                width: 55px;
                height: 55px;
                display: block;
                margin: 0 auto 20px;
                border: 5px solid #e5e7eb;
                border-top-color: #143c64;
                border-radius: 50%;
                animation: culqi-spin 0.8s linear infinite;
            ">
            </div>

            <h3 id="culqi-processing-title"
                style="
                margin: 0 0 10px;
                font-size: 20px;
                font-weight: 700;
                color: #111827;
            ">
                Procesando tu pago
            </h3>

            <p id="culqi-processing-description"
                style="
                margin: 0;
                color: #6b7280;
                font-size: 14px;
                line-height: 1.5;
            ">
                Estamos confirmando tu pago.<br>
                Por favor, no cierres ni recargues esta página.
            </p>

            <div id="culqi-processing-recovery" style="display: none; margin-top: 20px;">
                <p style="margin: 0 0 14px; color: #92400e; font-size: 14px; line-height: 1.5;">
                    No vuelvas a intentar el pago todavía. Revisa el estado de este pedido para evitar un cobro duplicado.
                </p>
                <a id="culqi-processing-order-link" href="{{ route('account.orders') }}"
                    style="display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; background: #111827; padding: 10px 18px; color: white; font-size: 14px; font-weight: 600; text-decoration: none;">
                    Ver el estado de mi pedido
                </a>
            </div>
        </div>
    </div>

    <style>
        @keyframes culqi-spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Columna principal --}}
        <div class="space-y-4 lg:col-span-2">

            {{-- 1. Dirección de entrega --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                    <span
                        class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">1</span>
                    Dirección de entrega
                </h2>

                @if ($addresses->isNotEmpty())
                    @foreach ($addresses as $address)
                        <label for="addr-{{ $address->id }}"
                            class="mb-2 flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 transition hover:border-gray-300">
                            <input type="radio" name="address" value="{{ $address->id }}"
                                id="addr-{{ $address->id }}" wire:model="selectedAddressId"
                                wire:change="selectAddress({{ $address->id }})"
                                @if ($address->is_default) checked @endif
                                class="mt-1 size-4 shrink-0 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            <span class="w-full">
                                <span class="flex items-center justify-between gap-2">
                                    <strong class="text-sm text-gray-900">{{ $address->full_name }}</strong>
                                    @if ($address->is_default)
                                        <span
                                            class="rounded-full border border-gray-300 bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-600">Principal</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-gray-500">
                                    {{ $address->address }}
                                    @if ($address->reference)
                                        ({{ $address->reference }})
                                    @endif
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
                    <button
                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-900 transition hover:bg-gray-50"
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
                                @error('newFullName')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-gray-700">Teléfono</label>
                                <input type="text" wire:model="newPhone"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                @error('newPhone')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
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
                                    <flux:icon name="chevron-down" variant="mini"
                                        class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
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
                                    <flux:icon name="chevron-down" variant="mini"
                                        class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
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
                                    <flux:icon name="chevron-down" variant="mini"
                                        class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                                </div>
                                @error('newDistrictId')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold text-gray-700">Dirección</label>
                                <input type="text" wire:model="newAddress" placeholder="Av., Calle, Jr. y número"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                @error('newAddress')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
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
                    <span
                        class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">2</span>
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
                    <span
                        class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">3</span>
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
                        <input type="text" wire:model="couponCode" placeholder="Ingresa tu código (ej: BREVARE10)"
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
                    <span
                        class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">4</span>
                    Método de pago
                </h2>

                @if ($usesProviderModal)
                    {{-- Culqi muestra aquí sus medios habilitados, sin salir
                         del checkout de Brevare. --}}
                    @if ($culqiPublicKey)
                        <div class="rounded-xl border border-gray-200 bg-gray-50/60 px-4 py-4">
                            <p class="flex items-start gap-3 text-sm text-gray-700">
                                <flux:icon name="credit-card" class="mt-0.5 size-5 shrink-0" />
                                <span>
                                    El formulario seguro de Culqi aparecerá aquí. Podrás elegir entre
                                    tarjeta, Yape y los demás medios disponibles.
                                </span>
                            </p>
                        </div>
                        <p class="mt-3 flex items-start gap-2 text-xs text-gray-500">
                            <flux:icon name="lock-closed" class="size-4 shrink-0" />
                            Tu tarjeta se procesa dentro del formulario de Culqi. No almacenamos el número completo.
                        </p>

                    @else
                        {{-- Sin clave pública no hay checkout que abrir: decirlo aquí evita
                             prometer un botón de pago que no se puede usar. --}}
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            El pago con tarjeta no está disponible en este momento. Escríbenos y coordinamos tu compra.
                        </div>
                    @endif
                @elseif ($paymentMethods === [])
                    <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
                        No hay métodos de pago disponibles en este momento. Escríbenos y coordinamos tu compra.
                    </div>
                @else
                    @foreach ($paymentMethods as $key => $label)
                        <label for="pm-{{ $key }}"
                            class="mb-2 flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition {{ $paymentMethod === $key ? 'border-gray-900 bg-gray-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                            {{-- .live es imprescindible: sin él el servidor no conoce el
                                 método elegido hasta una acción, y los campos del medio
                                 anterior se quedan en pantalla. --}}
                            <input type="radio" name="payment_method" id="pm-{{ $key }}"
                                value="{{ $key }}" wire:model.live="paymentMethod"
                                class="size-4 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            <span class="text-sm font-medium text-gray-900">{{ $label }}</span>
                        </label>
                    @endforeach

                    <p class="mt-3 flex items-start gap-2 text-xs text-gray-500">
                        <flux:icon name="information-circle" class="size-4 shrink-0" />
                        Modo demostración: no se realizará un cargo real.
                    </p>
                @endif
            </section>
        </div>

        {{-- Resumen --}}
        <aside class="h-fit space-y-4 lg:sticky lg:top-24">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="font-bold text-gray-900">Resumen del pedido</h2>

                <div class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
                    @foreach ($summaryItems as $item)
                        <div class="flex justify-between gap-3">
                            <div class="min-w-0">
                                <span class="block truncate text-gray-700">
                                    {{ $item['quantity'] }} × {{ $item['name'] }}
                                </span>
                                @if ($item['regularSubtotal'] > $item['subtotal'])
                                    <span class="text-xs text-gray-400 line-through">S/ {{ number_format($item['regularSubtotal'], 2) }}</span>
                                    <span class="ml-1 text-xs font-semibold text-emerald-700">-{{ number_format($item['discountPercent'], 0) }}%</span>
                                @endif
                            </div>
                            <strong class="shrink-0 text-gray-900">S/ {{ number_format($item['subtotal'], 2) }}</strong>
                        </div>
                    @endforeach
                </div>

                <dl class="mt-4 space-y-1.5 border-t border-gray-100 pt-4 text-sm">
                    @if ($productDiscount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Subtotal antes de descuentos</dt>
                            <dd class="text-gray-500 line-through">S/ {{ number_format($regularSubtotal, 2) }}</dd>
                        </div>
                        <div class="flex justify-between text-emerald-700">
                            <dt>Descuentos de productos</dt>
                            <dd>- S/ {{ number_format($productDiscount, 2) }}</dd>
                        </div>
                    @endif
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
                    <span class="text-2xl font-extrabold tracking-tight text-gray-900">S/
                        {{ number_format($total, 2) }}</span>
                </div>

                <div class="mt-4">
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700">Notas (opcional)</label>
                    <textarea wire:model="notes" rows="2"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10"></textarea>
                </div>

                @if ($awaitingPayment)
                    {{-- El pedido ya existe con su precio congelado y su stock
                         reservado; lo que falta es el pago. Sin este aviso el
                         comprador vería un carrito vacío y pensaría que se le
                         está cobrando 0.00. --}}
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="font-semibold">Tu pedido {{ $pendingOrderNumber }} está reservado y esperando el
                            pago.</p>
                        <p class="mt-1 text-amber-800">
                            Vuelve a abrir el formulario de Culqi para completarlo. Lo dejamos reservado un tiempo;
                            si no lo pagas, se libera automáticamente.
                        </p>
                    </div>
                @endif

                @if ($settledOrderNumber)
                    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                        <p class="font-semibold text-gray-900">El pedido {{ $settledOrderNumber }} ya está cerrado.
                        </p>
                        <p class="mt-1">
                            No se puede volver a pagar desde aquí.
                            <a href="{{ route('account.orders') }}" class="font-semibold underline">Ver mis
                                pedidos</a>
                        </p>
                    </div>
                @endif

                @if ($usesProviderModal && !$culqiPublicKey)
                    {{-- Sin clave pública el modal no se puede abrir. Se avisa en
                         lugar de dejar un botón que crearía un pedido y
                         reservaría stock sin dar forma de pagarlo. --}}
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        El pago con Culqi no está disponible. Escríbenos para atender tu pedido.
                    </div>
                @endif

                @if ($usesProviderModal && $culqiPublicKey && !$settledOrderNumber)
                    @if ($culqiSession === [])
                        <button wire:click="startCulqiCheckout" wire:loading.attr="disabled"
                            wire:target="startCulqiCheckout" @if (!$selectedAddressId) disabled @endif
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                            <span wire:loading.remove wire:target="startCulqiCheckout"
                                class="inline-flex items-center gap-2">
                                <flux:icon name="lock-closed" class="size-4" />
                                Continuar al pago
                            </span>
                            <span wire:loading wire:target="startCulqiCheckout">Cargando Culqi...</span>
                        </button>
                    @elseif (!$culqiFormOpened && $paymentStage === 'idle')
                        <button wire:click="startCulqiCheckout" wire:loading.attr="disabled"
                            wire:target="startCulqiCheckout"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                            <span wire:loading.remove wire:target="startCulqiCheckout">Continuar al pago</span>
                            <span wire:loading wire:target="startCulqiCheckout">Cargando Culqi...</span>
                        </button>
                    @endif
                @elseif (!$usesProviderModal && !$settledOrderNumber)
                    <button wire:click="placeOrder" wire:loading.attr="disabled"
                        @if (!$selectedAddressId) disabled @endif
                        class="mt-4 inline-flex w-full items-center justify-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                        <span wire:loading.remove>Realizar pedido y pagar</span>
                        <span wire:loading>Procesando...</span>
                    </button>
                @endif
                <button type="button" wire:click="returnToCart" wire:loading.attr="disabled"
                    class="mt-2 block w-full rounded-full px-6 py-2.5 text-center text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                    Volver al carrito
                </button>
            </div>
        </aside>
    </div>

    @if ($usesProviderModal && $culqiSession !== [])
        <div id="culqi-payment-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-gray-950/60 p-2 backdrop-blur-sm sm:p-4"
            role="dialog" aria-modal="true" aria-labelledby="culqi-payment-title">
            <div class="flex max-h-[calc(100dvh-1rem)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 sm:max-h-[calc(100dvh-2rem)]">
                <header class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-7">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Pago seguro</p>
                        <h2 id="culqi-payment-title" class="mt-1 text-xl font-bold tracking-tight text-gray-950">Completa tu pago</h2>
                        <p class="mt-1 text-sm text-gray-500">Elige tarjeta, Yape u otro medio disponible.</p>
                    </div>
                    <button type="button" wire:click="cancelCulqiCheckout" aria-label="Cerrar y conservar el carrito"
                        class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/20">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </header>
                <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3 sm:overflow-hidden sm:px-6 sm:py-5">
                    <div id="culqi-container" wire:ignore class="w-full"></div>
                </div>
                <footer class="flex flex-col-reverse items-center justify-between gap-2 border-t border-gray-100 bg-gray-50/80 px-5 py-3 sm:flex-row sm:px-7">
                    <p class="flex items-center gap-2 text-xs text-gray-500"><flux:icon name="lock-closed" class="size-4" />Pago protegido por Culqi</p>
                    @if ($paymentStage === 'idle')
                        <button type="button" wire:click="cancelCulqiCheckout" wire:loading.attr="disabled"
                            class="rounded-full px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200/70">
                            Cancelar y conservar mi carrito
                        </button>
                    @endif
                </footer>
            </div>
        </div>
    @endif
</div>
</div>
</div>
