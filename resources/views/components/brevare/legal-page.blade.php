@props(['title', 'updated' => null])
@php($company = config('brevare.company'))

<section class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
    <nav class="mb-6 text-sm text-gray-400">
        <a href="{{ route('home') }}" class="transition hover:text-gray-900">Inicio</a>
        <span class="mx-2">/</span>
        <span class="text-gray-600">{{ $title }}</span>
    </nav>

    <header class="border-b border-gray-200 pb-6">
        <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">{{ $title }}</h1>
        <p class="mt-2 text-sm text-gray-500">
            Última actualización: {{ $updated ?? now()->translatedFormat('d \d\e F \d\e Y') }}
        </p>
    </header>

    <div class="mt-8">
        {{ $slot }}
    </div>

    <div class="mt-12 rounded-2xl border border-gray-200 bg-gray-50 p-6">
        <h3 class="text-sm font-semibold text-gray-900">¿Tienes dudas?</h3>
        <p class="mt-1 text-sm text-gray-600">
            Escríbenos a
            <a href="mailto:{{ $company['support_email'] }}" class="font-medium text-gray-900 underline underline-offset-4">{{ $company['support_email'] }}</a>
            o llámanos al <a href="tel:{{ $company['phone_e164'] }}" class="font-medium text-gray-900 underline underline-offset-4">{{ $company['phone'] }}</a>.
        </p>
        <p class="mt-1 text-sm text-gray-600">{{ $company['address'] }}, distrito de {{ $company['city'] }}, provincia y departamento de {{ $company['region'] }}, {{ $company['country_name'] }}.</p>
    </div>
</section>
