<div>
    <a href="{{ route('account.orders') }}"
        class="mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 transition hover:text-gray-900">
        <flux:icon name="arrow-left" class="size-4" /> {{ app()->getLocale() === 'en' ? 'Back to my orders' : 'Volver a mis pedidos' }}
    </a>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold tracking-tight text-gray-900">{{ app()->getLocale() === 'en' ? 'Order' : 'Pedido' }} {{ $order->order_number }}</h1>
        {{-- Las etiquetas de estado y la acción van en grupos separados: mezcladas
             en la misma fila, el botón filled se leía como una tercera etiqueta. --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <x-brevare.badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-brevare.badge>
                <x-brevare.badge :color="$order->payment_status->badgeColor()">{{ app()->getLocale() === 'en' ? 'Payment' : 'Pago' }}: {{ $order->payment_status->label() }}</x-brevare.badge>
            </div>
            @if ($order->isPayable())
                {{-- El checkout se cierra con el pedido ya creado, así que el pago
                     se retoma aquí con el mismo pedido y la misma orden, sin que el
                     comprador tenga que rehacer el carrito. --}}
                <a href="{{ route('checkout', ['pagar' => $order->order_number]) }}"
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">
                    <flux:icon name="credit-card" class="size-4" /> {{ app()->getLocale() === 'en' ? 'Pay now' : 'Pagar ahora' }}
                </a>
            @endif
        </div>
    </div>

    {{-- Línea de tiempo del estado --}}
    <x-order-timeline
        :status="$order->status->value"
        :cancelled="$order->status->value === 'cancelled'"
        :cancelledAt="$order->cancelled_at"
        :cancelledReason="$order->cancellation_reason"
        :history="$order->getStatusHistory()"
    />

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- Detalle --}}
            <div class="space-y-4 lg:col-span-2">
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <header class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-6 py-4">
                        <span class="font-bold text-gray-900">{{ app()->getLocale() === 'en' ? 'Products' : 'Productos' }}</span>
                        @if ($order->hasCancelledItems())
                            <x-brevare.badge color="danger">{{ app()->getLocale() === 'en' ? 'Cancelled' : 'Cancelado' }}: {{ $storefront->formatFromPen($order->cancelledTotal()) }}</x-brevare.badge>
                        @endif
                    </header>
                    <div class="responsive-table-scroll" role="region" aria-label="Tabla desplazable horizontalmente" tabindex="0">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="px-6 py-3">{{ __('storefront.common.product') }}</th>
                                    <th class="px-6 py-3 text-center">{{ app()->getLocale() === 'en' ? 'Qty.' : 'Cant.' }}</th>
                                    <th class="px-6 py-3 text-right">{{ app()->getLocale() === 'en' ? 'Price' : 'Precio' }}</th>
                                    <th class="px-6 py-3 text-right">{{ app()->getLocale() === 'en' ? 'Status' : 'Estado' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($order->items as $item)
                                    @php $image = $item->imageUrl(160); @endphp
                                    <tr class="{{ $item->isActive() ? '' : 'bg-red-50/40' }}">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                @if ($image)
                                                    <img src="{{ $image }}" alt="{{ $item->product_name }}" loading="lazy"
                                                        class="size-14 shrink-0 rounded-xl border border-gray-100 object-cover {{ $item->isActive() ? '' : 'opacity-50 grayscale' }}">
                                                @else
                                                    <span class="flex size-14 shrink-0 items-center justify-center rounded-xl border border-gray-100 bg-gray-50 text-gray-300">
                                                        <flux:icon name="cube" class="size-5" />
                                                    </span>
                                                @endif
                                                <div class="min-w-0">
                                                    <div class="font-semibold text-gray-900 {{ $item->isActive() ? '' : 'line-through opacity-60' }}">
                                                        {{ $item->product_name }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">{{ $item->variant_sku }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-900">{{ $item->quantity }}</td>
                                        <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                            <x-brevare.price :amount="(float) $item->unit_price" size="xs" class="font-semibold text-gray-900" />
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <x-brevare.badge :color="$item->status->badgeColor()">{{ $item->status->label() }}</x-brevare.badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                @include('partials.course-materials', ['order' => $order])

                @if (auth()->user()?->isStaff())
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <header class="border-b border-gray-100 px-6 py-4 font-bold text-gray-900">Envío a los proveedores</header>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($order->supplierOrders as $supplierOrder)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                                <div>
                                    <span class="flex items-center gap-2 font-semibold text-gray-900">
                                        {{ $supplierOrder->supplier?->business_name }}
                                        <x-brevare.badge :color="$supplierOrder->status->badgeColor()">{{ $supplierOrder->status->label() }}</x-brevare.badge>
                                    </span>
                                    <span class="mt-1 block text-xs text-gray-500">
                                        {{ $supplierOrder->supplier_order_number }}
                                        @if ($supplierOrder->tracking_code)
                                            · Tracking: {{ $supplierOrder->tracking_code }}
                                        @endif
                                    </span>
                                </div>
                                <span class="font-bold text-gray-900">S/ {{ number_format((float) $supplierOrder->total_cost, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
            </div>

            {{-- Resumen y entrega --}}
            <aside class="space-y-4">
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-gray-900">{{ __('storefront.cart.summary') }}</h2>
                    <dl class="mt-4 space-y-1.5 border-t border-gray-100 pt-4 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('storefront.common.subtotal') }}</dt>
                            <dd class="text-gray-900"><x-brevare.price :amount="(float) $order->subtotal" size="xs" class="font-semibold text-gray-900" /></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('storefront.checkout.shipping_now') }}</dt>
                            <dd class="text-gray-900"><x-brevare.price :amount="(float) $order->shipping_total" size="xs" class="font-semibold text-gray-900" /></dd>
                        </div>
                        @if ((float) $order->cod_shipping_amount > 0)
                            <div class="flex justify-between text-emerald-800">
                                <dt>{{ __('storefront.checkout.shipping_at_pickup') }}</dt>
                                <dd><x-brevare.price :amount="(float) $order->cod_shipping_amount" size="xs" class="font-semibold text-emerald-800" /></dd>
                            </div>
                        @endif
                        @if ($order->delivery_days)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('storefront.checkout.delivery') }}</dt>
                                <dd class="text-gray-900">
                                    {{ $order->delivery_days }} {{ app()->getLocale() === 'en' ? ($order->delivery_days === 1 ? 'business day' : 'business days') : ($order->delivery_days === 1 ? 'día hábil' : 'días hábiles') }}
                                </dd>
                            </div>
                        @endif
                        @if ((float) $order->discount_total > 0)
                            <div class="flex justify-between text-green-700">
                                <dt>Cupón {{ $order->coupon_code }}</dt>
                                <dd>- <x-brevare.price :amount="(float) $order->discount_total" size="xs" class="font-semibold text-green-700" /></dd>
                            </div>
                        @endif
                    </dl>
                    <div class="mt-4 flex justify-between border-t border-gray-100 pt-4">
                        <span class="font-bold text-gray-900">{{ __('storefront.common.total') }}</span>
                        <span class="font-extrabold text-gray-900"><x-brevare.price :amount="(float) $order->total" size="xs" class="font-extrabold text-gray-900" /></span>
                    </div>
                </section>

                @if (! empty($order->address_snapshot))
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-gray-900">{{ app()->getLocale() === 'en' ? 'Pickup' : 'Recojo' }}</h2>
                    @php($pickup = $order->address_snapshot['pickup_agency'] ?? null)
                    @if (! empty($pickup))
                        <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Agencia Shalom · Regular</p>
                            <p class="mt-1 font-semibold text-emerald-950">{{ $pickup['address'] ?? '' }}</p>
                            <p class="text-sm text-emerald-800">{{ $pickup['district'] ?? '' }} · {{ $pickup['province'] ?? '' }}</p>
                            <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-600">Powered by Shalom</p>
                        </div>

                        @if ($order->payment_status->value === 'paid' && $order->pickup_code)
                            <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-center">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500">{{ app()->getLocale() === 'en' ? 'Pickup code' : 'Código de recojo' }}</p>
                                <p class="mt-1 text-3xl font-black tracking-[0.3em] text-gray-900">{{ $order->pickup_code }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ app()->getLocale() === 'en' ? 'Present it at the Shalom office with your ID.' : 'Preséntalo en la agencia junto con tu DNI.' }}</p>
                            </div>
                        @endif
                    @endif
                    <div class="mt-3 space-y-1 text-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ app()->getLocale() === 'en' ? 'Contact address' : 'Dirección de contacto' }}</p>
                        <p class="font-semibold text-gray-900">{{ $order->address_snapshot['full_name'] ?? '' }}</p>
                        <p class="text-gray-500">{{ $order->address_snapshot['address'] ?? '' }}</p>
                        <p class="text-gray-500">{{ $order->address_snapshot['location_label'] ?? '' }}</p>
                        <p class="text-gray-500">{{ $order->address_snapshot['phone'] ?? '' }}</p>
                    </div>
                </section>
                @endif
            </aside>
        </div>
</div>
