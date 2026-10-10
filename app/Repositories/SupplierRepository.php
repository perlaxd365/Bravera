<?php

namespace App\Repositories;

use App\DTOs\SupplierDTO;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierRepository
{
    /**
     * Obtiene un listado paginado.
     */
    public function paginate(
        int $perPage = 10,
        ?string $search = null
    ): LengthAwarePaginator {

        return Supplier::query()
            ->where('is_internal', false)

            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('business_name', 'like', "%{$search}%")
                        ->orWhere('trade_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%");
                });
            })

            ->latest()

            ->paginate($perPage);
    }

    /**
     * Crear proveedor.
     */
    public function create(SupplierDTO $dto): Supplier
    {
        return Supplier::create(
            $dto->toArray()
        );
    }

    /**
     * Actualizar proveedor.
     */
    public function update(
        Supplier $supplier,
        SupplierDTO $dto
    ): Supplier {

        $supplier->update(
            $dto->toArray()
        );

        return $supplier->refresh();
    }

    /**
     * Eliminación lógica.
     */
    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }

    /**
     * Restaurar.
     */
    public function restore(int $id): void
    {
        Supplier::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    /**
     * Buscar por ID.
     */
    public function find(int $id): Supplier
    {
        return Supplier::findOrFail($id);
    }
}
