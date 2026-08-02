<x-modal wire:model="show" :title="$form->id ? 'Editar Producto' : 'Nuevo Producto'" size="modal-xl">

    <form wire:submit="save">

        {{-- Información General --}}
        <h6 class="fw-bold border-bottom pb-2 mb-3">
            Información General
        </h6>

        <div class="row">

            <div class="col-md-8">

                <x-input label="Nombre del Producto" wire:model.live="form.name" wire:blur="form.generateSlug"
                    placeholder="Ingrese el nombre del producto" />

            </div>

            <div class="col-md-4">

                <x-input label="Slug" wire:model.live="form.slug" placeholder="slug-del-producto" />

            </div>

            <div class="col-md-6">

                <x-select label="Categoría" wire:model.live="form.category_id">

                    <option value="">
                        Seleccione una categoría
                    </option>

                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">

                            {{ $category->name }}

                        </option>
                    @endforeach

                </x-select>

            </div>

            <div class="col-md-6">

                <x-select label="Marca" wire:model.live="form.brand_id">

                    <option value="">
                        Sin marca
                    </option>

                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">

                            {{ $brand->name }}

                        </option>
                    @endforeach

                </x-select>

            </div>

        </div>

        {{-- Descripción --}}
        <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
            Descripción
        </h6>

        <div class="row">

            <div class="col-md-12">

                <x-textarea label="Descripción corta" rows="2" wire:model.live="form.short_description" />

            </div>

            <div class="col-md-12">

                <x-textarea label="Descripción" rows="5" wire:model.live="form.description" />

            </div>

        </div>

        {{-- SEO --}}
        <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
            SEO
        </h6>

        <div class="row">

            <div class="col-md-12">

                <x-input label="Título SEO" wire:model.live="form.seo_title" />

            </div>

            <div class="col-md-12">

                <x-textarea label="Descripción SEO" rows="3" wire:model.live="form.seo_description" />

            </div>

        </div>

        {{-- Configuración --}}
        <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">
            Configuración
        </h6>

        <div class="row">

            <div class="col-md-4">

                <div class="form-check mt-4">

                    <input class="form-check-input" type="checkbox" wire:model.live="form.status">

                    <label class="form-check-label">

                        Producto activo

                    </label>

                </div>

            </div>

            <div class="col-md-4">

                <div class="form-check mt-4">

                    <input class="form-check-input" type="checkbox" wire:model.live="form.is_visible">

                    <label class="form-check-label">

                        Visible en tienda

                    </label>

                </div>

            </div>

            <div class="col-md-4">

                <div class="form-check mt-4">

                    <input class="form-check-input" type="checkbox" wire:model.live="form.is_featured">

                    <label class="form-check-label">

                        Producto destacado

                    </label>

                </div>

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
