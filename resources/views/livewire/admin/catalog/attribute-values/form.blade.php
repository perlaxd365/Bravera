<div>

    <div class="modal fade @if ($show) show d-block @endif" tabindex="-1"
        @if ($show) style="background: rgba(0,0,0,.5);" @endif>

        <div class="modal-dialog modal-lg">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">

                        {{ $attributeValue ? 'Editar Valor' : 'Nuevo Valor' }}

                    </h5>

                    <button type="button" class="btn-close" wire:click="$set('show', false)">
                    </button>

                </div>

                <form wire:submit="save">

                    <div class="modal-body">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Atributo
                                </label>

                                <select class="form-select @error('attribute_id') is-invalid @enderror"
                                    wire:model="attribute_id">

                                    <option value="">
                                        Seleccione...
                                    </option>

                                    @foreach ($attributes as $attribute)
                                        <option value="{{ $attribute->id }}">
                                            {{ $attribute->name }}
                                        </option>
                                    @endforeach

                                </select>

                                @error('attribute_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Valor
                                </label>

                                <input type="text" class="form-control @error('value') is-invalid @enderror"
                                    wire:model.live="value">

                                @error('value')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Slug
                                </label>

                                <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                    wire:model="slug">

                                @error('slug')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Color
                                </label>

                                <input type="color" class="form-control form-control-color" wire:model="color">

                            </div>

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Orden
                                </label>

                                <input type="number" min="0" class="form-control" wire:model="sort_order">

                            </div>

                            <div class="col-12">

                                <div class="form-check">

                                    <input type="checkbox" class="form-check-input" id="is_active"
                                        wire:model="is_active">

                                    <label class="form-check-label" for="is_active">

                                        Activo

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" wire:click="$set('show', false)">

                            Cancelar

                        </button>

                        <button type="submit" class="btn btn-primary">

                            <i class="fas fa-save"></i>

                            Guardar

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>
