@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-4']) }}>
    <div class="flex items-center gap-2">
        <flux:icon name="lock-closed" class="size-4 shrink-0 text-emerald-600" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">Pago 100% seguro</p>
            <p class="mt-0.5 text-xs text-gray-500">Elige el medio que prefieras al pagar</p>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-gray-500">
        <span>Powered by</span>
        <img
            src="https://culqi.com/assets/images/shared/logos/culqiConNombre.svg"
            alt="Culqi"
            width="82"
            height="24"
            loading="lazy"
            decoding="async"
            referrerpolicy="no-referrer"
            style="display:block;width:auto!important;height:22px!important;max-width:82px!important;object-fit:contain"
        >
    </div>

    <ul
        class="mx-auto mt-4 w-full {{ $compact ? 'max-w-[480px]' : 'max-w-[560px]' }}"
        aria-label="Medios de pago disponibles con Culqi"
        style="display:flex;flex-wrap:wrap;justify-content:center;gap:8px"
    >
        @foreach ([
            ['Visa', 'visa.svg'],
            ['Mastercard', 'masterCard.svg'],
            ['Diners Club', 'diners.svg'],
            ['American Express', 'amex.svg'],
            ['Yape', 'yape.svg'],
            ['Plin', 'plin.svg'],
        ] as [$name, $logo])
            <li class="flex shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white px-2"
                style="width:72px;height:48px;flex:0 0 72px">
                <img
                    src="https://culqi.com/assets/images/shared/logos/{{ $logo }}"
                    alt="{{ $name }}"
                    title="{{ $name }}"
                    width="76"
                    height="28"
                    loading="lazy"
                    decoding="async"
                    referrerpolicy="no-referrer"
                    style="display:block;width:auto!important;height:28px!important;max-width:100%!important;object-fit:contain"
                >
            </li>
        @endforeach
    </ul>

</div>
