<div>
    <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm sm:p-10">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full {{ $order->payment_status->value === 'paid' ? 'bg-green-50 text-green-600' : ($order->payment_status->value === 'pending' ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600') }}">
                <flux:icon name="{{ $order->payment_status->value === 'paid' ? 'check-circle' : ($order->payment_status->value === 'pending' ? 'clock' : 'exclamation-circle') }}" class="size-9" />
            </span>
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900">
                {{ $order->payment_status->value === 'paid'
                    ? (app()->getLocale() === 'en' ? 'Thank you for your purchase!' : '¡Gracias por tu compra!')
                    : ($order->payment_status->value === 'pending'
                        ? (app()->getLocale() === 'en' ? 'We are confirming your payment' : 'Estamos verificando tu pago')
                        : (app()->getLocale() === 'en' ? 'Payment was not completed' : 'No se completó el pago')) }}
            </h1>
            <p class="mt-2 text-gray-600">
                {{ app()->getLocale() === 'en' ? 'Order' : 'Tu pedido' }}
                <strong class="text-gray-900">{{ $order->order_number }}</strong>
                @if ($order->payment_status->value === 'paid')
                    {{ app()->getLocale() === 'en' ? 'has been paid and confirmed.' : 'fue pagado y confirmado.' }}
                @elseif ($order->payment_status->value === 'pending' && $latestPayment?->status?->value === 'failed')
                    {{ app()->getLocale() === 'en' ? 'is still reserved, but the payment attempt did not complete. You can try again.' : 'sigue reservado, pero el intento de pago no se completó. Puedes volver a intentarlo.' }}
                @elseif ($order->payment_status->value === 'pending')
                    {{ app()->getLocale() === 'en' ? 'is reserved. We will confirm it as soon as the payment provider notifies us.' : 'está reservado. Te mostraremos la confirmación cuando Mercado Pago nos notifique.' }}
                @else
                    {{ app()->getLocale() === 'en' ? 'could not be paid. Choose another payment method or contact us.' : 'no se confirmó el pago. Elige otro medio de pago o contáctanos.' }}
                @endif
            </p>

            <div class="mx-auto mt-6 grid max-w-md grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="block text-gray-500">{{ $order->payment_status->value === 'paid' ? (app()->getLocale() === 'en' ? 'Total paid' : 'Total pagado') : (app()->getLocale() === 'en' ? 'Order total' : 'Total del pedido') }}</span>
                    <strong class="text-gray-900"><x-brevare.price :amount="(float) $order->total" size="xs" class="font-bold text-gray-900" /></strong>
                </div>
                <div>
                    <span class="block text-gray-500">{{ app()->getLocale() === 'en' ? 'Method' : 'Método' }}</span>
                    <strong class="text-gray-900">{{ $latestPayment?->gateway === 'mercadopago' ? 'Mercado Pago' : strtoupper($latestPayment?->method ?? 'tarjeta') }}</strong>
                </div>
                <div>
                    <span class="block text-gray-500">{{ app()->getLocale() === 'en' ? 'Status' : 'Estado' }}</span>
                    <x-brevare.badge :color="$order->payment_status->badgeColor()">{{ app()->getLocale() === 'en' ? match ($order->payment_status->value) {
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                        default => ucfirst($order->payment_status->value),
                    } : $order->payment_status->label() }}</x-brevare.badge>
                </div>
            </div>

            <div class="mt-8 text-left">
                <x-order-timeline
                    :status="$order->status->value"
                    :cancelled="$order->status->value === 'cancelled'"
                    :cancelledAt="$order->cancelled_at"
                    :cancelledReason="$order->cancellation_reason"
                    :history="$order->getStatusHistory()"
                />
            </div>

            <div class="mt-6 border-t border-gray-100 pt-6 text-left">
                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">{{ app()->getLocale() === 'en' ? 'Your order' : 'Tu pedido' }}</h2>
                <ul class="mt-3 divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        @php $image = $item->imageUrl(160); @endphp
                        <li class="flex items-center justify-between gap-4 py-3 text-sm">
                            @if ($image)
                                <img src="{{ $image }}" alt="{{ $item->product_name }}" loading="lazy"
                                    class="size-14 shrink-0 rounded-xl border border-gray-100 object-cover">
                            @else
                                <span class="flex size-14 shrink-0 items-center justify-center rounded-xl border border-gray-100 bg-gray-50 text-gray-300">
                                    <flux:icon name="cube" class="size-5" />
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900">{{ $item->product_name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $item->variant_sku }}
                                    @if ($item->variant_attributes)
                                        · {{ collect($item->variant_attributes)->implode(', ') }}
                                    @endif
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-semibold text-gray-900"><x-brevare.price :amount="(float) $item->unit_price" size="xs" class="font-semibold text-gray-900" /></p>
                                <p class="text-xs text-gray-500">{{ __('storefront.common.quantity') }} {{ $item->quantity }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @include('partials.course-materials', ['order' => $order])

                <dl class="mt-3 space-y-1.5 border-t border-gray-100 pt-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('storefront.common.subtotal') }}</dt>
                        <dd class="text-gray-900"><x-brevare.price :amount="(float) $order->subtotal" size="xs" class="font-semibold text-gray-900" /></dd>
                    </div>
                    @if ($order->hasPhysicalItems())
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
                            <dt class="text-gray-500">{{ app()->getLocale() === 'en' ? 'Delivery time' : 'Tiempo de entrega' }}</dt>
                                <dd class="text-gray-900">
                                    {{ $order->delivery_days }} {{ app()->getLocale() === 'en' ? ($order->delivery_days === 1 ? 'business day' : 'business days') : ($order->delivery_days === 1 ? 'día hábil' : 'días hábiles') }}
                                </dd>
                            </div>
                        @endif
                    @endif
                    @if ((float) $order->discount_total > 0)
                        <div class="flex justify-between text-green-700">
                            <dt>Cupón {{ $order->coupon_code }}</dt>
                            <dd>- <x-brevare.price :amount="(float) $order->discount_total" size="xs" class="font-semibold text-green-700" /></dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2">
                        <dt class="font-bold text-gray-900">
                            {{ (float) $order->cod_shipping_amount > 0 ? (app()->getLocale() === 'en' ? 'Paid now' : 'Total pagado ahora') : __('storefront.common.total') }}
                        </dt>
                        <dd class="font-extrabold text-gray-900"><x-brevare.price :amount="(float) $order->total" size="xs" class="font-extrabold text-gray-900" /></dd>
                    </div>
                </dl>
                @if ((float) $order->cod_shipping_amount > 0)
                    <p class="mt-2 text-xs leading-5 text-emerald-800">
                        {{ __('storefront.order.shipping_due_pickup', ['amount' => $storefront->formatFromPen((float) $order->cod_shipping_amount)]) }}
                    </p>
                @endif
            </div>

            <div class="mt-6 space-y-3 border-t border-gray-100 pt-6 text-left text-sm text-gray-700">
                @php($pickup = $order->address_snapshot['pickup_agency'] ?? null)
                @if (! empty($pickup))
                    <p class="flex items-start gap-2">
                        <flux:icon name="building-storefront" class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                        <span>
                            <strong class="text-gray-900">Recojo en agencia Shalom:</strong>
                            {{ $pickup['address'] ?? '' }} · {{ $pickup['district'] ?? '' }} · Regular
                            <span class="mt-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                Powered by Shalom
                            </span>
                        </span>
                    </p>

                    @if ($order->payment_status->value === 'paid' && $order->pickup_code)
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 text-center">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">
                                Código de recojo
                            </p>
                            <p class="mt-1 text-4xl font-black tracking-[0.35em] text-emerald-950">
                                {{ $order->pickup_code }}
                            </p>
                            <p class="mt-2 text-xs text-emerald-800">
                                Presenta este código en la agencia Shalom junto con tu DNI para recoger el pedido.
                            </p>
                        </div>
                    @elseif ($order->payment_status->value === 'pending')
                        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                            Tu código de recojo de 4 dígitos se generará cuando se confirme el pago.
                        </p>
                    @endif
                @endif
                @if ($order->hasPhysicalItems() && ! empty($order->address_snapshot))
                    <p class="flex items-start gap-2">
                        <flux:icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                        <span>
                            <strong class="text-gray-900">Dirección de contacto:</strong>
                            {{ $order->address_snapshot['full_name'] ?? '' }} —
                            {{ $order->address_snapshot['address'] ?? '' }},
                            {{ $order->address_snapshot['location_label'] ?? '' }}
                        </span>
                    </p>
                @endif
                @if (auth()->user()?->isStaff())
                    <p class="flex items-start gap-2">
                        <flux:icon name="cube" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                        <span>
                            <strong class="text-gray-900">Proveedores:</strong>
                            {{ $order->supplierOrders->pluck('supplier.business_name')->implode(', ') }}
                        </span>
                    </p>
                @endif
            </div>

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                @if ($order->payment_status->value === 'pending')
                    <a href="{{ route('checkout', ['pagar' => $order->order_number]) }}"
                        class="inline-flex items-center justify-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                        {{ app()->getLocale() === 'en' ? 'Complete payment' : 'Completar pago' }}
                    </a>
                @endif
                <a href="{{ route('account.orders') }}"
                    class="inline-flex items-center justify-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                    {{ __('storefront.common.my_orders') }}
                </a>
                <a href="{{ route('home') }}"
                    class="inline-flex items-center justify-center rounded-full border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-900 transition hover:bg-gray-50">
                    {{ __('storefront.common.continue_shopping') }}
                </a>
            </div>
        </div>
    </div>
</div>
