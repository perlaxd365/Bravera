<div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-6 py-5">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-gray-900">Tarifas de envío</h1>
            <p class="mt-0.5 text-sm text-gray-500">Administra las tarifas de envío.</p>
        </div>

        <button type="button" wire:click="$dispatch('shipping-rate-create')"
            class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
            <flux:icon name="plus" class="size-4" /> Nueva tarifa
        </button>
    </header>

    <div class="border-b border-gray-100 p-6">
        <div class="relative max-w-sm">
            <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
            <input type="text" placeholder="Buscar tarifa..." wire:model.live.debounce.300ms="search"
                class="w-full rounded-full border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">Zona</th>
                    <th class="px-6 py-3">Proveedor</th>
                    <th class="px-6 py-3">Producto</th>
                    <th class="px-6 py-3">Variante</th>
                    <th class="px-6 py-3">Precio</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rates as $rate)
                    <tr class="transition hover:bg-gray-50">
                        <td class="px-6 py-4 text-gray-500">{{ $rate->id }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $rate->shippingZone?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $rate->supplier?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $rate->product?->name ?? 'General' }}</td>
                        <td class="px-6 py-4">
                            @if ($rate->productVariant)
                                <span class="whitespace-nowrap text-gray-900">
                                    {{ $rate->productVariant->sku }}
                                </span>
                            @else
                                <span class="text-gray-500">
                                    General
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-semibold text-gray-900">
                            S/ {{ number_format($rate->price, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($rate->status)
                                <x-brevare.badge color="success">Activo</x-brevare.badge>
                            @else
                                <x-brevare.badge color="neutral">Inactivo</x-brevare.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-flex gap-2">
                                <button type="button" wire:click="$dispatch('shipping-rate-edit', { id: {{ $rate->id }} })"
                                    title="Editar"
                                    class="rounded-full border border-gray-300 p-2 text-gray-700 transition hover:bg-gray-50"
                                    aria-label="Editar">
                                    <flux:icon name="pencil" class="size-4" />
                                </button>
                                <button type="button" wire:click="toggleStatus({{ $rate->id }})"
                                    title="Cambiar estado"
                                    class="rounded-full border border-gray-300 p-2 text-gray-700 transition hover:bg-gray-50"
                                    aria-label="Cambiar estado">
                                    <flux:icon name="power" class="size-4" />
                                </button>
                                <button type="button" wire:click="delete({{ $rate->id }})"
                                    wire:confirm="¿Eliminar esta tarifa de envío?"
                                    title="Eliminar"
                                    class="rounded-full border border-red-300 p-2 text-red-600 transition hover:bg-red-50"
                                    aria-label="Eliminar">
                                    <flux:icon name="trash" class="size-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-14 text-center">
                            <x-brevare.empty-state icon="truck" title="No existen tarifas de envío registradas" description="Crea la primera tarifa de envío para comenzar." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rates->hasPages())
        <div class="border-t border-gray-100 p-4">
            {{ $rates->links() }}
        </div>
    @endif
</div>