<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.set('show', false)">
            <div class="absolute inset-0 bg-gray-950/40 backdrop-blur-sm" @click="$wire.set('show', false)"></div>

            <div class="relative flex max-h-[90vh] w-full max-w-5xl flex-col rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/10">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold tracking-tight text-gray-900">
                        {{ $form->id ? 'Editar Producto' : 'Nuevo Producto' }}
                    </h3>
                    <button wire:click="$set('show', false)" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
                        <flux:icon name="x-mark" variant="mini" class="size-5" />
                    </button>
                </div>

                <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
                    <div class="grid min-h-0 flex-1 gap-4 overflow-y-auto bg-gray-50 px-6 py-5 content-start">
                        {{-- ===================================================== --}}
                        {{-- INFORMACIÓN DEL PRODUCTO --}}
                        {{-- ===================================================== --}}

                        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-100 px-6 py-4 font-bold text-gray-900">
                                Información del producto
                            </div>

                            <div class="p-5">
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="sm:col-span-2">
                                        <x-input label="Nombre" wire:model.blur="form.name" />
                                    </div>
                                    <div>
                                        <x-select label="Categoría" wire:model.live="form.category_id">
                                            <option value="">Seleccione...</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div>
                                        <x-select label="Marca" wire:model.live="form.brand_id">
                                            <option value="">Seleccione...</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                    <div>
                                        <x-input label="Slug" wire:model="form.slug" />
                                    </div>
                                    <div class="mb-4 flex items-center gap-2 text-sm text-gray-700 sm:self-end">
                                        <input type="checkbox" wire:model="form.status"
                                            class="size-4 rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900/30">
                                        Activo
                                    </div>
                                </div>

                                <x-textarea label="Descripción corta" rows="2" wire:model="form.short_description" />

                                <x-textarea label="Descripción" rows="5" wire:model="form.description" />

                                <details class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                    <summary class="cursor-pointer text-sm font-semibold text-gray-800">Vista previa para Google (SEO)</summary>
                                    <p class="mt-2 text-xs leading-5 text-gray-500">Si los dejas vacíos, usaremos el nombre y la descripción del producto. Google decide cuándo mostrar resultados enriquecidos.</p>
                                    <div class="mt-3 space-y-3">
                                        <x-input label="Título SEO" wire:model="form.seo_title" placeholder="Ej.: Cámara Canon EOS R100 | Brevare" maxlength="255" />
                                        <x-textarea label="Descripción para Google" rows="2" wire:model="form.seo_description" placeholder="Resume qué incluye, para quién es y qué lo hace útil." />
                                    </div>
                                </details>

                                <div class="mb-4 grid gap-3 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" wire:model="form.is_featured"
                                            class="size-4 rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900/30">
                                        Producto destacado
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" wire:model="form.is_visible"
                                            class="size-4 rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900/30">
                                        Visible en tienda
                                    </label>
                                </div>
                                @error('form.is_visible') <p class="-mt-2 mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
                                @if (! $form->id)
                                    <p class="-mt-2 mb-4 text-xs leading-5 text-gray-500">El producto se crea oculto. Agrega una variante y una foto, completa su información y luego publícalo para la tienda y Google.</p>
                                @endif
                            </div>
                        </div>

                        {{-- VIDEO DEL PRODUCTO --}}
                        @if ($form->id)
                            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                                <div class="border-b border-gray-100 px-6 py-4">
                                    <h4 class="font-bold text-gray-900">Video del producto</h4>
                                    <p class="mt-1 text-sm text-gray-500">Se optimiza a 320 × 180 y baja tasa de bits al guardarlo. MP4, MOV o WebM, hasta 12 MB; el peso final también depende de la duración.</p>
                                </div>
                                <div class="grid gap-5 p-5 md:grid-cols-2">
                                    <div>
                                        @if ($productVideoUrl)
                                            <video src="{{ $productVideoUrl }}" controls preload="metadata" class="aspect-video w-full rounded-xl bg-gray-950 object-contain" aria-label="Video actual del producto"></video>
                                        @else
                                            <div class="flex aspect-video items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 text-center text-sm text-gray-500">
                                                <span><flux:icon name="video-camera" class="mx-auto mb-2 size-8 text-gray-400" />Todavía no hay un video para este producto.</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col justify-center">
                                        <label for="product-video-upload" class="text-sm font-semibold text-gray-800">Subir o reemplazar video</label>
                                        <input id="product-video-upload" type="file" wire:model="productVideo" accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm"
                                            class="mt-2 block w-full cursor-pointer rounded-xl border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:rounded-l-xl file:border-0 file:bg-amber-100 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-amber-950 hover:file:bg-amber-200">
                                        <div wire:loading wire:target="productVideo" class="mt-2 text-xs font-medium text-amber-800">Preparando el archivo…</div>
                                        @error('productVideo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                        @if ($productVideo)
                                            <p class="mt-2 truncate text-xs text-gray-500">Seleccionado: {{ $productVideo->getClientOriginalName() }}</p>
                                        @endif
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <button type="button" wire:click="saveProductVideo" wire:loading.attr="disabled" wire:target="productVideo,saveProductVideo"
                                                @disabled(! $productVideo)
                                                class="inline-flex min-h-10 items-center gap-2 rounded-full bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50">
                                                <span wire:loading.remove wire:target="saveProductVideo">Guardar video</span>
                                                <span wire:loading wire:target="saveProductVideo">Subiendo…</span>
                                            </button>
                                            @if ($productVideoUrl)
                                                <button type="button" wire:click="deleteProductVideo" wire:confirm="¿Eliminar el video de este producto?"
                                                    class="inline-flex min-h-10 items-center gap-2 rounded-full border border-red-200 px-4 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                                    <flux:icon name="trash" class="size-4" /> Eliminar video
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @else
                            <div class="flex items-center gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-800">
                                <flux:icon name="video-camera" class="size-5 shrink-0" /> Guarda el producto primero para agregar su video.
                            </div>
                        @endif

                        {{-- ===================================================== --}}
                        {{-- VARIANTES --}}
                        {{-- ===================================================== --}}

                        @if ($form->id)
                            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-6 py-4">
                                    <div>
                                        <h4 class="font-bold text-gray-900">Variantes</h4>
                                        <p class="mt-0.5 text-sm font-normal text-gray-500">Administra las variantes de este producto.</p>
                                    </div>
                                    <button type="button" wire:click="createVariant"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                        <flux:icon name="plus" class="size-4" /> Nueva variante
                                    </button>
                                </div>

                                <div class="space-y-4 p-5">
                                    {{-- ================================================= --}}
                                    {{-- FORMULARIO DE VARIANTE --}}
                                    {{-- ================================================= --}}

                                    <div class="rounded-xl border border-gray-200 bg-gray-50/50">
                                        <div class="border-b border-gray-100 px-4 py-3 font-semibold text-gray-900">
                                            {{ $variantForm->id ? 'Editar variante' : 'Nueva variante' }}
                                        </div>

                                        <div class="p-4">
                                            <div class="space-y-4">
                                                <div class="grid gap-3 sm:grid-cols-3">
                                                    <div>
                                                        <x-input label="SKU" wire:model.blur="variantForm.sku" />
                                                    </div>
                                                    <div>
                                                        <x-input label="Código de barras" wire:model.blur="variantForm.barcode" />
                                                    </div>
                                                    <div>
                                                        <x-input label="Precio antes del descuento" type="number" step="0.01" min="0" wire:model.blur="variantForm.sale_price" />
                                                    </div>
                                                </div>

                                                <div class="grid gap-3 sm:grid-cols-2">
                                                    <div>
                                                        <x-input label="Costo de adquisición" type="number" step="0.01" min="0" wire:model.blur="variantForm.cost_price" />
                                                    </div>
                                                    <div>
                                                        <x-input label="Descuento (%)" type="number" step="0.01" min="0" max="90" wire:model.blur="variantForm.discount_percent" />
                                                    </div>
                                                </div>

                                                {{-- ================================================= --}}
                                                {{-- ATRIBUTOS DE LA VARIANTE --}}
                                                {{-- ================================================= --}}

                                                @if ($attributes->isNotEmpty())
                                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                        @foreach ($attributes as $attribute)
                                                            <div>
                                                                <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                                                    {{ $attribute->name }}
                                                                    @if ($attribute->is_required)
                                                                        <span class="text-red-600">*</span>
                                                                    @endif
                                                                </label>
                                                                <div class="relative">
                                                                    <select wire:model.live="variantForm.attribute_values.{{ $attribute->id }}"
                                                                        @class([
                                                                            'w-full appearance-none rounded-lg border bg-white px-3 py-2 pr-9 text-sm focus:outline-none focus:ring-2',
                                                                            'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' => ! $errors->has("variantForm.attribute_values.{$attribute->id}"),
                                                                            'border-red-300 focus:border-red-400 focus:ring-red-500/10' => $errors->has("variantForm.attribute_values.{$attribute->id}"),
                                                                        ])>
                                                                        <option value="">Seleccione...</option>
                                                                        @foreach ($attribute->values as $value)
                                                                            <option value="{{ $value->id }}">{{ $value->value }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                                                                </div>
                                                                @error("variantForm.attribute_values.{$attribute->id}")
                                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                                @enderror
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                                    <div>
                                                        <x-input label="Peso (kg)" type="number" step="0.01" min="0" wire:model.blur="variantForm.weight" />
                                                    </div>
                                                    <div>
                                                        <x-input label="Largo (cm)" type="number" step="0.01" min="0" wire:model.blur="variantForm.length" />
                                                    </div>
                                                    <div>
                                                        <x-input label="Ancho (cm)" type="number" step="0.01" min="0" wire:model.blur="variantForm.width" />
                                                    </div>
                                                    <div>
                                                        <x-input label="Alto (cm)" type="number" step="0.01" min="0" wire:model.blur="variantForm.height" />
                                                    </div>
                                                </div>

                                                <div class="grid gap-3 sm:grid-cols-3">
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" wire:model="variantForm.sync_enabled"
                                                            class="size-4 rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900/30">
                                                        Sincronizar proveedores
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" wire:model="variantForm.is_default"
                                                            class="size-4 rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900/30">
                                                        Variante principal
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" wire:model="variantForm.is_active"
                                                            class="size-4 rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900/30">
                                                        Activa
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 px-4 py-3">
                                            <button type="button" wire:click="createVariant"
                                                class="rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                                Limpiar
                                            </button>
                                            <button type="button" wire:click="saveVariant"
                                                class="rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                                                {{ $variantForm->id ? 'Actualizar variante' : 'Guardar variante' }}
                                            </button>
                                        </div>
                                    </div>

                                    {{-- ================================================= --}}
                                    {{-- LISTADO DE VARIANTES --}}
                                    {{-- ================================================= --}}

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    <th class="px-4 py-3">SKU</th>
                                                    <th class="px-4 py-3">Código</th>
                                                    <th class="px-4 py-3 text-right">Precio</th>
                                                    <th class="px-4 py-3 text-center">Principal</th>
                                                    <th class="px-4 py-3 text-center">Estado</th>
                                                    <th class="px-4 py-3 text-right">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @forelse ($variants as $variant)
                                                    <tr class="transition hover:bg-gray-50">
                                                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $variant->sku }}</td>
                                                        <td class="px-4 py-3 text-gray-600">{{ $variant->barcode ?: '—' }}</td>
                                                        <td class="px-4 py-3 text-right text-gray-900">
                                                            S/ {{ number_format((float) $variant->sale_price, 2) }}
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            @if ($variant->is_default)
                                                                <x-brevare.badge color="primary">Principal</x-brevare.badge>
                                                            @else
                                                                <span class="text-gray-400">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            @if ($variant->is_active)
                                                                <x-brevare.badge color="success">Activa</x-brevare.badge>
                                                            @else
                                                                <x-brevare.badge color="neutral">Inactiva</x-brevare.badge>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 text-right">
                                                            <div class="inline-flex flex-wrap justify-end gap-2">
                                                                <button type="button" wire:click="editVariant({{ $variant->id }})"
                                                                    class="inline-flex items-center gap-1 rounded-full border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                                                    <flux:icon name="pencil" class="size-3.5" /> Editar
                                                                </button>
                                                                <button type="button" wire:click="createSupplierVariant({{ $variant->id }})"
                                                                    class="inline-flex items-center gap-1 rounded-full border border-sky-300 px-3 py-1.5 text-xs font-medium text-sky-700 transition hover:bg-sky-50">
                                                                    <flux:icon name="truck" class="size-3.5" /> Proveedores
                                                                </button>
                                                                <button type="button" wire:click="openGallery({{ $variant->id }})"
                                                                    @class([
                                                                        'inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-medium transition',
                                                                        'border-gray-900 bg-gray-900 text-white' => $galleryVariantId === $variant->id,
                                                                        'border-purple-300 text-purple-700 hover:bg-purple-50' => $galleryVariantId !== $variant->id,
                                                                    ])>
                                                                    <flux:icon name="photo" class="size-3.5" /> Imágenes
                                                                </button>
                                                                <button type="button" wire:click="toggleVariantStatus({{ $variant->id }})"
                                                                    class="inline-flex items-center gap-1 rounded-full border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                                                    {{ $variant->is_active ? 'Desactivar' : 'Activar' }}
                                                                </button>
                                                                <button type="button" wire:click="deleteVariant({{ $variant->id }})"
                                                                    wire:confirm="¿Deseas eliminar esta variante?"
                                                                    class="inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                                                    <flux:icon name="trash" class="size-3.5" /> Eliminar
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                                            Este producto todavía no tiene variantes.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- ================================================= --}}
                                    {{-- GALERÍA DE IMÁGENES DE LA VARIANTE --}}
                                    {{-- ================================================= --}}

                                    @if ($galleryVariant && $galleryVariantId === $galleryVariant->id)
                                        <div class="rounded-xl border border-purple-200 bg-purple-50/40">
                                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-purple-100 px-4 py-3">
                                                <div>
                                                    <h4 class="flex items-center gap-2 font-semibold text-gray-900">
                                                        <flux:icon name="photo" class="size-4 text-purple-600" />
                                                        Imágenes de la variante
                                                    </h4>
                                                    <p class="mt-0.5 text-sm text-gray-500">
                                                        {{ $galleryVariant->sku }} · Sube y administra las fotos de esta variante.
                                                    </p>
                                                </div>
                                                <button type="button" wire:click="closeGallery"
                                                    class="rounded-full border border-purple-300 px-3 py-1.5 text-xs font-medium text-purple-700 transition hover:bg-purple-100">
                                                    Cerrar galería
                                                </button>
                                            </div>

                                            <div class="p-4">
                                                {{-- Subir imágenes --}}
                                                <div class="mb-4 rounded-xl border border-dashed border-purple-300 bg-white p-4">
                                                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-purple-700">
                                                        <flux:icon name="arrow-up-tray" class="size-4" />
                                                        Seleccionar imágenes
                                                        <input type="file" wire:model="galleryImages" multiple accept="image/*" class="sr-only">
                                                    </label>

                                                    @if ($galleryImages)
                                                        <div class="mt-3 flex flex-wrap items-center gap-3">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                @foreach ($galleryImages as $image)
                                                                    <img src="{{ $image->temporaryUrl() }}"
                                                                        class="size-14 rounded-lg border border-gray-200 object-cover">
                                                                @endforeach
                                                            </div>
                                                            <button type="button" wire:click="saveGalleryImages"
                                                                class="inline-flex items-center gap-1.5 rounded-full bg-purple-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800">
                                                                <flux:icon name="cloud-arrow-up" class="size-4" />
                                                                Subir {{ count($galleryImages) }} imagen(es)
                                                            </button>
                                                        </div>
                                                    @endif

                                                    <p class="mt-1.5 text-xs text-gray-400">JPG, PNG o WebP. Máximo 5 MB por imagen.</p>

                                                    @error('galleryImages.*')
                                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                {{-- Listado de imágenes --}}
                                                @if ($galleryVariant->images->isEmpty())
                                                    <p class="rounded-lg bg-white px-4 py-8 text-center text-sm text-gray-500">
                                                        Esta variante todavía no tiene imágenes.
                                                    </p>
                                                @else
                                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                                        @foreach ($galleryVariant->images->sortBy('sort_order') as $image)
                                                            <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white">
                                                                <img src="{{ $image->secure_url ?? $image->url }}" alt="{{ $image->file_name }}"
                                                                    class="aspect-square w-full object-cover">
                                                                <div class="absolute inset-x-0 top-0 flex justify-between p-2">
                                                                    @if ($image->is_primary)
                                                                        <x-brevare.badge color="primary">Principal</x-brevare.badge>
                                                                    @endif
                                                                </div>
                                                                <div class="flex items-center justify-between gap-1 border-t border-gray-100 bg-white p-2">
                                                                    @if ($image->is_primary)
                                                                        <span class="text-xs text-gray-400">Principal</span>
                                                                    @else
                                                                        <button type="button" wire:click="setPrimaryImage({{ $image->id }})"
                                                                            class="text-xs font-medium text-purple-700 transition hover:text-purple-900">
                                                                            Hacer principal
                                                                        </button>
                                                                    @endif
                                                                    <button type="button" wire:click="deleteImage({{ $image->id }})"
                                                                        wire:confirm="¿Deseas eliminar esta imagen?"
                                                                        class="rounded-full border border-red-300 p-1.5 text-red-600 transition hover:bg-red-50"
                                                                        aria-label="Eliminar imagen">
                                                                        <flux:icon name="trash" class="size-3.5" />
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    {{-- ================================================= --}}
                                    {{-- PROVEEDORES DE LA VARIANTE --}}
                                    {{-- ================================================= --}}

                                    @if ($supplierVariantVariantId)
                                        <div class="mt-2 space-y-4 border-t border-gray-100 pt-5">
                                            <div class="flex flex-wrap items-center justify-between gap-4">
                                                <div>
                                                    <h4 class="flex items-center gap-2 font-bold text-gray-900">
                                                        <flux:icon name="truck" class="size-4 text-gray-500" />
                                                        Proveedores de la variante
                                                    </h4>
                                                    <p class="mt-0.5 text-sm text-gray-500">Administra los proveedores asociados a esta variante.</p>
                                                </div>
                                                <button type="button" wire:click="createSupplierVariant({{ $supplierVariantVariantId }})"
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                                    <flux:icon name="plus" class="size-4" /> Nuevo proveedor
                                                </button>
                                            </div>

                                            {{-- FORMULARIO --}}
                                            <div class="rounded-xl border border-gray-200 bg-gray-50/50">
                                                <div class="border-b border-gray-100 px-4 py-3 font-semibold text-gray-900">
                                                    {{ $supplierVariantForm->id ? 'Editar proveedor' : 'Nuevo proveedor' }}
                                                </div>

                                                <div class="p-4">
                                                    <div class="space-y-4">
                                                        <div class="grid gap-3 sm:grid-cols-2">
                                                            <div>
                                                                <x-select label="Proveedor" wire:model.live="supplierVariantForm.supplier_id">
                                                                    <option value="">Seleccione un proveedor...</option>
                                                                    @foreach ($suppliers as $supplier)
                                                                        <option value="{{ $supplier->id }}">
                                                                            {{ $supplier->trade_name ?: $supplier->business_name }}
                                                                        </option>
                                                                    @endforeach
                                                                </x-select>
                                                            </div>
                                                            <div>
                                                                <x-input label="SKU del proveedor" wire:model.blur="supplierVariantForm.supplier_sku" />
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <x-input label="URL del producto" type="url" placeholder="https://proveedor.com/producto"
                                                                wire:model.blur="supplierVariantForm.supplier_product_url" />
                                                        </div>

                                                        <div class="grid gap-3 sm:grid-cols-3">
                                                            <div>
                                                                <x-input label="Costo" type="number" step="0.01" min="0" wire:model.blur="supplierVariantForm.cost_price" />
                                                            </div>
                                                            <div>
                                                                <x-input label="Costo de envío" type="number" step="0.01" min="0" wire:model.blur="supplierVariantForm.shipping_cost" />
                                                            </div>
                                                            <div>
                                                                <x-input label="Precio del proveedor" type="number" step="0.01" min="0" wire:model.blur="supplierVariantForm.supplier_sale_price" />
                                                            </div>
                                                        </div>

                                                        <div class="grid gap-3 sm:grid-cols-3">
                                                            <div>
                                                                <x-input label="Stock" type="number" min="0" wire:model.blur="supplierVariantForm.stock" />
                                                            </div>
                                                            <div>
                                                                <x-input label="Stock reservado" type="number" min="0" wire:model.blur="supplierVariantForm.reserved_stock" />
                                                            </div>
                                                            <div>
                                                                <x-input label="Stock mínimo" type="number" min="0" wire:model.blur="supplierVariantForm.minimum_stock" />
                                                            </div>
                                                        </div>

                                                        <div class="grid gap-3 sm:grid-cols-3">
                                                            <div>
                                                                <x-input label="Días de despacho" type="number" min="0" wire:model.blur="supplierVariantForm.estimated_dispatch_days" />
                                                            </div>
                                                            <div class="mb-4 flex items-center gap-2 text-sm text-gray-700 sm:self-end">
                                                                <input type="checkbox" wire:model="supplierVariantForm.is_default"
                                                                    class="size-4 rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900/30">
                                                                Proveedor principal
                                                            </div>
                                                            <div class="mb-4 flex items-center gap-2 text-sm text-gray-700 sm:self-end">
                                                                <input type="checkbox" wire:model="supplierVariantForm.is_active"
                                                                    class="size-4 rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900/30">
                                                                Proveedor activo
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <x-textarea label="Notas internas" rows="3" wire:model.blur="supplierVariantForm.internal_notes" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 px-4 py-3">
                                                    <button type="button" wire:click="createSupplierVariant({{ $supplierVariantVariantId }})"
                                                        class="rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                                        Limpiar
                                                    </button>
                                                    <button type="button" wire:click="saveSupplierVariant"
                                                        class="rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                                                        {{ $supplierVariantForm->id ? 'Actualizar proveedor' : 'Guardar proveedor' }}
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- LISTADO DE PROVEEDORES --}}
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                    <thead class="bg-gray-50">
                                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                            <th class="px-4 py-3">Proveedor</th>
                                                            <th class="px-4 py-3">SKU</th>
                                                            <th class="px-4 py-3 text-right">Costo</th>
                                                            <th class="px-4 py-3 text-right">Stock</th>
                                                            <th class="px-4 py-3 text-center">Principal</th>
                                                            <th class="px-4 py-3 text-center">Estado</th>
                                                            <th class="px-4 py-3 text-right">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        @forelse ($supplierVariants as $supplierVariant)
                                                            <tr class="transition hover:bg-gray-50">
                                                                <td class="px-4 py-3 font-semibold text-gray-900">
                                                                    {{ $supplierVariant->supplier->trade_name ?: $supplierVariant->supplier->business_name }}
                                                                </td>
                                                                <td class="px-4 py-3 text-gray-600">{{ $supplierVariant->supplier_sku ?: '—' }}</td>
                                                                <td class="px-4 py-3 text-right text-gray-900">
                                                                    S/ {{ number_format((float) $supplierVariant->cost_price, 2) }}
                                                                </td>
                                                                <td class="px-4 py-3 text-right text-gray-900">{{ $supplierVariant->stock }}</td>
                                                                <td class="px-4 py-3 text-center">
                                                                    @if ($supplierVariant->is_default)
                                                                        <x-brevare.badge color="primary">Principal</x-brevare.badge>
                                                                    @else
                                                                        <span class="text-gray-400">—</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 text-center">
                                                                    @if ($supplierVariant->is_active)
                                                                        <x-brevare.badge color="success">Activo</x-brevare.badge>
                                                                    @else
                                                                        <x-brevare.badge color="neutral">Inactivo</x-brevare.badge>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 text-right">
                                                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                                                        <button type="button" wire:click="editSupplierVariant({{ $supplierVariant->id }})"
                                                                            class="inline-flex items-center gap-1 rounded-full border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                                                            <flux:icon name="pencil" class="size-3.5" /> Editar
                                                                        </button>
                                                                        <button type="button" wire:click="toggleSupplierVariantStatus({{ $supplierVariant->id }})"
                                                                            class="inline-flex items-center gap-1 rounded-full border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                                                            {{ $supplierVariant->is_active ? 'Desactivar' : 'Activar' }}
                                                                        </button>
                                                                        <button type="button" wire:click="deleteSupplierVariant({{ $supplierVariant->id }})"
                                                                            wire:confirm="¿Deseas eliminar este proveedor de la variante?"
                                                                            class="rounded-full border border-red-300 p-2 text-red-600 transition hover:bg-red-50"
                                                                            aria-label="Eliminar">
                                                                            <flux:icon name="trash" class="size-4" />
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                                                    Esta variante todavía no tiene proveedores.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4">
                                <flux:icon name="information-circle" class="mt-0.5 size-5 text-sky-600" />
                                <p class="text-sm text-sky-800">Guarda primero el producto para poder agregar sus variantes.</p>
                            </div>
                        @endif
                    </div>

                    {{-- ========================================================= --}}
                    {{-- FOOTER PRODUCTO --}}
                    {{-- ========================================================= --}}

                    <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button type="button" wire:click="$set('show', false)"
                            class="rounded-full border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                            Cancelar
                        </button>
                        <button class="rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800" type="submit">
                            Guardar producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
