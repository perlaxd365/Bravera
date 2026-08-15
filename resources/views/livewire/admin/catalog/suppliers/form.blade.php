<div class="modal fade @if ($show) show d-block @endif" tabindex="-1"
    @if ($show) style="background: rgba(0,0,0,.5);" @endif>
    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    {{ $form->id ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
                </h5>

                <button type="button" class="btn-close" wire:click="$set('show', false)"></button>

            </div>

            <form wire:submit="save">

                <div class="modal-body">

                    {{-- Información General --}}
                    <h6 class="fw-bold border-bottom pb-2 mb-3">
                        Información General
                    </h6>

                    <div class="row">

                        <div class="col-md-3 mb-3">

                            <x-input label="Código" wire:model.live="form.code" placeholder="SUP000001" />

                        </div>

                        <div class="col-md-5 mb-3">

                            <x-input label="Razón Social" wire:model.live="form.business_name"
                                placeholder="Ingrese la razón social" />

                        </div>

                        <div class="col-md-4 mb-3">

                            <x-input label="Nombre Comercial" wire:model.live="form.trade_name"
                                placeholder="Ingrese el nombre comercial" />

                        </div>

                    </div>


                    {{-- Información Fiscal y Contacto --}}
                    <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
                        Información Fiscal y Contacto
                    </h6>

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <x-input label="RUC" wire:model.live="form.tax_id" placeholder="20601234567" />

                        </div>

                        <div class="col-md-4 mb-3">

                            <x-input label="Persona de Contacto" wire:model.live="form.contact_name"
                                placeholder="Nombre del contacto" />

                        </div>

                        <div class="col-md-4 mb-3">

                            <x-input label="Correo Electrónico" type="email" wire:model.live="form.email"
                                placeholder="correo@proveedor.com" />

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <x-input label="Teléfono" wire:model.live="form.phone" placeholder="999999999" />

                        </div>

                        <div class="col-md-4 mb-3">

                            <x-input label="WhatsApp" wire:model.live="form.whatsapp" placeholder="999999999" />

                        </div>

                        <div class="col-md-4 mb-3">

                            <x-input label="Sitio Web" type="url" wire:model.live="form.website"
                                placeholder="https://proveedor.com" />

                        </div>

                    </div>


                    {{-- Ubicación --}}
                    <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
                        Ubicación
                    </h6>

                    <div class="row">

                        {{-- Departamento --}}
                        <div class="col-md-4 mb-3">

                            <x-select label="Departamento" wire:model.live="departmentId">
                                <option value="">Seleccione un departamento</option>

                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </x-select>

                        </div>

                        {{-- Provincia --}}
                        <div class="col-md-4 mb-3">

                            <x-select label="Provincia" wire:model.live="provinceId" :disabled="!$departmentId">
                                <option value="">
                                    {{ $departmentId ? 'Seleccione una provincia' : 'Seleccione primero un departamento' }}
                                </option>

                                @foreach ($provinces as $province)
                                    <option value="{{ $province->id }}">
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </x-select>

                        </div>

                        {{-- Distrito --}}
                        <div class="col-md-4 mb-3">

                            <x-select label="Distrito" wire:model.live="districtId" :disabled="!$provinceId">
                                <option value="">
                                    {{ $provinceId ? 'Seleccione un distrito' : 'Seleccione primero una provincia' }}
                                </option>

                                @foreach ($districts as $district)
                                    <option value="{{ $district->id }}">
                                        {{ $district->name }}
                                    </option>
                                @endforeach
                            </x-select>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-12 mb-3">

                            <x-input label="Dirección" wire:model.live="form.address"
                                placeholder="Ingrese la dirección" />

                        </div>

                    </div>


                    {{-- Operación --}}
                    <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
                        Operación
                    </h6>

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <x-input label="Días Estimados de Despacho" type="number" min="0" max="255"
                                wire:model.live="form.estimated_dispatch_days" />

                        </div>

                        <div class="col-md-4 mb-3">

                            <x-select label="Estado" wire:model.live="form.status">

                                <option value="active">
                                    Activo
                                </option>

                                <option value="inactive">
                                    Inactivo
                                </option>

                            </x-select>

                        </div>

                    </div>


                    {{-- Observaciones --}}
                    <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
                        Observaciones
                    </h6>

                    <div class="row">

                        <div class="col-md-12 mb-3">

                            <x-textarea label="Observaciones Internas" rows="4"
                                wire:model.live="form.internal_notes"
                                placeholder="Ingrese observaciones internas del proveedor..." />

                        </div>

                    </div>

                </div>


                {{-- Footer --}}
                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" wire:click="$set('show', false)">
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
