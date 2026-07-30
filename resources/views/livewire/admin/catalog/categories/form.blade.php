<div>

    @if ($show)

        <div class="modal fade show d-block" style="background:rgba(0,0,0,.4)">

            <div class="modal-dialog">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">

                            {{ $category ? 'Editar categoría' : 'Nueva categoría' }}

                        </h5>

                        <button class="btn-close" wire:click="$set('show',false)">
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="mb-3">

                            <label>Nombre</label>

                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                wire:model.live="name">

                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="mb-3">

                            <label>Slug</label>

                            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                wire:model.live="slug">

                            @error('slug')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="mb-3">

                            <label>Categoría padre</label>

                            <select class="form-select" wire:model="parent_id">

                                <option value="">Ninguna</option>

                                @foreach ($parents as $parent)
                                    <option value="{{ $parent->id }}">
                                        {{ $parent->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>

                        <div class="mb-3">

                            <label>Posición</label>

                            <input type="number" class="form-control" wire:model="position">

                        </div>

                        <div class="form-check">

                            <input class="form-check-input" type="checkbox" wire:model="is_visible">

                            <label class="form-check-label">

                                Visible

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
