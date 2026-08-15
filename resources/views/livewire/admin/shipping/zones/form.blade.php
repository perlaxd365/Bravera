<div class="modal fade @if ($show) show d-block @endif" tabindex="-1"
    @if ($show) style="background: rgba(0,0,0,.5);" @endif>

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    {{ $form->id ? 'Editar Zona de Envío' : 'Nueva Zona de Envío' }}
                </h5>

                <button type="button" class="btn-close" wire:click="close"></button>

            </div>

            <form wire:submit="save">

                <div class="modal-body">

                    {{-- Información General --}}
                    <h6 class="fw-bold border-bottom pb-2 mb-3">
                        Información General
                    </h6>

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <x-input label="Nombre" wire:model.live="form.name" placeholder="Ej. Lima Metropolitana" />

                        </div>

                        <div class="col-md-6 mb-3">

                            <x-select label="Nivel de Zona" wire:model.live="zoneType">

                                <option value="">
                                    Seleccione un nivel
                                </option>

                                <option value="department">
                                    Departamento
                                </option>

                                <option value="province">
                                    Provincia
                                </option>

                                <option value="district">
                                    Distrito
                                </option>

                            </x-select>

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

                                <option value="">
                                    Seleccione un departamento
                                </option>

                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">
                                        {{ $department->name }}
                                    </option>
                                @endforeach

                            </x-select>

                        </div>


                        {{-- Provincia --}}
                        <div class="col-md-4 mb-3">

                            <x-select label="Provincia" wire:model.live="provinceId" :disabled="!$departmentId || $zoneType === 'department'">

                                <option value="">

                                    @if (!$departmentId)
                                        Seleccione primero un departamento
                                    @elseif ($zoneType === 'department')
                                        No aplica para departamento
                                    @else
                                        Seleccione una provincia
                                    @endif

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

                            <x-select label="Distrito" wire:model.live="districtId" :disabled="!$provinceId || $zoneType !== 'district'">

                                <option value="">

                                    @if (!$provinceId)
                                        Seleccione primero una provincia
                                    @elseif ($zoneType !== 'district')
                                        No aplica para este nivel
                                    @else
                                        Seleccione un distrito
                                    @endif

                                </option>

                                @foreach ($districts as $district)
                                    <option value="{{ $district->id }}">
                                        {{ $district->name }}
                                    </option>
                                @endforeach

                            </x-select>

                        </div>

                    </div>


                    {{-- Estado --}}
                    <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
                        Estado
                    </h6>

                    <div class="row">

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


                {{-- Footer --}}
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
