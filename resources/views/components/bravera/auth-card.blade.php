@props([
    'title',
    'subtitle' => null,
    'icon' => null,
])

<section {{ $attributes->merge(['class' => 'auth-rise w-full max-w-md']) }}>
    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-2xl shadow-gray-950/5 sm:p-8">

        @if ($icon)
            <span class="mb-5 inline-flex size-11 items-center justify-center rounded-2xl bg-gray-900 text-white">
                <flux:icon name="{{ $icon }}" class="size-5" />
            </span>
        @endif

        <h2 class="text-2xl font-extrabold tracking-tight text-gray-900">{{ $title }}</h2>

        @if ($subtitle)
            <p class="mt-2 text-sm leading-relaxed text-gray-500">{{ $subtitle }}</p>
        @endif

        <div class="mt-6">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="mt-6 border-t border-gray-100 pt-5">
                {{ $footer }}
            </div>
        @endisset
    </div>
</section>