@php
    $items = [
        ['route' => 'admin.settings.index', 'active' => 'admin.settings.index', 'label' => 'Resumen', 'icon' => 'squares-2x2'],
        ['route' => 'admin.settings.homepage', 'active' => 'admin.settings.homepage', 'label' => 'Portada', 'icon' => 'photo'],
        ['route' => 'admin.settings.integrations', 'active' => 'admin.settings.integrations', 'label' => 'Integraciones', 'icon' => 'key'],
        ['route' => 'admin.shipping.zones.index', 'active' => 'admin.shipping.*', 'label' => 'Envíos', 'icon' => 'truck'],
    ];
@endphp

<nav {{ $attributes->merge(['class' => 'mb-6']) }} aria-label="Secciones de configuración">
    <div class="flex gap-2 overflow-x-auto rounded-2xl border border-stone-200 bg-white p-1.5 shadow-sm lg:grid lg:grid-cols-4">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}"
                @class([
                    'inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold transition lg:justify-start',
                    'bg-stone-950 text-white shadow-sm' => request()->routeIs($item['active']),
                    'text-stone-600 hover:bg-amber-50 hover:text-stone-950' => ! request()->routeIs($item['active']),
                ])
                @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                <flux:icon :name="$item['icon']" class="size-4" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
