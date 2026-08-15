<div class="card shadow-sm">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h5 class="mb-0">
            <i class="bi bi-truck me-2"></i>
            Tarifas de envío
        </h5>

        <button type="button" class="btn btn-primary" wire:click="$dispatch('shipping-rate-create')">
            <i class="bi bi-plus-lg me-1"></i>
            Nueva tarifa
        </button>

    </div>

    <div class="card-body">

        <div class="row mb-3">

            <div class="col-md-4">

                <x-input placeholder="Buscar tarifa..." wire:model.live.debounce.300ms="search" />

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead class="table-light">

                    <tr>

                        <th width="70">
                            ID
                        </th>

                        <th>
                            Zona
                        </th>

                        <th>
                            Proveedor
                        </th>

                        <th>
                            Producto
                        </th>

                        <th>
                            Variante
                        </th>

                        <th width="120">
                            Precio
                        </th>

                        <th width="100">
                            Estado
                        </th>

                        <th width="140" class="text-center">
                            Acciones
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse ($rates as $rate)
                        <tr>

                            <td>
                                <strong>
                                    {{ $rate->id }}
                                </strong>
                            </td>

                            <td>
                                {{ $rate->shippingZone?->name ?? '-' }}
                            </td>

                            <td>
                                {{ $rate->supplier?->name ?? '-' }}
                            </td>

                            <td>
                                {{ $rate->product?->name ?? 'General' }}
                            </td>

                            <td>

                                @if ($rate->productVariant)
                                    <span class="text-nowrap">
                                        {{ $rate->productVariant->sku }}
                                    </span>
                                @else
                                    <span class="text-muted">
                                        General
                                    </span>
                                @endif

                            </td>

                            <td>
                                <strong>
                                    S/ {{ number_format($rate->price, 2) }}
                                </strong>
                            </td>

                            <td>

                                @if ($rate->status)
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

                                <button type="button" class="btn btn-warning btn-sm"
                                    wire:click="$dispatch('shipping-rate-edit', { id: {{ $rate->id }} })"
                                    title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <button type="button" class="btn btn-secondary btn-sm"
                                    wire:click="toggleStatus({{ $rate->id }})" title="Cambiar estado">
                                    <i class="bi bi-power"></i>
                                </button>

                                <button type="button" class="btn btn-danger btn-sm"
                                    wire:click="delete({{ $rate->id }})"
                                    wire:confirm="¿Eliminar esta tarifa de envío?" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="8" class="text-center py-4 text-muted">
                                No existen tarifas de envío registradas.
                            </td>

                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-3">
            {{ $rates->links() }}
        </div>

    </div>

</div>
