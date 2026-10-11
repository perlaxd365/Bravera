@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'plainOnMobile' => false,
])

<section {{ $attributes->merge(['class' => 'auth-rise mx-auto w-full min-w-0 max-w-md']) }}>
    <div @class([
        'w-full min-w-0 rounded-3xl border border-gray-200 bg-white p-5 shadow-2xl shadow-gray-950/5 sm:p-8' => ! $plainOnMobile,
        'w-full min-w-0 bg-transparent p-0 sm:rounded-3xl sm:border sm:border-gray-200 sm:bg-white sm:p-8 sm:shadow-2xl sm:shadow-gray-950/5' => $plainOnMobile,
    ])>

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
