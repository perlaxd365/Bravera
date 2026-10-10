<?php

namespace App\Services;

use App\DTOs\SupplierDTO;
use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SupplierService
{
    public function __construct(
        protected SupplierRepository $repository
    ) {}

    /**
     * Crear proveedor.
     */
    public function create(SupplierDTO $dto): Supplier
    {
        // Validar RUC duplicado
        if (
            $dto->tax_id &&
            Supplier::where('tax_id', $dto->tax_id)->exists()
        ) {
            throw ValidationException::withMessages([
                'tax_id' => 'El RUC ya se encuentra registrado.',
            ]);
        }

        // Generar código automático
        $code = $this->generateCode();

        return $this->repository->create(
            new SupplierDTO(
                code: $code,
                business_name: $dto->business_name,
                trade_name: $dto->trade_name,
                tax_id: $dto->tax_id,
                contact_name: $dto->contact_name,
                email: $dto->email,
                phone: $dto->phone,
                whatsapp: $dto->whatsapp,
                website: $dto->website,
                location_id: $dto->location_id,
                address: $dto->address,
                estimated_dispatch_days: $dto->estimated_dispatch_days,
                status: $dto->status,
                internal_notes: $dto->internal_notes,
                created_by: Auth::id(),
                updated_by: null,
            )
        );
    }

    /**
     * Actualizar proveedor.
     */
    public function update(
        Supplier $supplier,
        SupplierDTO $dto
    ): Supplier {
        abort_if($supplier->is_internal, 403, 'El registro interno de Brevare no se edita desde proveedores.');
        if (
            $dto->tax_id &&
            Supplier::where('tax_id', $dto->tax_id)
                ->whereKeyNot($supplier->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'tax_id' => 'El RUC ya se encuentra registrado.',
            ]);
        }

        return $this->repository->update(
            $supplier,
            new SupplierDTO(
                code: $supplier->code,
                business_name: $dto->business_name,
                trade_name: $dto->trade_name,
                tax_id: $dto->tax_id,
                contact_name: $dto->contact_name,
                email: $dto->email,
                phone: $dto->phone,
                whatsapp: $dto->whatsapp,
                website: $dto->website,
                location_id: $dto->location_id,
                address: $dto->address,
                estimated_dispatch_days: $dto->estimated_dispatch_days,
                status: $dto->status,
                internal_notes: $dto->internal_notes,
                created_by: $supplier->created_by,
                updated_by: Auth::id(),
            )
        );
    }

    /**
     * Eliminar proveedor.
     */
    public function delete(Supplier $supplier): void
    {
        abort_if($supplier->is_internal, 403, 'El registro interno de Brevare no se puede eliminar.');

        $this->repository->delete($supplier);
    }

    /**
     * Restaurar proveedor.
     */
    public function restore(int $id): void
    {
        $this->repository->restore($id);
    }

    /**
     * Genera el código del proveedor.
     */
    private function generateCode(): string
    {
        $next = Supplier::withTrashed()->count() + 1;

        return 'SUP'.str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
