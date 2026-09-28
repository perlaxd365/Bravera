<div class="mx-auto max-w-7xl">
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-6 py-5">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">Pedidos</h1>
                <p class="mt-0.5 text-sm text-gray-500">Administra los pedidos de la tienda.</p>
            </div>
        </header>

        <div class="border-b border-gray-100 p-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full max-w-sm">
                    <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por número, cliente, cupón..."
                        class="w-full rounded-full border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                </div>

                <div class="relative">
                    <select wire:model.live="status"
                        class="appearance-none rounded-full border border-gray-300 bg-white py-2 pl-4 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        <option value="">Todos los estados</option>
                        @foreach (\App\Enums\OrderStatus::cases() as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-6 py-3">N.º pedido</th>
                        <th class="px-6 py-3">Cliente</th>
                        <th class="px-6 py-3 text-right">Total</th>
                        <th class="px-6 py-3 text-center">Pago</th>
                        <th class="px-6 py-3 text-center">Estado</th>
                        <th class="px-6 py-3 text-right">Fecha</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ $order->order_number }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $order->customer_snapshot['name'] ?? $order->user?->name }}</div>
                                <div class="text-xs text-gray-500">{{ $order->user?->email }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">S/ {{ number_format((float) $order->total, 2) }}</td>
                            <td class="px-6 py-4 text-center">
                                <x-brevare.badge :color="$order->payment_status->badgeColor()">{{ $order->payment_status->label() }}</x-brevare.badge>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <x-brevare.badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-brevare.badge>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                    class="inline-flex rounded-full border border-gray-300 p-2 text-gray-700 transition hover:bg-gray-50"
                                    aria-label="Ver pedido">
                                    <flux:icon name="eye" class="size-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <x-brevare.empty-state icon="shopping-cart" title="No hay pedidos" description="Los pedidos que se realicen en la tienda aparecerán en esta lista." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>