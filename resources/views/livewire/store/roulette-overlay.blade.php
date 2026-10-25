@php
    $colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#ec4899', '#8b5cf6', '#facc15', '#14b8a6'];
    $labelColors = ['#fff7ed', '#1c1917', '#ecfdf5', '#eff6ff', '#fff1f2', '#f5f3ff', '#292524', '#f0fdfa'];
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

<div x-data="{
        open: false,
        rotation: 0,
        spinning: false,
        reveal: {{ ($lastSpin || $guestPrize) ? 'true' : 'false' }},
        confetti() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const root = this.$refs.confetti;
            const colors = ['#f6c85f', '#d97706', '#10b981', '#ef4444', '#ffffff'];
            for (let i = 0; i < 55; i++) {
                const piece = document.createElement('span');
                piece.className = 'roulette-confetti-piece';
                piece.style.background = colors[i % colors.length];
                piece.style.left = (45 + Math.random() * 10) + '%';
                piece.style.setProperty('--confetti-x', ((Math.random() - .5) * 420) + 'px');
                piece.style.setProperty('--confetti-rotate', (Math.random() * 720 - 360) + 'deg');
                piece.style.animationDelay = (Math.random() * .35) + 's';
                root.appendChild(piece);
                setTimeout(() => piece.remove(), 2600);
            }
        },
        startSpin(index, result) {
            this.reveal = false;
            this.spinning = true;
            this.rotation = (Math.floor(this.rotation / 360) + 7) * 360 - (index * {{ $segmentAngle }});
            setTimeout(() => {
                this.spinning = false;
                this.reveal = true;
                if (result !== 'none') this.confetti();
            }, 5000);
        },
        init() {
            setTimeout(() => {
                if (sessionStorage.getItem('brevare-roulette-dismissed') !== '1') this.open = true;
            }, 650);
        }
    }"
    x-on:roulette-result.window="startSpin(Number($event.detail.index || 0), $event.detail.result)"
    x-on:roulette-error.window="spinning = false"
    x-on:roulette-require-login.window="window.location.href = $event.detail.url">

    <button type="button" x-show="!open" @click="open = true"
        class="roulette-floating-cta group fixed bottom-24 left-3 z-[75] flex max-w-[310px] items-center gap-3 rounded-full border border-amber-200 bg-stone-950 px-4 py-3 text-left text-white shadow-2xl shadow-amber-950/30 sm:bottom-6 sm:left-6 sm:px-5 sm:py-4"
        aria-label="Girar y ganar un descuento gratis">
        <span class="roulette-cta-spark flex size-11 shrink-0 items-center justify-center rounded-full bg-amber-300 text-amber-950">
            <flux:icon name="sparkles" class="size-5" />
        </span>
        <span>
            <span class="block text-[10px] font-bold uppercase tracking-[.18em] text-amber-300">Ruleta Brevare Perú</span>
            <span class="mt-0.5 block text-sm font-extrabold leading-5">¡GIRA Y GANA DESCUENTO GRATIS!</span>
        </span>
    </button>

    <div x-show="open" x-cloak class="roulette-overlay-backdrop"
        role="dialog" aria-modal="true" aria-labelledby="roulette-overlay-title"
        @keydown.escape.window="open = false"
        @click.self="open = false">
        <div class="roulette-overlay-panel relative overflow-hidden rounded-[2rem] border border-amber-200 bg-[#fffdf8] p-4 shadow-2xl sm:p-6">
            <div x-ref="confetti" class="pointer-events-none absolute inset-0 z-40 overflow-hidden"></div>

            <button type="button" @click="open = false; sessionStorage.setItem('brevare-roulette-dismissed', '1')"
                class="absolute right-3 top-3 z-50 flex size-10 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-600 shadow-sm hover:bg-stone-50"
                aria-label="Cerrar ruleta">
                <flux:icon name="x-mark" class="size-5" />
            </button>

            <div class="relative z-10">
                <p class="text-[10px] font-bold uppercase tracking-[.22em] text-amber-800">Premio inmediato</p>
                <h2 id="roulette-overlay-title" class="mt-2 text-3xl font-extrabold leading-tight tracking-[-.045em] text-stone-950 sm:text-4xl">
                    Gira y gana descuento gratis.
                </h2>
                <p class="mt-3 text-sm leading-6 text-stone-600">
                    Prueba tu suerte con Brevare Perú. Puedes ganar <strong>15%</strong> desde S/100,
                    <strong>10%</strong> desde S/40 o <strong>5%</strong> sin mínimo.
                </p>
                <div class="mt-5 space-y-2 rounded-2xl border border-amber-200 bg-white/80 p-4 text-xs leading-5 text-stone-600">
                    <p class="flex gap-2"><flux:icon name="ticket" class="mt-0.5 size-4 shrink-0 text-amber-700" /> El cupón queda vinculado a tu cuenta.</p>
                    <p class="flex gap-2"><flux:icon name="check-badge" class="mt-0.5 size-4 shrink-0 text-emerald-700" /> Se consume solo con una compra pagada.</p>
                    <p class="flex gap-2"><flux:icon name="arrow-path" class="mt-0.5 size-4 shrink-0 text-sky-700" /> Después de usarlo podrás girar otra vez.</p>
                </div>

                @if ($error)
                    <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $error }}</p>
                @endif
            </div>

            <div class="relative z-10">
                <div class="roulette-overlay-stage relative mx-auto aspect-square w-full">
                    <div class="roulette-pointer absolute left-1/2 top-0 z-30 -translate-x-1/2 -translate-y-1">
                        <div class="h-0 w-0 border-x-[16px] border-t-[28px] border-x-transparent border-t-stone-950 drop-shadow"></div>
                    </div>
                    <div class="roulette-overlay-wheel absolute inset-0 rounded-full border-[10px] border-[#40352b] shadow-[inset_0_0_0_5px_#fff8e7,0_25px_60px_-30px_rgba(41,37,36,.55)]"
                        :class="spinning ? 'roulette-wheel-spinning' : ''"
                        :style="`transform: rotate(${rotation}deg); background: {{ $wheelGradient }}`">
                        @foreach ($segments as $index => $segment)
                            @php $angle = $index * $segmentAngle; @endphp
                            <span class="roulette-wheel-label absolute left-1/2 top-1/2 flex items-center justify-center text-center font-extrabold leading-3 text-stone-950"
                                style="--label-angle: {{ $angle }}deg; --label-counter-angle: {{ -$angle }}deg; color: {{ $labelColors[$index % count($labelColors)] }};">
                                {{ $segment['result'] === 'none' ? 'Sigue' : $segment['label'] }}
                            </span>
                        @endforeach
                    </div>
                    <div class="roulette-center-button absolute left-1/2 top-1/2 z-20 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-[5px] border-[#fff8e7] bg-stone-950 shadow-xl">
                        <button type="button" wire:click="spin"
                            x-on:click="if ({{ $eligibility['allowed'] ? 'true' : 'false' }} && !spinning) { $nextTick(() => {}) }"
                            wire:loading.attr="disabled" wire:target="spin"
                                    @disabled(! $eligibility['allowed'])
                                    x-bind:disabled="spinning || {{ $eligibility['allowed'] ? 'false' : 'true' }}"
                            class="flex size-full items-center justify-center rounded-full px-3 text-center text-sm font-extrabold text-white transition hover:bg-amber-800 disabled:cursor-not-allowed disabled:text-white/50 sm:text-base">
                            <span x-show="!spinning">GIRAR</span>
                            <span x-show="spinning" x-cloak class="inline-flex flex-col items-center gap-1">
                                <span class="size-5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                <span class="text-[10px]">Girando</span>
                            </span>
                        </button>
                    </div>
                </div>

                @if (! $eligibility['allowed'] && ! $guestPrize)
                    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm text-amber-900">
                        {{ $eligibility['message'] }}
                    </p>
                @endif

                @php
                    $prize = $guestPrize ?? null;
                    $won = $prize && (int) ($prize['percent'] ?? 0) > 0;
                    $spinCoupon = $lastSpin?->coupon;
                @endphp

                <div x-show="reveal" x-cloak class="mt-4 rounded-2xl border p-4 text-center"
                    :class="reveal ? 'border-amber-200 bg-white shadow-lg' : ''">
                    @if ($guestPrize)
                        @if ($won)
                            <p class="text-[10px] font-bold uppercase tracking-[.18em] text-amber-800">¡Felicidades!</p>
                            <p class="mt-1 text-2xl font-extrabold text-stone-950">{{ $guestPrize['label'] }} de descuento</p>
                            <p class="mt-1 text-xs text-stone-500">
                                {{ (float) $guestPrize['min'] > 0 ? 'En compras desde S/'.number_format((float) $guestPrize['min'], 0) : 'Válido en toda la tienda' }}
                            </p>
                            @auth
                                <button type="button" wire:click="claimPending" wire:loading.attr="disabled" wire:target="claimPending"
                                    class="mt-3 inline-flex min-h-11 items-center rounded-full bg-stone-950 px-5 text-sm font-bold text-white hover:bg-amber-800">
                                    Reclamar mi cupón
                                </button>
                            @else
                                <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}"
                                    @click="sessionStorage.removeItem('brevare-roulette-dismissed')"
                                    class="mt-3 inline-flex min-h-11 items-center rounded-full bg-stone-950 px-5 text-sm font-bold text-white hover:bg-amber-800">
                                    Inicia sesión para reclamar
                                </a>
                            @endauth
                        @else
                            <p class="text-sm font-semibold text-stone-700">Esta vez no salió un descuento.</p>
                            <p class="mt-1 text-xs text-stone-500">Vuelve a intentarlo en una nueva visita.</p>
                        @endif
                    @elseif ($spinCoupon)
                        <p class="text-[10px] font-bold uppercase tracking-[.18em] text-amber-800">¡Felicidades!</p>
                        <p class="mt-1 text-2xl font-extrabold text-stone-950">{{ $lastSpin->discount_percent }}% de descuento</p>
                        @if (! $lastSpin->claimed_at)
                            <button type="button" wire:click="claim({{ $lastSpin->id }})"
                                class="mt-3 inline-flex min-h-11 items-center rounded-full bg-stone-950 px-5 text-sm font-bold text-white hover:bg-amber-800">
                                Reclamar mi cupón
                            </button>
                        @else
                            <p class="mt-2 rounded-xl border border-dashed border-amber-300 bg-amber-50 px-4 py-2 font-mono text-lg font-bold tracking-[.13em] text-amber-950">{{ $spinCoupon->code }}</p>
                            <a href="{{ route('account.coupons') }}" wire:navigate class="mt-2 inline-flex text-xs font-semibold text-amber-900 underline">Ver en mis cupones</a>
                        @endif
                    @elseif ($lastSpin)
                        <p class="text-sm font-semibold text-stone-700">Esta vez no salió un descuento.</p>
                        @if ($eligibility['allowed'])
                            <p class="mt-1 text-xs text-stone-500">Puedes volver a girar ahora.</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes roulette-float {
            from { transform: translateY(0) rotate(-1deg); }
            to { transform: translateY(-8px) rotate(1deg); }
        }
        @keyframes roulette-spark {
            to { transform: rotate(360deg); }
        }
        @keyframes roulette-confetti-fall {
            0% { transform: translate3d(0, -10px, 0) rotate(0); opacity: 1; }
            100% { transform: translate3d(var(--confetti-x), 560px, 0) rotate(var(--confetti-rotate)); opacity: 0; }
        }
        .roulette-floating-cta {
            animation: roulette-float 2.4s ease-in-out infinite alternate;
            box-shadow: 0 18px 45px -18px rgba(120, 53, 15, .55), 0 0 0 1px rgba(251, 191, 36, .12);
        }
        .roulette-cta-spark { animation: roulette-spark 7s linear infinite; }
        .roulette-overlay-wheel { transition: transform 5s cubic-bezier(.12,.72,.1,1); }
        .roulette-confetti-piece {
            position: absolute;
            top: -12px;
            display: block;
            width: 8px;
            height: 13px;
            border-radius: 2px;
            animation: roulette-confetti-fall 2.2s cubic-bezier(.2,.7,.25,1) forwards;
        }
        @media (prefers-reduced-motion: reduce) {
            .roulette-floating-cta, .roulette-cta-spark { animation: none; }
            .roulette-overlay-wheel { transition-duration: .01s; }
            .roulette-confetti-piece { display: none; }
        }
        .roulette-overlay-backdrop {
            position: fixed;
            inset: 0;
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: 12px;
            background: rgba(12, 10, 9, .82);
            backdrop-filter: blur(8px);
        }
        .roulette-overlay-panel {
            width: min(820px, calc(100vw - 24px));
            max-height: calc(100dvh - 24px);
            overflow-y: auto;
            display: grid;
            gap: 1rem;
            margin: auto;
            background: #fffdf8;
            box-shadow: 0 30px 90px -35px rgba(12, 10, 9, .7);
        }
        .roulette-overlay-stage {
            width: min(100%, 270px);
        }
        .roulette-wheel-label {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 54px;
            height: 24px;
            margin: -12px 0 0 -27px;
            font-size: 8px;
            transform: rotate(var(--label-angle)) translateY(-88px) rotate(var(--label-counter-angle));
        }
        .roulette-overlay-wheel {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            overflow: hidden;
        }
        .roulette-center-button {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 78px;
            height: 78px;
            transform: translate(-50%, -50%);
        }
        .roulette-pointer {
            position: absolute;
            left: 50%;
            top: 0;
            transform: translate(-50%, -3px);
        }
        @media (min-width: 800px) {
            .roulette-overlay-panel {
                grid-template-columns: minmax(0, .85fr) minmax(260px, 1fr);
                align-items: center;
                gap: 1.5rem;
                padding: 1.75rem;
            }
            .roulette-overlay-stage {
                width: 270px;
            }
            .roulette-wheel-label {
                width: 60px;
                height: 24px;
                margin: -12px 0 0 -30px;
                font-size: 9px;
                transform: rotate(var(--label-angle)) translateY(-96px) rotate(var(--label-counter-angle));
            }
            .roulette-center-button {
                width: 82px;
                height: 82px;
            }
        }
        @media (max-height: 700px) and (min-width: 800px) {
            .roulette-overlay-stage {
                width: 230px;
            }
            .roulette-wheel-label {
                width: 54px;
                height: 22px;
                margin: -11px 0 0 -27px;
                font-size: 8px;
                transform: rotate(var(--label-angle)) translateY(-80px) rotate(var(--label-counter-angle));
            }
            .roulette-center-button {
                width: 72px;
                height: 72px;
            }
        }
    </style>
</div>
