<div class="card shadow-sm">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h5 class="mb-0">
            <i class="bi bi-truck me-2"></i>
            Zonas de envío
        </h5>

        <button class="btn btn-primary" wire:click="$dispatch('shipping-zone-create')">

            <i class="bi bi-plus-lg"></i>

            Nueva zona

        </button>

    </div>

    <div class="card-body">

        <div class="row mb-3">

            <div class="col-md-4">

                <x-input placeholder="Buscar zona..." wire:model.live.debounce.300ms="search" />

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead class="table-light">

                    <tr>

                        <th width="80">
                            ID
                        </th>

                        <th>
                            Nombre
                        </th>

                        <th width="150">
                            Nivel
                        </th>

                        <th>
                            Ubicación
                        </th>

                        <th width="120">
                            Estado
                        </th>

                        <th width="140" class="text-center">
                            Acciones
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($zones as $zone)
                        <tr>

                            <td>

                                <strong>
                                    {{ $zone->id }}
                                </strong>

                            </td>

                            <td>

                                {{ $zone->name }}

                            </td>

                            <td>

                                @switch($zone->type->value)
                                    @case('department')
                                        <span class="badge bg-primary">
                                            Departamento
                                        </span>
                                    @break

                                    @case('province')
                                        <span class="badge bg-info">
                                            Provincia
                                        </span>
                                    @break

                                    @case('district')
                                        <span class="badge bg-secondary">
                                            Distrito
                                        </span>
                                    @break

                                    @default
                                        <span class="badge bg-dark">
                                            {{ $zone->type->value }}
                                        </span>
                                @endswitch

                            </td>

                            <td>

                                {{ $zone->location?->name ?? '-' }}

                            </td>

                            <td>

                                @if ($zone->status)
                                    <span class="badge bg-success">
                                        Activo
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        Inactivo
                                    </span>
                                @endif

                            </td>

                            <td class="text-center">

                                <button class="btn btn-warning btn-sm"
                                    wire:click="$dispatch('shipping-zone-edit', { id: {{ $zone->id }} })">

                                    <i class="bi bi-pencil"></i>

                                </button>

                                <button class="btn btn-secondary btn-sm" wire:click="toggleStatus({{ $zone->id }})">

                                    <i class="bi bi-power"></i>

                                </button>

                                <button class="btn btn-danger btn-sm" wire:click="delete({{ $zone->id }})"
                                    wire:confirm="¿Eliminar esta zona de envío?">

                                    <i class="bi bi-trash"></i>

                                </button>

                            </td>

                        </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="text-center py-4">

                                    No existen zonas de envío registradas.

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="mt-3">

                {{ $zones->links() }}

            </div>

        </div>

    </div>
