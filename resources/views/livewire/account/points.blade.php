<div class="space-y-6">
    <section class="overflow-hidden rounded-[2rem] border border-amber-200 bg-gradient-to-br from-amber-200 via-amber-100 to-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-amber-900/70">{{ __('storefront.common.points') }}</p>
                <p class="mt-2 text-5xl font-extrabold tracking-tight text-gray-950">{{ number_format($balance) }}</p>
                <p class="mt-1 text-sm font-medium text-amber-950/70">{{ app()->getLocale() === 'en' ? 'points available' : 'puntos disponibles' }}</p>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/75 px-5 py-4 backdrop-blur">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ app()->getLocale() === 'en' ? 'Available value' : 'Valor disponible' }}</p>
                <p class="mt-1 text-2xl font-extrabold text-emerald-700"><x-brevare.price :amount="$rewardAmount" size="md" class="font-extrabold text-emerald-700" /></p>
                <p class="mt-1 text-xs text-gray-500">{{ app()->getLocale() === 'en' ? '1,000 points = S/ 50 reward' : '1,000 puntos = S/ 50 de regalo' }}</p>
            </div>
        </div>

        <div class="mt-7">
            <div class="h-2.5 overflow-hidden rounded-full bg-white/70">
                <div class="h-full rounded-full bg-amber-500 transition-all duration-700" style="width: {{ max(0, min(100, $progress)) }}%"></div>
            </div>
            <p class="mt-2 text-xs font-medium text-amber-950/70">
                @if ($canRedeem)
                    {{ app()->getLocale() === 'en' ? 'You can request a redemption now. '.number_format(max(0, $pointsToNext)).' points remain until the next S/ 50 tier.' : 'Puedes solicitar un canje ahora. Te faltan '.number_format(max(0, $pointsToNext)).' puntos para el siguiente tramo de S/ 50.' }}
                @else
                    {{ app()->getLocale() === 'en' ? number_format(max(0, $pointsToNext)).' points remain to unlock your first S/ 50 reward.' : 'Te faltan '.number_format(max(0, $pointsToNext)).' puntos para obtener tu primer premio de S/ 50.' }}
                @endif
            </p>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ app()->getLocale() === 'en' ? 'How you earn' : 'Cómo ganas' }}</p>
            <p class="mt-2 text-lg font-bold text-gray-950">{{ app()->getLocale() === 'en' ? 'S/ 1 = 1 point' : 'S/ 1 = 1 punto' }}</p>
            <p class="mt-1 text-sm leading-6 text-gray-500">{{ app()->getLocale() === 'en' ? 'Every purchase earns points automatically.' : 'Cada compra suma automáticamente.' }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ app()->getLocale() === 'en' ? 'Minimum redemption' : 'Canje mínimo' }}</p>
            <p class="mt-2 text-lg font-bold text-gray-950">{{ app()->getLocale() === 'en' ? '1,000 points' : '1,000 puntos' }}</p>
            <p class="mt-1 text-sm leading-6 text-gray-500">{{ app()->getLocale() === 'en' ? 'Worth a S/ 50 reward.' : 'Equivalen a S/ 50 de regalo.' }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ app()->getLocale() === 'en' ? 'Welcome bonus' : 'Bono de bienvenida' }}</p>
            <p class="mt-2 text-lg font-bold text-gray-950">{{ app()->getLocale() === 'en' ? '50 points' : '50 puntos' }}</p>
            <p class="mt-1 text-sm leading-6 text-gray-500">{{ app()->getLocale() === 'en' ? 'Added when you create your account.' : 'Se acreditan al crear tu cuenta.' }}</p>
        </div>
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-950">{{ app()->getLocale() === 'en' ? 'Redeem points' : 'Canjear puntos' }}</h2>
        <p class="mt-1 text-sm text-gray-500">
            {{ app()->getLocale() === 'en' ? 'Request your reward. The Brevare team will validate the redemption and coordinate the reward payment.' : 'Solicita tu premio. El equipo de Brevare validará el canje y coordinará el depósito de Brevare Puntos.' }}
        </p>

        @if ($canRedeem)
            <form wire:submit="redeem" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="w-full sm:max-w-xs">
                    <label for="pointsToRedeem" class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Points to redeem' : 'Puntos a canjear' }}</label>
                    <div class="relative">
                        <select id="pointsToRedeem" wire:model="pointsToRedeem"
                            class="w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 py-3 pr-9 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            @foreach ($redeemOptions as $option)
                                <option value="{{ $option }}">{{ number_format($option) }} puntos — S/ {{ number_format(($option / 1000) * 50, 2) }}</option>
                            @endforeach
                        </select>
                        <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    </div>
                    @error('pointsToRedeem') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                    class="inline-flex min-h-11 items-center justify-center rounded-full bg-gray-950 px-6 text-sm font-semibold text-white transition hover:bg-amber-800">
                    {{ app()->getLocale() === 'en' ? 'Request redemption' : 'Solicitar canje' }}
                </button>
            </form>
            <p class="mt-3 text-xs leading-5 text-gray-400">
                {{ app()->getLocale() === 'en' ? 'Points are deducted when you submit the request. If it is rejected, they are returned automatically.' : 'Los puntos se descuentan al enviar la solicitud. Si se rechaza, se devuelven automáticamente.' }}
            </p>
        @else
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ app()->getLocale() === 'en' ? 'You need at least 1,000 points to redeem. '.number_format($pointsToNext).' points remaining.' : 'Necesitas al menos 1,000 puntos para canjear. Te faltan '.number_format($pointsToNext).'.' }}
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="border-b border-gray-100 px-6 py-4">
            <h2 class="font-bold text-gray-950">{{ app()->getLocale() === 'en' ? 'Points activity' : 'Movimientos de puntos' }}</h2>
            <p class="text-xs text-gray-500">{{ app()->getLocale() === 'en' ? 'History of earned and redeemed points.' : 'Historial de puntos ganados y canjeados.' }}</p>
        </header>
        <div class="divide-y divide-gray-100">
            @forelse ($transactions as $transaction)
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $transaction->description }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $transaction->created_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-bold {{ $transaction->points >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $transaction->points >= 0 ? '+' : '' }}{{ number_format($transaction->points) }}
                        </p>
                        <p class="text-xs text-gray-400">{{ app()->getLocale() === 'en' ? 'Balance' : 'Saldo' }}: {{ number_format($transaction->balance_after) }}</p>
                    </div>
                </div>
            @empty
                <p class="px-6 py-8 text-sm text-gray-500">{{ app()->getLocale() === 'en' ? 'No points activity yet.' : 'Aún no tienes movimientos de puntos.' }}</p>
            @endforelse
        </div>
    </section>

    @if ($redemptions->isNotEmpty())
        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <header class="border-b border-gray-100 px-6 py-4">
                <h2 class="font-bold text-gray-950">{{ app()->getLocale() === 'en' ? 'My redemptions' : 'Mis canjes' }}</h2>
            </header>
            <div class="divide-y divide-gray-100">
                @foreach ($redemptions as $redemption)
                    @php
                        $statusLabel = match ($redemption->status) {
                            'approved' => app()->getLocale() === 'en' ? 'Approved' : 'Aprobado',
                            'paid' => app()->getLocale() === 'en' ? 'Paid' : 'Depositado',
                            'rejected' => app()->getLocale() === 'en' ? 'Rejected' : 'Rechazado',
                            default => app()->getLocale() === 'en' ? 'Pending' : 'Pendiente',
                        };
                        $statusClass = match ($redemption->status) {
                            'approved' => 'bg-sky-100 text-sky-800',
                            'paid' => 'bg-emerald-100 text-emerald-800',
                            'rejected' => 'bg-rose-100 text-rose-800',
                            default => 'bg-amber-100 text-amber-800',
                        };
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ app()->getLocale() === 'en' ? 'Request' : 'Solicitud' }} #{{ $redemption->id }}</p>
                            <p class="text-xs text-gray-500">{{ number_format($redemption->points) }} {{ app()->getLocale() === 'en' ? 'points' : 'puntos' }} · <x-brevare.price :amount="(float) $redemption->amount" size="xs" class="font-normal text-gray-500" /></p>
                            <p class="mt-0.5 text-xs text-gray-400">{{ $redemption->created_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
