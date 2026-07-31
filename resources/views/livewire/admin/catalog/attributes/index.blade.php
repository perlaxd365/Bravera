<div class="container-fluid py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h3 class="mb-1">
                        Atributos
                    </h3>

                    <small class="text-muted">
                        Administra los atributos de los productos.
                    </small>

                </div>

                <livewire:admin.catalog.attributes.form />

                <button class="btn btn-primary" wire:click="$dispatch('attribute-create')">

                    <i class="bi bi-plus-circle me-2"></i>

                    Nuevo atributo

                </button>

            </div>

        </div>

        <div class="card-body">

            <div class="row mb-4">

                <div class="col-md-4">

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-search"></i>

                        </span>

                        <input type="text" class="form-control" placeholder="Buscar atributo..."
                            wire:model.live.debounce.300ms="search">

                    </div>

                </div>

            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th width="70">#</th>

                            <th>Nombre</th>

                            <th>Slug</th>

                            <th width="120">Tipo</th>

                            <th width="100">Filtro</th>

                            <th width="120">Obligatorio</th>

                            <th width="100">Estado</th>

                            <th width="100">Orden</th>

                            <th width="150" class="text-center">

                                Acciones

                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($attributes as $attribute)
                            <tr>

                                <td>{{ $attribute->id }}</td>

                                <td>

                                    <strong>{{ $attribute->name }}</strong>

                                </td>

                                <td>

                                    <code>{{ $attribute->slug }}</code>

                                </td>

                                <td>

                                    <span class="badge bg-info">

                                        {{ ucfirst($attribute->type) }}

                                    </span>

                                </td>

                                <td>

                                    @if ($attribute->is_filter)
                                        <span class="badge bg-success">

                                            Sí

                                        </span>
                                    @else
                                        <span class="badge bg-secondary">

                                            No

                                        </span>
                                    @endif

                                </td>

                                <td>

                                    @if ($attribute->is_required)
                                        <span class="badge bg-warning text-dark">

                                            Sí

                                        </span>
                                    @else
                                        <span class="badge bg-secondary">

                                            No

                                        </span>
                                    @endif

                                </td>

                                <td>

                                    @if ($attribute->is_active)
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

                                    {{ $attribute->sort_order }}

                                </td>

                                <td class="text-center">

                                    <button class="btn btn-sm btn-outline-primary"
                                        wire:click="$dispatch('attribute-edit',{id:{{ $attribute->id }}})">

                                        <i class="bi bi-pencil"></i>

                                    </button>

                                    <button class="btn btn-sm btn-outline-danger"
                                        wire:click="delete({{ $attribute->id }})"
                                        wire:confirm="¿Está seguro de eliminar este atributo?">

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="9" class="text-center py-5 text-muted">

                                    <i class="bi bi-sliders fs-1 d-block mb-3"></i>

                                    No existen atributos registrados.

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

                @if ($attributes->hasPages())
                    <div class="mt-3">

                        {{ $attributes->links() }}

                    </div>
                @endif

            </div>

        </div>

    </div>

</div>
