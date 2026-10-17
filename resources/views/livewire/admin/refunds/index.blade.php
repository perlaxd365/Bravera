<div>
    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-amber-800">Ventas</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">Devoluciones</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Valida las devoluciones solicitadas. El reembolso se envía a la pasarela recién al aprobarlas.
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-3 text-right shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Por validar</p>
                <p class="mt-0.5 text-lg font-bold tracking-tight text-gray-900">
                    S/ {{ number_format($pendingTotal, 2) }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $counts['pending'] ?? 0 }} {{ ($counts['pending'] ?? 0) === 1 ? 'solicitud' : 'solicitudes' }}
                </p>
            </div>
        </header>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex flex-wrap gap-2">
                    @php
                        $tabs = collect(\App\Enums\RefundStatus::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                            ->put('all', 'Todas');
                    @endphp
                    @foreach ($tabs as $key => $label)
                        <button type="button" wire:click="$set('status', '{{ $key }}')"
                            class="inline-flex min-h-9 items-center gap-2 rounded-full px-3.5 text-xs font-semibold transition {{ $status === $key ? 'bg-gray-950 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $label }}
                            @if ($key !== 'all')
                                <span class="rounded-full bg-white/20 px-1.5 text-[11px]">{{ $counts[$key] ?? 0 }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="relative w-full sm:max-w-xs">
                    <flux:icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="N.º de pedido o cliente…"
                        class="min-h-11 w-full rounded-xl border-gray-300 pl-9 pr-4 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($refunds as $refund)
                    <article wire:key="refund-{{ $refund->id }}" class="p-5">
                        <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-start">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($refund->order)
                                        <a href="{{ route('admin.orders.show', $refund->order) }}"
                                            class="text-sm font-semibold text-gray-900 underline underline-offset-2">
                                            Pedido {{ $refund->order->order_number }}
                                        </a>
                                    @else
                                        <span class="text-sm font-semibold text-gray-500">Pedido eliminado</span>
                                    @endif

                                    <x-brevare.badge :color="$refund->status->badgeColor()">{{ $refund->statusLabel() }}</x-brevare.badge>

                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700">
                                        S/ {{ number_format((float) $refund->amount, 2) }}
                                    </span>
                                </div>

                                <p class="mt-2 text-sm text-gray-700">
                                    {{ $refund->order?->customer_snapshot['name'] ?? $refund->order?->user?->name ?? 'Cliente' }}
                                    @if ($refund->order?->customer_snapshot['email'] ?? $refund->order?->user?->email)
                                        <span class="text-gray-500">· {{ $refund->order->customer_snapshot['email'] ?? $refund->order->user->email }}</span>
                                    @endif
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Motivo: <span class="font-medium text-gray-700">{{ $refund->reasonLabel() }}</span>
                                    @if ($refund->payment)
                                        · {{ $refund->payment->gateway }} · {{ $refund->payment->gateway_transaction_id ?: 'sin cargo' }}
                                    @endif
                                    · solicitada el {{ optional($refund->requested_at ?? $refund->created_at)->format('d/m/Y H:i') }}
                                    @if ($refund->requestedBy)
                                        por {{ $refund->requestedBy->name }}
                                    @endif
                                </p>

                                @if ($refund->notes)
                                    <p class="mt-2 rounded-xl bg-gray-50 px-3 py-2 text-sm text-gray-600">{{ $refund->notes }}</p>
                                @endif

                                @if ($refund->validated_at)
                                    <p class="mt-2 text-xs text-gray-500">
                                        Validada el {{ $refund->validated_at->format('d/m/Y H:i') }}
                                        @if ($refund->validatedBy)
                                            por <span class="font-medium text-gray-700">{{ $refund->validatedBy->name }}</span>
                                        @endif
                                        @if ($refund->gateway_refund_id)
                                            · operación {{ $refund->gateway_refund_id }}
                                        @endif
                                    </p>
                                @endif

                                @if ($refund->resolution_notes)
                                    <p @class([
                                        'mt-2 rounded-xl px-3 py-2 text-sm',
                                        'bg-amber-50 text-amber-900' => $refund->status === \App\Enums\RefundStatus::PENDING,
                                        'bg-red-50 text-red-700' => in_array($refund->status, [\App\Enums\RefundStatus::FAILED, \App\Enums\RefundStatus::REJECTED], true),
                                        'bg-sky-50 text-sky-800' => $refund->status === \App\Enums\RefundStatus::PROCESSING,
                                        'bg-emerald-50 text-emerald-800' => $refund->status === \App\Enums\RefundStatus::COMPLETED,
                                    ])>{{ $refund->resolution_notes }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                @if ($refund->isPending())
                                    <button type="button" wire:click="approve({{ $refund->id }})"
                                        wire:loading.attr="disabled" wire:target="approve({{ $refund->id }})"
                                        wire:confirm="¿Aprobar y devolver S/ {{ number_format((float) $refund->amount, 2) }} al cliente? El reembolso se envía a la pasarela ahora mismo."
                                        class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-emerald-700 px-4 text-xs font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-60">
                                        <flux:icon name="arrow-uturn-left" class="size-4" />
                                        <span wire:loading.remove wire:target="approve({{ $refund->id }})">Aprobar y devolver</span>
                                        <span wire:loading wire:target="approve({{ $refund->id }})">Enviando…</span>
                                    </button>

                                    <button type="button" wire:click="toggleRejectForm({{ $refund->id }})"
                                        class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-gray-100 px-4 text-xs font-semibold text-gray-700 transition hover:bg-gray-200">
                                        <flux:icon name="x-mark" class="size-4" /> Rechazar
                                    </button>
                                @endif

                                @php
                                    $isCulqiPayment = $refund->payment?->gateway === 'culqi';
                                @endphp

                                @if ($refund->status === \App\Enums\RefundStatus::PROCESSING && $isCulqiPayment)
                                    <button type="button" wire:click="refresh({{ $refund->id }})"
                                        wire:loading.attr="disabled" wire:target="refresh({{ $refund->id }})"
                                        class="inline-flex min-h-10 items-center gap-1.5 rounded-full border border-gray-300 bg-white px-4 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-wait disabled:opacity-60">
                                        <flux:icon name="arrow-path" class="size-4" />
                                        <span wire:loading.remove wire:target="refresh({{ $refund->id }})">Verificar en Culqi</span>
                                        <span wire:loading wire:target="refresh({{ $refund->id }})">Consultando…</span>
                                    </button>
                                @elseif ($refund->status === \App\Enums\RefundStatus::PROCESSING)
                                    <span class="text-xs text-gray-500">
                                        Mercado Pago confirmará la devolución en su panel.
                                    </span>
                                @endif
                            </div>
                        </div>

                        @php
                            $chargeReference = (string) ($refund->payment->gateway_charge_id ?: $refund->payment->gateway_transaction_id);
                            $needsCharge = $refund->isPending()
                                && $refund->payment !== null
                                && $refund->payment->gateway === 'culqi'
                                && ! str_starts_with($chargeReference, 'chr_');
                        @endphp

                        @if ($needsCharge)
                            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                                <p class="text-sm font-semibold text-amber-900">Este pago no tiene un cargo de Culqi</p>
                                <p class="mt-1 text-xs leading-relaxed text-amber-900/80">
                                    Los métodos asíncronos (billeteras, banca móvil, agentes, Cuotéalo) se cobran con una
                                    orden, y Culqi solo devuelve contra un cargo. Copia el cargo <span class="font-mono">chr_...</span>
                                    del panel de Culqi de este pago y vincúlalo para poder aprobar la devolución.
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <input type="text" wire:model="chargeId.{{ $refund->id }}" placeholder="chr_..."
                                        class="w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/10">
                                    <button type="button" wire:click="linkCharge({{ $refund->id }})"
                                        wire:loading.attr="disabled" wire:target="linkCharge({{ $refund->id }})"
                                        class="rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:cursor-wait disabled:opacity-60">
                                        <span wire:loading.remove wire:target="linkCharge({{ $refund->id }})">Vincular cargo</span>
                                        <span wire:loading wire:target="linkCharge({{ $refund->id }})">Verificando…</span>
                                    </button>
                                </div>
                                @error('chargeId.'.$refund->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        @if ($refund->isPending() && ($showRejectFor[$refund->id] ?? false))
                            <div class="mt-4 rounded-xl border border-red-100 bg-red-50/50 p-4">
                                <label for="reject-{{ $refund->id }}" class="text-sm font-semibold text-red-700">
                                    Motivo del rechazo (queda registrado)
                                </label>
                                <textarea id="reject-{{ $refund->id }}" rows="2" wire:model="rejectReason.{{ $refund->id }}"
                                    placeholder="Ej.: la devolución no cumple la política de cambios y devoluciones."
                                    class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/10"></textarea>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <button type="button" wire:click="reject({{ $refund->id }})"
                                        wire:loading.attr="disabled" wire:target="reject({{ $refund->id }})"
                                        class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500 disabled:cursor-wait disabled:opacity-60">
                                        <span wire:loading.remove wire:target="reject({{ $refund->id }})">Sí, rechazar la devolución</span>
                                        <span wire:loading wire:target="reject({{ $refund->id }})">Guardando…</span>
                                    </button>
                                    <button type="button" wire:click="toggleRejectForm({{ $refund->id }})"
                                        class="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                                        Volver
                                    </button>
                                </div>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <x-brevare.empty-state icon="arrow-uturn-left" title="No hay devoluciones en este estado"
                            description="Las solicitudes de devolución se registran desde el detalle del pedido y aparecen aquí para su validación." />
                    </div>
                @endforelse
            </div>

            @if ($refunds->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">{{ $refunds->links() }}</div>
            @endif
        </section>
    </div>
</div>
