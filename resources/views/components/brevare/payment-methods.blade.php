@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-4']) }}>
    <div class="flex items-center gap-2">
        <flux:icon name="lock-closed" class="size-4 shrink-0 text-emerald-600" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">Pago 100% seguro</p>
            <p class="mt-0.5 text-xs text-gray-500">Elige el medio que prefieras al pagar</p>
        </div>
    </div>

    <img
        src="{{ asset('images/metodos-de-pago.svg') }}"
        alt="Medios de pago: Visa, Mastercard, Yape, Plin y efectivo"
        width="620"
        height="72"
        loading="lazy"
        decoding="async"
        class="mx-auto mt-4 h-auto w-full {{ $compact ? 'max-w-[480px]' : 'max-w-[560px]' }}"
    >
</div>
