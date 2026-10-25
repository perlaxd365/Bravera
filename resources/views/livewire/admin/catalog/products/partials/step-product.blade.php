<section x-show="$wire.currentStep === 1" x-cloak class="space-y-4" role="region" aria-label="Producto">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-700">Paso 1</p>
            <h4 class="mt-1 text-xl font-bold tracking-tight text-stone-950">Datos principales</h4>
            <p class="mt-1 text-sm leading-6 text-stone-600">Empieza por lo que identifica al producto en el catálogo.</p>
        </div>
        @if (! $form->id)
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-inset ring-amber-200">Se creará oculto</span>
        @endif
    </div>

    <details class="overflow-hidden rounded-3xl border {{ $readiness['ready'] ? 'border-emerald-200' : 'border-amber-200' }} bg-white shadow-sm"
        @if (! $readiness['ready']) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-4 sm:p-5">
            <div class="flex min-w-0 items-center gap-3">
                <div class="relative flex size-12 shrink-0 items-center justify-center rounded-full"
                    style="background: conic-gradient({{ $readiness['ready'] ? '#059669' : ($readiness['percent'] >= 50 ? '#d97706' : '#dc2626') }} {{ $readiness['percent'] }}%, #e7e5e4 0)">
                    <span class="flex size-9 items-center justify-center rounded-full bg-white text-xs font-bold text-stone-900">{{ $readiness['percent'] }}%</span>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-stone-950">
                        {{ $readiness['ready'] ? 'Producto listo para publicar' : 'Qué falta para publicar' }}
                    </p>
                    <p class="mt-0.5 text-xs leading-5 text-stone-500">
                        {{ $readiness['completed'] }} de {{ $readiness['total'] }} requisitos completados
                    </p>
                </div>
            </div>
            <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
        </summary>

        <div class="border-t border-stone-100 p-4 sm:p-5">
            <div class="mb-4 h-1.5 overflow-hidden rounded-full bg-stone-100" role="progressbar"
                aria-label="Preparación del producto para publicar"
                aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $readiness['percent'] }}">
                <div @class([
                    'h-full rounded-full transition-all duration-300',
                    'bg-emerald-600' => $readiness['ready'],
                    'bg-amber-500' => ! $readiness['ready'] && $readiness['percent'] >= 50,
                    'bg-red-500' => ! $readiness['ready'] && $readiness['percent'] < 50,
                ]) style="width: {{ $readiness['percent'] }}%"></div>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($readiness['checks'] as $check)
                    <div @class([
                        'flex min-h-16 items-center gap-3 rounded-2xl border p-3',
                        'border-emerald-100 bg-emerald-50/50' => $check['complete'],
                        'border-stone-200 bg-stone-50/70' => ! $check['complete'],
                    ])>
                        <span @class([
                            'flex size-8 shrink-0 items-center justify-center rounded-full',
                            'bg-emerald-600 text-white' => $check['complete'],
                            'border border-stone-300 bg-white text-stone-400' => ! $check['complete'],
                        ])>
                            <flux:icon :name="$check['complete'] ? 'check' : 'minus'" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-semibold text-stone-900">{{ $check['label'] }}</span>
                            <span class="mt-0.5 block text-[11px] leading-4 text-stone-500">{{ $check['description'] }}</span>
                        </span>
                        @if (! $check['complete'])
                            <button type="button" wire:click="goToStep({{ $check['step'] }})"
                                class="inline-flex min-h-8 shrink-0 items-center rounded-full border border-amber-300 bg-white px-2.5 text-[10px] font-semibold text-amber-900 hover:bg-amber-50">
                                Completar
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </details>

    @if ($categories->isEmpty())
        <div class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 sm:flex-row sm:items-center sm:justify-between">
            <p>No hay categorías visibles. Necesitas una antes de terminar el registro.</p>
            <a href="{{ route('admin.catalog.categories.index') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-full bg-amber-950 px-4 text-xs font-semibold text-white">Crear categoría</a>
        </div>
    @endif

    <details class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" open>
        <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
            <span class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-xl bg-stone-100 text-stone-700"><flux:icon name="tag" class="size-4" /></span>
                <span>
                    <span class="block text-sm font-bold text-stone-950">Identidad del producto</span>
                    <span class="mt-0.5 block text-xs text-stone-500">Nombre, clasificación y dirección web.</span>
                </span>
            </span>
            <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
        </summary>
        <div class="grid gap-4 border-t border-stone-100 p-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input label="Nombre del producto" wire:model.blur="form.name" />
            </div>
            <div>
                <x-select label="Categoría" wire:model.live="form.category_id">
                    <option value="">Selecciona una categoría</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-select>
                @if ($categories->isEmpty())
                    <a href="{{ route('admin.catalog.categories.index') }}" class="mt-1 inline-flex text-xs font-semibold text-amber-800 hover:text-amber-950">Ir a categorías →</a>
                @endif
            </div>
            <div>
                <x-select label="Marca" wire:model.live="form.brand_id">
                    <option value="">Sin marca</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </x-select>
                @if ($brands->isEmpty())
                    <a href="{{ route('admin.catalog.brands.index') }}" class="mt-1 inline-flex text-xs font-semibold text-amber-800 hover:text-amber-950">Registrar una marca →</a>
                @endif
            </div>
            <div class="sm:col-span-2">
                <p class="mb-2 text-sm font-medium text-stone-800">Tipo de producto</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex min-h-20 cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 p-4 transition has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/60">
                        <input type="radio" wire:model.live="form.product_type" value="physical"
                            class="mt-0.5 size-4 border-stone-300 text-stone-950 focus:ring-stone-900/20">
                        <span>
                            <span class="block text-sm font-semibold text-stone-900">Producto físico</span>
                            <span class="mt-1 block text-xs leading-5 text-stone-500">Requiere inventario, proveedor y envío.</span>
                        </span>
                    </label>
                    <label class="flex min-h-20 cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 p-4 transition has-[:checked]:border-sky-400 has-[:checked]:bg-sky-50/60">
                        <input type="radio" wire:model.live="form.product_type" value="course"
                            class="mt-0.5 size-4 border-stone-300 text-sky-700 focus:ring-sky-600/20">
                        <span>
                            <span class="block text-sm font-semibold text-stone-900">Curso digital</span>
                            <span class="mt-1 block text-xs leading-5 text-stone-500">Se entrega mediante archivos o enlaces privados.</span>
                        </span>
                    </label>
                </div>
            </div>
            @if ($form->product_type === 'course')
                <div class="sm:col-span-2">
                    <x-input label="Precio del curso (S/)" type="number" step="0.01" min="0.01" wire:model.blur="form.course_price" />
                    <p class="mt-1.5 text-xs leading-5 text-stone-500">
                        El curso usará una variante digital automática, sin stock ni envío.
                    </p>
                    @error('form.course_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
            <div class="sm:col-span-2">
                <x-input label="Slug o dirección web" wire:model="form.slug" placeholder="ejemplo: audifonos-bluetooth" />
                <p class="mt-1.5 text-xs leading-5 text-stone-500">Se usa en el enlace público del producto. Puedes mantener el valor sugerido.</p>
            </div>
        </div>
    </details>

    <details class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" open>
        <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
            <span class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-800"><flux:icon name="document-text" class="size-4" /></span>
                <span>
                    <span class="block text-sm font-bold text-stone-950">Descripción</span>
                    <span class="mt-0.5 block text-xs text-stone-500">Una lectura rápida y el detalle completo.</span>
                </span>
            </span>
            <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
        </summary>
        <div class="space-y-4 border-t border-stone-100 p-5">
            <x-textarea label="Descripción corta" rows="2" wire:model="form.short_description" placeholder="Un resumen breve para tarjetas y resultados de búsqueda." />
            <x-textarea label="Descripción completa" rows="6" wire:model="form.description" placeholder="Materiales, medidas, contenido del paquete, cuidados y otros detalles útiles." />
        </div>
    </details>

    <details class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
        <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
            <span class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><flux:icon name="eye" class="size-4" /></span>
                <span>
                    <span class="block text-sm font-bold text-stone-950">Visibilidad y venta</span>
                    <span class="mt-0.5 block text-xs text-stone-500">Publicación, destacados y contra entrega.</span>
                </span>
            </span>
            <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
        </summary>
        <div class="space-y-3 border-t border-stone-100 p-5">
            <label class="flex min-h-16 cursor-pointer items-start justify-between gap-4 rounded-2xl border border-stone-200 bg-stone-50/70 p-4">
                <span>
                    <span class="block text-sm font-semibold text-stone-900">Producto activo</span>
                    <span class="mt-0.5 block text-xs leading-5 text-stone-500">Permite administrar la ficha y preparar su publicación.</span>
                </span>
                <input type="checkbox" wire:model="form.status" class="mt-1 size-5 shrink-0 rounded border-stone-300 text-stone-950 focus:ring-stone-900/20">
            </label>
            <label class="flex min-h-16 cursor-pointer items-start justify-between gap-4 rounded-2xl border border-stone-200 bg-stone-50/70 p-4">
                <span>
                    <span class="block text-sm font-semibold text-stone-900">Visible en la tienda</span>
                    <span class="mt-0.5 block text-xs leading-5 text-stone-500">Requiere al menos una variante activa con foto.</span>
                </span>
                <input type="checkbox" wire:model="form.is_visible" class="mt-1 size-5 shrink-0 rounded border-stone-300 text-stone-950 focus:ring-stone-900/20">
            </label>
            @error('form.is_visible') <p class="rounded-xl bg-red-50 px-3 py-2 text-xs text-red-700">{{ $message }}</p> @enderror
            <label class="flex min-h-16 cursor-pointer items-start justify-between gap-4 rounded-2xl border border-stone-200 bg-stone-50/70 p-4">
                <span>
                    <span class="block text-sm font-semibold text-stone-900">Producto destacado</span>
                    <span class="mt-0.5 block text-xs leading-5 text-stone-500">Puede ocupar lugares editoriales de la portada.</span>
                </span>
                <input type="checkbox" wire:model="form.is_featured" class="mt-1 size-5 shrink-0 rounded border-stone-300 text-stone-950 focus:ring-stone-900/20">
            </label>
            <label class="flex min-h-20 cursor-pointer items-start justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4">
                <span>
                    <span class="block text-sm font-semibold text-emerald-950">Envío contra entrega por Shalom</span>
                    <span class="mt-0.5 block text-xs leading-5 text-emerald-900/75">El cliente podrá pagar el envío al recoger. Si mezcla productos sin esta opción, el envío se cobra por adelantado.</span>
                </span>
                <input type="checkbox" wire:model="form.allows_shalom_cod" class="mt-1 size-5 shrink-0 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-600/25">
            </label>
            @error('form.allows_shalom_cod') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
        </div>
    </details>

    @if (! $form->id)
        <div class="flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
            <flux:icon name="information-circle" class="mt-0.5 size-5 shrink-0 text-sky-600" />
            <p class="leading-6">Guarda primero el producto. Después podrás agregar video, variantes, imágenes y proveedores sin cerrar este asistente.</p>
        </div>
    @endif
</section>
