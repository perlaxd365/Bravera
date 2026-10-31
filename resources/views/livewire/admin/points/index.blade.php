<div>
    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-amber-800">Fidelización</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">Brevare Puntos</h1>
                <p class="mt-1 text-sm text-gray-600">Valida solicitudes de canje y registra el depósito correspondiente.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-2 font-semibold text-amber-900">
                    {{ $stats['pending'] }} pendientes
                </span>
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 font-semibold text-emerald-800">
                    S/ {{ number_format($stats['paid_amount'], 2) }} depositados
                </span>
            </div>
        </header>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap gap-2 border-b border-gray-100 p-4">
                @foreach (['pending' => 'Pendientes', 'approved' => 'Aprobados', 'paid' => 'Depositados', 'rejected' => 'Rechazados', 'all' => 'Todos'] as $value => $label)
                    <button type="button" wire:click="$set('status', '{{ $value }}')"
                        class="rounded-full px-4 py-2 text-sm font-medium transition {{ $status === $value ? 'bg-gray-950 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="responsive-table-scroll" role="region" aria-label="Tabla desplazable horizontalmente" tabindex="0">
                <table class="min-w-full divide-y divide-gray-100 text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3.5">Solicitud</th>
                            <th scope="col" class="px-5 py-3.5">Cliente</th>
                            <th scope="col" class="px-5 py-3.5">Canje</th>
                            <th scope="col" class="px-5 py-3.5">Estado</th>
                            <th scope="col" class="px-5 py-3.5">Validación</th>
                            <th scope="col" class="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($redemptions as $redemption)
                            @php
                                $statusLabel = match ($redemption->status) {
                                    'approved' => 'Aprobado',
                                    'paid' => 'Depositado',
                                    'rejected' => 'Rechazado',
                                    default => 'Pendiente',
                                };
                                $statusClass = match ($redemption->status) {
                                    'approved' => 'bg-sky-100 text-sky-800',
                                    'paid' => 'bg-emerald-100 text-emerald-800',
                                    'rejected' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-amber-100 text-amber-800',
                                };
                            @endphp
                            <tr class="align-top">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-950">#{{ $redemption->id }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $redemption->created_at->translatedFormat('d M Y, H:i') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-gray-900">{{ $redemption->user?->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $redemption->user?->email }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-950">{{ number_format($redemption->points) }} puntos</p>
                                    <p class="text-xs text-emerald-700">S/ {{ number_format((float) $redemption->amount, 2) }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-5 py-4 text-xs text-gray-500">
                                    @if ($redemption->reviewer)
                                        <p>{{ $redemption->reviewer->name }}</p>
                                        <p>{{ $redemption->reviewed_at?->translatedFormat('d M Y, H:i') }}</p>
                                    @else
                                        —
                                    @endif
                                    @if ($redemption->payout_reference)
                                        <p class="mt-1 font-medium text-gray-700">Ref: {{ $redemption->payout_reference }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($redemption->status === 'pending')
                                            <button type="button" wire:click="approve({{ $redemption->id }})"
                                                class="rounded-full border border-sky-300 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-50">
                                                Validar
                                            </button>
                                        @endif
                                        @if (in_array($redemption->status, ['pending', 'approved'], true))
                                            <button type="button"
                                                @click="const ref = prompt('Referencia del depósito Brevare Puntos'); if (ref) $wire.markPaid({{ $redemption->id }}, ref)"
                                                class="rounded-full border border-emerald-300 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                                Depositar
                                            </button>
                                            <button type="button"
                                                @click="const reason = prompt('Motivo del rechazo'); if (reason !== null) $wire.reject({{ $redemption->id }}, reason)"
                                                class="rounded-full border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                                Rechazar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">No hay solicitudes en este estado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 p-4">{{ $redemptions->links() }}</div>
        </section>
    </div>
</div>
