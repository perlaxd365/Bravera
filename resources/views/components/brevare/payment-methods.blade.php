@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-4']) }}>
    <div class="flex items-center gap-2">
        <flux:icon name="lock-closed" class="size-4 shrink-0 text-emerald-600" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">Pago 100% seguro</p>
            <p class="mt-0.5 text-xs text-gray-500">Elige el medio que prefieras al pagar</p>
        </div>
    </div>

    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 620 72"
        role="img"
        aria-label="Medios de pago: Visa, Mastercard, Yape, Plin y efectivo"
        class="mx-auto mt-4 h-auto w-full {{ $compact ? 'max-w-[480px]' : 'max-w-[560px]' }}"
    >
        <style>
            .payment-chip { fill: #fff; stroke: #e5e7eb; stroke-width: 1.5; }
            .payment-label { font-family: Inter, 'Helvetica Neue', Arial, sans-serif; font-weight: 700; }
            .payment-sub { font-family: Inter, 'Helvetica Neue', Arial, sans-serif; font-weight: 600; font-size: 10px; fill: #4b5563; }
        </style>

        <g transform="translate(0,4)">
            <rect class="payment-chip" width="116" height="64" rx="14" />
            <text class="payment-label" x="58" y="41" text-anchor="middle" font-size="24" font-style="italic" fill="#1a1f71" letter-spacing="1">VISA</text>
        </g>
        <g transform="translate(126,4)">
            <rect class="payment-chip" width="116" height="64" rx="14" />
            <circle cx="50" cy="26" r="12" fill="#eb001b" />
            <circle cx="68" cy="26" r="12" fill="#f79e1b" fill-opacity=".9" />
            <text class="payment-sub" x="58" y="52" text-anchor="middle">Mastercard</text>
        </g>
        <g transform="translate(252,4)">
            <rect class="payment-chip" width="116" height="64" rx="14" />
            <circle cx="58" cy="26" r="13" fill="#6e2b8e" />
            <text class="payment-label" x="58" y="31" text-anchor="middle" font-size="15" fill="#fff">Y</text>
            <text class="payment-sub" x="58" y="52" text-anchor="middle" fill="#6e2b8e">Yape</text>
        </g>
        <g transform="translate(378,4)">
            <rect class="payment-chip" width="116" height="64" rx="14" />
            <circle cx="48" cy="26" r="11" fill="none" stroke="#00a9e0" stroke-width="4" />
            <circle cx="68" cy="26" r="11" fill="none" stroke="#7bc143" stroke-width="4" />
            <text class="payment-sub" x="58" y="52" text-anchor="middle" fill="#0089b7">Plin</text>
        </g>
        <g transform="translate(504,4)">
            <rect class="payment-chip" width="116" height="64" rx="14" />
            <g stroke="#374151" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <rect x="44" y="17" width="28" height="18" rx="3" />
                <circle cx="58" cy="26" r="4" />
                <path d="M50 17v-3M66 17v-3M50 38v-3M66 38v-3" />
            </g>
            <text class="payment-sub" x="58" y="52" text-anchor="middle">Efectivo</text>
        </g>
    </svg>
</div>
