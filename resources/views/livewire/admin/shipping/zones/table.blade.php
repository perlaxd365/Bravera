<div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-6 py-5">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-gray-900">Zonas de envío</h1>
            <p class="mt-0.5 text-sm text-gray-500">Administra las zonas de envío.</p>
        </div>

        <button wire:click="$dispatch('shipping-zone-create')"
            class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
            <flux:icon name="plus" class="size-4" /> Nueva zona
        </button>
    </header>

    <div class="border-b border-gray-100 p-6">
        <div class="relative max-w-sm">
            <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
            <input type="text" placeholder="Buscar zona..." wire:model.live.debounce.300ms="search"
                class="w-full rounded-full border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">Nombre</th>
                    <th class="px-6 py-3">Nivel</th>
                    <th class="px-6 py-3">Ubicación</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($zones as $zone)
                    <tr class="transition hover:bg-gray-50">
                        <td class="px-6 py-4 text-gray-500">{{ $zone->id }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $zone->name }}</td>
                        <td class="px-6 py-4">
                            @switch($zone->type->value)
                                @case('department')
                                    <x-brevare.badge color="primary">Departamento</x-brevare.badge>
                                @break

                                @case('province')
                                    <x-brevare.badge color="info">Provincia</x-brevare.badge>
                                @break

                                @case('district')
                                    <x-brevare.badge color="neutral">Distrito</x-brevare.badge>
                                @break

                                @default
                                    <x-brevare.badge color="dark">{{ $zone->type->value }}</x-brevare.badge>
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $zone->location?->name ?? '-' }}</td>
                        <td class="px-6 py-4">
                            @if ($zone->status)
                                <x-brevare.badge color="success">Activo</x-brevare.badge>
                            @else
                                <x-brevare.badge color="neutral">Inactivo</x-brevare.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-flex gap-2">
                                <button wire:click="$dispatch('shipping-zone-edit', { id: {{ $zone->id }} })"
                                    class="rounded-full border border-gray-300 p-2 text-gray-700 transition hover:bg-gray-50"
                                    aria-label="Editar">
                                    <flux:icon name="pencil" class="size-4" />
                                </button>
                                <button wire:click="toggleStatus({{ $zone->id }})"
                                    class="rounded-full border border-gray-300 p-2 text-gray-700 transition hover:bg-gray-50"
                                    aria-label="Cambiar estado">
                                    <flux:icon name="power" class="size-4" />
                                </button>
                                <button wire:click="delete({{ $zone->id }})"
                                    wire:confirm="¿Eliminar esta zona de envío?"
                                    class="rounded-full border border-red-300 p-2 text-red-600 transition hover:bg-red-50"
                                    aria-label="Eliminar">
                                    <flux:icon name="trash" class="size-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-14 text-center">
                            <x-brevare.empty-state icon="truck" title="No existen zonas de envío registradas" description="Crea la primera zona de envío para comenzar." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($zones->hasPages())
        <div class="border-t border-gray-100 p-4">
            {{ $zones->links() }}
        </div>
    @endif
</div>