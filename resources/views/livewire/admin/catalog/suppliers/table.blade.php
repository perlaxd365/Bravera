<div class="card shadow-sm">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h5 class="mb-0">
            <i class="bi bi-truck me-2"></i>
            Proveedores
        </h5>

        <button class="btn btn-primary" wire:click="$dispatch('supplier-create')">

            <i class="bi bi-plus-lg"></i>

            Nuevo proveedor

        </button>

    </div>

    <div class="card-body">

        <div class="row mb-3">

            <div class="col-md-4">

                <x-input placeholder="Buscar proveedor..." wire:model.live.debounce.300ms="search" />

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead class="table-light">

                    <tr>

                        <th width="110">
                            Código
                        </th>

                        <th>
                            Razón Social
                        </th>

                        <th>
                            Contacto
                        </th>

                        <th width="140">
                            WhatsApp
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

                    @forelse($suppliers as $supplier)
                        <tr>

                            <td>

                                <strong>

                                    {{ $supplier->code }}

                                </strong>

                            </td>

                            <td>

                                {{ $supplier->business_name }}

                                @if ($supplier->trade_name)
                                    <br>

                                    <small class="text-muted">

                                        {{ $supplier->trade_name }}

                                    </small>
                                @endif

                            </td>

                            <td>

                                {{ $supplier->contact_name ?: '-' }}

                            </td>

                            <td>

                                {{ $supplier->whatsapp ?: '-' }}

                            </td>
                            <td>

                                @if ($supplier->status === 'active')
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
                                    wire:click="$dispatch('supplier-edit',{ id: {{ $supplier->id }} })">

                                    <i class="bi bi-pencil"></i>

                                </button>

                                <button class="btn btn-danger btn-sm" wire:click="delete({{ $supplier->id }})"
                                    wire:confirm="¿Eliminar este proveedor?">

                                    <i class="bi bi-trash"></i>

                                </button>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="text-center py-4">

                                No existen proveedores registrados.

                            </td>

                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-3">

            {{ $suppliers->links() }}

        </div>

    </div>

</div>
