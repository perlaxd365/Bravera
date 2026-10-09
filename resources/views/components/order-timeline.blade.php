@props([
    'status' => null,
    'cancelled' => false,
    'cancelledAt' => null,
    'cancelledReason' => null,
    'history' => [],
])

@php
    $steps = [
        'confirmed' => [
            'label' => 'Confirmado',
            'icon' => 'check',
            'description' => 'Pedido confirmado',
        ],
        'processing' => [
            'label' => 'Preparación',
            'icon' => 'cog',
            'description' => 'En preparación',
        ],
        'shipped' => [
            'label' => 'Enviado',
            'icon' => 'truck',
            'description' => 'En camino',
        ],
        'delivered' => [
            'label' => 'Entregado',
            'icon' => 'check-badge',
            'description' => 'Entregado',
        ],
    ];

    $currentKey = $status ?? '';
    $keys = array_keys($steps);
    $currentIndex = array_search($currentKey, $keys);
    $currentIndex = $currentIndex !== false ? $currentIndex : -1;
@endphp

<div class="w-full">

    {{-- Seguimiento --}}
    <section class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-[0_2px_12px_rgba(0,0,0,0.03)]">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold tracking-tight text-gray-900 sm:text-[15px]">
                    Seguimiento del pedido
                </h2>

                <p class="mt-0.5 text-xs text-gray-400">
                    Estado y progreso de tu pedido
                </p>
            </div>

            @if ($currentIndex !== -1 && isset($steps[$currentKey]))
                <div class="shrink-0">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[10px] font-semibold text-gray-600 sm:px-3 sm:py-1.5 sm:text-xs">
                        <span class="size-1.5 rounded-full bg-gray-900"></span>
                        {{ $steps[$currentKey]['label'] }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Timeline --}}
        <div class="w-full px-5 py-6 sm:px-8 sm:py-9">

            <div class="relative w-full">

                {{-- Línea base desktop --}}
                <div
                    class="absolute left-[12.5%] right-[12.5%] top-[18px] hidden h-px bg-gray-200 sm:block"
                    aria-hidden="true">
                </div>

                {{-- Línea de progreso desktop --}}
                @if ($currentIndex > 0)
                    <div
                        class="absolute left-[12.5%] top-[18px] hidden h-px bg-gray-900 transition-all duration-500 sm:block"
                        style="width: {{ ($currentIndex / (count($steps) - 1)) * 75 }}%;"
                        aria-hidden="true">
                    </div>
                @endif

                {{-- Etapas --}}
                <div class="grid w-full grid-cols-1 gap-0 sm:grid-cols-4 sm:gap-0">

                    @foreach ($steps as $key => $step)

                        @php
                            $stepIndex = array_search($key, $keys);

                            $isCompleted =
                                $currentIndex !== -1 &&
                                $stepIndex < $currentIndex;

                            $isCurrent =
                                $currentIndex !== -1 &&
                                $stepIndex === $currentIndex;

                            $isFuture =
                                $currentIndex === -1 ||
                                $stepIndex > $currentIndex;
                        @endphp

                        <div class="relative flex w-full min-w-0 items-start gap-4 py-4 first:pt-0 last:pb-0 sm:flex-col sm:items-center sm:gap-0 sm:py-0">

                            {{-- Línea vertical móvil --}}
                            @if ($stepIndex < count($steps) - 1)
                                <div
                                    class="absolute bottom-[-1px] left-[17px] top-[52px] w-px bg-gray-200 sm:hidden"
                                    aria-hidden="true">
                                </div>
                            @endif

                            {{-- Punto --}}
                            <div class="relative z-10 shrink-0">

                                @if ($isCompleted)

                                    <div class="flex size-9 items-center justify-center rounded-full bg-gray-900 text-white shadow-sm ring-4 ring-white">
                                        <flux:icon
                                            name="check"
                                            class="size-3.5"
                                        />
                                    </div>

                                @elseif ($isCurrent)

                                    <div class="flex size-9 items-center justify-center rounded-full bg-gray-900 text-white shadow-[0_2px_8px_rgba(0,0,0,0.15)] ring-4 ring-gray-100">
                                        <flux:icon
                                            name="{{ $step['icon'] }}"
                                            class="size-3.5"
                                        />
                                    </div>

                                @else

                                    <div class="flex size-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-300 ring-4 ring-white">
                                        <flux:icon
                                            name="{{ $step['icon'] }}"
                                            class="size-3.5"
                                        />
                                    </div>

                                @endif

                            </div>

                            {{-- Información --}}
                            <div class="min-w-0 flex-1 sm:mt-3 sm:w-full sm:flex-none sm:px-2 sm:text-center">

                                <p class="truncate text-sm font-semibold leading-5
                                    @if ($isCompleted || $isCurrent)
                                        text-gray-900
                                    @else
                                        text-gray-400
                                    @endif
                                ">
                                    {{ $step['label'] }}
                                </p>

                                <p class="mt-0.5 text-xs leading-4
                                    @if ($isCurrent)
                                        text-gray-500
                                    @elseif ($isCompleted)
                                        text-gray-400
                                    @else
                                        text-gray-300
                                    @endif
                                ">
                                    {{ $step['description'] }}
                                </p>

                                {{-- Fecha --}}
                                @if (($isCompleted || $isCurrent) && isset($history[$key]) && $history[$key])
                                    <p class="mt-1.5 text-[10px] font-medium tracking-wide text-gray-400">
                                        {{ \Carbon\Carbon::parse($history[$key])->locale('es')->isoFormat('DD MMM · HH:mm') }}
                                    </p>
                                @endif

                                {{-- Estado actual --}}
                                @if ($isCurrent)
                                    <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-2.5 py-1 text-[10px] font-semibold text-white">
                                        <span class="size-1 rounded-full bg-white"></span>
                                        Actual
                                    </span>
                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        </div>

    </section>

    {{-- Cancelado --}}
    @if ($cancelled)

        <section class="mt-4 overflow-hidden rounded-2xl border border-red-100 bg-white shadow-[0_2px_12px_rgba(0,0,0,0.03)]">

            <div class="flex items-start gap-3.5 p-5 sm:p-6">

                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500">
                    <flux:icon
                        name="x-circle"
                        class="size-4.5"
                    />
                </div>

                <div class="min-w-0 flex-1">

                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="text-sm font-semibold text-gray-900">
                            Pedido cancelado
                        </h4>

                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-red-600">
                            Cancelado
                        </span>
                    </div>

                    <p class="mt-1 text-sm leading-5 text-gray-600">
                        {{ $cancelledReason ?? 'El pedido fue cancelado' }}
                    </p>

                    @if ($cancelledAt)
                        <p class="mt-2 text-xs text-gray-400">
                            Cancelado el
                            {{ \Carbon\Carbon::parse($cancelledAt)->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm') }}
                        </p>
                    @endif

                </div>

            </div>

        </section>

    @endif

</div>