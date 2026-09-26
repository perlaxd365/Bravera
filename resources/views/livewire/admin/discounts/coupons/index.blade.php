<div class="mx-auto max-w-7xl">
    <livewire:admin.discounts.coupons.form />

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-6 py-5">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">Cupones de descuento</h1>
                <p class="mt-0.5 text-sm text-gray-500">Administra los cupones de descuento de la tienda.</p>
            </div>

            <button wire:click="$dispatch('coupon-create')"
                class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                <flux:icon name="plus" class="size-4" /> Nuevo cupón
            </button>
        </header>

        <div class="border-b border-gray-100 p-6">
            <div class="relative max-w-sm">
                <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por código o nombre..."
                    class="w-full rounded-full border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-6 py-3">Código</th>
                        <th class="px-6 py-3">Nombre</th>
                        <th class="px-6 py-3 text-center">Tipo</th>
                        <th class="px-6 py-3 text-right">Valor</th>
                        <th class="px-6 py-3 text-center">Usos</th>
                        <th class="px-6 py-3 text-center">Vigencia</th>
                        <th class="px-6 py-3 text-center">Estado</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($coupons as $coupon)
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-6 py-4"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-semibold text-gray-900">{{ $coupon->code }}</code></td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $coupon->name }}</td>
                            <td class="px-6 py-4 text-center text-gray-600">{{ $coupon->type->label() }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                @if ($coupon->type->value === 'percentage')
                                    {{ rtrim(rtrim(number_format($coupon->value, 2), '0'), '.') }}%
                                @else
                                    S/ {{ number_format((float) $coupon->value, 2) }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-gray-900">
                                {{ $coupon->usages_count }}
                                @if ($coupon->usage_limit)
                                    / {{ $coupon->usage_limit }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-xs text-gray-500 whitespace-nowrap">
                                @if ($coupon->starts_at || $coupon->ends_at)
                                    {{ $coupon->starts_at?->format('d/m/Y') }} - {{ $coupon->ends_at?->format('d/m/Y') }}
                                @else
                                    Indefinido
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="toggle({{ $coupon->id }})" class="transition hover:opacity-80" aria-label="Cambiar estado">
                                    @if ($coupon->is_active)
                                        <x-bravera.badge color="success">Activo</x-bravera.badge>
                                    @else
                                        <x-bravera.badge color="neutral">Inactivo</x-bravera.badge>
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex gap-2">
                                    <button wire:click="$dispatch('coupon-edit', { id: {{ $coupon->id }} })"
                                        class="rounded-full border border-gray-300 p-2 text-gray-700 transition hover:bg-gray-50"
                                        aria-label="Editar">
                                        <flux:icon name="pencil" class="size-4" />
                                    </button>
                                    <button wire:click="delete({{ $coupon->id }})"
                                        wire:confirm="¿Eliminar el cupón {{ $coupon->code }}?"
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
                                <x-bravera.empty-state icon="ticket" title="No hay cupones" description="Crea el primer cupón para comenzar." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($coupons->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $coupons->links() }}
            </div>
        @endif
    </div>
</div>