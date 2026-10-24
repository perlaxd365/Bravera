<section x-show="$wire.currentStep === 3" x-cloak class="space-y-4" role="region" aria-label="Variantes">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-700">Paso 3</p>
            <h4 class="mt-1 text-xl font-bold tracking-tight text-stone-950">Variantes e imágenes</h4>
            <p class="mt-1 text-sm leading-6 text-stone-600">Cada combinación de talla, color o modelo puede tener precio, stock y galería propios.</p>
        </div>
        @if ($form->id)
            <button type="button" wire:click="createVariant"
                class="inline-flex min-h-10 items-center gap-1.5 rounded-full border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-800 transition hover:bg-amber-50">
                <flux:icon name="plus" class="size-4" /> Nueva variante
            </button>
        @endif
    </div>

    @if (! $form->id)
        <div class="rounded-3xl border border-sky-200 bg-sky-50 p-6 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-white text-sky-700 shadow-sm"><flux:icon name="lock-closed" class="size-5" /></span>
            <p class="mt-4 text-sm font-semibold text-sky-950">Guarda primero el producto</p>
            <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-sky-800/80">Después de crearlo podrás agregar variantes sin cerrar este asistente.</p>
            <button type="button" wire:click="goToStep(1)" class="mt-4 inline-flex min-h-10 items-center justify-center rounded-full bg-sky-900 px-4 text-sm font-semibold text-white">Volver a los datos del producto</button>
        </div>
    @else
        @if ($attributes->isEmpty())
            <div class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 sm:flex-row sm:items-center sm:justify-between">
                <p>No hay atributos activos. Puedes crear la variante igualmente, pero conviene registrar talla, color u otro atributo.</p>
                <a href="{{ route('admin.catalog.attributes.index') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-full bg-amber-950 px-4 text-xs font-semibold text-white">Agregar atributos</a>
            </div>
        @endif

        <details class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm"
            @if ($variantForm->id || $variants->isEmpty()) open @endif>
            <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
                <span class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-stone-100 text-stone-700"><flux:icon name="swatch" class="size-4" /></span>
                    <span>
                        <span class="block text-sm font-bold text-stone-950">{{ $variantForm->id ? 'Editar variante' : 'Agregar variante' }}</span>
                        <span class="mt-0.5 block text-xs text-stone-500">Abre solo la parte que necesitas completar.</span>
                    </span>
                </span>
                <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
            </summary>

            <div class="space-y-3 border-t border-stone-100 bg-stone-50/60 p-4 sm:p-5">
                <details class="overflow-hidden rounded-xl border border-stone-200 bg-white" open>
                    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                        <span>
                            <span class="block text-sm font-semibold text-stone-900">Código y precio</span>
                            <span class="mt-0.5 block text-xs text-stone-500">SKU, precio minorista y mayoreo.</span>
                        </span>
                        <flux:icon name="chevron-down" class="size-4 text-stone-400" />
                    </summary>
                    <div class="grid gap-4 border-t border-stone-100 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 sm:col-span-2 lg:col-span-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-emerald-950">Cálculo automático de precios</p>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Editable</span>
                            </div>
                            <p class="mt-1 text-xs leading-5 text-emerald-900/80">
                                El costo sugiere el precio normal y los demás valores se recalcularán al cambiar cualquier campo. Ejemplo: S/ 30 → S/ 100 normal → S/ 50 minorista (50%) → S/ 45 mayorista desde 3.
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                                <span class="rounded-full bg-white px-3 py-1 text-stone-700 ring-1 ring-inset ring-stone-200">Normal: S/ {{ number_format((float) $variantForm->sale_price, 2) }}</span>
                                <span class="rounded-full bg-white px-3 py-1 text-emerald-700 ring-1 ring-inset ring-emerald-200">Minorista: S/ {{ number_format((float) $variantForm->retail_price, 2) }}</span>
                                <span class="rounded-full bg-white px-3 py-1 text-sky-700 ring-1 ring-inset ring-sky-200">
                                    Mayorista: {{ $variantForm->wholesale_price !== null ? 'S/ '.number_format((float) $variantForm->wholesale_price, 2) : '—' }}
                                    desde {{ $variantForm->wholesale_min_quantity ?: 3 }}
                                </span>
                            </div>
                        </div>

                        <x-input label="SKU" wire:model.blur="variantForm.sku" />
                        <x-input label="Código de barras" wire:model.blur="variantForm.barcode" />
                        <div>
                            <x-input label="Costo de adquisición" type="number" step="0.01" min="0" wire:model.blur="variantForm.cost_price" />
                            <p class="mt-1 text-xs leading-5 text-stone-500">Base para sugerir automáticamente los precios de venta.</p>
                        </div>
                        <div>
                            <x-input label="Precio normal antes del descuento" type="number" step="0.01" min="0" wire:model.blur="variantForm.sale_price" />
                            <p class="mt-1 text-xs leading-5 text-stone-500">Puedes reemplazarlo; el precio final y el mayorista se ajustan.</p>
                        </div>
                        <div>
                            <x-input label="Descuento (%)" type="number" step="0.01" min="0" max="90" wire:model.blur="variantForm.discount_percent" />
                            <p class="mt-1 text-xs leading-5 text-stone-500">Por defecto se sugiere 50%. Puedes escribir cualquier porcentaje.</p>
                        </div>
                        <div>
                            <x-input label="Precio minorista final" type="number" step="0.01" min="0" wire:model.blur="variantForm.retail_price" />
                            <p class="mt-1 text-xs leading-5 text-stone-500">Si lo editas, ajustamos el porcentaje mostrado.</p>
                        </div>
                        <div>
                            <x-input label="Precio mayorista" type="number" step="0.01" min="0" wire:model.blur="variantForm.wholesale_price" />
                            <p class="mt-1 text-xs text-stone-500">Se mantiene un 10% debajo del minorista hasta que lo reemplaces.</p>
                        </div>
                        <div>
                            <x-input label="Cantidad mínima para mayoreo" type="number" step="1" min="2" wire:model.blur="variantForm.wholesale_min_quantity" />
                            <p class="mt-1 text-xs text-stone-500">Por defecto comienza desde 3 unidades.</p>
                        </div>
                    </div>
                </details>

                <details class="overflow-hidden rounded-xl border border-stone-200 bg-white" open>
                    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                        <span>
                            <span class="block text-sm font-semibold text-stone-900">Atributos</span>
                            <span class="mt-0.5 block text-xs text-stone-500">Talla, color, modelo y otras diferencias.</span>
                        </span>
                        <flux:icon name="chevron-down" class="size-4 text-stone-400" />
                    </summary>
                    <div class="border-t border-stone-100 p-4">
                        @if ($attributes->isNotEmpty())
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($attributes as $attribute)
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-stone-800">
                                            {{ $attribute->name }}
                                            @if ($attribute->is_required)
                                                <span class="text-red-600" aria-hidden="true">*</span>
                                            @endif
                                        </label>
                                        <div class="relative">
                                            <select wire:model.live="variantForm.attribute_values.{{ $attribute->id }}"
                                                @class([
                                                    'min-h-11 w-full appearance-none rounded-xl border bg-white px-3 py-2 pr-9 text-sm focus:outline-none focus:ring-2',
                                                    'border-stone-300 focus:border-stone-900 focus:ring-stone-900/10' => ! $errors->has("variantForm.attribute_values.{$attribute->id}"),
                                                    'border-red-300 focus:border-red-400 focus:ring-red-500/10' => $errors->has("variantForm.attribute_values.{$attribute->id}"),
                                                ])>
                                                <option value="">Selecciona…</option>
                                                @foreach ($attribute->values as $value)
                                                    <option value="{{ $value->id }}">{{ $value->value }}</option>
                                                @endforeach
                                            </select>
                                            <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                                        </div>
                                        @error("variantForm.attribute_values.{$attribute->id}")
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-950 sm:flex-row sm:items-center sm:justify-between">
                                <p>No hay atributos disponibles.</p>
                                <a href="{{ route('admin.catalog.attributes.index') }}" class="text-xs font-semibold text-amber-800 hover:text-amber-950">Crear atributos →</a>
                            </div>
                        @endif
                    </div>
                </details>

                <details class="overflow-hidden rounded-xl border border-stone-200 bg-white">
                    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                        <span>
                            <span class="block text-sm font-semibold text-stone-900">Logística y visibilidad</span>
                            <span class="mt-0.5 block text-xs text-stone-500">Peso, medidas, sincronización y estado.</span>
                        </span>
                        <flux:icon name="chevron-down" class="size-4 text-stone-400" />
                    </summary>
                    <div class="space-y-4 border-t border-stone-100 p-4">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <x-input label="Peso (kg)" type="number" step="0.01" min="0" wire:model.blur="variantForm.weight" />
                            <x-input label="Largo (cm)" type="number" step="0.01" min="0" wire:model.blur="variantForm.length" />
                            <x-input label="Ancho (cm)" type="number" step="0.01" min="0" wire:model.blur="variantForm.width" />
                            <x-input label="Alto (cm)" type="number" step="0.01" min="0" wire:model.blur="variantForm.height" />
                        </div>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <label class="flex min-h-12 cursor-pointer items-center gap-2 rounded-xl border border-stone-200 px-3 text-sm text-stone-700">
                                <input type="checkbox" wire:model="variantForm.sync_enabled" class="size-4 rounded border-stone-300 text-stone-950 focus:ring-stone-900/20">
                                Sincronizar proveedores
                            </label>
                            <label class="flex min-h-12 cursor-pointer items-center gap-2 rounded-xl border border-stone-200 px-3 text-sm text-stone-700">
                                <input type="checkbox" wire:model="variantForm.is_default" class="size-4 rounded border-stone-300 text-stone-950 focus:ring-stone-900/20">
                                Variante principal
                            </label>
                            <label class="flex min-h-12 cursor-pointer items-center gap-2 rounded-xl border border-stone-200 px-3 text-sm text-stone-700">
                                <input type="checkbox" wire:model="variantForm.is_active" class="size-4 rounded border-stone-300 text-stone-950 focus:ring-stone-900/20">
                                Variante activa
                            </label>
                        </div>
                    </div>
                </details>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-stone-200 bg-white px-4 py-3">
                <button type="button" wire:click="createVariant"
                    class="inline-flex min-h-10 items-center justify-center rounded-full border border-stone-300 px-4 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                    Limpiar
                </button>
                <button type="button" wire:click="saveVariant"
                    class="inline-flex min-h-10 items-center justify-center rounded-full bg-stone-950 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-stone-800">
                    {{ $variantForm->id ? 'Actualizar variante' : 'Guardar variante' }}
                </button>
            </div>
        </details>

        <div>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h5 class="text-sm font-bold text-stone-950">Variantes registradas</h5>
                <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-600">{{ $variants->total() }}</span>
            </div>

            @forelse ($variants as $variant)
                <article wire:key="variant-card-{{ $variant->id }}" class="mb-3 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4 p-4 sm:p-5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h6 class="truncate text-sm font-bold text-stone-950">{{ $variant->sku }}</h6>
                                @if ($variant->is_default)
                                    <x-brevare.badge color="primary">Principal</x-brevare.badge>
                                @endif
                                @if ($variant->is_active)
                                    <x-brevare.badge color="success">Activa</x-brevare.badge>
                                @else
                                    <x-brevare.badge color="neutral">Inactiva</x-brevare.badge>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-stone-500">{{ $variant->barcode ?: 'Sin código de barras' }}</p>
                            <div class="mt-3 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <span class="text-lg font-bold text-stone-950">S/ {{ number_format((float) $variant->sale_price, 2) }}</span>
                                @if ($variant->hasWholesalePrice())
                                    <span class="text-xs font-medium text-emerald-700">Mayorista S/ {{ number_format((float) $variant->wholesale_price, 2) }} desde {{ $variant->wholesale_min_quantity }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="editVariant({{ $variant->id }})"
                                class="inline-flex min-h-9 items-center gap-1 rounded-full border border-stone-300 px-3 text-xs font-semibold text-stone-700 hover:bg-stone-50">
                                <flux:icon name="pencil" class="size-3.5" /> Editar
                            </button>
                            <button type="button" wire:click="openGallery({{ $variant->id }})"
                                @class([
                                    'inline-flex min-h-9 items-center gap-1 rounded-full border px-3 text-xs font-semibold transition',
                                    'border-stone-950 bg-stone-950 text-white' => $galleryVariantId === $variant->id,
                                    'border-violet-300 text-violet-700 hover:bg-violet-50' => $galleryVariantId !== $variant->id,
                                ])>
                                <flux:icon name="photo" class="size-3.5" /> Imágenes
                            </button>
                            <button type="button" wire:click="createSupplierVariant({{ $variant->id }})"
                                class="inline-flex min-h-9 items-center gap-1 rounded-full border border-sky-300 px-3 text-xs font-semibold text-sky-700 hover:bg-sky-50">
                                <flux:icon name="truck" class="size-3.5" /> Proveedores
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 bg-stone-50/70 px-4 py-2.5">
                        <p class="text-xs text-stone-500">{{ $variant->images->count() }} {{ $variant->images->count() === 1 ? 'imagen' : 'imágenes' }}</p>
                        <div class="flex gap-2">
                            <button type="button" wire:click="toggleVariantStatus({{ $variant->id }})"
                                class="min-h-9 rounded-full px-3 text-xs font-semibold text-stone-600 hover:bg-white hover:text-stone-950">
                                {{ $variant->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                            <button type="button" wire:click="deleteVariant({{ $variant->id }})" wire:confirm="¿Deseas eliminar esta variante?"
                                class="inline-flex min-h-9 items-center gap-1 rounded-full px-3 text-xs font-semibold text-red-600 hover:bg-red-50">
                                <flux:icon name="trash" class="size-3.5" /> Eliminar
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-10 text-center">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-800"><flux:icon name="swatch" class="size-5" /></span>
                    <p class="mt-4 text-sm font-semibold text-stone-950">Todavía no hay variantes</p>
                    <p class="mt-1 text-sm text-stone-500">Completa la variante principal para poder agregar fotos y proveedores.</p>
                </div>
            @endforelse
        </div>

        @if ($galleryVariant && $galleryVariantId === $galleryVariant->id)
            <section class="overflow-hidden rounded-3xl border border-violet-200 bg-white shadow-sm" aria-label="Galería de imágenes de la variante">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-violet-100 bg-violet-50/70 px-5 py-4">
                    <div>
                        <h5 class="flex items-center gap-2 text-sm font-bold text-stone-950">
                            <flux:icon name="photo" class="size-4 text-violet-700" /> Imágenes de {{ $galleryVariant->sku }}
                        </h5>
                        <p class="mt-1 text-xs text-stone-500">Sube varias fotos y elige cuál aparece primero.</p>
                    </div>
                    <button type="button" wire:click="closeGallery"
                        class="inline-flex min-h-9 items-center justify-center rounded-full border border-violet-300 px-3 text-xs font-semibold text-violet-700 hover:bg-violet-100">
                        Cerrar galería
                    </button>
                </div>

                <div class="p-5">
                    <div class="rounded-2xl border border-dashed border-violet-300 bg-violet-50/30 p-4">
                        <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-violet-700 shadow-sm ring-1 ring-inset ring-violet-200">
                            <flux:icon name="arrow-up-tray" class="size-4" />
                            Seleccionar imágenes
                            <input type="file" wire:model="galleryImages" multiple accept="image/*" class="sr-only">
                        </label>
                        <p class="mt-2 text-xs text-stone-500">JPG, PNG o WebP · máximo 5 MB por imagen.</p>

                        @if ($galleryImages)
                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach ($galleryImages as $image)
                                        <img src="{{ $image->temporaryUrl() }}" alt="Vista previa de la imagen seleccionada" class="size-14 rounded-xl border border-stone-200 object-cover">
                                    @endforeach
                                </div>
                                <button type="button" wire:click="saveGalleryImages"
                                    class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-violet-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-violet-800">
                                    <flux:icon name="cloud-arrow-up" class="size-4" /> Subir {{ count($galleryImages) }}
                                </button>
                            </div>
                        @endif

                        @error('galleryImages.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($galleryVariant->images->isEmpty())
                        <p class="mt-4 rounded-2xl bg-stone-50 px-4 py-8 text-center text-sm text-stone-500">Esta variante todavía no tiene imágenes.</p>
                    @else
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($galleryVariant->images->sortBy('sort_order') as $image)
                                <article wire:key="variant-image-{{ $image->id }}" class="group overflow-hidden rounded-2xl border border-stone-200 bg-white">
                                    <div class="relative">
                                        <img src="{{ $image->secure_url ?? $image->url }}" alt="{{ $image->file_name }}" class="aspect-square w-full object-cover">
                                        @if ($image->is_primary)
                                            <span class="absolute left-2 top-2 rounded-full bg-stone-950 px-2 py-0.5 text-[10px] font-semibold text-white">Principal</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between gap-1 border-t border-stone-100 p-2">
                                        @if (! $image->is_primary)
                                            <button type="button" wire:click="setPrimaryImage({{ $image->id }})" class="min-h-8 px-1 text-left text-[11px] font-semibold text-violet-700 hover:text-violet-950">Hacer principal</button>
                                        @else
                                            <span class="px-1 text-[11px] text-stone-400">Portada</span>
                                        @endif
                                        <button type="button" wire:click="deleteImage({{ $image->id }})" wire:confirm="¿Deseas eliminar esta imagen?"
                                            class="flex size-8 items-center justify-center rounded-full text-red-600 hover:bg-red-50" aria-label="Eliminar imagen">
                                            <flux:icon name="trash" class="size-3.5" />
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif
    @endif
</section>
