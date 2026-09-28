@props([
    'amount' => 0,
    'currency' => 'PEN',
    'size' => 'sm',
])

@php
    $amount = (float) $amount;
    $formatted = match (mb_strtoupper($currency)) {
        'USD' => '$ ' . number_format($amount, 2),
        'EUR' => '€ ' . number_format($amount, 2),
        default => 'S/ ' . number_format($amount, 2),
    };
    $sizes = [
        'xs' => 'text-sm',
        'sm' => 'text-base',
        'md' => 'text-xl',
        'lg' => 'text-2xl',
    ];
@endphp

<span {{ $attributes->class(['font-semibold tracking-tight text-gray-900', $sizes[$size] ?? $sizes['sm']]) }}>
    {{ $formatted }}
</span>