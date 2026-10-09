<div>
    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-amber-800">Catálogo</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">Reseñas</h1>
                <p class="mt-1 text-sm text-gray-600">Modera las opiniones de los clientes antes de publicarlas en la tienda.</p>
            </div>
        </header>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex flex-wrap gap-2">
                    @foreach (['pending' => 'Pendientes', 'approved' => 'Aprobadas', 'rejected' => 'Rechazadas', 'all' => 'Todas'] as $key => $label)
                        <button type="button" wire:click="$set('status', '{{ $key }}')"
                            class="inline-flex min-h-9 items-center gap-2 rounded-full px-3.5 text-xs font-semibold transition {{ $status === $key ? 'bg-gray-950 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $label }}
                            @if (isset($counts[$key]))
                                <span class="rounded-full bg-white/20 px-1.5 text-[11px]">{{ $counts[$key] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="relative w-full sm:max-w-xs">
                    <flux:icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="Producto, cliente o comentario…"
                        class="min-h-11 w-full rounded-xl border-gray-300 pl-9 pr-4 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($reviews as $review)
                    <article wire:key="review-{{ $review->id }}" class="grid gap-4 p-5 sm:grid-cols-[1fr_auto] sm:items-start">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="flex items-center gap-0.5">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <flux:icon name="star" @class([
                                            'size-4',
                                            'fill-amber-500 text-amber-500' => $star <= $review->rating,
                                            'text-gray-300' => $star > $review->rating,
                                        ]) />
                                    @endfor
                                </div>
                                <span class="text-xs font-semibold text-gray-500">{{ $review->rating }}/5</span>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                    'bg-amber-100 text-amber-800' => $review->status === \App\Models\ProductReview::STATUS_PENDING,
                                    'bg-emerald-100 text-emerald-800' => $review->status === \App\Models\ProductReview::STATUS_APPROVED,
                                    'bg-red-100 text-red-700' => $review->status === \App\Models\ProductReview::STATUS_REJECTED,
                                ])>{{ $review->statusLabel() }}</span>
                            </div>

                            <p class="mt-2 text-sm leading-relaxed text-gray-700">{{ $review->comment }}</p>

                            <p class="mt-2 text-xs text-gray-500">
                                <span class="font-semibold text-gray-700">{{ $review->user?->name ?? 'Cliente' }}</span>
                                · {{ $review->user?->email }}
                                · producto:
                                @if ($review->product)
                                    <a href="{{ route('store.product', ['slug' => $review->product->slug]) }}" target="_blank" rel="noopener noreferrer" class="font-medium text-gray-700 underline underline-offset-2">{{ $review->product->name }}</a>
                                @else
                                    <span class="text-gray-400">eliminado</span>
                                @endif
                                · {{ $review->created_at?->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            @if ($review->status !== \App\Models\ProductReview::STATUS_APPROVED)
                                <button type="button" wire:click="approve({{ $review->id }})"
                                    class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-emerald-700 px-4 text-xs font-semibold text-white transition hover:bg-emerald-800">
                                    <flux:icon name="check" class="size-4" /> Aprobar
                                </button>
                            @endif
                            @if ($review->status !== \App\Models\ProductReview::STATUS_REJECTED)
                                <button type="button" wire:click="reject({{ $review->id }})"
                                    class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-gray-100 px-4 text-xs font-semibold text-gray-700 transition hover:bg-gray-200">
                                    <flux:icon name="x-mark" class="size-4" /> Rechazar
                                </button>
                            @endif
                            <button type="button" wire:click="delete({{ $review->id }})" wire:confirm="¿Eliminar esta reseña definitivamente?"
                                class="inline-flex min-h-10 items-center gap-1.5 rounded-full px-4 text-xs font-semibold text-red-600 transition hover:bg-red-50">
                                <flux:icon name="trash" class="size-4" /> Eliminar
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <x-brevare.empty-state icon="star" title="No hay reseñas" description="Las opiniones de los clientes aparecerán aquí para su moderación." />
                    </div>
                @endforelse
            </div>

            @if ($reviews->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">{{ $reviews->links() }}</div>
            @endif
        </section>
    </div>
</div>
