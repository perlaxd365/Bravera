<div>
    <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm sm:p-10">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-50 text-green-600">
                <flux:icon name="check-circle" class="size-9" />
            </span>
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900">¡Gracias por tu compra!</h1>
            <p class="mt-2 text-gray-600">
                Tu pedido
                <strong class="text-gray-900">{{ $order->order_number }}</strong>
                fue confirmado, estamos verificando tu pago.
            </p>

            <div class="mx-auto mt-6 grid max-w-md grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="block text-gray-500">Total pagado</span>
                    <strong class="text-gray-900">S/ {{ number_format((float) $order->total, 2) }}</strong>
                </div>
                <div>
                    <span class="block text-gray-500">Método</span>
                    <strong class="text-gray-900">{{ strtoupper($order->payment?->method ?? 'tarjeta') }}</strong>
                </div>
                <div>
                    <span class="block text-gray-500">Estado</span>
                    <x-brevare.badge :color="$order->payment_status->badgeColor()">{{ $order->payment_status->label() }}</x-brevare.badge>
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
                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">Tu pedido</h2>
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
                                    @if ($item->supplier_name)
                                        · {{ $item->supplier_name }}
                                    @endif
                                    @if ($item->variant_attributes)
                                        · {{ collect($item->variant_attributes)->implode(', ') }}
                                    @endif
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-semibold text-gray-900">S/ {{ number_format((float) $item->unit_price, 2) }}</p>
                                <p class="text-xs text-gray-500">Cant. {{ $item->quantity }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-3 space-y-1.5 border-t border-gray-100 pt-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="text-gray-900">S/ {{ number_format((float) $order->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Envío</dt>
                        <dd class="text-gray-900">S/ {{ number_format((float) $order->shipping_total, 2) }}</dd>
                    </div>
                    @if ((float) $order->discount_total > 0)
                        <div class="flex justify-between text-green-700">
                            <dt>Cupón {{ $order->coupon_code }}</dt>
                            <dd>- S/ {{ number_format((float) $order->discount_total, 2) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2">
                        <dt class="font-bold text-gray-900">Total</dt>
                        <dd class="font-extrabold text-gray-900">S/ {{ number_format((float) $order->total, 2) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-6 space-y-3 border-t border-gray-100 pt-6 text-left text-sm text-gray-700">
                <p class="flex items-start gap-2">
                    <flux:icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                    <span>
                        <strong class="text-gray-900">Entrega a:</strong>
                        {{ $order->address_snapshot['full_name'] ?? '' }} —
                        {{ $order->address_snapshot['address'] ?? '' }},
                        {{ $order->address_snapshot['location_label'] ?? '' }}
                    </span>
                </p>
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
                <a href="{{ route('account.orders') }}"
                    class="inline-flex items-center justify-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                    Ver mis pedidos
                </a>
                <a href="{{ route('home') }}"
                    class="inline-flex items-center justify-center rounded-full border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-900 transition hover:bg-gray-50">
                    Seguir comprando
                </a>
            </div>
        </div>
    </div>
</div>
