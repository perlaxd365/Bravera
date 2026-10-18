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

            {{-- 1. Dirección de recojo --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                    <span
                        class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">1</span>
                    Dirección de recojo
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

                @if ($selectedAddressId)
                    <div id="shalom-picker" class="mt-6 border-t border-gray-100 pt-5"
                        data-agencies-url="{{ $shalomAgenciesUrl }}"
                        data-maps-key="{{ $googleMapsKey }}"
                        data-selected-id="{{ $selectedShalomAgencyId }}"
                        data-department="{{ $pickupDepartment }}"
                        data-province="{{ $pickupProvince }}"
                        data-district="{{ $pickupDistrict }}">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-red-100 bg-white px-2 shadow-sm">
                                    <x-shalom-wordmark class="h-4 w-auto" />
                                </span>
                                <div>
                                    <h3 class="flex items-center gap-2 text-sm font-bold text-gray-900">
                                        <flux:icon name="building-storefront" class="size-4 text-emerald-600" />
                                        Agencia de recojo Shalom
                                    </h3>
                                    <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                        Powered by Shalom
                                    </p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                                Regular
                            </span>
                        </div>

                        <div class="mt-3 rounded-xl border border-red-100 bg-red-50/60 px-4 py-3">
                            <p class="text-xs leading-relaxed text-gray-700">
                                Estamos afiliados a <strong class="text-[#E30613]">Shalom</strong>.
                                Elegimos automáticamente la agencia más cercana a tu dirección; puedes
                                cambiarla desde la lista o buscando otra ciudad.
                            </p>
                            <p class="mt-1.5 flex items-start gap-1.5 text-xs leading-relaxed text-gray-600">
                                <flux:icon name="key" class="mt-0.5 size-3.5 shrink-0 text-emerald-600" />
                                Una vez confirmado el pago te daremos un código de 4 dígitos para recoger
                                tu pedido en la agencia.
                            </p>
                        </div>

                        <div class="relative mt-4">
                            <flux:icon name="magnifying-glass"
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                            <input id="shalom-search" type="search" autocomplete="off"
                                placeholder="Buscar otra ciudad, distrito o dirección…"
                                class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/15">
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                            <div id="shalom-agency-list" wire:ignore
                                class="max-h-[360px] space-y-2 overflow-y-auto pr-1"></div>
                            <div id="shalom-map" wire:ignore
                                class="h-[320px] overflow-hidden rounded-2xl border border-gray-200 bg-gray-100"></div>
                        </div>

                        <p id="shalom-empty" class="mt-3 hidden rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800">
                            No encontramos agencias con esa búsqueda. Prueba con otro distrito o ciudad.
                        </p>
                        <p id="shalom-map-fallback" class="mt-3 hidden text-xs text-gray-500">
                            No se pudo cargar el mapa. La dirección de la agencia sigue disponible en la lista.
                        </p>
                        <p class="mt-2 text-[11px] leading-relaxed text-gray-500">
                            Los pines ámbar y los números indican agencias con la misma ubicación
                            referencial del distrito; toca un pin para ver todas las agencias de ese punto.
                        </p>
                    </div>
                @endif
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
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-gray-600">
                        <flux:icon name="clock" class="size-4 text-emerald-600" />
                        @if ($deliveryDays)
                            Tiempo de entrega estimado:
                            <strong class="text-gray-900">
                                {{ $deliveryDays }} {{ $deliveryDays === 1 ? 'día hábil' : 'días hábiles' }}
                            </strong>
                        @else
                            Tiempo de entrega: <strong class="text-gray-900">por confirmar</strong>
                        @endif
                    </p>
                    @if ($deliveryDays)
                        <p class="mt-1 text-xs text-gray-500">
                            Si tu pedido incluye productos de varios proveedores, mostramos el plazo más largo.
                        </p>
                    @endif
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

                @if (count($availableGateways) > 0)
                    <div class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup" aria-label="Pasarela de pago">
                        @foreach ($availableGateways as $gatewayKey => $gateway)
                            <label for="gateway-{{ $gatewayKey }}"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition {{ $gatewayChoice === $gatewayKey ? 'border-gray-900 bg-gray-50 ring-1 ring-gray-900/10' : 'border-gray-200 hover:border-gray-300' }}">
                                <input id="gateway-{{ $gatewayKey }}" type="radio" name="payment_gateway"
                                    value="{{ $gatewayKey }}" wire:model.live="gatewayChoice"
                                    @disabled(in_array($paymentStage, ['processing', 'pending', 'paid'], true))
                                    class="mt-0.5 size-4 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-900">{{ $gateway['title'] }}</span>
                                    <span class="mt-0.5 block text-xs leading-5 text-gray-500">
                                        {{ $gateway['description'] }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                @if ($awaitingPayment)
                    <p class="mb-3 text-xs leading-5 text-gray-500">
                        Tu pedido está reservado esperando el pago. Si eliges otra pasarela, liberamos
                        esa reserva y tus productos vuelven al carrito automáticamente.
                    </p>
                @endif

                @if ($gatewayChoice === '')
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50/70 px-4 py-4">
                        <p class="flex items-start gap-3 text-sm text-gray-600">
                            <flux:icon name="cursor-arrow-rays" class="mt-0.5 size-5 shrink-0 text-gray-500" />
                            <span>Selecciona una pasarela de pago para ver los medios disponibles y continuar.</span>
                        </p>
                    </div>
                @elseif ($usesProviderModal)
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
                        <x-brevare.payment-methods compact class="mt-4" />

                        <div class="mt-4">
                            <label for="culqi-identification"
                                class="mb-1.5 block text-xs font-semibold text-gray-700">DNI del titular</label>
                            <input id="culqi-identification" type="text" wire:model="culqiIdentification"
                                inputmode="numeric" maxlength="8" placeholder="8 dígitos (ej. 12345678)"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <p class="mt-1.5 text-xs text-gray-500">
                                Culqi valida el DNI de 8 dígitos para autorizar el pago.
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
                @elseif ($selectedGateway === 'mercadopago')
                    <x-brevare.payment-methods provider="mercadopago" compact class="mt-0" />
                    <p class="mt-3 flex items-start gap-2 text-xs text-gray-500">
                        <flux:icon name="lock-closed" class="size-4 shrink-0" />
                        Al continuar, completarás el pago en el formulario seguro de Mercado Pago, sin salir de Brevare.
                    </p>
                @elseif ($selectedGateway === 'izipay')
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-4">
                        <p class="flex items-start gap-3 text-sm text-gray-900">
                            <flux:icon name="shield-check" class="mt-0.5 size-5 shrink-0" />
                            <span>Al continuar, te llevaremos al formulario seguro de Izipay. Ahí podrás pagar con tarjeta, Yape o el medio que tengas habilitado.</span>
                        </p>
                    </div>
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
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="relative size-14 shrink-0 overflow-hidden rounded-xl border border-gray-100 bg-[#f7f5ef]">
                                    <div class="absolute inset-0 flex items-center justify-center text-gray-300" aria-hidden="true">
                                        <flux:icon name="photo" class="size-5" />
                                    </div>
                                    @if (filled($item['image']) || filled($item['imageFallback']))
                                        <img src="{{ $item['image'] ?: $item['imageFallback'] }}"
                                            @if (filled($item['imageFallback'])) data-fallback="{{ $item['imageFallback'] }}" @endif
                                            alt="{{ $item['name'] }}" loading="lazy" decoding="async"
                                            onerror="if (this.dataset.fallback && this.src !== this.dataset.fallback) { this.src = this.dataset.fallback; this.dataset.fallback = ''; } else { this.remove(); }"
                                            class="relative h-full w-full object-contain">
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <span class="block truncate text-gray-700">
                                    {{ $item['quantity'] }} × {{ $item['name'] }}
                                    </span>
                                    @if ($item['regularSubtotal'] > $item['subtotal'])
                                        <span class="text-xs text-gray-400 line-through">S/ {{ number_format($item['regularSubtotal'], 2) }}</span>
                                        <span class="ml-1 text-xs font-semibold text-emerald-700">-{{ number_format($item['discountPercent'], 0) }}%</span>
                                    @endif
                                </div>
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
                    @if ($deliveryDays)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Entrega</dt>
                            <dd class="text-gray-900">{{ $deliveryDays }} {{ $deliveryDays === 1 ? 'día hábil' : 'días hábiles' }}</dd>
                        </div>
                    @endif
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

                @if ($selectedShalomAgency)
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
                        <p class="flex items-center gap-2 text-sm font-semibold text-emerald-900">
                            <flux:icon name="building-storefront" class="size-4" />
                            Recojo en agencia Shalom
                        </p>
                        <p class="mt-1 text-sm text-emerald-900">{{ $selectedShalomAgency->displayName() }}</p>
                        <p class="text-xs text-emerald-700">
                            {{ $selectedShalomAgency->district }} · {{ $selectedShalomAgency->province }} · Regular
                        </p>
                    </div>
                @endif

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
                            Vuelve a {{ match ($selectedGateway) {
                                'mercadopago' => 'continuar a Mercado Pago',
                                'izipay' => 'abrir el formulario de Izipay',
                                default => 'abrir el formulario de Culqi',
                            } }} para completarlo. Lo dejamos reservado un tiempo;
                            si no lo pagas, se libera automáticamente. También puedes cambiar de
                            pasarela arriba sin perder tus productos.
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

                @if ($gatewayChoice !== '' && $usesProviderModal && $culqiPublicKey && !$settledOrderNumber)
                    @if ($culqiSession === [])
                        <button wire:click="startCulqiCheckout" wire:loading.attr="disabled"
                            wire:target="startCulqiCheckout" @if (!$selectedAddressId || !$selectedShalomAgencyId) disabled @endif
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
                @elseif ($gatewayChoice !== '' && !$usesProviderModal && !$settledOrderNumber)
                    <button wire:click="placeOrder" wire:loading.attr="disabled"
                        @if (!$selectedAddressId || !$selectedShalomAgencyId) disabled @endif
                        class="mt-4 inline-flex w-full items-center justify-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                        <span wire:loading.remove>
                            {{ match ($selectedGateway) {
                                'mercadopago' => 'Continuar a Mercado Pago',
                                'izipay' => 'Continuar a Izipay',
                                default => 'Realizar pedido y pagar',
                            } }}
                        </span>
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

    @if ($usesProviderModal && $culqiSession !== [] && ! $settledOrderNumber)
        <div id="culqi-payment-modal" class="pay-modal {{ $culqiFormOpened ? '' : 'hidden' }}"
            data-culqi-session="{{ json_encode($culqiSession) }}"
            role="dialog" aria-modal="true" aria-labelledby="culqi-payment-title">
            <div class="pay-modal__backdrop"></div>

            <div class="pay-modal__sheet">
                <span class="pay-modal__grabber" aria-hidden="true"></span>

                <header class="pay-modal__head">
                    <div class="pay-modal__brand">
                        <span class="pay-modal__badge" aria-hidden="true">
                            <flux:icon name="shield-check" class="size-4" />
                        </span>
                        <div>
                            <p class="pay-modal__eyebrow">Pago verificado</p>
                            <h2 id="culqi-payment-title">Culqi</h2>
                        </div>
                    </div>
                    <button type="button" wire:click="cancelCulqiCheckout" aria-label="Cerrar y conservar el carrito"
                        class="pay-modal__close">
                        <flux:icon name="x-mark" class="size-4" />
                    </button>
                </header>

                <p class="pay-modal__lede">Elige tarjeta, Yape o el medio que prefieras. Tu pago se procesa dentro de Culqi.</p>

                <div class="pay-modal__body">
                    <div id="culqi-container" wire:ignore class="w-full"></div>
                </div>

                <footer class="pay-modal__footer">
                    <span class="pay-modal__secure-note">
                        <flux:icon name="lock-closed" class="size-3.5" />
                        Pago cifrado y protegido
                    </span>
                    @if ($paymentStage === 'idle')
                        <button type="button" wire:click="cancelCulqiCheckout" wire:loading.attr="disabled"
                            class="pay-modal__ghost">
                            Cancelar y conservar mi carrito
                        </button>
                    @endif
                </footer>
            </div>
        </div>
    @endif

    @if ($showMercadoPagoModal && $mercadoPagoOrder)
        @php
            $mpModalTestMode = str_starts_with((string) config('payments.mercadopago.public_key'), 'TEST-');
            $mpPayerEmail = (string) ($mercadoPagoOrder->user?->email
                ?? data_get($mercadoPagoOrder->customer_snapshot, 'email', ''));
        @endphp

        <div id="mp-modal"
            data-amount="{{ number_format((float) $mercadoPagoOrder->total, 2, '.', '') }}"
            data-payer-email="{{ $mpPayerEmail }}"
            class="mp-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mp-modal-title"
            aria-describedby="mp-modal-description">
            <div class="mp-modal__backdrop" wire:click="closeMercadoPagoModal"></div>

            <section class="mp-modal__sheet" tabindex="-1">
                <span class="mp-modal__grabber" aria-hidden="true"></span>

                <header class="mp-modal__head">
                    <div class="mp-modal__brand">
                        <span class="mp-modal__verified" aria-hidden="true">
                            <flux:icon name="check" class="size-4" />
                        </span>
                        <div class="mp-modal__titles">
                            <p class="mp-modal__eyebrow">Pago verificado</p>
                            <h2 id="mp-modal-title">Mercado Pago</h2>
                        </div>
                    </div>
                    <button type="button" wire:click="closeMercadoPagoModal"
                        class="mp-modal__close" aria-label="Cerrar pago">
                        <flux:icon name="x-mark" class="size-4" />
                    </button>
                </header>

                <div id="mp-modal-description" class="mp-modal__order">
                    <div class="mp-modal__order-meta">
                        <span class="mp-modal__order-label">Pedido</span>
                        <span class="mp-modal__order-number">{{ $mercadoPagoOrder->order_number }}</span>
                    </div>
                    <div class="mp-modal__amount">
                        <span>Total a pagar</span>
                        <strong>S/ {{ number_format((float) $mercadoPagoOrder->total, 2) }}</strong>
                    </div>
                </div>

                @if ($mpModalTestMode)
                    <p class="mp-modal__test">
                        <strong>Modo prueba</strong>
                        <span>Nombre APRO · DNI 123456789</span>
                    </p>
                @endif

                <div id="mp-modal-error" role="alert" aria-live="assertive" hidden wire:ignore
                    class="mp-modal__alert"></div>

                <div class="mp-modal__form-card">
                    <p class="mp-modal__form-label">Datos de la tarjeta</p>

                    <div id="mp-modal-brick-loading" class="mp-modal__brick-loading" aria-live="polite">
                        <span class="mp-modal__brick-spinner" aria-hidden="true"></span>
                        <span>Cargando formulario seguro…</span>
                    </div>

                    <div class="mp-modal__card-field">
                        <div id="mp-modal-card-brick" wire:ignore class="mp-modal__brick"></div>
                        <span id="mp-modal-brand" class="mp-modal__card-brand" aria-hidden="true" hidden></span>
                    </div>
                </div>

                <footer class="mp-modal__footer">
                    <span class="mp-modal__secure-note">
                        <flux:icon name="lock-closed" class="size-3.5" />
                        Pago cifrado y protegido
                    </span>
                    <button type="button" wire:click="switchToCulqi"
                        wire:loading.attr="disabled" wire:target="switchToCulqi"
                        class="mp-modal__switch">
                        Pagar con Culqi
                        <span aria-hidden="true">→</span>
                    </button>
                </footer>

                <div id="mp-modal-result" role="status" aria-live="polite" hidden wire:ignore
                    class="mp-modal__result"></div>

            </section>
        </div>
    @endif

</div>
</div>
</div>

