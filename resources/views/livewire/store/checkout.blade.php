<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
    data-storefront-locale="{{ app()->getLocale() }}"
    data-culqi-public-key="{{ $culqiPublicKey ?? '' }}"
    data-order-status-url-template="{{ route('store.order.placed', ['order' => '__ORDER__']) }}"
    data-orders-url="{{ route('account.orders') }}">
    <h1 class="mb-6 flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900">
        <flux:icon name="credit-card" class="size-6" /> {{ __('storefront.product.finish_purchase') }}
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
                {{ __('storefront.checkout.processing_title') }}
            </h3>

            <p id="culqi-processing-description"
                style="
                margin: 0;
                color: #6b7280;
                font-size: 14px;
                line-height: 1.5;
            ">
                {{ __('storefront.checkout.processing_copy') }}<br>
                {{ app()->getLocale() === 'en' ? 'Please do not close or reload this page.' : 'Por favor, no cierres ni recargues esta página.' }}
            </p>

            <div id="culqi-processing-recovery" style="display: none; margin-top: 20px;">
                <p style="margin: 0 0 14px; color: #92400e; font-size: 14px; line-height: 1.5;">
                    {{ __('storefront.checkout.processing_warning') }}
                </p>
                <a id="culqi-processing-order-link" href="{{ route('account.orders') }}"
                    style="display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; background: #111827; padding: 10px 18px; color: white; font-size: 14px; font-weight: 600; text-decoration: none;">
                    {{ app()->getLocale() === 'en' ? 'View order status' : 'Ver el estado de mi pedido' }}
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
    <div class="grid min-w-0 grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-6">

        {{-- Columna principal --}}
        <div class="min-w-0 space-y-4 lg:col-span-2">

            @guest
                <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2.5 font-bold text-gray-900">
                                <span class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">1</span>
                                {{ app()->getLocale() === 'en' ? 'Contact' : 'Contacto' }}
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-gray-600">
                                {{ app()->getLocale() === 'en'
                                    ? 'Check out without creating a password. If you use a new email and the payment is confirmed, we will send you one so you can sign in later.'
                                    : 'Compra sin crear una contraseña. Si usas un correo nuevo y el pago se confirma, te enviaremos una para ingresar después.' }}
                            </p>
                        </div>
                        <a href="{{ route('login') }}"
                            class="shrink-0 rounded-full border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-900 transition hover:bg-gray-50">
                            {{ __('storefront.common.sign_in') }}
                        </a>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="guest-first-name" class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'First name' : 'Nombre' }}</label>
                            <input id="guest-first-name" type="text" autocomplete="given-name"
                                wire:model="guestFirstName"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            @error('guestFirstName')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="guest-last-name" class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Last name' : 'Apellidos' }}</label>
                            <input id="guest-last-name" type="text" autocomplete="family-name"
                                wire:model="guestLastName"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            @error('guestLastName')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="guest-email" class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Email address' : 'Correo electrónico' }}</label>
                            <input id="guest-email" type="email" autocomplete="email"
                                wire:model.blur="guestEmail"
                                aria-describedby="guest-email-help"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <p id="guest-email-help" class="mt-1 text-xs text-gray-500">
                                Se usará para la confirmación del pedido y los avisos del carrito.
                            </p>
                            @error('guestEmail')
                                <p class="mt-1 text-xs font-medium text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="guest-phone" class="mb-1.5 block text-xs font-semibold text-gray-700">Teléfono</label>
                            <input id="guest-phone" type="tel" autocomplete="tel"
                                wire:model="guestPhone"
                                aria-describedby="guest-phone-help"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <p id="guest-phone-help" class="mt-1 text-xs text-gray-500">
                                En caso tengamos que contactarte sobre tu pedido.
                            </p>
                            @error('guestPhone')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if ($emailNeedsVerification)
                        <div class="mt-5 rounded-2xl border border-blue-200 bg-blue-50/70 p-4 sm:p-5">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-blue-700 shadow-sm">
                                    <flux:icon name="envelope" class="size-5" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-blue-950">Este correo ya tiene una cuenta</h3>
                                    <p class="mt-1 text-sm leading-6 text-blue-900">
                                        Enviamos un código de 6 dígitos a
                                        <strong class="break-all">{{ $guestEmail }}</strong>.
                                        Ingrésalo para asociar esta compra a tu cuenta.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                                <div class="min-w-0 flex-1">
                                    <label for="checkout-verification-code" class="mb-1.5 block text-xs font-semibold text-blue-950">
                                        Código de verificación
                                    </label>
                                    <input id="checkout-verification-code" type="text" inputmode="numeric" maxlength="6"
                                        autocomplete="one-time-code" wire:model="checkoutVerificationCode"
                                        placeholder="123456"
                                        class="w-full rounded-xl border border-blue-300 bg-white px-4 py-2.5 text-base font-semibold tracking-[0.18em] text-gray-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15">
                                    @error('checkoutVerificationCode')
                                        <p class="mt-1 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="button" wire:click="verifyGuestEmailCode" wire:loading.attr="disabled"
                                    class="mt-0 inline-flex min-h-11 items-center justify-center rounded-full bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-wait disabled:opacity-70 sm:mt-6">
                                    Verificar código
                                </button>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-semibold">
                                <button type="button" wire:click="resendGuestEmailCode"
                                    class="text-blue-800 underline underline-offset-2 hover:text-blue-950">
                                    Reenviar código
                                </button>
                                <button type="button" wire:click="changeGuestEmail"
                                    class="text-gray-600 underline underline-offset-2 hover:text-gray-900">
                                    Cambiar correo
                                </button>
                            </div>
                        </div>
                    @endif
                </section>
            @endguest

            @if ($requiresShipping)
            {{-- Dirección de entrega --}}
            <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                    <span
                        class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">{{ auth()->check() ? 1 : 2 }}</span>
                    Dirección de entrega
                </h2>

                @if ($addresses->isNotEmpty())
                    @foreach ($addresses as $address)
                        <label for="addr-{{ $address->id }}"
                            class="mb-2 flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 transition hover:border-gray-300">
                            <input type="radio" name="address" value="{{ $address->id }}"
                                id="addr-{{ $address->id }}" wire:model="selectedAddressId"
                                wire:change="selectAddress({{ $address->id }})"
                                @if ($address->is_default) checked @endif
                                class="mt-1 size-4 shrink-0 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            <span class="min-w-0 w-full">
                                <span class="flex min-w-0 items-start justify-between gap-2">
                                    <strong class="min-w-0 break-words text-sm text-gray-900">{{ $address->full_name }}</strong>
                                    @if ($address->is_default)
                                        <span
                                            class="shrink-0 rounded-full border border-gray-300 bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-600">Principal</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block break-words text-xs leading-relaxed text-gray-500">
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
                    <div class="mt-4 min-w-0 rounded-xl border border-gray-200 bg-gray-50 p-4 sm:p-5" wire:key="new-address">
                        <h3 class="mb-4 font-bold text-gray-900">Nueva dirección</h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @auth
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
                            @endauth
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
                            <div class="flex flex-wrap gap-2 sm:col-span-2">
                                <button wire:click="saveNewAddress"
                                    class="rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                                    Guardar dirección y calcular envío
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
                        <p id="shalom-list-hint" wire:ignore
                            class="mt-2 hidden text-[11px] leading-relaxed text-gray-500"></p>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div id="shalom-agency-list" wire:ignore
                                class="shalom-agency-list"
                                role="group" aria-label="Agencias Shalom disponibles"></div>
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

            {{-- Envío --}}
            <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                    <span
                        class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">{{ auth()->check() ? 2 : 3 }}</span>
                    {{ __('storefront.checkout.shipping') }}
                </h2>
                @if ($quote)
                    <div class="flex flex-wrap items-center gap-2 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        <flux:icon name="truck" class="size-5 text-gray-500" />
                        {{ app()->getLocale() === 'en' ? 'Estimated shipping:' : 'Costo de envío estimado:' }}
                        <strong><x-brevare.price :amount="$quotedShippingTotal" size="xs" class="font-bold text-gray-700" /></strong>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-gray-600">
                            {{ $shippingPaymentMethod === 'shalom_cod' ? (app()->getLocale() === 'en' ? 'Pay at Shalom' : 'Pago en Shalom') : (app()->getLocale() === 'en' ? 'Prepaid' : 'Pago adelantado') }}
                        </span>
                    </div>
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-gray-600">
                        <flux:icon name="clock" class="size-4 text-emerald-600" />
                        @if ($deliveryDays)
                            {{ app()->getLocale() === 'en' ? 'Estimated delivery:' : 'Tiempo de entrega estimado:' }}
                            <strong class="text-gray-900">
                                {{ $deliveryDays }} {{ app()->getLocale() === 'en' ? ($deliveryDays === 1 ? 'business day' : 'business days') : ($deliveryDays === 1 ? 'día hábil' : 'días hábiles') }}
                            </strong>
                        @else
                            {{ app()->getLocale() === 'en' ? 'Delivery:' : 'Tiempo de entrega:' }} <strong class="text-gray-900">{{ app()->getLocale() === 'en' ? 'to be confirmed' : 'por confirmar' }}</strong>
                        @endif
                    </p>
                    @if ($deliveryDays)
                        <p class="mt-1 text-xs text-gray-500">
                            {{ app()->getLocale() === 'en' ? 'If your order includes products from multiple suppliers, we show the longest estimate.' : 'Si tu pedido incluye productos de varios proveedores, mostramos el plazo más largo.' }}
                        </p>
                    @endif

                    @if ($awaitingPayment)
                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                            <p class="font-semibold">
                                {{ $shippingPaymentMethod === 'shalom_cod'
                                    ? 'Envío a pagar al recoger en Shalom'
                                    : 'Envío pagado por adelantado' }}
                            </p>
                            <p class="mt-1 text-emerald-800">
                                {{ $shippingPaymentMethod === 'shalom_cod'
                                    ? (app()->getLocale() === 'en'
                                        ? 'Shipping of '.$storefrontContext->formatFromPen($codShippingAmount).' is reserved to be paid at Shalom.'
                                        : 'El pedido ya tiene reservado su envío por '.$storefrontContext->formatFromPen($codShippingAmount).'.')
                                    : (app()->getLocale() === 'en' ? 'Shipping is already included in this payment.' : 'El envío ya está incluido en el pago del pedido.') }}
                            </p>
                        </div>
                    @else
                        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-4">
                            <p class="text-sm font-bold text-gray-900">{{ app()->getLocale() === 'en' ? 'How would you like to pay for shipping?' : '¿Cómo deseas pagar el envío?' }}</p>

                            @if ($codAvailable)
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-start gap-2 rounded-xl border p-3 transition {{ $shippingPaymentMethod === 'prepaid' ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:border-gray-300' }}">
                                        <input type="radio" wire:model.live="shippingPaymentMethod" value="prepaid"
                                            class="mt-0.5 size-4 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                                        <span>
                                            <span class="block text-sm font-semibold text-gray-900">{{ __('storefront.checkout.prepay') }}</span>
                                            <span class="mt-0.5 block text-xs leading-5 text-gray-500">{{ app()->getLocale() === 'en' ? 'Shipping is charged with your order.' : 'El envío se cobra junto con tu compra.' }}</span>
                                        </span>
                                    </label>
                                    <label class="flex cursor-pointer items-start gap-2 rounded-xl border p-3 transition {{ $shippingPaymentMethod === 'shalom_cod' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-emerald-300' }}">
                                        <input type="radio" wire:model.live="shippingPaymentMethod" value="shalom_cod"
                                            class="mt-0.5 size-4 border-gray-300 text-emerald-600 focus:ring-emerald-600/30">
                                        <span>
                                            <span class="block text-sm font-semibold text-gray-900">{{ __('storefront.checkout.pay_at_pickup') }}</span>
                                            <span class="mt-0.5 block text-xs leading-5 text-gray-500">{{ app()->getLocale() === 'en' ? 'Pay the shipping fee at the Shalom office when you collect your order.' : 'Pagas el envío en la agencia al recoger tu pedido.' }}</span>
                                        </span>
                                    </label>
                                </div>
                            @else
                                <label class="mt-3 flex items-start gap-2 rounded-xl border border-gray-900 bg-gray-50 p-3">
                                    <input type="radio" checked disabled
                                        class="mt-0.5 size-4 border-gray-300 text-gray-900">
                                    <span>
                                        <span class="block text-sm font-semibold text-gray-900">{{ app()->getLocale() === 'en' ? 'Prepaid shipping' : 'Pago adelantado' }}</span>
                                        <span class="mt-0.5 block text-xs leading-5 text-gray-500">
                                            Los productos de este carrito requieren que el envío se pague junto con la compra.
                                        </span>
                                    </span>
                                </label>
                            @endif
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-500">{{ app()->getLocale() === 'en' ? 'Select an address to calculate shipping.' : 'Selecciona una dirección para calcular el envío.' }}</p>
                @endif
            </section>
            @else
                <section class="min-w-0 rounded-2xl border border-sky-200 bg-sky-50/70 p-4 shadow-sm sm:p-6">
                    <h2 class="flex items-center gap-2.5 font-bold text-sky-950">
                        <flux:icon name="document-arrow-down" class="size-5" />
                        {{ app()->getLocale() === 'en' ? 'Digital purchase' : 'Compra digital' }}
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-sky-900">
                        {{ __('storefront.checkout.course_only') }}
                    </p>
                </section>
            @endif

            {{-- Cupón de descuento --}}
            <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                    <span
                        class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">{{ $requiresShipping ? (auth()->check() ? 3 : 4) : (auth()->check() ? 1 : 2) }}</span>
                    {{ app()->getLocale() === 'en' ? 'Discount code' : 'Cupón de descuento' }}
                </h2>
                @if ($couponApplied)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-green-50 px-4 py-3">
                        <div class="min-w-0">
                            <p class="flex min-w-0 items-center gap-1.5 break-words text-sm font-semibold text-green-800">
                                <flux:icon name="tag" class="size-4" /> {{ $couponApplied['coupon']->code }}
                                — {{ app()->getLocale() === 'en' ? 'Discount:' : 'Descuento:' }} <x-brevare.price :amount="$discount" size="xs" class="font-semibold text-green-800" />
                            </p>
                            <p class="text-xs text-green-700">{{ $couponApplied['description'] }}</p>
                        </div>
                        <button wire:click="removeCoupon"
                            class="shrink-0 rounded-full border border-red-300 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                            {{ app()->getLocale() === 'en' ? 'Remove' : 'Quitar' }}
                        </button>
                    </div>
                @else
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input type="text" wire:model="couponCode" placeholder="{{ app()->getLocale() === 'en' ? 'Enter your code (e.g. BREVARE10)' : 'Ingresa tu código (ej: BREVARE10)' }}"
                            class="min-w-0 w-full rounded-full border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        <button wire:click="applyCoupon"
                            class="w-full shrink-0 rounded-full border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-gray-50 sm:w-auto sm:py-2">
                            {{ app()->getLocale() === 'en' ? 'Apply' : 'Aplicar' }}
                        </button>
                    </div>
                    @if ($couponError)
                        <p class="mt-2 flex items-center gap-1.5 text-sm text-red-600">
                            <flux:icon name="exclamation-circle" class="size-4" /> {{ $couponError }}
                        </p>
                    @endif
                @endif
            </section>

            {{-- Pago --}}
            <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="mb-4 flex items-center gap-2.5 font-bold text-gray-900">
                    <span
                        class="flex size-6 items-center justify-center rounded-full bg-gray-900 text-xs text-white">{{ $requiresShipping ? (auth()->check() ? 4 : 5) : (auth()->check() ? 2 : 3) }}</span>
                    {{ __('storefront.checkout.payment_method') }}
                </h2>

                @if (array_key_exists('culqi', $availableGateways)
                    && ($gatewayChoice === '' || $gatewayChoice === 'culqi'))
                    <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50/60 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <label for="culqi-identification" class="flex items-center gap-2 text-sm font-bold text-gray-900">
                                <flux:icon name="identification" class="size-4 text-amber-800" />
                                {{ app()->getLocale() === 'en' ? 'Cardholder ID' : 'DNI del titular' }}
                                <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-900 ring-1 ring-inset ring-amber-200">
                                {{ app()->getLocale() === 'en' ? 'Required by Culqi' : 'Obligatorio para Culqi' }}
                            </span>
                        </div>
                        <input id="culqi-identification" type="text" wire:model.live.debounce.400ms="culqiIdentification"
                            inputmode="numeric" autocomplete="off" maxlength="8" required aria-required="true"
                            aria-describedby="culqi-identification-help culqi-identification-error"
                            placeholder="8 dígitos (ej. 12345678)"
                            @class([
                                'mt-3 min-h-12 w-full rounded-xl border bg-white px-4 py-3 text-base font-semibold tracking-[0.08em] text-gray-900 shadow-sm focus:outline-none focus:ring-2',
                                'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' => ! $errors->has('culqiIdentification'),
                                'border-red-400 focus:border-red-500 focus:ring-red-500/15' => $errors->has('culqiIdentification'),
                            ])>
                        <div class="mt-2 flex items-start justify-between gap-3">
                            <p id="culqi-identification-help" class="text-xs leading-5 text-gray-600">
                                {{ app()->getLocale() === 'en' ? 'Required to authorize payment. If you bought before, we fill it in automatically.' : 'Necesario para autorizar el pago. Si ya compraste antes, lo completamos automáticamente.' }}
                            </p>
                            <span class="shrink-0 text-[10px] font-medium text-gray-400">{{ strlen($culqiIdentification) }}/8</span>
                        </div>
                        @error('culqiIdentification')
                            <p id="culqi-identification-error" class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-red-700" role="alert">
                                <flux:icon name="exclamation-circle" class="size-3.5" /> {{ $message }}
                            </p>
                        @enderror
                    </div>
                @endif

                @if (count($availableGateways) > 0)
                        <div class="mb-4 grid grid-cols-1 gap-2 sm:grid-cols-2" role="radiogroup" aria-label="{{ __('storefront.checkout.gateway') }}">
                        @foreach ($availableGateways as $gatewayKey => $gateway)
                            <label for="gateway-{{ $gatewayKey }}"
                                    class="flex min-w-0 cursor-pointer flex-col gap-3 rounded-xl border p-3 transition {{ $gatewayChoice === $gatewayKey ? 'border-gray-900 bg-gray-50 ring-1 ring-gray-900/10' : 'border-gray-200 hover:border-gray-300' }}">
                                <span class="flex min-w-0 items-start gap-3">
                                    <input id="gateway-{{ $gatewayKey }}" type="radio" name="payment_gateway"
                                        value="{{ $gatewayKey }}" wire:model.live="gatewayChoice"
                                        @disabled(in_array($paymentStage, ['processing', 'pending', 'paid'], true))
                                        class="mt-0.5 size-4 shrink-0 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-gray-900">{{ $gateway['title'] }}</span>
                                        <span class="mt-0.5 block break-words text-xs leading-5 text-gray-500">
                                            {{ $gateway['description'] }}
                                        </span>
                                    </span>
                                </span>

                                @if (in_array($gatewayKey, ['culqi', 'mercadopago'], true))
                                    <x-brevare.payment-methods
                                        :provider="$gatewayKey"
                                        presentation="inline"
                                        compact
                                        class="w-full" />
                                @endif
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
                            <span>{{ __('storefront.checkout.select_gateway') }}</span>
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
                                    {{ app()->getLocale() === 'en' ? 'The secure Culqi form will appear here. You can choose card, Yape, or another available method.' : 'El formulario seguro de Culqi aparecerá aquí. Podrás elegir entre tarjeta, Yape y los demás medios disponibles.' }}
                                </span>
                            </p>
                        </div>
                        <p class="mt-3 flex items-start gap-2 text-xs text-gray-500">
                            <flux:icon name="lock-closed" class="size-4 shrink-0" />
                            {{ app()->getLocale() === 'en' ? 'Your card is processed inside Culqi. We never store the full card number.' : 'Tu tarjeta se procesa dentro del formulario de Culqi. No almacenamos el número completo.' }}
                        </p>

                    @else
                        {{-- Sin clave pública no hay checkout que abrir: decirlo aquí evita
                             prometer un botón de pago que no se puede usar. --}}
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            {{ app()->getLocale() === 'en' ? 'Card payment is currently unavailable. Contact us and we will help complete your order.' : 'El pago con tarjeta no está disponible en este momento. Escríbenos y coordinamos tu compra.' }}
                        </div>
                    @endif
                @elseif ($selectedGateway === 'mercadopago')
                    <p class="mt-3 flex items-start gap-2 text-xs text-gray-500">
                        <flux:icon name="lock-closed" class="size-4 shrink-0" />
                        {{ app()->getLocale() === 'en' ? 'Next, you will complete payment in the secure Mercado Pago form without leaving Brevare.' : 'Al continuar, completarás el pago en el formulario seguro de Mercado Pago, sin salir de Brevare.' }}
                    </p>
                @elseif ($selectedGateway === 'izipay')
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-4">
                        <p class="flex items-start gap-3 text-sm text-gray-900">
                            <flux:icon name="shield-check" class="mt-0.5 size-5 shrink-0" />
                            <span>{{ app()->getLocale() === 'en' ? 'Next, we will take you to the secure Izipay form where you can pay by card, Yape, or another enabled method.' : 'Al continuar, te llevaremos al formulario seguro de Izipay. Ahí podrás pagar con tarjeta, Yape o el medio que tengas habilitado.' }}</span>
                        </p>
                    </div>
                @elseif ($paymentMethods === [])
                    <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ app()->getLocale() === 'en' ? 'No payment methods are available right now. Contact us and we will help complete your order.' : 'No hay métodos de pago disponibles en este momento. Escríbenos y coordinamos tu compra.' }}
                    </div>
                @else
                    @foreach ($paymentMethods as $key => $label)
                        <label for="pm-{{ $key }}"
                            class="mb-2 flex min-w-0 cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition {{ $paymentMethod === $key ? 'border-gray-900 bg-gray-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                            {{-- .live es imprescindible: sin él el servidor no conoce el
                                 método elegido hasta una acción, y los campos del medio
                                 anterior se quedan en pantalla. --}}
                            <input type="radio" name="payment_method" id="pm-{{ $key }}"
                                value="{{ $key }}" wire:model.live="paymentMethod"
                                class="size-4 border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            <span class="min-w-0 break-words text-sm font-medium text-gray-900">{{ $label }}</span>
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
        <aside class="h-fit min-w-0 space-y-4 lg:sticky lg:top-24">
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="font-bold text-gray-900">{{ __('storefront.checkout.summary') }}</h2>

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
                                    <span class="line-clamp-2 break-words leading-5 text-gray-700">
                                    {{ $item['quantity'] }} × {{ $item['name'] }}
                                    </span>
                                    @if ($item['regularSubtotal'] > $item['subtotal'])
                                        <span class="text-xs text-gray-400 line-through"><x-brevare.price :amount="$item['regularSubtotal']" size="xs" class="font-normal text-gray-400 line-through" :show-approximation="false" /></span>
                                        <span class="ml-1 text-xs font-semibold text-emerald-700">-{{ number_format($item['discountPercent'], 0) }}%</span>
                                    @endif
                                </div>
                            </div>
                            <strong class="shrink-0 text-gray-900"><x-brevare.price :amount="$item['subtotal']" size="xs" class="font-bold text-gray-900" /></strong>
                        </div>
                    @endforeach
                </div>

                <dl class="mt-4 space-y-1.5 border-t border-gray-100 pt-4 text-sm">
                    @if ($productDiscount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('storefront.checkout.subtotal_before_discounts') }}</dt>
                            <dd class="text-gray-500 line-through"><x-brevare.price :amount="$regularSubtotal" size="xs" class="font-normal text-gray-500 line-through" :show-approximation="false" /></dd>
                        </div>
                        <div class="flex justify-between text-emerald-700">
                            <dt>{{ __('storefront.cart.product_discounts') }}</dt>
                            <dd>- <x-brevare.price :amount="$productDiscount" size="xs" class="font-semibold text-emerald-700" /></dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('storefront.common.subtotal') }}</dt>
                        <dd class="text-gray-900"><x-brevare.price :amount="$subtotal" size="xs" class="font-semibold text-gray-900" /></dd>
                    </div>
                    @if ($requiresShipping)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('storefront.checkout.shipping_now') }}</dt>
                            <dd class="text-gray-900"><x-brevare.price :amount="$shippingTotal" size="xs" class="font-semibold text-gray-900" /></dd>
                        </div>
                        @if ($codShippingAmount > 0)
                            <div class="flex justify-between text-emerald-800">
                                <dt>{{ __('storefront.checkout.shipping_at_pickup') }}</dt>
                                <dd><x-brevare.price :amount="$codShippingAmount" size="xs" class="font-semibold text-emerald-800" /></dd>
                            </div>
                        @endif
                        @if ($deliveryDays)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('storefront.checkout.delivery') }}</dt>
                                <dd class="text-gray-900">{{ $deliveryDays }} {{ app()->getLocale() === 'en' ? ($deliveryDays === 1 ? 'business day' : 'business days') : ($deliveryDays === 1 ? 'día hábil' : 'días hábiles') }}</dd>
                            </div>
                        @endif
                    @endif
                    @if ($discount > 0)
                        <div class="flex justify-between text-green-700">
                            <dt>Cupón</dt>
                            <dd>- <x-brevare.price :amount="$discount" size="xs" class="font-semibold text-green-700" /></dd>
                        </div>
                    @endif
                </dl>

                @if ($totalSavings > 0)
                    <div class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-emerald-100 bg-emerald-50/70 px-3 py-2">
                        <span class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-800">
                            <flux:icon name="tag" class="size-3.5" />
                            {{ __('storefront.checkout.total_savings') }}
                        </span>
                        <strong class="shrink-0 text-sm font-bold text-emerald-800">
                            <x-brevare.price :amount="$totalSavings" size="xs" class="font-bold text-emerald-800" />
                        </strong>
                    </div>
                @endif

                <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-100/80 px-5 py-4 shadow-sm"
                    aria-live="polite">
                    <div class="flex items-end justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-500">
                                {{ app()->getLocale() === 'en' ? 'Total charged now' : ($shippingPaymentMethod === 'shalom_cod' ? 'Total a pagar ahora' : 'Total a pagar') }}
                            </p>
                            <p class="mt-1 flex items-center gap-1.5 text-xs text-stone-500">
                                <flux:icon name="lock-closed" class="size-3.5" />
                                {{ app()->getLocale() === 'en' ? 'Protected checkout' : 'Compra protegida' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="whitespace-nowrap text-[2rem] font-extrabold tracking-[-0.035em] text-gray-950 tabular-nums sm:text-[2.25rem]">
                                {{ $storefrontContext->format($displayTotal) }}
                            </p>
                            @if ($storefrontContext->isInternational())
                                <p class="mt-1 whitespace-nowrap text-xs font-semibold text-stone-600">
                                    {{ __('storefront.prices.charged_in_soles', ['amount' => $storefrontContext->chargedFromPen((float) $total)]) }}
                                </p>
                            @endif
                        </div>
                    </div>
                    @if ($storefrontContext->isInternational())
                        <p class="mt-3 flex items-start gap-2 border-t border-stone-200 pt-3 text-[11px] leading-5 text-stone-600">
                            <flux:icon name="information-circle" class="mt-0.5 size-3.5 shrink-0" />
                            <span>{{ __('storefront.prices.exchange_rate', ['rate' => number_format($storefrontContext->exchangeRate, 2)]) }}</span>
                        </p>
                    @endif
                </div>
                @if ($requiresShipping && $codShippingAmount > 0)
                    <p class="mt-2 text-xs leading-5 text-emerald-800">
                        {{ __('storefront.order.shipping_due_pickup', ['amount' => $storefrontContext->formatFromPen($codShippingAmount)]) }}
                    </p>
                @endif

                @if ($requiresShipping && $selectedShalomAgency)
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
                        <p class="flex items-center gap-2 text-sm font-semibold text-emerald-900">
                            <flux:icon name="building-storefront" class="size-4" />
                            Recojo en agencia Shalom
                        </p>
                        <p class="mt-1 break-words text-sm text-emerald-900">{{ $selectedShalomAgency->displayName() }}</p>
                        <p class="text-xs text-emerald-700">
                            {{ $selectedShalomAgency->district }} · {{ $selectedShalomAgency->province }} · Regular
                        </p>
                    </div>
                @endif

                <div class="mt-4">
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Notes (optional)' : 'Notas (opcional)' }}</label>
                    <textarea wire:model="notes" rows="2"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10"></textarea>
                </div>

                @if ($awaitingPayment)
                    {{-- El pedido ya existe con su precio congelado y su stock
                         reservado; lo que falta es el pago. Sin este aviso el
                         comprador vería un carrito vacío y pensaría que se le
                         está cobrando 0.00. --}}
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="font-semibold">{{ app()->getLocale() === 'en'
                            ? 'Order '.$pendingOrderNumber.' is reserved and awaiting payment.'
                            : 'Tu pedido '.$pendingOrderNumber.' está reservado y esperando el pago.' }}</p>
                        <p class="mt-1 text-amber-800">
                            {{ app()->getLocale() === 'en'
                                ? 'Return to the payment form to complete it. We will keep it reserved for a limited time and release it automatically if it is not paid. You can also switch payment providers above without losing your items.'
                                : 'Vuelve a '.match ($selectedGateway) {
                                    'mercadopago' => 'continuar a Mercado Pago',
                                    'izipay' => 'abrir el formulario de Izipay',
                                    default => 'abrir el formulario de Culqi',
                                }.' para completarlo. Lo dejamos reservado un tiempo; si no lo pagas, se libera automáticamente. También puedes cambiar de pasarela arriba sin perder tus productos.' }}
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
                        {{ app()->getLocale() === 'en' ? 'Culqi payment is currently unavailable. Contact us and we will help with your order.' : 'El pago con Culqi no está disponible. Escríbenos para atender tu pedido.' }}
                    </div>
                @endif

                @if ($gatewayChoice !== '' && $usesProviderModal && $culqiPublicKey && !$settledOrderNumber)
                    @if ($culqiSession === [])
                        <button wire:click="startCulqiCheckout" wire:loading.attr="disabled"
                            wire:target="startCulqiCheckout" @if ($requiresShipping && (!$selectedAddressId || !$selectedShalomAgencyId)) disabled @endif
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                            <span wire:loading.remove wire:target="startCulqiCheckout"
                                class="inline-flex items-center gap-2">
                                <flux:icon name="lock-closed" class="size-4" />
                                {{ __('storefront.checkout.continue_payment') }}
                            </span>
                            <span wire:loading wire:target="startCulqiCheckout">{{ app()->getLocale() === 'en' ? 'Loading Culqi…' : 'Cargando Culqi...' }}</span>
                        </button>
                    @elseif (!$culqiFormOpened && $paymentStage === 'idle')
                        <button wire:click="startCulqiCheckout" wire:loading.attr="disabled"
                            wire:target="startCulqiCheckout"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                            <span wire:loading.remove wire:target="startCulqiCheckout">{{ __('storefront.checkout.continue_payment') }}</span>
                            <span wire:loading wire:target="startCulqiCheckout">{{ app()->getLocale() === 'en' ? 'Loading Culqi…' : 'Cargando Culqi...' }}</span>
                        </button>
                    @endif
                @elseif ($gatewayChoice !== '' && !$usesProviderModal && !$settledOrderNumber)
                    <button wire:click="placeOrder" wire:loading.attr="disabled"
                        @if ($requiresShipping && (!$selectedAddressId || !$selectedShalomAgencyId)) disabled @endif
                        class="mt-4 inline-flex w-full items-center justify-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                        <span wire:loading.remove>
                            {{ match ($selectedGateway) {
                                'mercadopago' => app()->getLocale() === 'en' ? 'Continue to Mercado Pago' : 'Continuar a Mercado Pago',
                                'izipay' => app()->getLocale() === 'en' ? 'Continue to Izipay' : 'Continuar a Izipay',
                                default => app()->getLocale() === 'en' ? 'Place order and pay' : 'Realizar pedido y pagar',
                            } }}
                        </span>
                        <span wire:loading>{{ app()->getLocale() === 'en' ? 'Processing…' : 'Procesando...' }}</span>
                    </button>
                @endif
                <button type="button" wire:click="returnToCart" wire:loading.attr="disabled"
                    class="mt-2 block w-full rounded-full px-6 py-2.5 text-center text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                    {{ app()->getLocale() === 'en' ? 'Back to cart' : 'Volver al carrito' }}
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
                            <p class="pay-modal__eyebrow">{{ app()->getLocale() === 'en' ? 'Verified payment' : 'Pago verificado' }}</p>
                            <h2 id="culqi-payment-title">Culqi</h2>
                        </div>
                    </div>
                    <button type="button" wire:click="cancelCulqiCheckout" aria-label="Cerrar y conservar el carrito"
                        class="pay-modal__close">
                        <flux:icon name="x-mark" class="size-4" />
                    </button>
                </header>

                <p class="pay-modal__lede">{{ app()->getLocale() === 'en' ? 'Choose a card, Yape, or another payment method. Your payment is processed securely by Culqi.' : 'Elige tarjeta, Yape o el medio que prefieras. Tu pago se procesa dentro de Culqi.' }}</p>

                <div class="pay-modal__body">
                    <div id="culqi-container" wire:ignore class="w-full"></div>
                </div>

                <footer class="pay-modal__footer">
                    <span class="pay-modal__secure-note">
                        <flux:icon name="lock-closed" class="size-3.5" />
                        {{ __('storefront.footer.payment_secure') }}
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
                            <p class="mp-modal__eyebrow">{{ app()->getLocale() === 'en' ? 'Verified payment' : 'Pago verificado' }}</p>
                            <h2 id="mp-modal-title">Mercado Pago</h2>
                        </div>
                    </div>
                    <button type="button" wire:click="closeMercadoPagoModal"
                        class="mp-modal__close" aria-label="{{ app()->getLocale() === 'en' ? 'Close payment' : 'Cerrar pago' }}">
                        <flux:icon name="x-mark" class="size-4" />
                    </button>
                </header>

                <div id="mp-modal-description" class="mp-modal__order">
                    <div class="mp-modal__order-meta">
                        <span class="mp-modal__order-label">{{ app()->getLocale() === 'en' ? 'Order' : 'Pedido' }}</span>
                        <span class="mp-modal__order-number">{{ $mercadoPagoOrder->order_number }}</span>
                    </div>
                    <div class="mp-modal__amount">
                        <span>Total a pagar</span>
                        <strong>{{ $storefrontContext->formatFromPen((float) $mercadoPagoOrder->total) }}</strong>
                    </div>
                </div>

                @if ($mpModalTestMode)
                    <p class="mp-modal__test">
                        <strong>Modo prueba</strong>
                        <span>Nombre APRO · DNI 123456789 (9 dígitos)</span>
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
                        {{ __('storefront.footer.payment_secure') }}
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

