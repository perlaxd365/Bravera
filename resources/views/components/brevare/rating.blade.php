@props([
    'value' => 0,
    'max' => 5,
])

@php
    $value = (float) $value;
    $percent = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1', 'role' => 'img', 'aria-label' => number_format($value, 1) . ' de ' . $max . ' estrellas']) }}>
    <span class="relative inline-flex">
        <span class="flex text-gray-300">
            @for ($i = 1; $i <= $max; $i++)
                <flux:icon name="star" variant="mini" class="size-4" />
            @endfor
        </span>
        <span class="absolute inset-0 flex overflow-hidden text-amber-400" style="width: {{ $percent }}%">
            @for ($i = 1; $i <= $max; $i++)
                <flux:icon name="star" variant="mini" class="size-4 shrink-0 fill-current" />
            @endfor
        </span>
    </span>
    <span class="text-xs text-gray-500">{{ number_format($value, 1) }}</span>
</span>