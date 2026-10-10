@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-4']) }}>
    <div class="flex items-center gap-2">
        <flux:icon name="lock-closed" class="size-4 shrink-0 text-emerald-600" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">Pago 100% seguro</p>
            <p class="mt-0.5 text-xs text-gray-500">Elige el medio que prefieras al pagar</p>
        </div>
    </div>

    <ul class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3" aria-label="Medios de pago aceptados">
        <li class="flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-2" aria-label="Visa">
            <span class="text-base font-black italic tracking-tight text-blue-800">VISA</span>
        </li>
        <li class="flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-2" aria-label="Mastercard">
            <span class="flex -space-x-1.5" aria-hidden="true">
                <span class="size-5 rounded-full bg-red-500/95"></span>
                <span class="size-5 rounded-full bg-amber-400/95"></span>
            </span>
            <span class="text-xs font-semibold text-gray-700">mastercard</span>
        </li>
        <li class="flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-2" aria-label="Yape">
            <span class="flex size-6 items-center justify-center rounded-full bg-purple-700 text-xs font-black text-white" aria-hidden="true">Y</span>
            <span class="text-sm font-bold text-purple-800">Yape</span>
        </li>
        <li class="flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-2" aria-label="Plin">
            <span class="flex size-6 items-center justify-center rounded-full bg-fuchsia-600 text-xs font-black text-white" aria-hidden="true">P</span>
            <span class="text-sm font-bold text-fuchsia-700">Plin</span>
        </li>
        <li class="col-span-2 flex min-h-11 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-2 py-2 sm:col-span-1" aria-label="Pago en efectivo">
            <flux:icon name="banknotes" class="size-5 text-emerald-700" />
            <span class="text-xs font-semibold text-gray-700">Pago en efectivo</span>
        </li>
    </ul>
</div>
