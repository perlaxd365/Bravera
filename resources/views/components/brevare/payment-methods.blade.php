@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-4']) }}>
    <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
        <flux:icon name="lock-closed" class="size-4 text-emerald-600" />
        Pago 100% seguro
    </p>

    <img
        src="{{ asset('images/metodos-de-pago.svg') }}"
        alt="Métodos de pago aceptados: Visa, Mastercard, Yape, Plin y efectivo"
        width="620"
        height="72"
        loading="lazy"
        decoding="async"
        @class(['mt-3 w-full', 'max-w-[360px]' => $compact, 'max-w-[420px]' => ! $compact])
    >
</div>
