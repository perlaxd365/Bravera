@props([
    'amount' => 0,
    'currency' => null,
    'size' => 'sm',
    'showApproximation' => true,
])

@php
    $amount = (float) $amount;
    $context = \App\Support\Localization\StorefrontContext::current();
    $displayCurrency = $currency ?: $context->currency;
    $displayAmount = strtoupper($displayCurrency) === $context->currency
        ? $context->displayAmount($amount)
        : ($displayCurrency === 'USD' ? $amount : $amount);
    $formatted = $context->format($displayAmount, $displayCurrency);
    $sizes = [
        'xs' => 'text-sm',
        'sm' => 'text-base',
        'md' => 'text-xl',
        'lg' => 'text-2xl',
    ];
@endphp

<span {{ $attributes->class(['font-semibold tracking-tight text-gray-900', $sizes[$size] ?? $sizes['sm']]) }}>
    @if ($showApproximation && $displayCurrency === 'USD')
        <span class="sr-only">{{ __('storefront.prices.approximate') }}</span>
        <span aria-hidden="true">≈</span>
    @endif
    {{ $formatted }}
</span>
