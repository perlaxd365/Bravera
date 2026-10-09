<?php

namespace App\Modules\Product\Repositories;

use App\Models\Product;
use App\Modules\Product\DTO\ProductData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function paginate(
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator;

    public function find(int $id): ?Product;

    public function create(ProductData $dto): Product;

    public function update(Product $product, ProductData $dto): Product;

    public function delete(Product $product): bool;
}
