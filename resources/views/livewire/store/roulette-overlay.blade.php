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

    $guestWon = $guestPrize && (int) ($guestPrize['percent'] ?? 0) > 0;
    $accountWon = ! $guestPrize && $lastSpin && (int) $lastSpin->discount_percent > 0;
    $isWinner = $guestWon || $accountWon;
    $winningPercent = $guestWon
        ? (int) $guestPrize['percent']
        : (int) ($lastSpin?->discount_percent ?? 0);
    $winningMinimum = $guestWon
        ? (float) ($guestPrize['min'] ?? 0)
        : (float) ($lastSpin?->min_subtotal ?? 0);
    $spinCoupon = $lastSpin?->coupon;
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
        class="roulette-floating-cta group fixed bottom-24 left-3 z-[75] flex max-w-[330px] items-center gap-3 overflow-hidden rounded-full border border-amber-300/45 bg-[#1b1713] px-3.5 py-3 text-left text-white shadow-2xl shadow-amber-950/35 sm:bottom-6 sm:left-6 sm:px-4 sm:py-3.5"
        aria-label="Girar y ganar un descuento gratis">
        <span class="roulette-cta-spark flex size-11 shrink-0 items-center justify-center rounded-full border border-amber-100/60 bg-amber-300 text-amber-950 shadow-[inset_0_-3px_0_rgba(120,53,15,.18)]">
            <flux:icon name="sparkles" class="size-5" />
        </span>
        <span class="relative z-10 min-w-0">
            <span class="block text-[9px] font-bold uppercase tracking-[.2em] text-amber-300">Ruleta Brevare Perú</span>
            <span class="mt-0.5 block text-[13px] font-extrabold leading-[1.15]">¡GIRA Y GANA DESCUENTO GRATIS!</span>
        </span>
        <span class="relative z-10 ml-1 flex size-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-amber-200 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:bg-amber-300 group-hover:text-stone-950">
            <flux:icon name="arrow-right" class="size-4" />
        </span>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.250ms class="roulette-overlay-backdrop"
        role="dialog" aria-modal="true" aria-labelledby="roulette-overlay-title"
        @keydown.escape.window="open = false"
        @click.self="open = false">
        <div class="roulette-overlay-panel relative overflow-hidden rounded-[1.75rem] border border-amber-200/80 bg-[#fffdf8] p-4 sm:rounded-[2.25rem] sm:p-6">
            <div x-ref="confetti" class="pointer-events-none absolute inset-0 z-40 overflow-hidden"></div>

            <button type="button" @click="open = false; sessionStorage.setItem('brevare-roulette-dismissed', '1')"
                class="roulette-close-button absolute right-3 top-3 z-50 flex size-10 items-center justify-center rounded-full border border-stone-200/80 bg-white/90 text-stone-600 shadow-[0_8px_24px_-14px_rgba(41,37,36,.8)] backdrop-blur-sm hover:border-amber-300 hover:bg-amber-50 hover:text-stone-950"
                aria-label="Cerrar ruleta">
                <flux:icon name="x-mark" class="size-5" />
            </button>

            <div class="roulette-copy relative z-10">
                <p class="inline-flex items-center gap-2 rounded-full bg-stone-950 px-3 py-1.5 text-[9px] font-bold uppercase tracking-[.2em] text-amber-200 shadow-[0_8px_24px_-15px_rgba(41,37,36,.9)]">
                    <flux:icon name="ticket" class="size-3.5 text-amber-300" />
                    Premio inmediato
                </p>
                <h2 id="roulette-overlay-title" class="mt-4 text-[2.3rem] font-extrabold leading-[.98] tracking-[-.058em] text-stone-950 sm:text-[3.2rem]">
                    Gira y gana
                    <span class="mt-1 inline-block">
                        <span class="relative inline-block px-0.5">
                            <span class="roulette-title-highlight absolute inset-x-0 bottom-[.16em] h-[.32em] -rotate-1 rounded-sm bg-amber-300" aria-hidden="true"></span>
                            <span class="relative">descuento gratis.</span>
                        </span>
                    </span>
                </h2>
                <p class="mt-3 max-w-[29rem] text-[13px] leading-5 text-stone-600 sm:mt-4 sm:text-sm sm:leading-6">
                    Un giro, tres descuentos posibles. Puedes ganar <strong class="text-stone-900">15%</strong> desde S/100,
                    <strong class="text-stone-900">10%</strong> desde S/40 o <strong class="text-stone-900">5%</strong> sin mínimo.
                </p>
            </div>

            <div class="roulette-game relative z-10">
                <div class="mb-3 flex items-center justify-between gap-3 px-1">
                    <p class="text-[9px] font-bold uppercase tracking-[.18em] text-stone-500">Tu suerte empieza aquí</p>
                    <p class="roulette-max-prize inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-amber-100 px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-[.12em] text-amber-950">
                        <flux:icon name="sparkles" class="size-3 text-amber-700" />
                        Hasta 15%
                    </p>
                </div>

                <div class="roulette-overlay-stage relative mx-auto aspect-square w-full">
                    <div class="roulette-stage-halo pointer-events-none absolute inset-[-18px] rounded-full" aria-hidden="true"></div>
                    <div class="roulette-pointer absolute left-1/2 top-0 z-30 -translate-x-1/2 -translate-y-1">
                        <div class="relative h-0 w-0 border-x-[15px] border-t-[32px] border-x-transparent border-t-amber-300 drop-shadow-[0_5px_4px_rgba(41,37,36,.35)]">
                            <span class="absolute left-1/2 top-1.5 size-3 -translate-x-1/2 rounded-full border-[3px] border-stone-950 bg-amber-300"></span>
                        </div>
                    </div>
                    <div class="roulette-overlay-wheel absolute inset-0 rounded-full border-[10px] border-[#30251e]"
                        :class="spinning ? 'roulette-wheel-spinning' : ''"
                        :style="`transform: rotate(${rotation}deg); background: {{ $wheelGradient }}`">
                        <svg class="roulette-wheel-pegs absolute inset-0 size-full" viewBox="0 0 100 100" aria-hidden="true">
                            @for ($peg = 0; $peg < 16; $peg++)
                                <g transform="rotate({{ $peg * 22.5 }} 50 50)">
                                    <circle cx="50" cy="5.2" r="1.35" fill="#fff0b5" stroke="#55412d" stroke-width=".45" />
                                </g>
                            @endfor
                        </svg>
                        @foreach ($segments as $index => $segment)
                            @php $angle = $index * $segmentAngle; @endphp
                            <span class="roulette-wheel-label absolute left-1/2 top-1/2 z-[2] flex items-center justify-center text-center font-extrabold leading-3"
                                style="--label-angle: {{ $angle }}deg; --label-counter-angle: {{ -$angle }}deg; color: {{ $labelColors[$index % count($labelColors)] }};">
                                {{ $segment['result'] === 'none' ? 'Sigue' : $segment['label'] }}
                            </span>
                        @endforeach
                    </div>
                    <div class="roulette-spin-shell absolute left-1/2 top-1/2 z-20 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full">
                        <div class="roulette-center-button flex items-center justify-center rounded-full border-[5px] border-[#fff5d4] bg-stone-950">
                            <button type="button" wire:click="spin"
                                x-on:click="if ({{ $eligibility['allowed'] ? 'true' : 'false' }} && !spinning) { $nextTick(() => {}) }"
                                wire:loading.attr="disabled" wire:target="spin"
                                @disabled(! $eligibility['allowed'])
                                x-bind:disabled="spinning || {{ $eligibility['allowed'] ? 'false' : 'true' }}"
                                class="roulette-spin-button flex size-full items-center justify-center rounded-full px-3 text-center font-extrabold text-white disabled:cursor-not-allowed disabled:text-white/50">
                                <span x-show="!spinning" class="flex flex-col items-center">
                                    <span class="text-[15px] tracking-[-.02em] sm:text-base">GIRAR</span>
                                    <span class="mt-0.5 text-[7px] tracking-[.2em] text-amber-300">AHORA</span>
                                </span>
                                <span x-show="spinning" x-cloak class="inline-flex flex-col items-center gap-1">
                                    <span class="size-5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                    <span class="text-[10px]">Girando</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                @if (! $eligibility['allowed'] && ! $guestPrize)
                    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm text-amber-900">
                        {{ $eligibility['message'] }}
                    </p>
                @endif

                @if ($error)
                    <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-center text-sm text-red-700" role="alert">{{ $error }}</p>
                @endif

                <div x-show="!reveal" x-cloak class="roulette-prize-preview mt-4 flex items-center gap-3 rounded-2xl px-4 py-3 text-white">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full border border-amber-200/30 bg-amber-300 text-stone-950">
                        <flux:icon name="gift" class="size-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[8px] font-bold uppercase tracking-[.2em] text-amber-300">El premio</span>
                        <span class="mt-0.5 block text-sm font-bold">Hasta <strong class="text-lg text-amber-300">15%</strong> de descuento</span>
                    </span>
                    <span class="rounded-full bg-white/10 px-2.5 py-1 text-[9px] font-bold uppercase tracking-[.12em] text-white/80">1 giro</span>
                </div>

                <div x-show="reveal" x-cloak class="roulette-prize-reveal mt-4" role="status" aria-live="polite">
                    @if ($isWinner)
                        <section class="roulette-win-card relative overflow-hidden rounded-[1.4rem] px-4 pb-4 pt-3 text-center">
                            <div class="roulette-win-burst pointer-events-none absolute inset-0" aria-hidden="true"></div>
                            <div class="relative">
                                <p class="inline-flex items-center gap-1.5 rounded-full bg-stone-950 px-3 py-1 text-[8px] font-extrabold uppercase tracking-[.18em] text-amber-200 shadow-sm">
                                    <flux:icon name="sparkles" class="size-3 text-amber-300" />
                                    ¡Premio desbloqueado!
                                </p>
                                <p class="roulette-win-number mt-1 flex items-start justify-center font-extrabold leading-none tracking-[-.08em] text-stone-950">
                                    {{ $winningPercent }}<span class="ml-0.5 mt-1.5 text-[.42em] tracking-[-.04em]">%</span>
                                </p>
                                <p class="-mt-0.5 text-[9px] font-extrabold uppercase tracking-[.24em] text-stone-800">De descuento</p>
                                <p class="mt-1.5 text-[11px] font-semibold text-stone-700">
                                    {{ $winningMinimum > 0 ? 'En compras desde S/'.number_format($winningMinimum, 0) : 'Válido en toda la tienda' }}
                                </p>

                                @if ($guestWon)
                            @auth
                                <button type="button" wire:click="claimPending" wire:loading.attr="disabled" wire:target="claimPending"
                                    class="roulette-prize-cta mt-3 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-stone-950 px-5 text-sm font-bold text-white">
                                    Reclamar mi cupón
                                    <flux:icon name="arrow-right" class="size-4 text-amber-300" />
                                </button>
                            @else
                                <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}"
                                    @click="sessionStorage.removeItem('brevare-roulette-dismissed')"
                                    class="roulette-prize-cta mt-3 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-stone-950 px-5 text-sm font-bold text-white">
                                    Inicia sesión para reclamar
                                    <flux:icon name="arrow-right" class="size-4 text-amber-300" />
                                </a>
                            @endauth
                                @elseif ($spinCoupon)
                                    @if (! $lastSpin->claimed_at)
                                        <button type="button" wire:click="claim({{ $lastSpin->id }})"
                                            wire:loading.attr="disabled" wire:target="claim"
                                            class="roulette-prize-cta mt-3 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-stone-950 px-5 text-sm font-bold text-white">
                                            Reclamar mi cupón
                                            <flux:icon name="arrow-right" class="size-4 text-amber-300" />
                                        </button>
                                    @else
                                        <div class="mt-2.5 rounded-xl border border-dashed border-stone-400/50 bg-white/65 px-3 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,.7)]">
                                            <p class="text-[8px] font-bold uppercase tracking-[.18em] text-stone-500">Tu cupón</p>
                                            <p class="mt-0.5 font-mono text-base font-bold tracking-[.13em] text-stone-950">{{ $spinCoupon->code }}</p>
                                        </div>
                                        <a href="{{ route('account.coupons') }}" wire:navigate
                                            class="roulette-prize-cta mt-2 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-full bg-stone-950 px-4 text-xs font-bold text-white">
                                            Ver en mis cupones
                                            <flux:icon name="arrow-up-right" class="size-3.5 text-amber-300" />
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </section>
                    @else
                        <section class="roulette-lose-card rounded-[1.25rem] border border-stone-200 bg-white/90 px-4 py-3.5 text-center shadow-[0_16px_30px_-26px_rgba(41,37,36,.7)]">
                            <p class="inline-flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-[.16em] text-stone-500">
                                <flux:icon name="arrow-path" class="size-3.5 text-amber-700" />
                                Esta vez no salió
                            </p>
                            <p class="mt-1 text-sm font-bold text-stone-900">Tu próximo giro puede ser el bueno.</p>
                            @if ($eligibility['allowed'])
                                <p class="mt-1 text-xs text-stone-500">Puedes volver a girar ahora.</p>
                            @else
                                <p class="mt-1 text-xs text-stone-500">Vuelve a intentarlo en una nueva visita.</p>
                            @endif
                        </section>
                    @endif
                </div>
            </div>

            <aside class="roulette-rules relative z-10 rounded-[1.35rem] border border-amber-200/75 bg-[#fff7df]/75 p-3.5 shadow-[inset_0_1px_0_rgba(255,255,255,.9)]">
                <div class="flex items-center justify-between gap-3 px-1">
                    <p class="text-[9px] font-extrabold uppercase tracking-[.2em] text-amber-900">Así funciona</p>
                    <span class="rounded-full bg-white/75 px-2.5 py-1 text-[8px] font-bold uppercase tracking-[.12em] text-stone-500">Sin sorpresas</span>
                </div>
                <div class="mt-2.5 divide-y divide-amber-200/70">
                    <p class="flex items-start gap-3 py-2 text-[11px] leading-[1.45] text-stone-600">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white text-amber-700 shadow-sm">
                            <flux:icon name="ticket" class="size-3.5" />
                        </span>
                        <span class="pt-0.5">El cupón queda vinculado a tu cuenta.</span>
                    </p>
                    <p class="flex items-start gap-3 py-2 text-[11px] leading-[1.45] text-stone-600">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white text-emerald-700 shadow-sm">
                            <flux:icon name="check-badge" class="size-3.5" />
                        </span>
                        <span class="pt-0.5">Se consume solo con una compra pagada.</span>
                    </p>
                    <p class="flex items-start gap-3 py-2 text-[11px] leading-[1.45] text-stone-600">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white text-sky-700 shadow-sm">
                            <flux:icon name="arrow-path" class="size-3.5" />
                        </span>
                        <span class="pt-0.5">Después de usarlo podrás girar otra vez.</span>
                    </p>
                </div>
            </aside>
        </div>
    </div>

    <style>
        @keyframes roulette-float {
            from { transform: translate3d(0, 0, 0) rotate(-.5deg); }
            to { transform: translate3d(0, -6px, 0) rotate(.5deg); }
        }
        @keyframes roulette-spark {
            to { transform: rotate(360deg); }
        }
        @keyframes roulette-cta-shine {
            0%, 62% { transform: translateX(-220%) skewX(-18deg); }
            82%, 100% { transform: translateX(480%) skewX(-18deg); }
        }
        @keyframes roulette-panel-in {
            from { opacity: 0; transform: translate3d(0, 22px, 0) scale(.975); }
            to { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
        }
        @keyframes roulette-halo-turn {
            to { transform: rotate(360deg); }
        }
        @keyframes roulette-spin-pulse {
            0%, 100% { opacity: .35; transform: scale(.94); }
            50% { opacity: .9; transform: scale(1.08); }
        }
        @keyframes roulette-prize-in {
            from { opacity: 0; transform: translate3d(0, 14px, 0) scale(.94); }
            to { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
        }
        @keyframes roulette-burst-turn {
            to { transform: rotate(360deg); }
        }
        @keyframes roulette-confetti-fall {
            0% { transform: translate3d(0, -10px, 0) rotate(0); opacity: 1; }
            100% { transform: translate3d(var(--confetti-x), 560px, 0) rotate(var(--confetti-rotate)); opacity: 0; }
        }
        .roulette-floating-cta {
            isolation: isolate;
            animation: roulette-float 2.8s cubic-bezier(.65, 0, .35, 1) infinite alternate;
            box-shadow:
                0 22px 48px -20px rgba(120, 53, 15, .7),
                0 0 0 1px rgba(251, 191, 36, .18),
                inset 0 1px 0 rgba(255, 255, 255, .12);
        }
        .roulette-floating-cta::after {
            content: '';
            position: absolute;
            inset: -70% auto -70% 0;
            z-index: 0;
            width: 35%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .16), transparent);
            pointer-events: none;
            animation: roulette-cta-shine 5.5s cubic-bezier(.16, 1, .3, 1) infinite;
        }
        .roulette-cta-spark { animation: roulette-spark 7s linear infinite; }
        .roulette-confetti-piece {
            position: absolute;
            top: -12px;
            display: block;
            width: 8px;
            height: 13px;
            border-radius: 2px;
            animation: roulette-confetti-fall 2.35s cubic-bezier(.16, 1, .3, 1) forwards;
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
            background:
                radial-gradient(circle at 50% 42%, rgba(245, 184, 52, .14), transparent 34%),
                rgba(17, 14, 11, .86);
            backdrop-filter: blur(12px) saturate(.8);
        }
        .roulette-overlay-panel {
            isolation: isolate;
            width: min(900px, calc(100vw - 24px));
            max-height: calc(100dvh - 24px);
            overflow-y: auto;
            display: grid;
            grid-template-areas:
                'copy'
                'game'
                'rules';
            gap: 1rem;
            margin: auto;
            background:
                radial-gradient(circle at 92% 4%, rgba(250, 204, 21, .18), transparent 27%),
                linear-gradient(145deg, #fffefb 0%, #fffdf7 58%, #fff9ea 100%);
            box-shadow:
                0 42px 110px -38px rgba(12, 10, 9, .88),
                0 0 0 1px rgba(255, 255, 255, .62) inset;
            animation: roulette-panel-in .55s cubic-bezier(.16, 1, .3, 1) both;
            scrollbar-color: rgba(180, 83, 9, .34) transparent;
            scrollbar-width: thin;
        }
        .roulette-overlay-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background-image: radial-gradient(rgba(146, 64, 14, .16) 1px, transparent 1.2px);
            background-size: 18px 18px;
            mask-image: linear-gradient(135deg, #000, transparent 42%);
            opacity: .32;
            pointer-events: none;
        }
        .roulette-copy {
            grid-area: copy;
            padding-right: 2rem;
        }
        .roulette-title-highlight {
            transform-origin: left center;
            box-shadow: 0 1px 0 rgba(255, 255, 255, .35) inset;
        }
        .roulette-game {
            grid-area: game;
            min-width: 0;
        }
        .roulette-rules {
            grid-area: rules;
        }
        .roulette-overlay-stage {
            width: min(100%, 240px);
            margin-top: .45rem;
            filter: drop-shadow(0 26px 24px rgba(71, 47, 24, .12));
        }
        .roulette-stage-halo {
            background: repeating-conic-gradient(
                from 0deg,
                rgba(217, 119, 6, .46) 0deg 1.8deg,
                transparent 1.8deg 22.5deg
            );
            mask-image: radial-gradient(circle, transparent 61%, #000 62% 66%, transparent 67%);
            opacity: .5;
            animation: roulette-halo-turn 28s linear infinite;
        }
        .roulette-max-prize {
            box-shadow:
                0 8px 20px -16px rgba(120, 53, 15, .6),
                inset 0 1px 0 rgba(255, 255, 255, .7);
        }
        .roulette-overlay-wheel {
            position: absolute;
            inset: 0;
            overflow: hidden;
            border-radius: 9999px;
            transform-origin: 50% 50%;
            box-shadow:
                0 0 0 4px #f4c84b,
                0 0 0 6px rgba(48, 37, 30, .15),
                0 24px 42px -22px rgba(48, 37, 30, .7),
                inset 0 0 0 5px rgba(255, 248, 230, .92),
                inset 0 0 28px rgba(41, 37, 36, .14);
            transition: transform 5s cubic-bezier(.12, .72, .1, 1);
        }
        .roulette-overlay-wheel.roulette-wheel-spinning {
            will-change: transform;
            filter: drop-shadow(0 0 16px rgba(251, 191, 36, .18));
        }
        .roulette-wheel-pegs {
            pointer-events: none;
            filter: drop-shadow(0 1px 1px rgba(41, 37, 36, .38));
        }
        .roulette-wheel-label {
            text-shadow: 0 1px 0 rgba(255, 255, 255, .22);
        }
        .roulette-wheel-label {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 54px;
            height: 24px;
            margin: -12px 0 0 -27px;
            font-size: 8px;
            transform: rotate(var(--label-angle)) translateY(-86px) rotate(var(--label-counter-angle));
        }
        .roulette-spin-shell {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 72px;
            height: 72px;
            transform: translate(-50%, -50%);
        }
        .roulette-spin-shell::before {
            content: '';
            position: absolute;
            inset: -10px;
            border: 1px solid rgba(251, 191, 36, .8);
            border-radius: 9999px;
            box-shadow: 0 0 0 8px rgba(251, 191, 36, .07);
            animation: roulette-spin-pulse 2.6s cubic-bezier(.65, 0, .35, 1) infinite;
            pointer-events: none;
        }
        .roulette-center-button {
            width: 100%;
            height: 100%;
            overflow: hidden;
            box-shadow:
                0 8px 0 #100d0a,
                0 12px 24px -8px rgba(17, 14, 11, .7),
                inset 0 1px 0 rgba(255, 255, 255, .16);
        }
        .roulette-spin-button {
            position: relative;
            cursor: pointer;
            background:
                radial-gradient(circle at 50% 18%, rgba(255, 255, 255, .12), transparent 38%),
                #17130f;
            transition: transform .16s cubic-bezier(.16, 1, .3, 1), background-color .2s ease, box-shadow .2s ease;
            touch-action: manipulation;
        }
        .roulette-spin-button:not(:disabled):hover {
            background-color: #2c2118;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, .16) inset;
        }
        .roulette-spin-button:not(:disabled):active {
            transform: scale(.97);
        }
        .roulette-spin-button:focus-visible {
            outline: 3px solid #fbbf24;
            outline-offset: 4px;
        }
        .roulette-close-button,
        .roulette-prize-cta {
            transition:
                transform .16s cubic-bezier(.16, 1, .3, 1),
                background-color .2s ease,
                border-color .2s ease,
                color .2s ease,
                box-shadow .2s ease;
            touch-action: manipulation;
        }
        .roulette-close-button:hover {
            transform: rotate(4deg) scale(1.03);
        }
        .roulette-close-button:focus-visible,
        .roulette-prize-cta:focus-visible {
            outline: 3px solid #b45309;
            outline-offset: 3px;
        }
        .roulette-prize-cta:hover {
            transform: translateY(-1px);
            background-color: #2c2118;
            box-shadow: 0 10px 20px -14px rgba(41, 37, 36, .78);
        }
        .roulette-prize-cta:active {
            transform: scale(.97);
        }
        .roulette-prize-preview {
            border: 1px solid rgba(251, 191, 36, .15);
            background:
                linear-gradient(110deg, rgba(255, 255, 255, .07), transparent 38%),
                #1b1713;
            box-shadow:
                0 18px 32px -26px rgba(41, 37, 36, .8),
                inset 0 1px 0 rgba(255, 255, 255, .08);
        }
        .roulette-win-card {
            border: 1px solid rgba(146, 64, 14, .26);
            background:
                radial-gradient(circle at 50% -20%, rgba(255, 255, 255, .65), transparent 43%),
                linear-gradient(145deg, #ffe99b 0%, #ffd75b 52%, #f6c643 100%);
            box-shadow:
                0 22px 38px -25px rgba(120, 53, 15, .72),
                inset 0 1px 0 rgba(255, 255, 255, .7),
                inset 0 -5px 0 rgba(146, 64, 14, .08);
        }
        .roulette-prize-reveal > section {
            animation: roulette-prize-in .55s cubic-bezier(.16, 1, .3, 1) both;
        }
        .roulette-win-burst {
            background: repeating-conic-gradient(
                from -8deg,
                rgba(255, 255, 255, .48) 0deg 1.5deg,
                transparent 1.5deg 20deg
            );
            mask-image: radial-gradient(circle at 50% 0%, #000 0 8%, transparent 70%);
            opacity: .52;
            animation: roulette-burst-turn 32s linear infinite;
        }
        .roulette-win-number {
            font-size: clamp(3.25rem, 12vw, 4.35rem);
        }
        .roulette-lose-card {
            position: relative;
            overflow: hidden;
        }
        .roulette-lose-card::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: #d97706;
        }
        .roulette-pointer {
            position: absolute;
            left: 50%;
            top: 0;
            transform: translate(-50%, -3px);
        }
        @media (min-width: 800px) {
            .roulette-overlay-panel {
                grid-template-columns: minmax(0, 1fr) minmax(300px, 330px);
                grid-template-areas:
                    'copy game'
                    'rules game';
                align-items: start;
                column-gap: 2rem;
                row-gap: 1.25rem;
                padding: 1.75rem;
            }
            .roulette-copy {
                padding-top: 1.1rem;
                padding-right: 0;
            }
            .roulette-overlay-stage {
                width: 300px;
            }
            .roulette-wheel-label {
                width: 60px;
                height: 24px;
                margin: -12px 0 0 -30px;
                font-size: 9px;
                transform: rotate(var(--label-angle)) translateY(-108px) rotate(var(--label-counter-angle));
            }
            .roulette-spin-shell {
                width: 88px;
                height: 88px;
            }
            .roulette-prize-preview,
            .roulette-prize-reveal {
                margin-top: .8rem;
            }
        }
        @media (max-height: 700px) and (min-width: 800px) {
            .roulette-overlay-stage {
                width: 250px;
            }
            .roulette-wheel-label {
                width: 54px;
                height: 22px;
                margin: -11px 0 0 -27px;
                font-size: 8px;
                transform: rotate(var(--label-angle)) translateY(-89px) rotate(var(--label-counter-angle));
            }
            .roulette-spin-shell {
                width: 78px;
                height: 78px;
            }
            .roulette-win-number {
                font-size: 3.4rem;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .roulette-floating-cta,
            .roulette-floating-cta::after,
            .roulette-cta-spark,
            .roulette-stage-halo,
            .roulette-spin-shell::before,
            .roulette-win-burst {
                animation: none;
            }
            .roulette-overlay-panel,
            .roulette-prize-reveal > section {
                animation-duration: .01ms;
            }
            .roulette-overlay-wheel {
                transition-duration: .01ms;
            }
            .roulette-confetti-piece {
                display: none;
            }
        }
    </style>
</div>
