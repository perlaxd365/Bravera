<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Producto</th>
                <th class="px-6 py-3">Categoría</th>
                <th class="px-6 py-3">Marca</th>
                <th class="px-6 py-3 text-center">Estado</th>
                <th class="px-6 py-3 text-center">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($products as $product)
                <tr wire:key="product-{{ $product->id }}" class="transition hover:bg-gray-50">
                    <td class="px-6 py-4 text-gray-500">{{ $product->id }}</td>
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                        <code class="mt-0.5 inline-block rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700">{{ $product->slug }}</code>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $product->category?->name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $product->brand?->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-center">
                        @if ($product->status)
                            <x-brevare.badge color="success">Activo</x-brevare.badge>
                        @else
                            <x-brevare.badge color="danger">Inactivo</x-brevare.badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="inline-flex gap-2">
                            <button type="button" wire:click="$dispatch('product-edit', { id: {{ $product->id }} })"
                                class="rounded-full border border-gray-300 p-2 text-gray-700 transition hover:bg-gray-50"
                                aria-label="Editar" title="Editar">
                                <flux:icon name="pencil" class="size-4" />
                            </button>
                            <button type="button" wire:click="delete({{ $product->id }})"
                                wire:confirm="¿Está seguro de eliminar este producto?"
                                class="rounded-full border border-red-300 p-2 text-red-600 transition hover:bg-red-50"
                                aria-label="Eliminar" title="Eliminar">
                                <flux:icon name="trash" class="size-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-14 text-center">
                        <x-brevare.empty-state icon="cube" title="No existen productos registrados" description="Crea el primer producto para comenzar." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($products->hasPages())
    <div class="border-t border-gray-100 p-4">
        {{ $products->links() }}
    </div>
@endif