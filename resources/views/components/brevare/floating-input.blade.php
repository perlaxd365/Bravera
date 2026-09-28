@props([
    'label',
    'type' => 'text',
    'name' => null,
    'icon' => null,
    'autocomplete' => null,
])

@php
    $wireModel = $attributes->wire('model');
    $model = $wireModel ? $wireModel->value() : null;
    $hasError = $model && $errors->has($model);
    $id = $attributes->get('id') ?? 'field-' . ($model ? str_replace('.', '-', $model) : uniqid());
@endphp

<div>
    <div class="relative">
        @if ($icon)
            <span class="pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-gray-400">
                <flux:icon name="{{ $icon }}" class="size-5" />
            </span>
        @endif

        <input
            id="{{ $id }}"
            type="{{ $type }}"
            name="{{ $name }}"
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            placeholder=" "
            {{ $attributes->except(['class', 'id']) }}
            @class([
                'peer h-14 w-full rounded-xl border bg-gray-50 px-4 pb-2 pt-6 text-sm text-gray-900 shadow-sm transition placeholder:text-transparent focus:outline-none',
                'pl-11' => $icon,
                'border-gray-300 focus:border-gray-900 focus:bg-white focus:ring-2 focus:ring-gray-900/10' => ! $hasError,
                'border-red-300 bg-red-50/50 focus:border-red-400 focus:ring-2 focus:ring-red-500/10' => $hasError,
            ])
        >

        <label
            for="{{ $id }}"
            @class([
                'pointer-events-none absolute top-1/2 z-10 -translate-y-1/2 text-sm text-gray-400 transition-all duration-200 peer-focus:top-2.5 peer-focus:translate-y-0 peer-focus:text-[11px] peer-focus:font-semibold peer-focus:text-gray-900 peer-[:not(:placeholder-shown)]:top-2.5 peer-[:not(:placeholder-shown)]:translate-y-0 peer-[:not(:placeholder-shown)]:text-[11px] peer-[:not(:placeholder-shown)]:font-semibold peer-[:not(:placeholder-shown)]:text-gray-900',
                'left-11' => $icon,
                'left-4' => ! $icon,
            ])
        >
            {{ $label }}
        </label>
    </div>

    @if ($hasError)
        <div class="auth-shake">
            @error($model)
                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif
</div>