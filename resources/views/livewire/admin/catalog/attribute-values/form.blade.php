<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.set('show', false)">
            <div class="absolute inset-0 bg-gray-950/40 backdrop-blur-sm" @click="$wire.set('show', false)"></div>

            <div class="relative w-full max-w-2xl rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/10">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold tracking-tight text-gray-900">
                        {{ $attributeValue ? 'Editar Valor' : 'Nuevo Valor' }}
                    </h3>
                    <button wire:click="$set('show', false)" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
                        <flux:icon name="x-mark" variant="mini" class="size-5" />
                    </button>
                </div>

                <form wire:submit="save">
                    <div class="px-6 py-5">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Atributo</label>
                                <div class="relative">
                                    <select wire:model="attribute_id"
                                        class="{{ $errors->has('attribute_id') ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} w-full appearance-none rounded-lg border bg-white px-3 py-2 pr-9 text-sm focus:outline-none focus:ring-2">
                                        <option value="">Seleccione...</option>
                                        @foreach ($attributes as $attribute)
                                            <option value="{{ $attribute->id }}">
                                                {{ $attribute->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                                </div>
                                @error('attribute_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Valor</label>
                                <input type="text" wire:model.live="value"
                                    class="{{ $errors->has('value') ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2">
                                @error('value') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Slug</label>
                                <input type="text" wire:model="slug"
                                    class="{{ $errors->has('slug') ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2">
                                @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Color</label>
                                <input type="color" wire:model="color"
                                    class="h-10 w-full cursor-pointer rounded-lg border border-gray-300 bg-white p-1 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Orden</label>
                                <input type="number" min="0" wire:model="sort_order"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            </div>

                            <div class="flex items-end pb-2">
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="is_active"
                                        class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                                    Activo
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button type="button" wire:click="$set('show', false)"
                            class="rounded-full border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                            <flux:icon name="check" class="size-4" /> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>