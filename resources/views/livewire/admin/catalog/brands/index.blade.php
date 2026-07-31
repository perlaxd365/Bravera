<div class="container-fluid py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <h3 class="mb-1">Marcas</h3>

                    <small class="text-muted">
                        Administra las marcas de los productos.
                    </small>
                </div>

                <livewire:admin.catalog.brands.form />

                <button class="btn btn-primary" wire:click="$dispatch('brand-create')">

                    <i class="bi bi-plus-circle me-2"></i>

                    Nueva marca

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

                        <input type="text" class="form-control" placeholder="Buscar marca..."
                            wire:model.live.debounce.300ms="search">

                    </div>

                </div>

            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th width="70">#</th>

                            <th width="80">Logo</th>

                            <th>Nombre</th>

                            <th>Slug</th>

                            <th width="100">Orden</th>

                            <th width="100">Estado</th>


                            <th width="150" class="text-center">

                                Acciones

                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($brands as $brand)
                            <tr>

                                <td>{{ $brand->id }}</td>

                                <td>

                                    @if ($brand->image)
                                        <img src="{{ $brand->image }}" alt="{{ $brand->name }}" class="rounded border"
                                            style="width:45px;height:45px;object-fit:cover;">
                                    @else
                                        <div class="bg-light border rounded d-flex align-items-center justify-content-center"
                                            style="width:45px;height:45px;">

                                            <i class="bi bi-image text-secondary"></i>

                                        </div>
                                    @endif

                                </td>

                                <td>

                                    <strong>{{ $brand->name }}</strong>

                                </td>

                                <td>

                                    <code>{{ $brand->slug }}</code>

                                </td>

                                <td>

                                    {{ $brand->sort_order }}

                                </td>

                                <td>

                                    @if ($brand->is_active)
                                        <span class="badge bg-success">

                                            Activa

                                        </span>
                                    @else
                                        <span class="badge bg-secondary">

                                            Inactiva

                                        </span>
                                    @endif

                                </td>

                            

                                <td class="text-center">

                                    <button class="btn btn-sm btn-outline-primary"
                                        wire:click="$dispatch('brand-edit',{id:{{ $brand->id }}})">

                                        <i class="bi bi-pencil"></i>

                                    </button>

                                    <button class="btn btn-sm btn-outline-danger"
                                        wire:click="delete({{ $brand->id }})"
                                        wire:confirm="¿Está seguro de eliminar esta marca?">

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="text-center py-5 text-muted">

                                    <i class="bi bi-tags fs-1 d-block mb-3"></i>

                                    No existen marcas registradas.

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

                @if ($brands->hasPages())
                    <div class="mt-3">

                        {{ $brands->links() }}

                    </div>
                @endif

            </div>

        </div>

    </div>

</div>
