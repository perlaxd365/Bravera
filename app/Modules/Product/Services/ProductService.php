<?php

namespace App\Modules\Product\Services;

use App\Models\Product;
use App\Modules\Product\DTO\ProductData;
use App\Modules\Product\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

            $payload = $this->ensureSeoDefaults($payload);

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

            $payload = $this->ensureSeoDefaults($payload);

            return $this->repository->update(
                $product,
                ProductData::fromArray($payload)
            );
        });
    }

    /**
     * Completa el título y la descripción SEO cuando el administrador los deja
     * vacíos, de modo que todo producto quede listo para Google y el feed.
     */
    private function ensureSeoDefaults(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));

        if (blank($payload['seo_title'] ?? null) && $name !== '') {
            $payload['seo_title'] = $name.' | Brevare';
        }

        if (blank($payload['seo_description'] ?? null)) {
            $source = $payload['short_description'] ?? $payload['description'] ?? '';
            $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $source)) ?? '');

            if ($text !== '') {
                $payload['seo_description'] = Str::limit($text, 160, '');
            } elseif ($name !== '') {
                $payload['seo_description'] = 'Compra '.$name.' en Brevare con envío a todo el Perú.';
            }
        }

        return $payload;
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
