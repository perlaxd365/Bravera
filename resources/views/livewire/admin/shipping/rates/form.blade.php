<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.call('close')">
            <div class="absolute inset-0 bg-gray-950/40 backdrop-blur-sm" @click="$wire.call('close')"></div>

            <div class="relative w-full max-w-3xl rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/10">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold tracking-tight text-gray-900">
                        {{ $form->id ? 'Editar Tarifa de Envío' : 'Nueva Tarifa de Envío' }}
                    </h3>
                    <button wire:click="close" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
                        <flux:icon name="x-mark" variant="mini" class="size-5" />
                    </button>
                </div>

                <form wire:submit="save">
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
                        <div>
                            <h4 class="border-b border-gray-100 pb-2 text-sm font-semibold tracking-tight text-gray-900">
                                Configuración de envío
                            </h4>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <x-select label="Zona de envío" wire:model.live="form.shipping_zone_id">
                                    <option value="">
                                        Seleccione una zona
                                    </option>

                                    @foreach ($shippingZones as $zone)
                                        <option value="{{ $zone->id }}">
                                            {{ $zone->name }}
                                            -
                                            {{ $zone->location?->name ?? '' }}
                                        </option>
                                    @endforeach
                                </x-select>

                                <x-select label="Proveedor" wire:model.live="form.supplier_id">
                                    <option value="">
                                        Seleccione un proveedor
                                    </option>

                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">
                                            {{ $supplier->business_name }}
                                        </option>
                                    @endforeach
                                </x-select>
                            </div>
                        </div>

                        <div>
                            <h4 class="border-b border-gray-100 pb-2 text-sm font-semibold tracking-tight text-gray-900">
                                Producto y variante
                            </h4>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <x-select label="Producto" wire:model.live="form.product_id" :disabled="!$form->supplier_id">
                                        <option value="">
                                            @if (!$form->supplier_id)
                                                Seleccione primero un proveedor
                                            @else
                                                Tarifa general del proveedor
                                            @endif
                                        </option>

                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">
                                                {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </x-select>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Déjelo vacío para establecer una tarifa general para el proveedor.
                                    </p>
                                </div>

                                <div>
                                    <x-select label="Variante" wire:model.live="form.product_variant_id" :disabled="!$form->product_id">
                                        <option value="">
                                            Tarifa para todo el producto
                                        </option>

                                        @foreach ($variants as $variant)
                                            <option value="{{ $variant->id }}">
                                                {{ $variant->sku }}
                                            </option>
                                        @endforeach
                                    </x-select>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Déjelo vacío para aplicar la tarifa a todo el producto.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="border-b border-gray-100 pb-2 text-sm font-semibold tracking-tight text-gray-900">
                                Costo de envío
                            </h4>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <x-input label="Precio de envío" type="number" step="0.01" min="0"
                                    wire:model.live="form.price" placeholder="0.00" />

                                <x-select label="Estado" wire:model.live="form.status">
                                    <option value="1">
                                        Activo
                                    </option>

                                    <option value="0">
                                        Inactivo
                                    </option>
                                </x-select>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button type="button" wire:click="close"
                            class="rounded-full border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                            {{ $form->id ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>