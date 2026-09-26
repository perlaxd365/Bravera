<div>
    <div class="mx-auto max-w-7xl">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-6 py-5">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-gray-900">Valores</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Administra los valores de los atributos.</p>
                </div>

                <button wire:click="$dispatch('attribute-value-create')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                    <flux:icon name="plus" class="size-4" /> Nuevo Valor
                </button>
            </header>

            <div class="border-b border-gray-100 p-6">
                <div class="flex flex-wrap gap-3">
                    <div class="relative max-w-sm">
                        <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        <input type="text" placeholder="Buscar valor..." wire:model.live.debounce.300ms="search"
                            class="w-full rounded-full border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    </div>

                    <div class="relative w-full max-w-sm">
                        <select wire:model.live="attributeFilter"
                            class="w-full appearance-none rounded-full border border-gray-300 bg-white py-2 pl-4 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <option value="">-- Todos los atributos --</option>
                            @foreach ($attributes as $attribute)
                                <option value="{{ $attribute->id }}">
                                    {{ $attribute->name }}
                                </option>
                            @endforeach
                        </select>
                        <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-6 py-3">#</th>
                            <th class="px-6 py-3">Atributo</th>
                            <th class="px-6 py-3">Valor</th>
                            <th class="px-6 py-3">Color</th>
                            <th class="px-6 py-3">Orden</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($values as $value)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-6 py-4 text-gray-500">{{ $value->id }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $value->attribute->name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $value->value }}</td>
                                <td class="px-6 py-4">
                                    @if ($value->color)
                                        <span class="inline-flex items-center gap-2 text-gray-600">
                                            <span class="inline-block size-4 rounded-full ring-1 ring-inset ring-black/10" style="background:{{ $value->color }}"></span>
                                            {{ $value->color }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $value->sort_order }}</td>
                                <td class="px-6 py-4">
                                    @if ($value->is_active)
                                        <x-bravera.badge color="success">Activo</x-bravera.badge>
                                    @else
                                        <x-bravera.badge color="neutral">Inactivo</x-bravera.badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex gap-2">
                                        <button wire:click="$dispatch('attribute-value-edit', { id: {{ $value->id }} })"
                                            class="rounded-full border border-gray-300 p-2 text-gray-700 transition hover:bg-gray-50"
                                            aria-label="Editar">
                                            <flux:icon name="pencil" class="size-4" />
                                        </button>
                                        <button wire:click="delete({{ $value->id }})"
                                            wire:confirm="¿Eliminar este registro?"
                                            class="rounded-full border border-red-300 p-2 text-red-600 transition hover:bg-red-50"
                                            aria-label="Eliminar">
                                            <flux:icon name="trash" class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-14 text-center">
                                    <x-bravera.empty-state icon="swatch" title="No existen registros" description="Crea el primer valor para comenzar." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($values->hasPages())
                <div class="border-t border-gray-100 p-4">
                    {{ $values->links() }}
                </div>
            @endif
        </div>
    </div>

    <livewire:admin.catalog.attribute-values.form />
</div>