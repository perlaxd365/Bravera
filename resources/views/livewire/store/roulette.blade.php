@php
    $colors = ['#f6c85f', '#fff8e7', '#f2b544', '#fffdf7', '#d97706', '#fce7b2', '#fffdf7', '#f1b33c', '#fff3cf', '#f7d98a'];
    $segmentCount = count($segments);
    $segmentAngle = 360 / $segmentCount;
    $stops = [];

    foreach ($segments as $index => $segment) {
        $start = ($index * $segmentAngle) - ($segmentAngle / 2);
        $end = $start + $segmentAngle;
        $stops[] = $colors[$index % count($colors)].' '.$start.'deg '.$end.'deg';
    }

    $wheelGradient = 'conic-gradient(from 0deg, '.implode(', ', $stops).')';
@endphp

<div class="overflow-hidden bg-[#faf8f3]">
    <section class="relative mx-auto max-w-7xl px-4 pb-16 pt-10 sm:px-6 sm:pb-24 sm:pt-14 lg:px-8">
        <div class="pointer-events-none absolute -right-20 top-0 size-80 rounded-full bg-amber-200/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-24 bottom-0 size-72 rounded-full bg-emerald-200/20 blur-3xl"></div>

        <div class="relative grid items-center gap-12 lg:grid-cols-[minmax(0,.82fr)_minmax(460px,1.18fr)]">
            <div class="max-w-xl">
                <p class="text-[11px] font-bold uppercase tracking-[.24em] text-amber-800">Ruleta Brevare Perú</p>
                <h1 class="mt-3 text-4xl font-extrabold tracking-[-.055em] text-stone-950 sm:text-5xl">
                    Tu próxima compra puede traer un premio.
                </h1>
                <p class="mt-4 text-base leading-7 text-stone-600">
                    Gira una vez y gana <strong class="text-stone-900">15%</strong> en compras desde S/100,
                    <strong class="text-stone-900">10%</strong> desde S/40 o <strong class="text-stone-900">5%</strong> en toda la tienda.
                </p>

                <div class="mt-7 space-y-3 rounded-3xl border border-amber-200 bg-white/80 p-5 shadow-sm backdrop-blur">
                    <p class="flex items-start gap-3 text-sm leading-6 text-stone-700">
                        <flux:icon name="user-circle" class="mt-0.5 size-5 shrink-0 text-amber-700" />
                        El cupón queda vinculado únicamente a tu cuenta.
                    </p>
                    <p class="flex items-start gap-3 text-sm leading-6 text-stone-700">
                        <flux:icon name="credit-card" class="mt-0.5 size-5 shrink-0 text-emerald-700" />
                        El cupón se consume solo cuando una compra queda pagada y confirmada.
                    </p>
                    <p class="flex items-start gap-3 text-sm leading-6 text-stone-700">
                        <flux:icon name="arrow-path" class="mt-0.5 size-5 shrink-0 text-sky-700" />
                        Después de usar tu cupón, podrás volver a girar.
                    </p>
                </div>
            </div>

            <div class="mx-auto w-full max-w-[560px]">
                <div class="relative rounded-[2.25rem] border border-amber-200 bg-[#fffdf8] p-5 shadow-[0_30px_80px_-45px_rgba(120,53,15,.45)] sm:p-8"
                    x-data="{ rotation: 0, spinning: false }"
                    x-on:roulette-result.window="
                        const index = Number($event.detail.index || 0);
                        spinning = false;
                        rotation = (Math.floor(rotation / 360) + 6) * 360 - (index * {{ $segmentAngle }});
                    "
                    x-on:roulette-error.window="spinning = false">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-stone-950">Gira y descubre tu descuento</p>
                            <p class="text-xs text-stone-500">Una oportunidad por cupón utilizado.</p>
                        </div>
                        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[10px] font-bold uppercase tracking-[.12em] text-amber-900">
                            Brevare Perú
                        </span>
                    </div>

                    <div class="relative mx-auto mt-7 aspect-square w-full max-w-[390px]">
                        <div class="absolute left-1/2 top-0 z-30 -translate-x-1/2 -translate-y-1">
                            <div class="h-0 w-0 border-x-[14px] border-t-[24px] border-x-transparent border-t-stone-950 drop-shadow"></div>
                        </div>

                        <div class="roulette-wheel absolute inset-0 rounded-full border-[9px] border-[#40352b] shadow-[inset_0_0_0_4px_#fff8e7,0_20px_40px_-24px_rgba(41,37,36,.5)] transition-transform duration-[4200ms] ease-[cubic-bezier(.12,.72,.12,1)]"
                            :style="`transform: rotate(${rotation}deg); background: {{ $wheelGradient }}`">
                            @foreach ($segments as $index => $segment)
                                @php $angle = $index * $segmentAngle; @endphp
                                <span class="absolute left-1/2 top-1/2 flex w-24 -translate-x-1/2 -translate-y-1/2 items-center justify-center text-center text-[11px] font-extrabold leading-3 text-stone-950"
                                    style="transform: translate(-50%, -50%) rotate({{ $angle }}deg) translateY(-132px) rotate({{ -$angle }}deg);">
                                    {{ $segment['label'] }}
                                </span>
                            @endforeach
                        </div>

                        <div class="absolute left-1/2 top-1/2 z-20 flex size-24 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-[6px] border-[#fff8e7] bg-stone-950 shadow-xl">
                            @auth
                                <button type="button" wire:click="spin"
                                    x-on:click="if (! spinning && {{ $eligibility['allowed'] ? 'true' : 'false' }}) { spinning = true }"
                                    wire:loading.attr="disabled" wire:target="spin"
                                    @disabled(! $eligibility['allowed'] || $lastSpin?->coupon && ! $lastSpin?->coupon->usages()->exists())
                                    class="flex size-full items-center justify-center rounded-full px-3 text-center text-sm font-extrabold text-white transition hover:bg-amber-800 disabled:cursor-not-allowed disabled:text-white/50">
                                    <span x-show="! spinning">Girar</span>
                                    <span x-show="spinning" x-cloak class="inline-flex flex-col items-center gap-1">
                                        <span class="size-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                        <span class="text-[10px]">Girando</span>
                                    </span>
                                </button>
                            @else
                                <a href="{{ route('login', ['redirect' => route('store.roulette')]) }}"
                                    class="flex size-full items-center justify-center rounded-full px-3 text-center text-sm font-extrabold text-white hover:bg-amber-800">
                                    Entrar<br>y girar
                                </a>
                            @endauth
                        </div>
                    </div>

                    @if ($error)
                        <p class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $error }}</p>
                    @elseif (! auth()->check())
                        <p class="mt-5 rounded-2xl bg-amber-50 px-4 py-3 text-center text-sm text-amber-900">
                            Necesitas una cuenta para que el cupón quede guardado y no pueda compartirse.
                        </p>
                    @elseif (! $eligibility['allowed'])
                        <p class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm text-amber-900">
                            {{ $eligibility['message'] }}
                        </p>
                    @else
                        <p class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-900">
                            {{ $eligibility['message'] }}
                        </p>
                    @endif
                </div>

                @if ($lastSpin?->coupon)
                    <div class="mt-5 rounded-3xl border border-amber-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[.16em] text-amber-800">Tu último premio</p>
                                <p class="mt-1 text-2xl font-extrabold tracking-tight text-stone-950">
                                    {{ $lastSpin->discount_percent }}% de descuento
                                </p>
                                <p class="mt-1 text-xs text-stone-500">
                                    {{ (float) $lastSpin->min_subtotal > 0
                                        ? 'En compras desde S/'.number_format((float) $lastSpin->min_subtotal, 0)
                                        : 'Válido en toda la tienda' }}
                                </p>
                            </div>

                            @if (! $lastSpin->claimed_at)
                                <button type="button" wire:click="claim({{ $lastSpin->id }})"
                                    wire:loading.attr="disabled" wire:target="claim"
                                    class="inline-flex min-h-11 items-center justify-center rounded-full bg-stone-950 px-5 text-sm font-bold text-white hover:bg-amber-800">
                                    Reclamar cupón
                                </button>
                            @else
                                <div class="text-right">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-stone-500">Tu código</p>
                                    <p class="mt-1 rounded-xl border border-dashed border-amber-300 bg-amber-50 px-4 py-2 font-mono text-base font-bold tracking-[.14em] text-amber-950">
                                        {{ $lastSpin->coupon->code }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        @if ($lastSpin->claimed_at)
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('account.coupons') }}" wire:navigate
                                    class="inline-flex min-h-10 items-center rounded-full border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-800 hover:bg-stone-50">
                                    Ver mis cupones
                                </a>
                                <a href="{{ route('store.search') }}" wire:navigate
                                    class="inline-flex min-h-10 items-center rounded-full bg-amber-300 px-4 text-sm font-semibold text-amber-950 hover:bg-amber-400">
                                    Usar ahora
                                </a>
                            </div>
                        @endif
                    </div>
                @elseif ($lastSpin && ! $lastSpin->coupon)
                    <div class="mt-5 rounded-3xl border border-stone-200 bg-white p-5 text-center text-sm text-stone-600 shadow-sm">
                        Esta vez no salió un cupón. Puedes volver a intentarlo.
                    </div>
                @endif
            </div>
        </div>
    </section>

    <style>
        @media (prefers-reduced-motion: reduce) {
            .roulette-wheel {
                transition: none !important;
            }
        }
    </style>
</div>
