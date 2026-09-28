@props([
    'label',
    'name' => null,
    'id' => null,
    'autocomplete' => null,
    'strength' => false,
])

@php
    $wireModel = $attributes->wire('model');
    $model = $wireModel ? $wireModel->value() : null;
    $hasError = $model && $errors->has($model);
    $inputId = $id ?? ('password-' . ($model ? str_replace('.', '-', $model) : 'field'));
@endphp

<div
    x-data="{
        show: false,
        pw: '',
        score() {
            let s = 0;
            if (this.pw.length >= 8) s++;
            if (/[A-Z]/.test(this.pw)) s++;
            if (/\d/.test(this.pw)) s++;
            if (/[^A-Za-z0-9]/.test(this.pw)) s++;
            return s;
        },
        label() {
            const s = this.score();
            return this.pw.length === 0 ? '' : s <= 1 ? 'Débil' : s === 2 ? 'Regular' : s === 3 ? 'Buena' : 'Excelente';
        },
    }"
>
    <div class="relative">
        <span class="pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-gray-400">
            <flux:icon name="lock" class="size-5" />
        </span>

        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            :type="show ? 'text' : 'password'"
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($strength) x-on:input="pw = $event.target.value" @endif
            placeholder=" "
            {{ $attributes->except(['class', 'id']) }}
            @class([
                'peer h-14 w-full rounded-xl border bg-gray-50 pb-2 pl-11 pr-12 pt-6 text-sm text-gray-900 shadow-sm transition placeholder:text-transparent focus:outline-none',
                'border-gray-300 focus:border-gray-900 focus:bg-white focus:ring-2 focus:ring-gray-900/10' => ! $hasError,
                'border-red-300 bg-red-50/50 focus:border-red-400 focus:ring-2 focus:ring-red-500/10' => $hasError,
            ])
        >

        <label
            for="{{ $inputId }}"
            @class([
                'pointer-events-none absolute top-1/2 z-10 -translate-y-1/2 text-sm text-gray-400 transition-all duration-200 peer-focus:top-2.5 peer-focus:translate-y-0 peer-focus:text-[11px] peer-focus:font-semibold peer-focus:text-gray-900 peer-[:not(:placeholder-shown)]:top-2.5 peer-[:not(:placeholder-shown)]:translate-y-0 peer-[:not(:placeholder-shown)]:text-[11px] peer-[:not(:placeholder-shown)]:font-semibold peer-[:not(:placeholder-shown)]:text-gray-900',
                'left-11',
            ])
        >
            {{ $label }}
        </label>

        <button
            type="button"
            @click="show = !show"
            :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            class="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
        >
            <flux:icon name="eye" class="size-5" x-show="!show" x-cloak />
            <flux:icon name="eye-off" class="size-5" x-show="show" x-cloak />
        </button>
    </div>

    @if ($hasError)
        <div class="auth-shake">
            @error($model)
                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    @if ($strength)
        <div class="mt-2.5 flex items-center gap-1.5" x-cloak>
            <template x-for="i in 4" :key="i">
                <span
                    class="h-1 flex-1 rounded-full transition-colors duration-300"
                    :class="score() >= i ? 'bg-emerald-500' : 'bg-gray-200'"
                ></span>
            </template>
            <span class="ml-2 w-16 text-xs font-medium text-gray-500" x-text="label()"></span>
        </div>
    @endif
</div>