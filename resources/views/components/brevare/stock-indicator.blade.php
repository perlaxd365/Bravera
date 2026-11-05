@props([
    'count' => 0,
    'digital' => false,
])

@php
    $available = max(0, (int) $count);
    $isDigital = (bool) $digital;
    $isOutOfStock = ! $isDigital && $available <= 0;
    $isLowStock = ! $isDigital && $available > 0 && $available <= 5;
    $label = match (true) {
        $isDigital => __('storefront.catalog.stock_digital'),
        $isOutOfStock => __('storefront.catalog.stock_out'),
        $available === 1 => __('storefront.catalog.stock_one_left'),
        $isLowStock => __('storefront.catalog.stock_low_left', ['count' => $available]),
        default => __('storefront.catalog.stock_available', ['count' => $available]),
    };
@endphp

<span {{ $attributes->class([
    'brevare-stock',
    'brevare-stock--digital' => $isDigital,
    'brevare-stock--out' => $isOutOfStock,
    'brevare-stock--low' => $isLowStock,
    'brevare-stock--available' => ! $isDigital && ! $isOutOfStock && ! $isLowStock,
]) }}>
    <span class="brevare-stock__signal" aria-hidden="true">
        <span class="brevare-stock__halo"></span>
        <span class="brevare-stock__dot"></span>
    </span>
    <span>{{ $label }}</span>
</span>
