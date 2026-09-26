<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.set('show', false)">
            <div class="absolute inset-0 bg-gray-950/40 backdrop-blur-sm" @click="$wire.set('show', false)"></div>

            <div class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/10">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold tracking-tight text-gray-900">
                        {{ $category ? 'Editar categoría' : 'Nueva categoría' }}
                    </h3>
                    <button wire:click="$set('show', false)" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
                        <flux:icon name="x-mark" variant="mini" class="size-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" wire:model.live="name"
                            class="{{ $errors->has('name') ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Slug</label>
                        <input type="text" wire:model.live="slug"
                            class="{{ $errors->has('slug') ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2">
                        @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Categoría padre</label>
                        <div class="relative">
                            <select wire:model="parent_id"
                                class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                <option value="">Ninguna</option>
                                @foreach ($parents as $parent)
                                    <option value="{{ $parent->id }}">
                                        {{ collect(explode('/', $parent->path))->map(fn($item) => ucfirst(str_replace('-', ' ', $item)))->implode(' > ') }}
                                    </option>
                                @endforeach
                            </select>
                            <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Imagen</label>

                        @if ($image_file)
                            <img src="{{ $image_file->temporaryUrl() }}" alt="Vista previa"
                                class="mb-3 h-24 w-24 rounded-lg border border-gray-200 object-cover">
                        @elseif ($image && !$remove_image)
                            <img src="{{ $image }}" alt="Imagen actual"
                                class="mb-3 h-24 w-24 rounded-lg border border-gray-200 object-cover">
                        @endif

                        <div class="flex flex-wrap items-center gap-2">
                            <label
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                <flux:icon name="photo" class="size-4" />
                                {{ $image_file ? 'Cambiar imagen' : ($image ? 'Reemplazar imagen' : 'Subir imagen') }}
                                <input type="file" wire:model="image_file" accept="image/*" class="sr-only">
                            </label>

                            @if ($image && !$remove_image)
                                <button type="button" wire:click="removeImage"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-red-300 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                                    <flux:icon name="trash" class="size-4" /> Quitar
                                </button>
                            @endif
                        </div>

                        <p class="mt-1.5 text-xs text-gray-400">JPG, PNG o WebP. Máximo 5 MB.</p>

                        @error('image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('image_file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Posición</label>
                        <input type="number" wire:model="position"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="is_visible"
                            class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                        Visible
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <button wire:click="$set('show',false)"
                        class="rounded-full border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button wire:click="save"
                        class="rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>