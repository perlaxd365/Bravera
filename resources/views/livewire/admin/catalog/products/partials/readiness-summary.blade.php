@php
    $readinessColor = $readiness['ready']
        ? '#059669'
        : ($readiness['percent'] >= 50 ? '#d97706' : '#dc2626');
@endphp

<div class="mt-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-3">
        <div class="flex size-12 shrink-0 items-center justify-center rounded-full p-1"
            style="background: conic-gradient({{ $readinessColor }} {{ $readiness['percent'] }}%, #e7e5e4 0)">
            <div class="flex size-full items-center justify-center rounded-full bg-white text-[11px] font-bold text-stone-900">
                {{ $readiness['percent'] }}%
            </div>
        </div>
        <div class="min-w-0">
            <p class="text-xs font-bold text-stone-950">
                {{ $readiness['ready'] ? 'Listo para publicar' : 'Preparación para publicar' }}
            </p>
            <p class="mt-0.5 text-[11px] leading-4 text-stone-500">
                {{ $readiness['completed'] }}/{{ $readiness['total'] }} requisitos completos
            </p>
        </div>
    </div>

    @if ($readiness['missing'])
        <div class="mt-3 border-t border-stone-100 pt-3">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-stone-400">Siguiente pendiente</p>
            <p class="mt-1 text-[11px] font-medium leading-4 text-stone-700">{{ $readiness['missing'][0] }}</p>
            @if (count($readiness['missing']) > 1)
                <p class="mt-0.5 text-[10px] text-stone-400">+{{ count($readiness['missing']) - 1 }} pendiente(s) más</p>
            @endif
        </div>
    @else
        <p class="mt-3 border-t border-emerald-100 pt-3 text-[11px] font-medium leading-4 text-emerald-700">
            La ficha cumple todos los requisitos de publicación.
        </p>
    @endif
</div>
