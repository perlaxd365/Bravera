<div>

    <div class="modal fade @if ($show) show d-block @endif" tabindex="-1"
        @if ($show) style="background: rgba(0,0,0,.5);" @endif>

        <div class="modal-dialog modal-xl">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        {{ $form->id ? 'Editar Tarifa de Envío' : 'Nueva Tarifa de Envío' }}
                    </h5>

                    <button type="button" class="btn-close" wire:click="close"></button>

                </div>

                <form wire:submit="save">

                    <div class="modal-body">

                        {{-- Configuración --}}
                        <h6 class="fw-bold border-bottom pb-2 mb-3">
                            Configuración de envío
                        </h6>

                        <div class="row">

                            <div class="col-md-6 mb-3">

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

                            </div>

                            <div class="col-md-6 mb-3">

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


                        {{-- Producto y variante --}}
                        <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
                            Producto y variante
                        </h6>

                        <div class="row">

                            <div class="col-md-6 mb-3">

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

                                <small class="text-muted">
                                    Déjelo vacío para establecer una tarifa
                                    general para el proveedor.
                                </small>

                            </div>


                            <div class="col-md-6 mb-3">

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

                                <small class="text-muted">
                                    Déjelo vacío para aplicar la tarifa a todo
                                    el producto.
                                </small>

                            </div>

                        </div>


                        {{-- Precio --}}
                        <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
                            Costo de envío
                        </h6>

                        <div class="row">

                            <div class="col-md-4 mb-3">

                                <x-input label="Precio de envío" type="number" step="0.01" min="0"
                                    wire:model.live="form.price" placeholder="0.00" />

                            </div>

                            <div class="col-md-4 mb-3">

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


                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" wire:click="close">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-primary">

                            <i class="bi bi-check-lg me-1"></i>

                            {{ $form->id ? 'Actualizar' : 'Guardar' }}

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>
