<div>
    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-amber-800">Relación con clientes</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">Clientes</h1>
                <p class="mt-1 text-sm text-gray-600">Cuentas registradas, pedidos recientes y acceso rápido para dar seguimiento.</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                <span class="font-bold">{{ number_format($customers->total()) }}</span> clientes registrados
            </div>
        </header>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-4 sm:p-5">
                <label for="customer-search" class="sr-only">Buscar clientes</label>
                <div class="relative max-w-lg">
                    <flux:icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <input id="customer-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="Nombre, correo, teléfono o pedido…"
                        class="min-h-11 w-full rounded-xl border-gray-300 pl-9 pr-4 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3.5">Cliente</th>
                            <th scope="col" class="px-5 py-3.5">Contacto</th>
                            <th scope="col" class="px-5 py-3.5">Pedidos</th>
                            <th scope="col" class="px-5 py-3.5">Último pedido</th>
                            <th scope="col" class="px-5 py-3.5">Registro</th>
                            <th scope="col" class="px-5 py-3.5 text-right">Seguimiento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($customers as $customer)
                            @php
                                $latestOrder = $customer->orders->first();
                                $phone = $customer->phone ?: $customer->customerAddresses->first()?->phone;
                                $whatsappUrl = $this->whatsappUrl($customer);
                            @endphp
                            <tr wire:key="customer-{{ $customer->id }}" class="align-top transition hover:bg-amber-50/40">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-900">{{ $customer->name }}</p>
                                    <a href="mailto:{{ $customer->email }}" class="mt-1 inline-block text-xs text-gray-500 hover:text-amber-900">{{ $customer->email }}</a>
                                </td>
                                <td class="px-5 py-4 text-gray-700">
                                    @if ($phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="whitespace-nowrap hover:text-amber-900">{{ $phone }}</a>
                                    @else
                                        <span class="text-gray-400">Sin teléfono</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-medium text-gray-800">{{ $customer->orders_count }}</td>
                                <td class="px-5 py-4">
                                    @if ($latestOrder)
                                        <a href="{{ route('admin.orders.show', $latestOrder) }}" class="font-semibold text-gray-900 hover:text-amber-900">{{ $latestOrder->order_number }}</a>
                                        <span class="mt-1 block text-xs text-gray-500">{{ $latestOrder->status->label() }} · S/ {{ number_format((float) $latestOrder->total, 2) }}</span>
                                    @else
                                        <span class="text-gray-400">Aún sin pedidos</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $customer->created_at?->format('d/m/Y') }}</td>
                                <td class="px-5 py-4 text-right">
                                    @if ($whatsappUrl)
                                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-emerald-700 px-4 text-xs font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                                            <flux:icon name="chat-bubble-left-right" class="size-4" /> WhatsApp
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">Añade un teléfono</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <x-brevare.empty-state icon="users" title="No encontramos clientes" description="Cuando alguien cree una cuenta en la tienda, aparecerá aquí." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">{{ $customers->links() }}</div>
            @endif
        </section>

        <p class="text-xs leading-5 text-gray-500">WhatsApp abre un mensaje preparado para que el equipo lo revise y envíe manualmente. No se envían mensajes automáticamente.</p>
    </div>
</div>
