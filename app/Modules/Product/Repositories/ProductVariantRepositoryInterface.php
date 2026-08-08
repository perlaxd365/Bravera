<?php

namespace App\Modules\Product\Repositories;

use App\Models\ProductVariant;
use App\Modules\Product\DTO\ProductVariantData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductVariantRepositoryInterface
{
    public function paginate(
        int $productId,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator;

    public function find(int $id): ?ProductVariant;

    public function create(ProductVariantData $data): ProductVariant;

    public function update(
        ProductVariant $variant,
        ProductVariantData $data
    ): ProductVariant;

    public function delete(ProductVariant $variant): bool;

    public function toggleStatus(ProductVariant $variant): ProductVariant;
}
