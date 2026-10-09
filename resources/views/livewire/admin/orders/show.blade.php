<div>
    <div class="mx-auto max-w-7xl">
        <div class="mb-6">
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-700 transition hover:text-gray-900">
                <flux:icon name="arrow-left" class="size-4" /> Pedidos
            </a>

            <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Pedido {{ $order->order_number }}</h1>
                <div class="flex flex-wrap gap-2">
                    <x-brevare.badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-brevare.badge>
                    <x-brevare.badge :color="$order->payment_status->badgeColor()">{{ $order->payment_status->label() }}</x-brevare.badge>
                </div>
            </div>
        </div>

        @if ($order->status->isActive())
            <div class="mb-6 flex flex-wrap items-center gap-3 rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm">
                <span class="text-sm font-semibold text-gray-900">Cambiar estado:</span>
                <div class="relative">
                    <select wire:model="currentStatus" wire:loading.attr="disabled" wire:target="updateStatus"
                        class="appearance-none rounded-full border border-gray-300 bg-white py-2 pl-4 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        @foreach (\App\Enums\OrderStatus::cases() as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                </div>
                <button wire:click="updateStatus" wire:loading.attr="disabled" wire:target="updateStatus"
                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="updateStatus" class="inline-flex items-center gap-1.5">
                        <flux:icon name="check" class="size-4" /> Actualizar
                    </span>
                    <span wire:loading wire:target="updateStatus" class="inline-flex items-center gap-1.5">
                        <flux:icon name="arrow-path" class="size-4 animate-spin" /> Actualizando…
                    </span>
                </button>
                <span class="text-xs text-gray-500">Se enviará un correo al cliente con el nuevo estado.</span>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">

            <div class="space-y-6 lg:col-span-2">

                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
                        <h2 class="font-bold text-gray-900">
                            <flux:icon name="cube" class="mr-1.5 inline size-4 text-gray-400" /> Productos
                        </h2>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($order->hasCancelledItems())
                                <x-brevare.badge color="danger">Cancelado: S/ {{ number_format($order->cancelledTotal(), 2) }}</x-brevare.badge>
                            @endif
                            @if ($order->activeItems()->exists())
                                <button wire:click="$set('showCancelWholeOrder', true)"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-red-200 px-4 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                                    <flux:icon name="x-circle" class="size-4" /> Cancelar pedido completo
                                </button>
                            @endif
                        </div>
                    </header>

                    @if ($showCancelWholeOrder)
                        <div class="border-b border-red-100 bg-red-50/50 px-6 py-5">
                            <h3 class="text-sm font-semibold text-red-700">Cancelar el pedido {{ $order->order_number }} completo</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Se cancelarán los {{ $order->activeItems()->count() }} producto(s) aún activos,
                                se devolverá el stock a los proveedores y se cancelarán las órdenes de compra.
                                El cliente recibirá un correo de aviso.
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <input type="text" wire:model="cancelWholeOrderReason"
                                    placeholder="Motivo (opcional)"
                                    class="w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/10">
                                <button wire:click="cancelWholeOrder" wire:loading.attr="disabled" wire:target="cancelWholeOrder"
                                    class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500 disabled:cursor-wait disabled:opacity-60">
                                    <span wire:loading.remove wire:target="cancelWholeOrder">Sí, cancelar el pedido</span>
                                    <span wire:loading wire:target="cancelWholeOrder">Cancelando…</span>
                                </button>
                                <button wire:click="$set('showCancelWholeOrder', false)" wire:loading.attr="disabled" wire:target="cancelWholeOrder"
                                    class="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                                    Volver
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="px-6 py-3">Producto</th>
                                    <th class="px-6 py-3 text-center">Cant.</th>
                                    <th class="px-6 py-3 text-right">Precio</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                    <th class="px-6 py-3 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($order->items as $item)
                                    @php $image = $item->imageUrl(160); @endphp
                                    <tr wire:key="item-{{ $item->id }}" class="{{ $item->isActive() ? '' : 'bg-red-50/40' }}">
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
                                                    <div class="text-xs text-gray-500">{{ $item->supplier_name }}</div>
                                                    @unless ($item->isActive())
                                                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                            <x-brevare.badge :color="$item->status->badgeColor()">{{ $item->status->label() }}</x-brevare.badge>
                                                            @if ($item->cancellation_reason)
                                                                <span class="text-xs italic text-gray-500">{{ $item->cancellation_reason }}</span>
                                                            @endif
                                                        </div>
                                                    @endunless
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-900">{{ $item->quantity }}</td>
                                        <td class="px-6 py-4 text-right text-gray-900">S/ {{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">S/ {{ number_format((float) $item->line_subtotal, 2) }}</td>
                                        <td class="px-6 py-4 text-right">
                                            @if ($item->isActive())
                                                @if ($showCancelFor[$item->id] ?? false)
                                                    <div class="ml-auto w-full max-w-xs space-y-2 text-left">
                                                        <input type="text" wire:model="cancelReason.{{ $item->id }}"
                                                            placeholder="Motivo (opcional)"
                                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                                        <div class="flex justify-end gap-2">
                                                            <button wire:click="$set('showCancelFor.{{ $item->id }}', false)"
                                                                class="rounded-full border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                                                                Volver
                                                            </button>
                                                            <button wire:click="cancelItem({{ $item->id }})"
                                                                wire:confirm="¿Cancelar este producto del pedido? Se notificará al cliente por correo."
                                                                class="rounded-full bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-500">
                                                                Confirmar
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    <button wire:click="toggleCancelForm({{ $item->id }})"
                                                        class="inline-flex items-center gap-1 rounded-full border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                                        <flux:icon name="x-circle" class="size-3.5" /> Cancelar producto
                                                    </button>
                                                @endif
                                            @else
                                                <button wire:click="restoreItem({{ $item->id }})"
                                                    class="inline-flex items-center gap-1 rounded-full border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                                                    <flux:icon name="arrow-uturn-left" class="size-3.5" /> Reactivar
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 text-sm">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right text-gray-500">Subtotal</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">S/ {{ number_format((float) $order->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right text-gray-500">Envío</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">S/ {{ number_format((float) $order->shipping_total, 2) }}</td>
                                </tr>
                                @if ((float) $order->discount_total > 0)
                                    <tr>
                                        <td colspan="3" class="px-6 py-3 text-right text-gray-500">Descuento ({{ $order->coupon_code }})</td>
                                        <td class="px-6 py-3 text-right font-semibold text-emerald-700">- S/ {{ number_format((float) $order->discount_total, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right font-bold text-gray-900">Total</td>
                                    <td class="px-6 py-3 text-right font-extrabold text-gray-900">S/ {{ number_format((float) $order->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <header class="border-b border-gray-100 px-6 py-4 font-bold text-gray-900">
                        <flux:icon name="truck" class="mr-1.5 inline size-4 text-gray-400" /> Órdenes a proveedores
                    </header>

                    <div class="divide-y divide-gray-100">
                    @forelse ($order->supplierOrders as $so)
                        <div class="px-6 py-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $so->supplier_order_number }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500">{{ $so->supplier?->business_name }}</div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-brevare.badge :color="$so->status->badgeColor()">{{ $so->status->label() }}</x-brevare.badge>
                                    @if ($so->payment)
                                        <x-brevare.badge color="success">Pagado S/ {{ number_format((float) $so->payment->amount, 2) }}</x-brevare.badge>
                                    @else
                                        <x-brevare.badge color="warning">Pago pendiente</x-brevare.badge>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-xl border border-gray-100">
                                <table class="min-w-full divide-y divide-gray-100 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <th class="px-4 py-2.5">Producto</th>
                                            <th class="px-4 py-2.5 text-center">Cant.</th>
                                            <th class="px-4 py-2.5 text-right">Costo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($so->items as $soItem)
                                            <tr>
                                                <td class="px-4 py-2.5 text-gray-900">{{ $soItem->orderItem?->product_name ?? 'Producto' }}</td>
                                                <td class="px-4 py-2.5 text-center text-gray-900">{{ $soItem->quantity }}</td>
                                                <td class="px-4 py-2.5 text-right text-gray-900">S/ {{ number_format((float) $soItem->line_cost_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-gray-50">
                                            <td colspan="2" class="px-4 py-2.5 text-right text-gray-500">Total costo</td>
                                            <td class="px-4 py-2.5 text-right font-bold text-gray-900">S/ {{ number_format((float) $so->total_cost, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Estado</label>
                                    <div class="relative">
                                        <select wire:model="supplierStatus.{{ $so->id }}"
                                            class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                            @foreach (\App\Enums\SupplierOrderStatus::cases() as $sos)
                                                <option value="{{ $sos->value }}">{{ $sos->label() }}</option>
                                            @endforeach
                                        </select>
                                        <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">N.º tracking</label>
                                    <input type="text" wire:model="trackingCode.{{ $so->id }}"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                </div>
                                <div class="flex items-end">
                                    <button wire:click="updateSupplierStatus({{ $so->id }})"
                                        class="w-full rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                        Actualizar orden
                                    </button>
                                </div>
                            </div>

                            @unless ($so->payment)
                                <div class="mt-4 grid gap-3 border-t border-gray-100 pt-4 sm:grid-cols-3">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Registrar pago</label>
                                        <div class="relative">
                                            <select wire:model="paymentMethod.{{ $so->id }}"
                                                class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                                <option value="transferencia">Transferencia</option>
                                                <option value="deposito">Depósito</option>
                                                <option value="efectivo">Efectivo</option>
                                            </select>
                                            <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Referencia (opcional)</label>
                                        <input type="text" wire:model="paymentReference.{{ $so->id }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                    </div>
                                    <div class="flex items-end">
                                        <button wire:click="registerPayment({{ $so->id }})"
                                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                                            <flux:icon name="banknotes" class="size-4" />Pagar S/ {{ number_format((float) $so->total_cost, 2) }}
                                        </button>
                                    </div>
                                </div>
                            @endunless
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <x-brevare.empty-state icon="truck" title="Este pedido no generó órdenes a proveedores" description="Las órdenes de compra a proveedores aparecerán aquí." />
                        </div>
                    @endforelse
                </div>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-gray-900">
                        <flux:icon name="user" class="mr-1.5 inline size-4 text-gray-400" /> Cliente
                    </h2>
                    <div class="mt-3 space-y-1 text-sm">
                        <p class="font-semibold text-gray-900">{{ $order->customer_snapshot['name'] ?? $order->user?->name }}</p>
                        <p class="text-gray-500">{{ $order->customer_snapshot['email'] ?? $order->user?->email }} · {{ $order->customer_snapshot['phone'] ?? '' }}</p>
                        <p class="text-gray-500">Cuenta: {{ $order->user?->email }}</p>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-gray-900">
                        <flux:icon name="map-pin" class="mr-1.5 inline size-4 text-gray-400" /> Entrega
                    </h2>
                    <div class="mt-3 space-y-1 text-sm">
                        <p class="font-semibold text-gray-900">{{ $order->address_snapshot['full_name'] ?? '' }}</p>
                        <p class="text-gray-500">{{ $order->address_snapshot['address'] ?? '' }}</p>
                        <p class="text-gray-500">{{ $order->address_snapshot['zone_name'] ?? $order->address_snapshot['location_label'] ?? '' }}</p>
                        <p class="text-gray-500">{{ $order->address_snapshot['phone'] ?? '' }}</p>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-gray-900">
                        <flux:icon name="credit-card" class="mr-1.5 inline size-4 text-gray-400" /> Pago
                    </h2>
                    <div class="mt-3 space-y-1 text-sm">
                        @if ($order->payment)
                            <p class="text-gray-500">N.º operación: <span class="font-semibold text-gray-900">{{ $order->payment->gateway_transaction_id }}</span></p>
                            <p class="text-gray-500">Método: <span class="font-semibold text-gray-900">{{ $order->payment->method }}</span></p>
                            <p class="text-gray-500">Monto: <span class="font-semibold text-gray-900">S/ {{ number_format((float) $order->payment->amount, 2) }}</span></p>
                            <p class="text-gray-500">Fecha: {{ optional($order->payment->paid_at)->format('d/m/Y H:i') }}</p>
                        @else
                            <p class="text-gray-500">Sin pago registrado.</p>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-bold text-gray-900">
                        <flux:icon name="chart-bar" class="mr-1.5 inline size-4 text-gray-400" /> Margen
                    </h2>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Venta</dt>
                            <dd class="font-medium text-gray-900">S/ {{ number_format((float) $order->total, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Costo (proveedores)</dt>
                            <dd class="font-medium text-gray-900">S/ {{ number_format((float) $order->cost_total, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-semibold text-gray-900">Margen</dt>
                            <dd class="font-semibold text-emerald-700">S/ {{ number_format($order->margin(), 2) }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-500">Creado: {{ $order->created_at->format('d/m/Y H:i') }}</p>
                </section>
            </aside>
        </div>
    </div>
</div>
