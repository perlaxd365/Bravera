<div>
    <a href="{{ route('account.orders') }}"
        class="mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 transition hover:text-gray-900">
        <flux:icon name="arrow-left" class="size-4" /> Volver a mis pedidos
    </a>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold tracking-tight text-gray-900">Pedido {{ $order->order_number }}</h1>
        {{-- Las etiquetas de estado y la acción van en grupos separados: mezcladas
             en la misma fila, el botón filled se leía como una tercera etiqueta. --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <x-brevare.badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-brevare.badge>
                <x-brevare.badge :color="$order->payment_status->badgeColor()">Pago: {{ $order->payment_status->label() }}</x-brevare.badge>
            </div>
            @if ($order->isPayable())
                {{-- El checkout se cierra con el pedido ya creado, así que el pago
                     se retoma aquí con el mismo pedido y la misma orden, sin que el
                     comprador tenga que rehacer el carrito. --}}
                <a href="{{ route('checkout', ['pagar' => $order->order_number]) }}"
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">
                    <flux:icon name="credit-card" class="size-4" /> Pagar ahora
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
                        <span class="font-bold text-gray-900">Productos</span>
                        @if ($order->hasCancelledItems())
                            <x-brevare.badge color="danger">Cancelado: S/ {{ number_format($order->cancelledTotal(), 2) }}</x-brevare.badge>
                        @endif
                    </header>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="px-6 py-3">Producto</th>
                                    <th class="px-6 py-3">Proveedor</th>
                                    <th class="px-6 py-3 text-center">Cant.</th>
                                    <th class="px-6 py-3 text-right">Precio</th>
                                    <th class="px-6 py-3 text-right">Estado</th>
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
                                        <td class="px-6 py-4 text-gray-600">{{ $item->supplier_name }}</td>
                                        <td class="px-6 py-4 text-center text-gray-900">{{ $item->quantity }}</td>
                                        <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                            S/ {{ number_format((float) $item->unit_price, 2) }}
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
                    <h2 class="font-bold text-gray-900">Resumen</h2>
                    <dl class="mt-4 space-y-1.5 border-t border-gray-100 pt-4 text-sm">
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
                    </dl>
                    <div class="mt-4 flex justify-between border-t border-gray-100 pt-4">
                        <span class="font-bold text-gray-900">Total</span>
                        <span class="font-extrabold text-gray-900">S/ {{ number_format((float) $order->total, 2) }}</span>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-gray-900">Entrega</h2>
                    <div class="mt-3 space-y-1 text-sm">
                        <p class="font-semibold text-gray-900">{{ $order->address_snapshot['full_name'] ?? '' }}</p>
                        <p class="text-gray-500">{{ $order->address_snapshot['address'] ?? '' }}</p>
                        <p class="text-gray-500">{{ $order->address_snapshot['location_label'] ?? '' }}</p>
                        <p class="text-gray-500">{{ $order->address_snapshot['phone'] ?? '' }}</p>
                    </div>
                </section>
            </aside>
        </div>
</div>