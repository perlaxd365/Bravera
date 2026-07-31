<div>
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <div class="row w-100">

                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" placeholder="Buscar valor..."
                        wire:model.live.debounce.300ms="search">
                </div>

                <div class="col-md-4 mb-2">
                    <select class="form-select" wire:model.live="attributeFilter">

                        <option value="">-- Todos los atributos --</option>

                        @foreach ($attributes as $attribute)
                            <option value="{{ $attribute->id }}">
                                {{ $attribute->name }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <div class="col-md-4 text-end">
                    <button class="btn btn-primary" wire:click="$dispatch('attribute-value-create')">

                        <i class="fas fa-plus"></i>
                        Nuevo Valor
                    </button>
                </div>

            </div>

        </div>

        <div class="card-body table-responsive p-0">

            <table class="table table-hover align-middle mb-0">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Atributo</th>
                        <th>Valor</th>
                        <th>Color</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th width="140">Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($values as $value)
                        <tr>

                            <td>{{ $value->id }}</td>

                            <td>{{ $value->attribute->name }}</td>

                            <td>{{ $value->value }}</td>

                            <td>

                                @if ($value->color)
                                    <span class="rounded-circle border d-inline-block"
                                        style="
                                        width:25px;
                                        height:25px;
                                        background:{{ $value->color }};
                                    ">
                                    </span>

                                    {{ $value->color }}
                                @else
                                    —
                                @endif

                            </td>

                            <td>{{ $value->sort_order }}</td>

                            <td>

                                @if ($value->is_active)
                                    <span class="badge bg-success">
                                        Activo
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        Inactivo
                                    </span>
                                @endif

                            </td>

                            <td>

                                <button class="btn btn-sm btn-warning"
                                    wire:click="$dispatch('attribute-value-edit', { id: {{ $value->id }} })">

                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <button class="btn btn-sm btn-danger" wire:click="delete({{ $value->id }})"
                                    wire:confirm="¿Eliminar este registro?">

                                    <i class="bi bi-trash"></i>
                                </button>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7" class="text-center py-4">

                                No existen registros.

                            </td>

                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($values->hasPages())
            <div class="card-footer">

                {{ $values->links() }}

            </div>
        @endif

    </div>

    <livewire:admin.catalog.attribute-values.form />

</div>
