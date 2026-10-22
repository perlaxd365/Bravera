<div>
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7">
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
        <a href="{{ route('admin.customers.index') }}"
            class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
            <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <flux:icon name="users" class="size-3.5" /> Nuevos ingresantes
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900">
                {{ $newCustomers['total'] }}
                <span class="text-sm font-medium text-gray-400">en total</span>
            </p>
            <p class="mt-1 flex items-center text-xs text-gray-500">
                Clientes con cuenta registrada
                <span class="ml-auto text-emerald-700 transition group-hover:translate-x-0.5" aria-hidden="true">→</span>
            </p>
        </a>
        <a href="{{ route('admin.customers.index') }}"
            class="group rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
            <p class="flex items-center gap-1.5 text-xs font-medium text-emerald-800">
                <flux:icon name="user-plus" class="size-3.5" /> Último registro
            </p>
            <p class="mt-2 truncate text-lg font-extrabold tracking-tight text-emerald-950" title="{{ $latestCustomer?->name ?? 'Sin registros' }}">
                {{ $latestCustomer?->name ?? 'Sin registros' }}
            </p>
            <p class="mt-1 flex items-center gap-1.5 text-xs text-emerald-800/75">
                <span class="truncate">
                    @if ($latestCustomer)
                        Registrado el {{ $latestCustomer->created_at->format('d/m/Y H:i') }}
                    @else
                        Aún no hay clientes registrados
                    @endif
                </span>
                <span class="ml-auto text-emerald-700 transition group-hover:translate-x-0.5" aria-hidden="true">→</span>
            </p>
        </a>
        <div class="rounded-2xl border border-sky-200 bg-sky-50/50 p-5">
            <p class="flex items-center gap-1.5 text-xs font-medium text-sky-800">
                <flux:icon name="eye" class="size-3.5" /> Personas que vieron la página
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-sky-950">
                {{ $traffic['visitorsToday'] }}
                <span class="text-sm font-medium text-sky-800/70">hoy</span>
            </p>
            <p class="mt-1 text-xs text-sky-900/70">
                {{ $traffic['pageViewsToday'] }} páginas vistas · ayer: {{ $traffic['visitorsYesterday'] }} personas
            </p>
            @if ($trafficSource === 'ga4')
                <p class="mt-1 text-xs font-semibold text-emerald-700">
                    {{ $traffic['activeNow'] }} {{ $traffic['activeNow'] === 1 ? 'persona activa' : 'personas activas' }} ahora
                </p>
            @endif
            <p class="mt-2 flex items-center gap-1.5 text-[10px] font-medium text-sky-800/75">
                <span class="size-1.5 rounded-full {{ $trafficSource === 'ga4' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ $trafficSource === 'ga4' ? 'Google Analytics 4' : 'Contador local' }}
            </p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
        <a href="{{ route('admin.catalog.products.index') }}"
            class="group rounded-2xl border border-amber-300 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-400 hover:shadow-md">
            <p class="flex items-center gap-1.5 text-xs font-medium text-amber-800">
                <flux:icon name="cube" class="size-3.5" /> Productos por publicar
            </p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight {{ $pendingPublication > 0 ? 'text-amber-900' : 'text-emerald-700' }}">
                {{ $pendingPublication }}
            </p>
            <p class="mt-1 text-xs text-amber-800/70">
                {{ $pendingPublication === 1 ? 'Ficha incompleta' : 'Fichas incompletas o no visibles' }}
                <span class="ml-1 inline-block transition group-hover:translate-x-0.5" aria-hidden="true">→</span>
            </p>
        </a>
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
            <div class="responsive-table-scroll" role="region" aria-label="Tabla desplazable horizontalmente" tabindex="0">
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
