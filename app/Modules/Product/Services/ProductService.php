<?php

namespace App\Modules\Product\Services;

use App\Models\Product;
use App\Modules\Product\DTO\ProductData;
use App\Modules\Product\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        protected ProductRepository $repository
    ) {}

    /**
     * Obtener listado paginado.
     */
    public function paginate(
        string $search = '',
        ?int $categoryId = null,
        ?int $brandId = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        return $this->repository->paginate(
            search: $search,
            categoryId: $categoryId,
            brandId: $brandId,
            perPage: $perPage
        );
    }

    /**
     * Obtener todos.
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Buscar por ID.
     */
    public function find(int $id): ?Product
    {
        return $this->repository->find($id);
    }

    /**
     * Crear producto.
     */
    /**
     * Crear producto.
     */
    public function create(ProductData $data): Product
    {
        return DB::transaction(function () use ($data) {

            $payload = $data->toArray();

            // Generar slug automáticamente si viene vacío.
            if (blank($payload['slug'])) {
                $payload['slug'] = Str::slug($payload['name']);
            }

            return $this->repository->create(
                ProductData::fromArray($payload)
            );
        });
    }
    /**
     * Actualizar producto.
     */
    public function update(Product $product, ProductData $data): Product
    {
        return DB::transaction(function () use ($product, $data) {

            $payload = $data->toArray();

            // Generar slug automáticamente si viene vacío.
            if (blank($payload['slug'])) {
                $payload['slug'] = Str::slug($payload['name']);
            }

            return $this->repository->update(
                $product,
                ProductData::fromArray($payload)
            );
        });
    }

    /**
     * Eliminar producto.
     */
    public function delete(Product $product): bool
    {
        return $this->repository->delete($product);
    }

    /**
     * Cambiar estado.
     */
    public function toggleStatus(Product $product): Product
    {
        return $this->repository->toggleStatus($product);
    }
}
