<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.set('show', false)">
            <div class="absolute inset-0 bg-gray-950/40 backdrop-blur-sm" @click="$wire.set('show', false)"></div>

            <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto overscroll-contain rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/10">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold tracking-tight text-gray-900">
                        {{ $form->id ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
                    </h3>
                    <button wire:click="$set('show', false)" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
                        <flux:icon name="x-mark" variant="mini" class="size-5" />
                    </button>
                </div>

                <form wire:submit="save">
                    <div class="space-y-5 px-6 py-5">
                        <div>
                            <h6 class="mb-4 border-b border-gray-100 pb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Información General
                            </h6>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <x-input label="Código" value="{{ $form->code ?? 'Se genera automáticamente' }}" disabled />

                                <x-input label="Razón Social" wire:model.live="form.business_name"
                                    placeholder="Ingrese la razón social" />

                                <x-input label="Nombre Comercial" wire:model.live="form.trade_name"
                                    placeholder="Ingrese el nombre comercial" />
                            </div>
                        </div>

                        <div>
                            <h6 class="mb-4 border-b border-gray-100 pb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Información Fiscal y Contacto
                            </h6>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <x-input label="RUC" wire:model.live="form.tax_id" placeholder="20601234567" />

                                <x-input label="Persona de Contacto" wire:model.live="form.contact_name"
                                    placeholder="Nombre del contacto" />

                                <x-input label="Correo Electrónico" type="email" wire:model.live="form.email"
                                    placeholder="correo@proveedor.com" />
                            </div>

                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                <x-input label="Teléfono" wire:model.live="form.phone" placeholder="999999999" />

                                <x-input label="WhatsApp" wire:model.live="form.whatsapp" placeholder="999999999" />

                                <x-input label="Sitio Web" type="url" wire:model.live="form.website"
                                    placeholder="https://proveedor.com" />
                            </div>
                        </div>

                        <div>
                            <h6 class="mb-4 border-b border-gray-100 pb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Ubicación
                            </h6>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <x-select label="Departamento" wire:model.live="departmentId">
                                    <option value="">Seleccione un departamento</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </x-select>

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

                            <div class="mt-3">
                                <x-input label="Dirección" wire:model.live="form.address"
                                    placeholder="Ingrese la dirección" />
                            </div>
                        </div>

                        <div>
                            <h6 class="mb-4 border-b border-gray-100 pb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Operación
                            </h6>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-input label="Días Estimados de Despacho" type="number" min="0" max="255"
                                    wire:model.live="form.estimated_dispatch_days" />

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

                        <div>
                            <h6 class="mb-4 border-b border-gray-100 pb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Observaciones
                            </h6>

                            <x-textarea label="Observaciones Internas" rows="4"
                                wire:model.live="form.internal_notes"
                                placeholder="Ingrese observaciones internas del proveedor..." />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button type="button" wire:click="$set('show', false)"
                            class="rounded-full border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                            <flux:icon name="check" class="size-4" /> {{ $form->id ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
