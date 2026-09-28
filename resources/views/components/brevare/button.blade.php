@props([
    'type' => 'submit',
    'variant' => 'primary',
    'block' => true,
])

@php
    $styles = [
        'primary' => 'bg-gray-900 text-white shadow-sm hover:bg-gray-800 focus-visible:ring-gray-900/30',
        'secondary' => 'bg-gray-100 text-gray-900 hover:bg-gray-200 focus-visible:ring-gray-900/20',
        'ghost' => 'bg-transparent text-gray-700 hover:bg-gray-100 focus-visible:ring-gray-900/20',
        'success' => 'bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 focus-visible:ring-emerald-600/30',
    ][$variant] ?? 'bg-gray-900 text-white shadow-sm hover:bg-gray-800 focus-visible:ring-gray-900/30';
@endphp

<button
    {{ $attributes->merge(['type' => $type, 'class' => 'base']) }}
    @class([
        'btn-shimmer relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl px-5 py-3.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
        $styles,
        $block ? 'w-full' : '',
    ])
>
    <span wire:loading.remove wire:loading.class="opacity-0" {{ $attributes->whereStartsWith('wire:target')->class('inline-flex items-center justify-center gap-2') }}>
        {{ $slot }}
    </span>

    <span class="inline-flex items-center justify-center gap-2" wire:loading>
        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Procesando…
    </span>
</button>