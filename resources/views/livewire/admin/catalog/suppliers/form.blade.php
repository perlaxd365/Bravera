<x-modal wire:model="show" :title="$form->id ? 'Editar proveedor' : 'Nuevo proveedor'" size="modal-xl">

    <form wire:submit="save">

        {{-- Información General --}}
        <h6 class="fw-bold border-bottom pb-2 mb-3">
            Información General
        </h6>

        <div class="row">

            <div class="col-md-8">
                <x-input label="Razón Social" wire:model.live="form.business_name"
                    placeholder="Razón social del proveedor" />
            </div>

            <div class="col-md-4">
                <x-input label="RUC" wire:model.live="form.tax_id" placeholder="2060XXXXXXXX" />
            </div>

            <div class="col-md-6">
                <x-input label="Nombre Comercial" wire:model.live="form.trade_name" />
            </div>

            <div class="col-md-6">
                <x-input label="Contacto" wire:model.live="form.contact_name" />
            </div>

        </div>

        {{-- Contacto --}}
        <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
            Información de Contacto
        </h6>

        <div class="row">

            <div class="col-md-4">
                <x-input label="Teléfono" wire:model.live="form.phone" />
            </div>

            <div class="col-md-4">
                <x-input label="WhatsApp" wire:model.live="form.whatsapp" />
            </div>

            <div class="col-md-4">
                <x-input type="email" label="Correo" wire:model.live="form.email" />
            </div>

            <div class="col-md-12">
                <x-input label="Sitio Web" wire:model.live="form.website" />
            </div>

        </div>

        {{-- Dirección --}}
        <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
            Dirección
        </h6>

        <div class="row">

            <div class="col-md-4">
                <x-input label="Departamento" wire:model.live="form.department" />
            </div>

            <div class="col-md-4">
                <x-input label="Provincia" wire:model.live="form.province" />
            </div>

            <div class="col-md-4">
                <x-input label="Distrito" wire:model.live="form.district" />
            </div>

            <div class="col-md-12">
                <x-input label="Dirección" wire:model.live="form.address" />
            </div>

        </div>

        {{-- Configuración --}}
        <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
            Configuración
        </h6>

        <div class="row">

            <div class="col-md-6">
                <x-input type="number" label="Días estimados de despacho"
                    wire:model.live="form.estimated_dispatch_days" />
            </div>

            <div class="col-md-6">
                <x-select label="Estado" wire:model.live="form.status">

                    <option value="active">Activo</option>
                    <option value="inactive">Inactivo</option>

                </x-select>
            </div>

            <div class="col-md-12">
                <x-textarea label="Observaciones" rows="4" wire:model.live="form.internal_notes" />
            </div>

        </div>

        <div class="d-flex justify-content-end mt-4">

            <button type="button" class="btn btn-secondary me-2" wire:click="$set('show', false)">

                Cancelar

            </button>

            <button type="submit" class="btn btn-primary">

                <i class="bi bi-check-lg me-1"></i>

                {{ $form->id ? 'Actualizar' : 'Guardar' }}

            </button>

        </div>

    </form>

</x-modal>
