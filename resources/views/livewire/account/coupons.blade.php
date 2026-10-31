<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-gray-900">{{ app()->getLocale() === 'en' ? 'My earned coupons' : 'Mis cupones ganados' }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ app()->getLocale() === 'en' ? 'Each code is linked to your account and can be used once.' : 'Cada código está vinculado a tu cuenta y solo puede usarse una vez.' }}</p>
        </div>
        <a href="{{ route('store.roulette') }}" wire:navigate
            class="inline-flex min-h-11 items-center rounded-full bg-gray-950 px-5 text-sm font-semibold text-white hover:bg-amber-800">
            {{ app()->getLocale() === 'en' ? 'Go to rewards' : 'Ir a la ruleta' }}
        </a>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($spins as $spin)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                @if ($spin->coupon)
                    @php
                        $used = $spin->coupon->usages->isNotEmpty();
                        $completed = $spin->coupon->usages->contains(
                            fn ($usage) => $usage->order?->payment_status?->value === 'paid'
                        );
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[.16em] text-amber-800">
                                {{ $spin->created_at->format('d/m/Y') }}
                            </p>
                            <h3 class="mt-1 text-xl font-extrabold text-gray-950">{{ $spin->discount_percent }}% {{ app()->getLocale() === 'en' ? 'off' : 'de descuento' }}</h3>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ (float) $spin->min_subtotal > 0
                                    ? (app()->getLocale() === 'en' ? 'Minimum purchase ' : 'Compra mínima ').$storefront->formatFromPen((float) $spin->min_subtotal)
                                    : (app()->getLocale() === 'en' ? 'No minimum purchase' : 'Sin compra mínima') }}
                            </p>
                        </div>

                        @if ($completed)
                            <x-brevare.badge color="neutral">{{ app()->getLocale() === 'en' ? 'Redeemed' : 'Canjeado' }}</x-brevare.badge>
                        @elseif ($spin->claimed_at)
                            <x-brevare.badge color="success">{{ app()->getLocale() === 'en' ? 'Available' : 'Disponible' }}</x-brevare.badge>
                        @else
                            <x-brevare.badge color="warning">{{ app()->getLocale() === 'en' ? 'Ready to claim' : 'Pendiente de reclamar' }}</x-brevare.badge>
                        @endif
                    </div>

                    @if (! $spin->claimed_at)
                        <button type="button" wire:click="claim({{ $spin->id }})"
                            wire:loading.attr="disabled" wire:target="claim"
                            class="mt-4 inline-flex min-h-10 items-center rounded-full bg-amber-300 px-4 text-sm font-bold text-amber-950 hover:bg-amber-400">
                            {{ app()->getLocale() === 'en' ? 'Claim coupon' : 'Reclamar cupón' }}
                        </button>
                    @else
                        <div class="mt-4 flex flex-wrap items-center gap-3" x-data="{ copied: false }">
                            <code class="rounded-xl border border-dashed border-amber-300 bg-amber-50 px-4 py-2.5 text-base font-bold tracking-[.14em] text-amber-950">{{ $spin->coupon->code }}</code>
                            <button type="button"
                                x-on:click="navigator.clipboard.writeText(@js($spin->coupon->code)); copied = true; setTimeout(() => copied = false, 1600)"
                                class="inline-flex min-h-10 items-center rounded-full border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                                <span x-show="! copied">{{ app()->getLocale() === 'en' ? 'Copy code' : 'Copiar código' }}</span>
                                <span x-show="copied" x-cloak class="text-emerald-700">{{ app()->getLocale() === 'en' ? 'Copied' : 'Copiado' }}</span>
                            </button>
                            @if (! $completed)
                                <a href="{{ route('store.search') }}" wire:navigate
                                    class="inline-flex min-h-10 items-center rounded-full bg-gray-950 px-4 text-sm font-semibold text-white hover:bg-amber-800">
                                    {{ app()->getLocale() === 'en' ? 'Shop and use' : 'Comprar y usar' }}
                                </a>
                            @endif
                        </div>
                        @if (! $completed)
                            <p class="mt-2 text-xs text-gray-500">{{ app()->getLocale() === 'en' ? 'It will be marked as redeemed once your order payment is confirmed.' : 'Se marcará como canjeado cuando el pago de tu pedido sea confirmado.' }}</p>
                        @endif
                    @endif
                @else
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ app()->getLocale() === 'en' ? 'Spin without a coupon' : 'Giro sin cupón' }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $spin->created_at->format('d/m/Y · H:i') }}</p>
                        </div>
                        <x-brevare.badge color="neutral">{{ app()->getLocale() === 'en' ? 'No prize' : 'Sin premio' }}</x-brevare.badge>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
                <flux:icon name="ticket" class="mx-auto size-10 text-amber-500" />
                <h3 class="mt-3 font-bold text-gray-900">{{ app()->getLocale() === 'en' ? 'No coupons yet' : 'Todavía no tienes cupones' }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ app()->getLocale() === 'en' ? 'Visit rewards for a chance to earn an exclusive discount.' : 'Gira la ruleta para intentar ganar un descuento exclusivo.' }}</p>
                <a href="{{ route('store.roulette') }}" wire:navigate class="mt-4 inline-flex min-h-10 items-center rounded-full bg-gray-950 px-4 text-sm font-semibold text-white">{{ app()->getLocale() === 'en' ? 'View rewards' : 'Girar ahora' }}</a>
            </div>
        @endforelse
    </div>
</div>
