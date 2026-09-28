<div class="mx-auto max-w-7xl">
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Por enviar</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totals['pending'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Enviadas</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totals['sent'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Por pagar a proveedores</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">S/ {{ number_format((float) $totals['payable'], 2) }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-6 py-5">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">Órdenes a proveedores</h1>
                <p class="mt-0.5 text-sm text-gray-500">Administra las órdenes enviadas a los proveedores.</p>
            </div>
        </header>

        <div class="border-b border-gray-100 p-6">
            <div class="relative max-w-sm">
                <select wire:model.live="status"
                    class="appearance-none rounded-full border border-gray-300 bg-white py-2 pl-4 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    <option value="">Todas las órdenes</option>
                    @foreach (\App\Enums\SupplierOrderStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
                <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-6 py-3">Orden proveedor</th>
                        <th class="px-6 py-3">Proveedor</th>
                        <th class="px-6 py-3">Pedido</th>
                        <th class="px-6 py-3 text-right">Monto</th>
                        <th class="px-6 py-3 text-center">Envío</th>
                        <th class="px-6 py-3 text-center">Estado</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($supplierOrders as $so)
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ $so->supplier_order_number }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $so->supplier?->business_name }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.orders.show', $so->order) }}"
                                    class="font-medium text-gray-700 transition hover:text-gray-900">
                                    {{ $so->order?->order_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">S/ {{ number_format((float) $so->total_cost, 2) }}</td>
                            <td class="px-6 py-4 text-center">
                                @if ($so->payment)
                                    <x-brevare.badge color="success">Pagado</x-brevare.badge>
                                @else
                                    <x-brevare.badge color="warning">Pendiente</x-brevare.badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <x-brevare.badge :color="$so->status->badgeColor()">{{ $so->status->label() }}</x-brevare.badge>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="relative">
                                        <select wire:model="quickStatus.{{ $so->id }}"
                                            class="appearance-none rounded-full border border-gray-300 bg-white py-1.5 pl-3 pr-8 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                            @foreach (\App\Enums\SupplierOrderStatus::cases() as $s)
                                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                            @endforeach
                                        </select>
                                        <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-2.5 top-1/2 size-3.5 -translate-y-1/2 text-gray-400" />
                                    </div>
                                    <button wire:click="updateStatus({{ $so->id }})"
                                        class="rounded-full border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                        Actualizar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <x-brevare.empty-state icon="truck" title="No hay órdenes a proveedores" description="Las órdenes de compra enviadas a los proveedores aparecerán en esta lista." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($supplierOrders->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $supplierOrders->links() }}
            </div>
        @endif
    </div>
</div>