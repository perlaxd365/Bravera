<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                <flux:icon name="cube" class="size-5" />
            </span>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">Mis pedidos</h1>
                <p class="text-sm text-gray-500">Revisa el estado y el detalle de tus compras.</p>
            </div>
        </div>
    </div>

    @if ($orders->isEmpty())
        <x-brevare.empty-state icon="cube" title="Aún no has realizado pedidos"
            description="Cuando compres algo en Brevare, podrás seguirlo desde aquí.">
            <a href="{{ route('store.search') }}"
                class="inline-flex items-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                Explorar catálogo
            </a>
        </x-brevare.empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total de pedidos</p>
                <p class="mt-1 text-2xl font-extrabold tracking-tight text-gray-900">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Monto total</p>
                <p class="mt-1 text-2xl font-extrabold tracking-tight text-gray-900">S/ {{ number_format((float) $stats['spent'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Último pedido</p>
                <p class="mt-1 text-2xl font-extrabold tracking-tight text-gray-900">
                    {{ $stats['latest'] ? $stats['latest']->format('d M Y') : '—' }}
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-5 py-3.5">Pedido</th>
                            <th class="px-5 py-3.5">Fecha</th>
                            <th class="px-5 py-3.5">Total</th>
                            <th class="px-5 py-3.5">Pago</th>
                            <th class="px-5 py-3.5">Estado</th>
                            <th class="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($orders as $order)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <a href="{{ route('account.orders.show', ['order' => $order->order_number]) }}"
                                        class="font-semibold text-gray-900 transition hover:text-gray-600">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-gray-500">{{ $order->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-5 py-4 font-bold text-gray-900">S/ {{ number_format((float) $order->total, 2) }}</td>
                                <td class="px-5 py-4">
                                    <x-brevare.badge :color="$order->payment_status->badgeColor()">{{ $order->payment_status->label() }}</x-brevare.badge>
                                </td>
                                <td class="px-5 py-4">
                                    <x-brevare.badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-brevare.badge>
                                </td>
                                <td class="px-5 py-4">
                                    {{-- Una sola acción primaria y una secundaria. Antes
                                         iban sueltas en la celda con mr-2, y al tener
                                         pesos visuales distintos se leían como dos
                                         botones compitiendo entre sí. --}}
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($order->isPayable())
                                            {{-- El pedido quedó esperando el pago porque el
                                                 comprador cerró el modal o la sesión del
                                                 checkout se perdió. Sin este acceso se
                                                 queda sin forma de pagarlo. --}}
                                            <a href="{{ route('checkout', ['pagar' => $order->order_number]) }}"
                                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">
                                                <flux:icon name="credit-card" class="size-4" /> Pagar ahora
                                            </a>
                                        @endif
                                        <a href="{{ route('account.orders.show', ['order' => $order->order_number]) }}"
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
                                            Ver <flux:icon name="arrow-right" class="size-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($orders->hasPages())
            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    @endif
</div>