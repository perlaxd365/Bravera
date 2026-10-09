<div class="mx-auto max-w-7xl space-y-7">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Tienda online</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">Contenido de la portada</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600">Edita campañas, textos e imágenes que ven tus clientes en el inicio de Brevare.</p>
        </div>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save,slideUploads,categoryUploads"
            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-gray-950 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-800 disabled:cursor-wait disabled:opacity-60">
            <span wire:loading.remove wire:target="save">Guardar cambios</span>
            <span wire:loading wire:target="save,slideUploads,categoryUploads">Guardando imágenes…</span>
        </button>
    </header>

    @if (session()->has('status'))
        <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-gray-950">Portadas y banners</h2>
                <p class="mt-1 text-sm text-gray-500">Las campañas rotan automáticamente cada cinco segundos. Cambia sus textos, imágenes y enlaces desde aquí.</p>
            </div>
            <button type="button" wire:click="addSlide" class="inline-flex min-h-10 items-center gap-2 rounded-full border border-gray-300 px-4 text-sm font-semibold text-gray-800 transition hover:bg-gray-50">
                <flux:icon name="plus" class="size-4" /> Agregar portada
            </button>
        </div>

        <div class="space-y-5">
            @foreach ($slides as $index => $slide)
                @php
                    $slidePreview = isset($slideUploads[$index]) ? $slideUploads[$index]->temporaryUrl() : ($slide['image'] ?? null);
                @endphp
                <article wire:key="homepage-slide-{{ $index }}" class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 sm:px-5">
                        <div class="flex items-center gap-3">
                            <span class="flex size-8 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-900">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">{{ $slide['eyebrow'] ?: 'Nueva portada' }}</h3>
                                <p class="text-xs text-gray-500">{{ $slide['title'] ?? '' }} {{ $slide['highlight'] ?? '' }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="removeSlide({{ $index }})" class="inline-flex size-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-red-50 hover:text-red-700" aria-label="Eliminar portada">
                            <flux:icon name="trash" class="size-4" />
                        </button>
                    </div>

                    <div class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_250px]">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-gray-700">Texto pequeño
                                <input type="text" wire:model="slides.{{ $index }}.eyebrow" maxlength="80" class="mt-1.5 w-full rounded-xl border-gray-300 bg-white text-sm focus:border-amber-500 focus:ring-amber-500">
                                @error("slides.$index.eyebrow") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-sm font-medium text-gray-700">Título
                                <input type="text" wire:model="slides.{{ $index }}.title" maxlength="120" class="mt-1.5 w-full rounded-xl border-gray-300 bg-white text-sm focus:border-amber-500 focus:ring-amber-500">
                                @error("slides.$index.title") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-sm font-medium text-gray-700">Texto destacado
                                <input type="text" wire:model="slides.{{ $index }}.highlight" maxlength="120" class="mt-1.5 w-full rounded-xl border-gray-300 bg-white text-sm focus:border-amber-500 focus:ring-amber-500">
                            </label>
                            <label class="block text-sm font-medium text-gray-700">Texto del botón
                                <input type="text" wire:model="slides.{{ $index }}.cta" maxlength="60" class="mt-1.5 w-full rounded-xl border-gray-300 bg-white text-sm focus:border-amber-500 focus:ring-amber-500">
                            </label>
                            <label class="block text-sm font-medium text-gray-700 sm:col-span-2">Descripción
                                <textarea wire:model="slides.{{ $index }}.subtitle" rows="2" maxlength="240" class="mt-1.5 w-full rounded-xl border-gray-300 bg-white text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                            </label>
                            <label class="block text-sm font-medium text-gray-700 sm:col-span-2">Enlace del botón
                                <input type="text" wire:model="slides.{{ $index }}.url" placeholder="/buscar o https://..." class="mt-1.5 w-full rounded-xl border-gray-300 bg-white text-sm focus:border-amber-500 focus:ring-amber-500">
                                @error("slides.$index.url") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div>
                            <p class="mb-1.5 text-sm font-medium text-gray-700">Imagen de portada</p>
                            @if ($slidePreview)
                                <img src="{{ $slidePreview }}" alt="Vista previa de {{ $slide['eyebrow'] ?? 'portada' }}" class="aspect-[4/3] w-full rounded-xl bg-gray-200 object-cover">
                            @else
                                <div class="flex aspect-[4/3] items-center justify-center rounded-xl bg-gray-200 text-sm text-gray-500">Selecciona una imagen</div>
                            @endif
                            <input type="file" accept="image/*" wire:model="slideUploads.{{ $index }}" class="mt-3 block w-full text-xs text-gray-600 file:mr-3 file:rounded-full file:border-0 file:bg-amber-100 file:px-3 file:py-2 file:font-semibold file:text-amber-950 hover:file:bg-amber-200">
                            <p class="mt-2 text-xs text-gray-500">JPG, PNG o WebP · máximo 5 MB</p>
                            @error("slideUploads.$index") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-gray-950">Categorías principales e imágenes</h2>
            <p class="mt-1 text-sm text-gray-500">Actualiza las fotos de las categorías grandes de inicio y de las demás categorías visibles.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($categories as $category)
                @php
                    $categoryPreview = isset($categoryUploads[$category->id]) ? $categoryUploads[$category->id]->temporaryUrl() : $category->image;
                @endphp
                <label wire:key="homepage-category-{{ $category->id }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    @if ($categoryPreview)
                        <img src="{{ $categoryPreview }}" alt="{{ $category->name }}" class="aspect-[4/3] w-full bg-gray-100 object-cover transition duration-500 group-hover:scale-[1.02]">
                    @else
                        <div class="flex aspect-[4/3] items-center justify-center bg-amber-50 text-sm text-gray-500">Sin imagen</div>
                    @endif
                    <span class="block p-3">
                        <span class="block text-sm font-semibold text-gray-900">{{ $category->name }}</span>
                        <input type="file" accept="image/*" wire:model="categoryUploads.{{ $category->id }}" class="mt-2 block w-full text-xs text-gray-600 file:mr-2 file:rounded-full file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:font-semibold file:text-gray-700 hover:file:bg-amber-100">
                        @error("categoryUploads.$category->id") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </span>
                </label>
            @endforeach
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold text-gray-950">Textos de secciones</h2>
            <p class="mt-1 text-sm text-gray-500">Edita los títulos que acompañan la exploración de productos.</p>
            <div class="mt-5 space-y-4">
                @foreach (['primary_categories' => 'Categorías principales', 'categories' => 'Otras categorías', 'featured' => 'Productos destacados', 'latest' => 'Novedades'] as $key => $label)
                    <label class="block text-sm font-medium text-gray-700">{{ $label }}
                        <input type="text" wire:model="texts.{{ $key }}" maxlength="80" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @error("texts.$key") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-950">Frases de productos</h2>
                    <p class="mt-1 text-sm text-gray-500">Se muestran junto al producto destacado que cambia cada tres segundos.</p>
                </div>
                <button type="button" wire:click="addPhrase" class="inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-gray-300 text-gray-700 hover:bg-gray-50" aria-label="Agregar frase"><flux:icon name="plus" class="size-4" /></button>
            </div>
            <div class="mt-5 space-y-3">
                @foreach ($phrases as $index => $phrase)
                    <div wire:key="homepage-phrase-{{ $index }}" class="flex items-center gap-2">
                        <input type="text" wire:model="phrases.{{ $index }}" maxlength="100" class="w-full rounded-xl border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        <button type="button" wire:click="removePhrase({{ $index }})" aria-label="Eliminar frase" class="inline-flex size-9 shrink-0 items-center justify-center rounded-full text-gray-500 hover:bg-red-50 hover:text-red-700"><flux:icon name="x-mark" class="size-4" /></button>
                    </div>
                @endforeach
                @error('phrases') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>
    </section>

    <div class="flex justify-end pb-5">
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save,slideUploads,categoryUploads"
            class="inline-flex min-h-11 items-center justify-center rounded-full bg-gray-950 px-6 text-sm font-semibold text-white transition hover:bg-amber-800 disabled:cursor-wait disabled:opacity-60">
            <span wire:loading.remove wire:target="save">Guardar cambios</span>
            <span wire:loading wire:target="save,slideUploads,categoryUploads">Guardando…</span>
        </button>
    </div>
</div>
