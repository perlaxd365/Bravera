@props([
    'length' => 6,
    'method' => 'verify',
])

@php
    $wireModel = $attributes->wire('model');
    $model = $wireModel ? $wireModel->value() : 'code';
@endphp

<div
    x-data="{ {{ $model }}: $wire.entangle('{{ $model }}') }"
    class="relative cursor-text select-none"
    x-on:click="$refs.otp.focus()"
>
    <div class="flex gap-2" aria-hidden="true">
        <template x-for="(d, i) in ({{ $model }} + '          ').split('').slice(0, {{ $length }})" :key="i">
            <span
                x-text="d || ''"
                :class="d ? 'otp-filled' : ''"
                class="otp-input pointer-events-none flex h-14 w-full items-center justify-center rounded-xl border border-gray-300 bg-gray-50 text-2xl font-bold tabular-nums text-gray-900"
            ></span>
        </template>
    </div>

    <input
        type="text"
        inputmode="numeric"
        pattern="[0-9]*"
        maxlength="{{ $length }}"
        autocomplete="one-time-code"
        placeholder=""
        x-model="{{ $model }}"
        x-ref="otp"
        class="absolute inset-0 h-full w-full cursor-text opacity-0"
        x-init="$watch('{{ $model }}', (value) => { {{ $model }} = String(value ?? '').replace(/\D/g, '').slice(0, {{ $length }}); })"
        x-on:input="$nextTick(() => {
            const clean = String({{ $model }}).replace(/\D/g, '').slice(0, {{ $length }});
            {{ $model }} = clean;
            if (clean.length === {{ $length }}) $wire.{{ $method }}();
        })"
        aria-label="Código de verificación de {{ $length }} dígitos"
    >
</div>