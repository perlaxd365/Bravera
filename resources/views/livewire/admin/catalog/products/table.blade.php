<div class="responsive-table-scroll" role="region" aria-label="Tabla desplazable horizontalmente" tabindex="0">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Imagen</th>
                <th class="px-6 py-3">Producto</th>
                <th class="px-6 py-3">Categoría</th>
                <th class="px-6 py-3">Marca</th>
                <th class="px-6 py-3 text-center">Estado</th>
                <th class="px-6 py-3">Publicación</th>
                <th class="px-6 py-3 text-right">Visitas web · 30 d</th>
                <th class="px-6 py-3 text-right">Visitas Meta · 30 d</th>
                <th class="px-6 py-3 text-center">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($products as $product)
                @php($readiness = $readinessByProduct[$product->id])
                <tr wire:key="product-{{ $product->id }}" class="transition hover:bg-gray-50">
                    <td class="px-6 py-4 text-gray-500">{{ $product->id }}</td>
                    <td class="px-6 py-4">
                        @if ($product->coverImage())
                            <img src="{{ $product->coverImage() }}" alt="{{ $product->name }}"
                                loading="lazy" class="size-14 rounded-xl border border-gray-200 bg-gray-50 object-cover">
                        @else
                            <div class="flex size-14 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 text-gray-400" aria-label="Sin imagen">
                                <flux:icon name="photo" class="size-5" />
                            </div>
                        @endif
                    </td>
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
                    <td class="px-6 py-4">
                        <div class="min-w-36">
                            <div class="flex items-center justify-between gap-3">
                                <span @class([
                                    'text-sm font-bold',
                                    'text-emerald-700' => $readiness['ready'],
                                    'text-amber-800' => ! $readiness['ready'] && $readiness['percent'] >= 50,
                                    'text-red-700' => ! $readiness['ready'] && $readiness['percent'] < 50,
                                ])>{{ $readiness['percent'] }}%</span>
                                <span class="text-[10px] font-medium text-gray-500">
                                    {{ $readiness['ready'] ? 'Listo' : $readiness['completed'].'/'.$readiness['total'] }}
                                </span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100" role="progressbar"
                                aria-label="Preparación de {{ $product->name }}"
                                aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $readiness['percent'] }}">
                                <div @class([
                                    'h-full rounded-full',
                                    'bg-emerald-600' => $readiness['ready'],
                                    'bg-amber-500' => ! $readiness['ready'] && $readiness['percent'] >= 50,
                                    'bg-red-500' => ! $readiness['ready'] && $readiness['percent'] < 50,
                                ]) style="width: {{ $readiness['percent'] }}%"></div>
                            </div>
                            @if ($readiness['missing'])
                                <p class="mt-1.5 max-w-40 truncate text-[10px] text-gray-500" title="{{ implode(', ', $readiness['missing']) }}">
                                    Falta: {{ $readiness['missing'][0] }}
                                </p>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="font-semibold tabular-nums text-gray-900">
                            {{ number_format((int) $product->web_visits_30d, 0, ',', '.') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if ($product->active_meta_ads_count > 0)
                            <span class="font-semibold tabular-nums text-gray-900">
                                {{ number_format((int) $product->meta_visits_30d, 0, ',', '.') }}
                            </span>
                            <span class="mt-0.5 block text-[10px] font-medium uppercase tracking-wide text-emerald-700">
                                Anuncio activo
                            </span>
                        @else
                            <span class="text-gray-400" aria-label="Sin anuncio activo en Meta" title="Sin anuncio activo en Meta">—</span>
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
                    <td colspan="10" class="px-6 py-14 text-center">
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
