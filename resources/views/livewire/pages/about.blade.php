<div>
    <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:py-16">
        <nav class="mb-6 text-sm text-gray-400" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="transition hover:text-gray-900">{{ __('storefront.common.home') }}</a>
            <span class="mx-2">/</span>
            <span class="text-gray-600">{{ __('storefront.common.about') }}</span>
        </nav>

        <header class="max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-amber-700">{{ app()->getLocale() === 'en' ? 'Our story' : 'Nuestra historia' }}</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-gray-950 sm:text-5xl">{{ app()->getLocale() === 'en' ? 'About Brevare' : 'Sobre Brevare' }}</h1>
            <p class="mt-5 text-lg leading-8 text-gray-600">
                {{ app()->getLocale() === 'en'
                    ? 'Brevare is a Peruvian online store bringing fashion, technology, home goods, and gifts together in one place, with shipping throughout Peru and attentive support.'
                    : 'Brevare es una tienda online peruana que reúne moda, tecnología, hogar y regalos en un solo lugar, con envíos a todo el Perú y una atención cercana.' }}
            </p>
        </header>

        <div class="mt-10 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ app()->getLocale() === 'en' ? 'Founded' : 'Fundación' }}</p>
                <p class="mt-2 text-lg font-bold text-gray-900">17/10/2026</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ app()->getLocale() === 'en' ? 'Offices' : 'Oficinas' }}</p>
                <p class="mt-2 text-lg font-bold text-gray-900">Lima, Perú</p>
                <p class="mt-1 text-sm text-gray-500">{{ $company['address'] }}, {{ $company['city'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ app()->getLocale() === 'en' ? 'Coverage' : 'Cobertura' }}</p>
                <p class="mt-2 text-lg font-bold text-gray-900">{{ app()->getLocale() === 'en' ? 'All of Peru' : 'Todo el Perú' }}</p>
            </div>
        </div>

        <section class="mt-14">
            <h2 class="text-2xl font-bold tracking-tight text-gray-950">{{ app()->getLocale() === 'en' ? 'Our founders' : 'Nuestros fundadores' }}</h2>
            <p class="mt-2 max-w-2xl text-gray-600">
                {{ app()->getLocale() === 'en' ? 'Brevare was founded by Raúl Baca and Perla García on October 17, 2026 in Lima, Peru.' : 'Brevare fue fundada por Raúl Baca y Perla García el 17 de octubre de 2026 en Lima, Perú.' }}
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($seo['founders'] as $founder)
                    <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex size-14 items-center justify-center rounded-2xl bg-amber-300 text-lg font-extrabold text-amber-950">
                            {{ mb_substr($founder['name'], 0, 1) }}
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-gray-950">{{ $founder['name'] }}</h3>
                        <p class="text-sm font-medium text-amber-800">{{ $founder['job_title'] }}</p>
                        <p class="mt-3 text-sm leading-6 text-gray-600">
                            {{ app()->getLocale() === 'en' ? 'Part of the Brevare founding team.' : 'Parte del equipo fundador de Brevare.' }}
                        </p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-14 grid gap-8 border-t border-gray-200 pt-10 lg:grid-cols-2">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-950">{{ app()->getLocale() === 'en' ? 'What drives us' : 'Lo que nos mueve' }}</h2>
                <p class="mt-4 leading-7 text-gray-600">
                    {{ app()->getLocale() === 'en' ? 'We want online shopping to feel simple: thoughtfully chosen products, clear information, fair prices, and a team ready to help.' : 'Queremos que comprar online sea simple: productos bien elegidos, información clara, precios justos y un equipo que responda cuando lo necesites.' }}
                </p>
                <p class="mt-4 leading-7 text-gray-600">
                    {{ app()->getLocale() === 'en' ? 'Every order is prepared with care and shipped to the address you choose anywhere in Peru.' : 'Cada pedido se prepara con cuidado y se envía a la dirección que elijas en cualquier departamento del Perú.' }}
                </p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                <h2 class="text-lg font-bold text-amber-950">{{ app()->getLocale() === 'en' ? 'Contact details' : 'Datos de contacto' }}</h2>
                <ul class="mt-4 space-y-3 text-sm text-amber-950/80">
                    <li><span class="font-semibold">{{ app()->getLocale() === 'en' ? 'Legal name:' : 'Razón social:' }}</span> {{ $company['legal_name'] }}</li>
                    <li><span class="font-semibold">RUC:</span> {{ $company['ruc'] }}</li>
                    <li>
                        <span class="font-semibold">{{ app()->getLocale() === 'en' ? 'Email:' : 'Correo:' }}</span>
                        <a class="underline underline-offset-4" href="mailto:{{ $company['support_email'] }}">{{ $company['support_email'] }}</a>
                    </li>
                    <li>
                        <span class="font-semibold">{{ app()->getLocale() === 'en' ? 'Phone:' : 'Teléfono:' }}</span>
                        <a class="underline underline-offset-4" href="tel:{{ $company['phone_e164'] }}">{{ $company['phone'] }}</a>
                    </li>
                    <li><span class="font-semibold">{{ app()->getLocale() === 'en' ? 'Offices:' : 'Oficinas:' }}</span> {{ $company['address'] }}, {{ $company['city'] }}, {{ $company['region'] }}, {{ $company['country_name'] }}</li>
                </ul>
            </div>
        </section>

        <div class="mt-12">
            <a href="{{ route('store.search') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-full bg-gray-950 px-6 text-sm font-semibold text-white transition hover:bg-amber-800">
                {{ __('storefront.footer.explore') }}
            </a>
        </div>
    </section>
</div>
