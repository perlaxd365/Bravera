<div>

    <div class="modal fade @if ($show) show d-block @endif" tabindex="-1"
        @if ($show) style="background: rgba(0,0,0,.5);" @endif>

        <div class="modal-dialog modal-xl">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">

                        {{ $form->id ? 'Editar Producto' : 'Nuevo Producto' }}

                    </h5>

                    <button type="button" class="btn-close" wire:click="$set('show', false)"></button>

                </div>

                <form wire:submit="save">

                    <div class="modal-body">

                        <div class="row">

                            <div class="col-md-8 mb-3">

                                <label class="form-label">
                                    Nombre
                                </label>

                                <input type="text" class="form-control" wire:model.blur="form.name">

                                @error('form.name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                            </div>

                            <div class="col-md-4 mb-3">

                                <x-select label="Categoría" wire:model.live="form.category_id">

                                    <option value="">Seleccione...</option>

                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach

                                </x-select>

                                @error('form.category_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4 mb-3">

                                <x-select label="Marca" wire:model.live="form.brand_id">

                                    <option value="">Seleccione...</option>

                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach

                                </x-select>

                            </div>

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Slug
                                </label>

                                <input type="text" class="form-control" wire:model="form.slug">

                            </div>

                            <div class="col-md-4">

                                <div class="form-check mt-4">

                                    <input class="form-check-input" type="checkbox" wire:model="form.status">

                                    <label class="form-check-label">

                                        Activo

                                    </label>

                                </div>

                            </div>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">

                                Descripción corta

                            </label>

                            <textarea rows="2" class="form-control" wire:model="form.short_description"></textarea>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">

                                Descripción

                            </label>

                            <textarea rows="5" class="form-control" wire:model="form.description"></textarea>

                        </div>

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-check">

                                    <input class="form-check-input" type="checkbox" wire:model="form.is_featured">

                                    <label class="form-check-label">

                                        Producto destacado

                                    </label>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="form-check">

                                    <input class="form-check-input" type="checkbox" wire:model="form.is_visible">

                                    <label class="form-check-label">

                                        Visible en tienda

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" wire:click="$set('show', false)">
                            Cancelar
                        </button>

                        <button class="btn btn-primary" type="submit">
                            Guardar
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>
