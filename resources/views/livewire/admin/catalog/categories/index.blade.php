<div class="container-fluid py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <h3 class="mb-1">Categorías</h3>
                    <small class="text-muted">
                        Administra las categorías de los productos.
                    </small>
                </div>

                <livewire:admin.catalog.categories.form />

                <button class="btn btn-primary" wire:click="$dispatch('category-create')">

                    <i class="bi bi-plus-circle me-2"></i>
                    Nueva categoría
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

                        <input type="text" class="form-control" placeholder="Buscar categoría..."
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

                            <th>Categoría Padre</th>

                            <th width="100">Estado</th>

                            <th width="150" class="text-center">
                                Acciones
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($categories as $category)
                            <tr>

                                <td>{{ $category->id }}</td>

                                <td>
                                    <strong>{{ $category->name }}</strong>
                                </td>

                                <td>
                                    <code>{{ $category->slug }}</code>
                                </td>

                                <td>
                                    {{ optional($category->parent)->name ?? '-' }}
                                </td>

                                <td>

                                    @if ($category->is_visible)
                                        <span class="badge bg-success">
                                            Activo
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Inactivo
                                        </span>
                                    @endif

                                </td>

                                <td class="text-center">

                                    <button class="btn btn-sm btn-outline-primary"
                                        wire:click="$dispatch('category-edit',{id:{{ $category->id }}})">

                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                        wire:click="delete({{ $category->id }})"
                                        wire:confirm="¿Está seguro de eliminar esta categoría?">

                                        <i class="bi bi-trash"></i>
                                    </button>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="text-center py-5 text-muted">

                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>

                                    No existen categorías registradas.

                                </td>

                            </tr>
                        @endforelse

                    </tbody>


                </table>
                @if ($categories->hasPages())
                    <div class="mt-3">
                        {{ $categories->links() }}
                    </div>
                @endif

            </div>

        </div>

    </div>

</div>
