@props([
    'color' => 'neutral',
    'label' => null,
])

@php
    $styles = [
        'primary' => 'bg-gray-900 text-white',
        'neutral' => 'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200',
        'danger' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200',
        'info' => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200',
        'dark' => 'bg-gray-900 text-gray-100',
    ][$color] ?? 'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200';
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap', $styles]) }}>
    {{ $label ?? $slot }}
</span>