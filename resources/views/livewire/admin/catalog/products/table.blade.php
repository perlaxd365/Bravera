<div class="table-responsive">

    <table class="table table-bordered table-hover align-middle">

        <thead>

            <tr>

                <th width="70">ID</th>

                <th>Producto</th>

                <th>Categoría</th>

                <th>Marca</th>

                <th class="text-center">Estado</th>

                <th width="120" class="text-center">
                    Acciones
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse($products as $product)
                <tr wire:key="product-{{ $product->id }}">

                    <td>

                        {{ $product->id }}

                    </td>

                    <td>

                        <strong>

                            {{ $product->name }}

                        </strong>

                        <br>

                        <small class="text-muted">

                            {{ $product->slug }}

                        </small>

                    </td>

                    <td>

                        {{ $product->category?->name }}

                    </td>

                    <td>

                        {{ $product->brand?->name ?? '-' }}

                    </td>

                    <td class="text-center">

                        @if ($product->status)
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

                        <button type="button" class="btn btn-sm btn-outline-primary"
                            wire:click="$dispatch('product-edit', { id: {{ $product->id }} })" title="Editar">

                            <i class="bi bi-pencil-square"></i>

                        </button>

                        <button type="button" class="btn btn-sm btn-outline-danger"
                            wire:click="delete({{ $product->id }})"
                            wire:confirm="¿Está seguro de eliminar este producto?" title="Eliminar">

                            <i class="bi bi-trash"></i>

                        </button>


                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="6" class="text-center text-muted py-5">

                        No existen productos registrados.

                    </td>

                </tr>
            @endforelse

        </tbody>

    </table>

</div>

<div class="mt-3">

    {{ $products->links() }}

</div>
