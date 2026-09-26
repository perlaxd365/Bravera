<div class="mx-auto max-w-7xl">
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-6 py-5">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">Categorías</h1>
                <p class="mt-0.5 text-sm text-gray-500">Administra las categorías de los productos.</p>
            </div>

            <livewire:admin.catalog.categories.form />

            <button wire:click="$dispatch('category-create')"
                class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                <flux:icon name="plus" class="size-4" /> Nueva categoría
            </button>
        </header>

        <div class="border-b border-gray-100 p-6">
            <div class="relative max-w-sm">
                <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input type="text" placeholder="Buscar categoría..." wire:model.live.debounce.300ms="search"
                    class="w-full rounded-full border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-6 py-3">#</th>
                        <th class="px-6 py-3">Nombre</th>
                        <th class="px-6 py-3">Slug</th>
                        <th class="px-6 py-3">Categoría Padre</th>
                        <th class="px-6 py-3">Estado</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($categories as $category)
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-500">{{ $category->id }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                <div class="flex items-center gap-3">
                                    @if ($category->image)
                                        <img src="{{ $category->image }}" alt="{{ $category->name }}" class="size-10 rounded-lg object-cover">
                                    @endif
                                    {{ $category->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700">{{ $category->slug }}</code></td>
                            <td class="px-6 py-4 text-gray-600">{{ optional($category->parent)->name ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @if ($category->is_visible)
                                    <x-bravera.badge color="success">Activo</x-bravera.badge>
                                @else
                                    <x-bravera.badge color="neutral">Inactivo</x-bravera.badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex gap-2">
                                    <button wire:click="$dispatch('category-edit',{id:{{ $category->id }}})"
                                        class="rounded-full border border-gray-300 p-2 text-gray-700 transition hover:bg-gray-50"
                                        aria-label="Editar">
                                        <flux:icon name="pencil" class="size-4" />
                                    </button>
                                    <button wire:click="delete({{ $category->id }})"
                                        wire:confirm="¿Está seguro de eliminar esta categoría?"
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
                                <x-bravera.empty-state icon="cube" title="No existen categorías registradas" description="Crea la primera categoría para comenzar." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>