<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.set('show', false)">
            <div class="absolute inset-0 bg-gray-950/40 backdrop-blur-sm" @click="$wire.set('show', false)"></div>

            <div class="relative w-full max-w-2xl rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/10">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold tracking-tight text-gray-900">
                        {{ $attribute ? 'Editar atributo' : 'Nuevo atributo' }}
                    </h3>
                    <button wire:click="$set('show', false)" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
                        <flux:icon name="x-mark" variant="mini" class="size-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" wire:model.live="name"
                                class="{{ $errors->has('name') ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2">
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Orden</label>
                            <input type="number" wire:model="sort_order"
                                class="{{ $errors->has('sort_order') ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2">
                            @error('sort_order') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Slug</label>
                        <input type="text" wire:model.live="slug"
                            class="{{ $errors->has('slug') ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2">
                        @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Tipo</label>
                        <div class="relative">
                            <select wire:model="type"
                                class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                <option value="select">Lista desplegable</option>
                                <option value="color">Color</option>
                                <option value="text">Texto</option>
                                <option value="number">Número</option>
                                <option value="boolean">Sí / No</option>
                            </select>
                            <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        </div>
                        @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="is_filter"
                                class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            Mostrar como filtro
                        </label>

                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="is_required"
                                class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            Obligatorio
                        </label>

                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="is_active"
                                class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            Activo
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <button wire:click="$set('show', false)"
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