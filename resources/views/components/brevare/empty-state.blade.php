@props([
    'icon' => 'cube',
    'title' => 'No hay contenido',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16 text-center']) }}>
    <div class="flex size-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
        <flux:icon :name="$icon" variant="outline" class="size-8" />
    </div>

    <h3 class="mt-5 text-base font-semibold tracking-tight text-gray-900">{{ $title }}</h3>

    @if ($description)
        <p class="mt-2 max-w-sm text-sm leading-relaxed text-gray-500">{{ $description }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-6">
            {{ $slot }}
        </div>
    @endif
</div>