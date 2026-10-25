<section x-show="$wire.currentStep === 2" x-cloak class="space-y-4" role="region" aria-label="Contenido">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-700">Paso 2</p>
        <h4 class="mt-1 text-xl font-bold tracking-tight text-stone-950">Contenido y SEO</h4>
        <p class="mt-1 text-sm leading-6 text-stone-600">Prepara la información que ayuda a encontrar y entender el producto.</p>
    </div>

    @if ($form->product_type === 'course')
        <details class="overflow-hidden rounded-2xl border border-sky-200 bg-white shadow-sm" open>
            <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
                <span class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700"><flux:icon name="document-arrow-down" class="size-4" /></span>
                    <span>
                        <span class="block text-sm font-bold text-stone-950">Materiales del curso</span>
                        <span class="mt-0.5 block text-xs text-stone-500">Archivos privados y enlaces que recibirá el comprador.</span>
                    </span>
                </span>
                <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
            </summary>

            <div class="space-y-5 border-t border-sky-100 p-5">
                @if (! $form->id)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        Guarda primero el curso para adjuntar archivos y enlaces.
                        <button type="button" wire:click="goToStep(1)" class="ml-1 font-semibold underline">Volver al paso 1</button>
                    </div>
                @else
                    <div class="grid gap-4 rounded-2xl bg-sky-50/60 p-4" x-data="{ uploading: false, progress: 0 }"
                        x-on:livewire-upload-start.window="uploading = true; progress = 0"
                        x-on:livewire-upload-progress.window="progress = Number($event.detail.progress || 0)"
                        x-on:livewire-upload-finish.window="uploading = false; progress = 100"
                        x-on:livewire-upload-error.window="uploading = false">
                        <x-input label="Nombre visible del material" wire:model.blur="courseAssetTitle" maxlength="120" placeholder="Ej.: Guía completa en PDF" />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-stone-800">Archivo privado</label>
                                <input type="file" wire:model="courseFile" wire:loading.attr="disabled" wire:target="courseFile"
                                    class="block w-full cursor-pointer rounded-xl border border-stone-300 bg-white text-sm text-stone-700 file:mr-4 file:rounded-l-xl file:border-0 file:bg-sky-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-sky-900">
                                <div x-show="uploading" x-cloak class="mt-2">
                                    <div class="flex justify-between text-xs font-medium text-sky-800"><span>Subiendo…</span><span x-text="`${progress}%`"></span></div>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-sky-100"><div class="h-full bg-sky-600" x-bind:style="`width: ${progress}%`"></div></div>
                                </div>
                                @error('courseFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <button type="button" wire:click="saveCourseFile" wire:loading.attr="disabled" wire:target="courseFile,saveCourseFile"
                                    @disabled(! $courseFile)
                                    class="mt-2 inline-flex min-h-9 items-center rounded-full bg-sky-700 px-4 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:bg-stone-300">
                                    Agregar archivo
                                </button>
                            </div>
                            <div>
                                <x-input label="O pega un enlace" type="url" wire:model.blur="courseAssetUrl" placeholder="https://..." />
                                @error('courseAssetUrl') <p class="-mt-2 mb-2 text-xs text-red-600">{{ $message }}</p> @enderror
                                <button type="button" wire:click="saveCourseLink" wire:loading.attr="disabled" wire:target="saveCourseLink"
                                    @disabled(blank($courseAssetUrl))
                                    class="mt-2 inline-flex min-h-9 items-center rounded-full border border-sky-300 bg-white px-4 text-xs font-semibold text-sky-800 disabled:cursor-not-allowed disabled:opacity-50">
                                    Agregar enlace
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <h5 class="text-sm font-bold text-stone-900">Materiales adjuntos</h5>
                            <span class="text-xs text-stone-500">{{ $courseAssets->count() }} elemento(s)</span>
                        </div>
                        @if ($courseAssets->isEmpty())
                            <div class="rounded-2xl border border-dashed border-stone-300 p-6 text-center text-sm text-stone-500">
                                Agrega al menos un archivo o enlace antes de publicar el curso.
                            </div>
                        @else
                            <ul class="divide-y divide-stone-100 rounded-2xl border border-stone-200">
                                @foreach ($courseAssets as $asset)
                                    <li wire:key="course-asset-{{ $asset->id }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-600">
                                                <flux:icon :name="$asset->isFile() ? 'document' : 'link'" class="size-4" />
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-stone-900">{{ $asset->title }}</p>
                                                <p class="text-xs text-stone-500">
                                                    {{ $asset->isFile() ? ($asset->file_name.' · '.$asset->sizeLabel()) : $asset->url }}
                                                </p>
                                            </div>
                                        </div>
                                        <button type="button" wire:click="deleteCourseAsset({{ $asset->id }})"
                                            wire:confirm="¿Eliminar este material del curso?"
                                            class="inline-flex min-h-9 items-center gap-1 rounded-full border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50">
                                            <flux:icon name="trash" class="size-3.5" /> Eliminar
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        </details>
    @endif

    <details class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" open>
        <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
            <span class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-800"><flux:icon name="magnifying-glass" class="size-4" /></span>
                <span>
                    <span class="block text-sm font-bold text-stone-950">Vista previa para Google</span>
                    <span class="mt-0.5 block text-xs text-stone-500">Título y descripción para resultados de búsqueda.</span>
                </span>
            </span>
            <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
        </summary>
        <div class="space-y-4 border-t border-stone-100 p-5">
            <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                <p class="line-clamp-1 text-sm font-medium text-blue-800">{{ $form->seo_title ?: ($form->name ?: 'Nombre del producto') }}</p>
                <p class="mt-1 line-clamp-1 text-xs text-emerald-700">brevare.com › producto › {{ $form->slug ?: 'direccion-del-producto' }}</p>
                <p class="mt-1 line-clamp-2 text-xs leading-5 text-stone-600">{{ $form->seo_description ?: ($form->short_description ?: 'Aquí aparecerá el resumen del producto si Google decide mostrar esta página.') }}</p>
            </div>
            <x-input label="Título SEO" wire:model="form.seo_title" placeholder="Ej.: Audífonos inalámbricos | Brevare" maxlength="255" />
            <x-textarea label="Descripción para Google" rows="3" wire:model="form.seo_description" placeholder="Resume qué incluye, para quién es y qué lo hace útil." />
            <p class="text-xs leading-5 text-stone-500">Si los dejas vacíos se usan el nombre y la descripción del producto.</p>
        </div>
    </details>

    @if ($form->id)
        <details class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" open>
            <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
                <span class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700"><flux:icon name="video-camera" class="size-4" /></span>
                    <span>
                        <span class="flex items-center gap-2 text-sm font-bold text-stone-950">
                            Video del producto
                            @if ($productVideoUrl)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Cargado</span>
                            @endif
                        </span>
                        <span class="mt-0.5 block text-xs text-stone-500">Opcional. Se optimiza para cargar rápido en la ficha.</span>
                    </span>
                </span>
                <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
            </summary>
            <div class="grid gap-5 border-t border-stone-100 p-5 md:grid-cols-2">
                <div>
                    @if ($productVideoUrl)
                        <video src="{{ $productVideoUrl }}" controls preload="metadata" class="aspect-video w-full rounded-2xl bg-stone-950 object-contain" aria-label="Video actual del producto"></video>
                    @else
                        <div class="flex aspect-video items-center justify-center rounded-2xl border border-dashed border-stone-300 bg-stone-50 text-center text-sm text-stone-500">
                            <span><flux:icon name="video-camera" class="mx-auto mb-2 size-8 text-stone-400" />Todavía no hay un video</span>
                        </div>
                    @endif
                </div>
                <div class="flex flex-col justify-center">
                    <label for="product-video-upload" class="text-sm font-semibold text-stone-900">Subir o reemplazar video</label>
                    <input id="product-video-upload" type="file" wire:model="productVideo" accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm"
                        class="mt-2 block w-full cursor-pointer rounded-xl border border-stone-300 bg-white text-sm text-stone-700 file:mr-4 file:rounded-l-xl file:border-0 file:bg-amber-100 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-amber-950 hover:file:bg-amber-200">
                    <div wire:loading wire:target="productVideo" class="mt-2 text-xs font-medium text-amber-800">Preparando el archivo…</div>
                    @error('productVideo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if ($productVideo)
                        <p class="mt-2 truncate text-xs text-stone-500">Seleccionado: {{ $productVideo->getClientOriginalName() }}</p>
                    @endif
                    <p class="mt-2 text-xs leading-5 text-stone-500">MP4, MOV o WebM · hasta 12 MB. El formato final depende de la duración.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" wire:click="saveProductVideo" wire:loading.attr="disabled" wire:target="productVideo,saveProductVideo"
                            @disabled(! $productVideo)
                            class="inline-flex min-h-10 items-center gap-2 rounded-full bg-stone-950 px-4 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="saveProductVideo">Guardar video</span>
                            <span wire:loading wire:target="saveProductVideo">Subiendo…</span>
                        </button>
                        @if ($productVideoUrl)
                            <button type="button" wire:click="deleteProductVideo" wire:confirm="¿Eliminar el video de este producto?"
                                class="inline-flex min-h-10 items-center gap-2 rounded-full border border-red-200 px-4 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                <flux:icon name="trash" class="size-4" /> Eliminar
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </details>
    @else
        <div class="flex flex-col gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex items-start gap-2"><flux:icon name="lock-closed" class="mt-0.5 size-4 shrink-0" /> Guarda el producto para habilitar el video.</p>
            <button type="button" wire:click="goToStep(1)" class="inline-flex min-h-10 items-center justify-center rounded-full bg-sky-900 px-4 text-xs font-semibold text-white">Volver al paso 1</button>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700"><flux:icon name="photo" class="size-4" /></span>
                <div>
                    <p class="text-sm font-bold text-stone-950">Imágenes del producto</p>
                    <p class="mt-0.5 text-xs leading-5 text-stone-500">Las fotos se cargan por variante para que cada color o modelo tenga su propia galería.</p>
                </div>
            </div>
            <button type="button" wire:click="goToStep(3)" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-full border border-stone-300 px-4 text-xs font-semibold text-stone-800 hover:bg-stone-50">
                Ir a variantes e imágenes
            </button>
        </div>
    </div>
</section>
