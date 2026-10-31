@props(['compact' => false, 'provider' => 'culqi', 'presentation' => 'panel'])

@php
    $isMercadoPago = $provider === 'mercadopago';
    $isInline = $presentation === 'inline';
    $providerName = $isMercadoPago ? 'Mercado Pago' : 'Culqi';
    $methods = $isMercadoPago
        ? [
            ['Visa', 'visa.svg'],
            ['Mastercard', 'masterCard.svg'],
            ['American Express', 'amex.svg'],
            ['Diners Club', 'diners.svg'],
        ]
        : [
            ['Visa', 'visa.svg'],
            ['Mastercard', 'masterCard.svg'],
            ['American Express', 'amex.svg'],
            ['Diners Club', 'diners.svg'],
            ['Yape', 'yape.svg'],
            ['Plin', 'plin.svg'],
        ];
@endphp

@if ($presentation === 'product')
    <div {{ $attributes->merge(['class' => 'text-center']) }}>
        <h3 class="flex items-center justify-center gap-2 text-[15px] font-semibold text-stone-800">
            <flux:icon name="lock-closed" class="size-4 text-stone-700" />
            {{ app()->getLocale() === 'en' ? 'Multiple payment options' : 'Múltiples opciones de pago' }}
        </h3>

        <ul class="mx-auto mt-3 flex max-w-[540px] flex-wrap justify-center gap-2"
            aria-label="{{ app()->getLocale() === 'en' ? 'Payment methods available with' : 'Medios de pago disponibles con' }} {{ $providerName }}">
            @foreach ($methods as [$name, $logo])
                <li class="flex h-9 w-[74px] shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-200 bg-white px-2">
                    <img
                        src="https://culqi.com/assets/images/shared/logos/{{ $logo }}"
                        alt="{{ $name }}"
                        title="{{ $name }}"
                        width="68"
                        height="24"
                        loading="lazy"
                        decoding="async"
                        referrerpolicy="no-referrer"
                        style="display:block;width:auto!important;height:22px!important;max-width:100%!important;object-fit:contain"
                    >
                </li>
            @endforeach
        </ul>
    </div>
@else
<div {{ $attributes->merge([
    'class' => $isInline
        ? 'rounded-xl bg-gray-50/80 p-3'
        : 'rounded-2xl border border-gray-200 bg-white p-4',
]) }}>
    <div class="flex items-center gap-2">
        <flux:icon name="lock-closed" class="size-4 shrink-0 text-emerald-600" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">{{ app()->getLocale() === 'en' ? '100% secure payment' : 'Pago 100% seguro' }}</p>
            <p class="mt-0.5 text-xs text-gray-500">{{ app()->getLocale() === 'en' ? 'Choose your preferred payment method' : 'Elige el medio que prefieras al pagar' }}</p>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-gray-500">
        <span>Powered by</span>
        @if ($isMercadoPago)
            <span class="text-[15px] font-bold tracking-tight">
                <span class="text-[#009ee3]">Mercado</span>
                <span class="text-[#f5a623]">Pago</span>
            </span>
        @else
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
        @endif
    </div>

    <ul
        @class([
            'mx-auto w-full',
            'mt-3' => $isInline,
            'mt-4' => ! $isInline,
            'max-w-[480px]' => $compact,
            'max-w-[560px]' => ! $compact,
        ])
        aria-label="{{ app()->getLocale() === 'en' ? 'Payment methods available with' : 'Medios de pago disponibles con' }} {{ $providerName }}"
        style="display:flex;flex-wrap:wrap;justify-content:center;gap:8px"
    >
        @foreach ($methods as [$name, $logo])
            <li @class([
                'flex shrink-0 items-center justify-center overflow-hidden border border-gray-200 bg-white px-2',
                'rounded-lg' => $isInline,
                'rounded-xl' => ! $isInline,
            ])
                style="{{ $isInline ? 'width:64px;height:42px;flex:0 0 64px' : 'width:72px;height:48px;flex:0 0 72px' }}">
                <img
                    src="https://culqi.com/assets/images/shared/logos/{{ $logo }}"
                    alt="{{ $name }}"
                    title="{{ $name }}"
                    width="76"
                    height="28"
                    loading="lazy"
                    decoding="async"
                    referrerpolicy="no-referrer"
                    style="display:block;width:auto!important;height:{{ $isInline ? '24' : '28' }}px!important;max-width:100%!important;object-fit:contain"
                >
            </li>
        @endforeach
    </ul>

</div>
@endif
