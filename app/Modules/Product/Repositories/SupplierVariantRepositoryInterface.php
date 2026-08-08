<?php

namespace App\Modules\Product\Repositories;

use App\Models\SupplierVariant;
use App\Modules\Product\DTO\SupplierVariantData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupplierVariantRepositoryInterface
{
    public function paginate(
        int $productVariantId,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator;

    public function find(int $id): ?SupplierVariant;

    public function create(
        SupplierVariantData $data
    ): SupplierVariant;

    public function update(
        SupplierVariant $supplierVariant,
        SupplierVariantData $data
    ): SupplierVariant;

    public function delete(
        SupplierVariant $supplierVariant
    ): bool;

    public function toggleStatus(
        SupplierVariant $supplierVariant
    ): SupplierVariant;
}
