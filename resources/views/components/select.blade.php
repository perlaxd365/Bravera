@php
    $wireModel = $attributes->wire('model');
    $model = $wireModel ? $wireModel->value() : null;
@endphp

<div class="mb-4">
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-gray-700">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <select
            @class([
                'block w-full appearance-none rounded-lg border bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:ring-2',
                'disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500',
                'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' => ! ($model && $errors->has($model)),
                'border-red-300 focus:border-red-400 focus:ring-red-500/10' => $model && $errors->has($model),
            ])
            {{ $attributes }}>
            {{ $slot }}
        </select>

        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
            <flux:icon name="chevron-down" variant="mini" class="size-4" />
        </span>
    </div>
</div>

@if ($model && $errors->has($model))
    <p class="-mt-2 mb-4 text-sm text-red-600">{{ $errors->first($model) }}</p>
@endif