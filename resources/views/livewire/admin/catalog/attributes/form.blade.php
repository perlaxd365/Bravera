<div>

    @if ($show)
        <div class="modal fade show d-block" style="background:rgba(0,0,0,.4)">

            <div class="modal-dialog modal-lg">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">

                            {{ $attribute ? 'Editar atributo' : 'Nuevo atributo' }}

                        </h5>

                        <button type="button" class="btn-close" wire:click="$set('show', false)">
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="row">

                            <div class="col-md-8">

                                <div class="mb-3">

                                    <label class="form-label">
                                        Nombre
                                    </label>

                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        wire:model.live="name">

                                    @error('name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="mb-3">

                                    <label class="form-label">
                                        Orden
                                    </label>

                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                        wire:model="sort_order">

                                    @error('sort_order')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>

                            </div>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">

                                Slug

                            </label>

                            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                wire:model.live="slug">

                            @error('slug')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="mb-3">

                            <label class="form-label">

                                Tipo

                            </label>

                            <select class="form-select @error('type') is-invalid @enderror" wire:model="type">

                                <option value="select">Lista desplegable</option>
                                <option value="color">Color</option>
                                <option value="text">Texto</option>
                                <option value="number">Número</option>
                                <option value="boolean">Sí / No</option>

                            </select>

                            @error('type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="row">

                            <div class="col-md-4">

                                <div class="form-check">

                                    <input class="form-check-input" type="checkbox" wire:model="is_filter">

                                    <label class="form-check-label">

                                        Mostrar como filtro

                                    </label>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-check">

                                    <input class="form-check-input" type="checkbox" wire:model="is_required">

                                    <label class="form-check-label">

                                        Obligatorio

                                    </label>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-check">

                                    <input class="form-check-input" type="checkbox" wire:model="is_active">

                                    <label class="form-check-label">

                                        Activo

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button class="btn btn-secondary" wire:click="$set('show', false)">

                            Cancelar

                        </button>

                        <button class="btn btn-primary" wire:click="save">

                            Guardar

                        </button>

                    </div>

                </div>

            </div>

        </div>
    @endif

</div>
