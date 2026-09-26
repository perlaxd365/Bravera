<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.call('close')">
            <div class="absolute inset-0 bg-gray-950/40 backdrop-blur-sm" @click="$wire.call('close')"></div>

            <div class="relative w-full max-w-3xl rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/10">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold tracking-tight text-gray-900">
                        {{ $form->id ? 'Editar Zona de Envío' : 'Nueva Zona de Envío' }}
                    </h3>
                    <button wire:click="close" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
                        <flux:icon name="x-mark" variant="mini" class="size-5" />
                    </button>
                </div>

                <form wire:submit="save">
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
                        <div>
                            <h4 class="border-b border-gray-100 pb-2 text-sm font-semibold tracking-tight text-gray-900">
                                Información General
                            </h4>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <x-input label="Nombre" wire:model.live="form.name" placeholder="Ej. Lima Metropolitana" />

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

                        <div>
                            <h4 class="border-b border-gray-100 pb-2 text-sm font-semibold tracking-tight text-gray-900">
                                Ubicación
                            </h4>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
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

                        <div>
                            <h4 class="border-b border-gray-100 pb-2 text-sm font-semibold tracking-tight text-gray-900">
                                Estado
                            </h4>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
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