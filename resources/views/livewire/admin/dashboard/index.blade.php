<div>
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <flux:icon name="banknotes" class="size-3.5" /> Ingresos pagados
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900">S/ {{ number_format($kpis['revenue'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <flux:icon name="arrow-trending-up" class="size-3.5" /> Margen total
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-green-700">S/ {{ number_format($kpis['margins'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <flux:icon name="shopping-cart" class="size-3.5" /> Pedidos
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900">
                {{ $kpis['orders'] }}
                <span class="text-sm font-medium text-gray-400">(hoy: {{ $kpis['ordersToday'] }})</span>
            </p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <flux:icon name="check-circle" class="size-3.5" /> Entregados
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900">{{ $kpis['completed'] }}</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5">
            <p class="flex items-center gap-1.5 text-xs font-medium text-amber-700">
                <flux:icon name="truck" class="size-3.5" /> Órdenes a proveedores pendientes
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900">
                {{ $pendingSupplierOrders }}
                <span class="text-sm font-medium text-gray-500">= S/ {{ number_format($pendingSupplierAmount, 2) }}</span>
            </p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5">
            <p class="flex items-center gap-1.5 text-xs font-medium text-amber-700">
                <flux:icon name="exclamation-triangle" class="size-3.5" /> Stock bajo (&le;5)
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight {{ $lowStock > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $lowStock }}</p>
        </div>
        <div class="rounded-2xl border border-red-200 bg-red-50/50 p-5">
            <p class="flex items-center gap-1.5 text-xs font-medium text-red-700">
                <flux:icon name="x-circle" class="size-3.5" /> Sin stock
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight {{ $outOfStock > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $outOfStock }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-5">

        {{-- Últimos pedidos --}}
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm lg:col-span-3">
            <header class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="font-bold text-gray-900">
                    <span class="inline-flex items-center gap-1.5"><flux:icon name="clock" class="size-4" /> Últimos pedidos</span>
                </h2>
                <a href="{{ route('admin.orders.index') }}" class="rounded-full border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-900 transition hover:bg-gray-50">Ver todos</a>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-6 py-3">Pedido</th>
                            <th class="px-6 py-3">Cliente</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-center">Estado</th>
                            <th class="px-6 py-3 text-right">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentOrders as $order)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-6 py-3.5">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-gray-900 transition hover:text-gray-600">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-3.5 text-gray-600">{{ $order->customer_snapshot['name'] ?? $order->user?->name }}</td>
                                <td class="px-6 py-3.5 text-right font-semibold text-gray-900">S/ {{ number_format((float) $order->total, 2) }}</td>
                                <td class="px-6 py-3.5 text-center">
                                    <x-brevare.badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-brevare.badge>
                                </td>
                                <td class="px-6 py-3.5 text-right text-xs text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">Aún no hay pedidos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-4 lg:col-span-2">
            {{-- Productos más vendidos --}}
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <header class="border-b border-gray-100 px-6 py-4">
                    <h2 class="font-bold text-gray-900">
                        <span class="inline-flex items-center gap-1.5"><flux:icon name="fire" class="size-4" /> Productos más vendidos</span>
                    </h2>
                </header>
                <div class="divide-y divide-gray-100 px-6">
                    @forelse ($bestProducts as $product)
                        <div class="flex items-center justify-between gap-2 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-gray-900">{{ $product->product_name }}</div>
                                <div class="text-xs text-gray-500">{{ $product->total_qty }} unidades</div>
                            </div>
                            <div class="shrink-0 text-sm font-semibold text-green-700">S/ {{ number_format((float) $product->total_sales, 2) }}</div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-gray-500">Sin ventas aún.</p>
                    @endforelse
                </div>
            </section>

            {{-- Accesos rápidos --}}
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <header class="border-b border-gray-100 px-6 py-4">
                    <h2 class="font-bold text-gray-900">
                        <span class="inline-flex items-center gap-1.5"><flux:icon name="sparkles" class="size-4" /> Accesos rápidos</span>
                    </h2>
                </header>
                <div class="space-y-2 p-4">
                    <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-2.5 rounded-xl border border-gray-200 px-4 py-3 text-sm font-medium text-gray-900 transition hover:bg-gray-50">
                        <flux:icon name="shopping-cart" class="size-4 text-gray-500" /> Gestionar pedidos
                    </a>
                    <a href="{{ route('admin.dropshipping.orders.index') }}" class="flex items-center gap-2.5 rounded-xl border border-gray-200 px-4 py-3 text-sm font-medium text-gray-900 transition hover:bg-gray-50">
                        <flux:icon name="truck" class="size-4 text-gray-500" /> Órdenes a proveedores
                    </a>
                    <a href="{{ route('admin.discounts.coupons.index') }}" class="flex items-center gap-2.5 rounded-xl border border-gray-200 px-4 py-3 text-sm font-medium text-gray-900 transition hover:bg-gray-50">
                        <flux:icon name="ticket" class="size-4 text-gray-500" /> Cupones de descuento
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>