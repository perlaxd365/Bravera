@props([
    'label' => 'o continúa con tu correo',
])

<div {{ $attributes->merge(['class' => 'relative my-6']) }}>
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full border-t border-gray-200"></div>
    </div>
    <div class="relative flex justify-center">
        <span class="bg-white px-4 text-xs font-medium uppercase tracking-wider text-gray-400">
            {{ $label }}
        </span>
    </div>
</div>