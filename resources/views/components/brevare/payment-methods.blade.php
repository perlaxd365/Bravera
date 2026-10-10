@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-4']) }}>
    <div class="flex items-center gap-2">
        <flux:icon name="lock-closed" class="size-4 shrink-0 text-emerald-600" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">Pago 100% seguro</p>
            <p class="mt-0.5 text-xs text-gray-500">Elige el medio que prefieras al pagar</p>
        </div>
    </div>

    <ul
        class="mx-auto mt-4 grid w-full {{ $compact ? 'max-w-[480px]' : 'max-w-[560px]' }} grid-cols-3 gap-2 sm:grid-cols-6"
        aria-label="Medios de pago disponibles con Culqi"
    >
        @foreach ([
            ['Visa', 'visa.svg'],
            ['Mastercard', 'masterCard.svg'],
            ['Diners Club', 'diners.svg'],
            ['American Express', 'amex.svg'],
            ['Yape', 'yape.svg'],
            ['Plin', 'plin.svg'],
        ] as [$name, $logo])
            <li class="flex h-12 items-center justify-center rounded-xl border border-gray-200 bg-white px-2 sm:h-14">
                <img
                    src="https://culqi.com/assets/images/shared/logos/{{ $logo }}"
                    alt="{{ $name }}"
                    title="{{ $name }}"
                    width="88"
                    height="36"
                    loading="lazy"
                    decoding="async"
                    referrerpolicy="no-referrer"
                    class="max-h-8 w-full object-contain"
                >
            </li>
        @endforeach
    </ul>
</div>
