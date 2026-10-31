<div class="mx-auto max-w-7xl">
    <header class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Administración</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-950 sm:text-3xl">Configuración de Brevare</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">Cada área tiene su propio espacio. Revisa primero lo que falta y entra directamente a la sección que necesitas.</p>
    </header>

    <x-admin.settings-nav />

    <div class="grid gap-4 lg:grid-cols-2">
        <a href="{{ route('admin.settings.homepage') }}" class="group overflow-hidden rounded-3xl border border-stone-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-lg">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-900">
                <flux:icon name="photo" class="size-5" />
            </span>
            <h2 class="mt-5 text-lg font-bold text-stone-950">Contenido de la tienda</h2>
            <p class="mt-1 text-sm leading-6 text-stone-600">Portadas, banners, imágenes de categorías, textos editoriales y frases rotativas.</p>
            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-amber-800">Editar portada <span aria-hidden="true">→</span></span>
        </a>

        <a href="{{ route('admin.settings.integrations') }}" class="group overflow-hidden rounded-3xl border border-stone-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-900">
                <flux:icon name="key" class="size-5" />
            </span>
            <h2 class="mt-5 text-lg font-bold text-stone-950">Integraciones y credenciales</h2>
            <p class="mt-1 text-sm leading-6 text-stone-600">URLs de SEO, Cloudinary, Meta Pixel, Conversions API y Marketing API.</p>
            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-emerald-800">
                {{ collect($integrationGroups)->filter(fn ($group) => $group['ready'])->count() }} de {{ count($integrationGroups) }} áreas listas
                <span aria-hidden="true">→</span>
            </span>
        </a>
    </div>

    <section class="mt-5 rounded-3xl border border-stone-200 bg-stone-50 p-5 sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-stone-950">Estado rápido</h2>
                <p class="mt-1 text-sm text-stone-600">Solo se muestra si falta información, sin exponer tokens ni claves.</p>
            </div>
            <a href="{{ route('admin.settings.integrations') }}" class="inline-flex min-h-10 items-center rounded-full border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-800 hover:bg-white">Revisar todo</a>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($integrationGroups as $key => $group)
                <a href="{{ route('admin.settings.integrations', ['group' => $key]) }}" class="rounded-2xl border border-stone-200 bg-white p-4">
                    <span class="flex items-center justify-between gap-2 text-sm font-semibold text-stone-900">
                        {{ $group['label'] }}
                        <span @class(['size-2.5 rounded-full', 'bg-emerald-500' => $group['ready'], 'bg-amber-400' => ! $group['ready']])></span>
                    </span>
                    <span class="mt-1 block text-xs text-stone-500">{{ $group['configured'] }}/{{ $group['total'] }} campos listos</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mt-5 grid gap-3 md:grid-cols-2">
        <a href="{{ route('admin.shipping.zones.index') }}" class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 transition hover:border-stone-300">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700"><flux:icon name="map-pin" class="size-4.5" /></span>
            <span><span class="block text-sm font-semibold text-stone-900">Zonas de envío</span><span class="block text-xs text-stone-500">Cobertura y disponibilidad por destino.</span></span>
        </a>
        <a href="{{ route('admin.shipping.rates.index') }}" class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 transition hover:border-stone-300">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700"><flux:icon name="banknotes" class="size-4.5" /></span>
            <span><span class="block text-sm font-semibold text-stone-900">Tarifas de envío</span><span class="block text-xs text-stone-500">Costos y reglas de despacho.</span></span>
        </a>
    </section>
</div>
