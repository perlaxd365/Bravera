<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-stretch justify-center bg-stone-950/45 p-0 backdrop-blur-[3px] sm:items-center sm:p-4"
            role="dialog" aria-modal="true" aria-labelledby="product-wizard-title"
            @keydown.escape.window="$wire.set('show', false)">
            <button type="button" class="absolute inset-0 cursor-default" wire:click="$set('show', false)" aria-label="Cerrar formulario"></button>

            <div class="relative flex h-[100dvh] w-full max-w-[1180px] flex-col overflow-hidden bg-[#fbfaf7] shadow-2xl shadow-stone-950/20 sm:h-[min(94dvh,900px)] sm:rounded-[28px] sm:border sm:border-stone-200">
                <header class="shrink-0 border-b border-stone-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-6">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-900 ring-1 ring-inset ring-amber-200">
                                <flux:icon name="cube" class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <h3 id="product-wizard-title" class="truncate text-base font-bold tracking-tight text-stone-950 sm:text-lg">
                                        {{ $form->id ? 'Editar producto' : 'Registrar producto' }}
                                    </h3>
                                    <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-stone-600">
                                        Paso {{ $currentStep }} de 6
                                    </span>
                                    <span @class([
                                        'rounded-full px-2.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset',
                                        'bg-emerald-50 text-emerald-700 ring-emerald-200' => $readiness['ready'],
                                        'bg-amber-50 text-amber-800 ring-amber-200' => ! $readiness['ready'],
                                    ])>
                                        {{ $readiness['percent'] }}% listo
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-stone-500">
                                    {{ $form->name ?: 'Completa la ficha paso a paso; puedes avanzar y volver sin perder datos.' }}
                                </p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('show', false)"
                            class="flex size-11 shrink-0 items-center justify-center rounded-full text-stone-500 transition hover:bg-stone-100 hover:text-stone-900"
                            aria-label="Cerrar">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>
                </header>

                <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
                    <div class="grid min-h-0 flex-1 lg:grid-cols-[224px_minmax(0,1fr)]">
                        <aside class="hidden min-h-0 overflow-y-auto border-r border-stone-200 bg-[#f4f1e9] px-4 py-6 lg:block">
                            <p class="px-2 text-[10px] font-bold uppercase tracking-[0.18em] text-stone-500">Ruta de registro</p>
                            <nav class="mt-4 space-y-1.5" aria-label="Pasos del producto">
                                @foreach ([
                                    1 => ['Producto', 'Identidad y descripción', 'tag'],
                                    2 => ['Contenido', 'SEO, video e imágenes', 'photo'],
                                    3 => ['Variantes', 'Precios, stock y atributos', 'swatch'],
                                    4 => ['Proveedores', 'Costo, inventario y despacho', 'truck'],
                                    5 => ['Marketing', 'Anuncios en Meta', 'megaphone'],
                                    6 => ['Vista previa', 'Así se verá en la portada', 'eye'],
                                ] as $step => $item)
                                    <button type="button" wire:click="goToStep({{ $step }})"
                                        @class([
                                            'group flex w-full items-start gap-3 rounded-2xl px-3 py-3 text-left transition',
                                            'bg-white shadow-sm ring-1 ring-stone-200' => $currentStep === $step,
                                            'hover:bg-white/65' => $currentStep !== $step,
                                        ])
                                        @if ($currentStep === $step) aria-current="step" @endif>
                                        <span @class([
                                            'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl text-xs font-bold ring-1 ring-inset',
                                            'bg-stone-950 text-white ring-stone-950' => $currentStep === $step,
                                            'bg-white text-stone-500 ring-stone-200 group-hover:text-stone-800' => $currentStep !== $step,
                                        ])>{{ $step }}</span>
                                        <span class="min-w-0">
                                            <span class="flex items-center gap-1.5 text-sm font-semibold text-stone-900">
                                                {{ $item[0] }}
                                                @if ($step === 2 && $productVideoUrl)
                                                    <span class="size-1.5 rounded-full bg-emerald-500" title="Video cargado"></span>
                                                @elseif ($step === 3 && $variants->isNotEmpty())
                                                    <span class="size-1.5 rounded-full bg-emerald-500" title="Variantes creadas"></span>
                                                @elseif ($step === 4 && $supplierVariantVariantId)
                                                    <span class="size-1.5 rounded-full bg-sky-500" title="Variante seleccionada"></span>
                                                @elseif ($step === 5 && $metaAds->isNotEmpty())
                                                    <span class="size-1.5 rounded-full bg-sky-500" title="Anuncios creados"></span>
                                                @endif
                                            </span>
                                            <span class="mt-0.5 block text-[11px] leading-4 text-stone-500">{{ $item[1] }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </nav>

                            @include('livewire.admin.catalog.products.partials.readiness-summary')
                        </aside>

                        <div id="product-wizard-content" class="min-h-0 overflow-y-auto overscroll-contain"
                            x-on:product-step-changed.window="$el.scrollTo(0, 0)">
                            <div class="border-b border-stone-200 bg-[#f4f1e9] px-3 py-3 lg:hidden">
                                <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="Pasos del producto">
                                    @foreach ([1 => 'Producto', 2 => 'Contenido', 3 => 'Variantes', 4 => 'Proveedores', 5 => 'Marketing', 6 => 'Vista previa'] as $step => $label)
                                        <button type="button" wire:click="goToStep({{ $step }})"
                                            @class([
                                                'min-h-11 shrink-0 rounded-full px-4 text-xs font-semibold transition',
                                                'bg-stone-950 text-white' => $currentStep === $step,
                                                'border border-stone-300 bg-white text-stone-600' => $currentStep !== $step,
                                            ])
                                            @if ($currentStep === $step) aria-current="step" @endif>
                                            <span class="mr-1 opacity-70">{{ $step }}</span>{{ $label }}
                                        </button>
                                    @endforeach
                                </nav>
                                <div class="mt-2 h-1 overflow-hidden rounded-full bg-stone-200">
                                    <div class="h-full rounded-full bg-amber-500 transition-all duration-300" style="width: {{ ($currentStep / 6) * 100 }}%"></div>
                                </div>
                            </div>

                            <div class="mx-auto w-full max-w-4xl px-4 py-5 sm:px-6 sm:py-7">
                                @include('livewire.admin.catalog.products.partials.step-product')
                                @include('livewire.admin.catalog.products.partials.step-content')
                                @include('livewire.admin.catalog.products.partials.step-variants')
                                @include('livewire.admin.catalog.products.partials.step-suppliers')
                                @include('livewire.admin.catalog.products.partials.step-marketing')
                                @include('livewire.admin.catalog.products.partials.step-preview')
                            </div>
                        </div>
                    </div>

                    <footer class="shrink-0 border-t border-stone-200 bg-white px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <button type="button" wire:click="$set('show', false)"
                                class="inline-flex min-h-11 items-center justify-center rounded-full px-3 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950">
                                Cancelar
                            </button>

                            <div class="ml-auto flex items-center gap-2">
                                @if ($currentStep > 1)
                                    <button type="button" wire:click="goToStep({{ $currentStep - 1 }})"
                                        class="inline-flex min-h-11 items-center justify-center gap-1 rounded-full border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-700 transition hover:bg-stone-50">
                                        <flux:icon name="arrow-left" class="size-4" /> Anterior
                                    </button>
                                @endif

                                @if ($currentStep < 6)
                                    <button type="button" wire:click="goToStep({{ $currentStep + 1 }})"
                                        class="hidden min-h-11 items-center justify-center gap-1 rounded-full border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 sm:inline-flex">
                                        Siguiente <flux:icon name="arrow-right" class="size-4" />
                                    </button>
                                @endif

                                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-stone-950 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-800 disabled:cursor-wait disabled:opacity-60">
                                    <flux:icon name="check" class="size-4" />
                                    <span wire:loading.remove wire:target="save">{{ $form->id ? 'Guardar cambios' : 'Guardar producto' }}</span>
                                    <span wire:loading wire:target="save">Guardando…</span>
                                </button>
                            </div>
                        </div>
                    </footer>
                </form>
            </div>
        </div>
    @endif
</div>
