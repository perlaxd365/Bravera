<div>

    @if ($show)
        <div class="modal fade show d-block" style="background:rgba(0,0,0,.4)">

            <div class="modal-dialog modal-lg">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">

                            {{ $brand ? 'Editar marca' : 'Nueva marca' }}

                        </h5>

                        <button type="button" class="btn-close" wire:click="$set('show',false)">
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

                                Descripción

                            </label>

                            <textarea rows="4" class="form-control @error('description') is-invalid @enderror" wire:model="description"></textarea>

                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="mb-3">

                            <label class="form-label">

                                Logo

                            </label>

                            <input type="text" class="form-control @error('image') is-invalid @enderror"
                                wire:model="image" placeholder="Ruta o URL del logo">

                            @error('image')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="form-check">

                            <input class="form-check-input" type="checkbox" wire:model="is_active">

                            <label class="form-check-label">

                                Marca activa

                            </label>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button class="btn btn-secondary" wire:click="$set('show',false)">

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
