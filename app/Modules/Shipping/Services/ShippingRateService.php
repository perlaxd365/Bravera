<?php

namespace App\Modules\Shipping\Services;

use App\DTOs\ShippingRateDTO;
use App\Models\ShippingRate;
use App\Models\SupplierVariant;
use App\Modules\Shipping\Repositories\ShippingRateRepository;
use Illuminate\Validation\ValidationException;

class ShippingRateService
{
    public function __construct(
        protected ShippingRateRepository $repository
    ) {}

    /**
     * Obtener todas las tarifas.
     */
    public function getAll()
    {
        return $this->repository->getAll();
    }

    /**
     * Obtener tarifas activas.
     */
    public function getActive()
    {
        return $this->repository->getActive();
    }

    /**
     * Buscar una tarifa.
     */
    public function find(int $id): ?ShippingRate
    {
        return $this->repository->find($id);
    }

    /**
     * Crear una tarifa.
     */
    public function create(ShippingRateDTO $dto): ShippingRate
    {
        $this->validateData($dto);

        $this->ensureUnique(
            $dto->supplier_id,
            $dto->shipping_zone_id,
            $dto->product_id,
            $dto->product_variant_id
        );

        return $this->repository->create($dto);
    }

    /**
     * Actualizar una tarifa.
     */
    public function update(
        ShippingRate $rate,
        ShippingRateDTO $dto
    ): ShippingRate {
        $this->validateData($dto);

        $this->ensureUnique(
            $dto->supplier_id,
            $dto->shipping_zone_id,
            $dto->product_id,
            $dto->product_variant_id,
            $rate->id
        );

        return $this->repository->update(
            $rate,
            $dto
        );
    }

    /**
     * Activar o desactivar una tarifa.
     */
    public function toggleStatus(
        ShippingRate $rate
    ): ShippingRate {
        return $this->repository->toggleStatus($rate);
    }

    /**
     * Eliminar una tarifa.
     */
    public function delete(
        ShippingRate $rate
    ): void {
        $this->repository->delete($rate);
    }

    /**
     * Resolver el precio de envío.
     *
     * Prioridad:
     *
     * 1. Variante
     * 2. Producto
     * 3. Proveedor
     */
    public function resolveRate(
        int $supplierId,
        int $shippingZoneId,
        int $productId,
        ?int $productVariantId = null
    ): ?ShippingRate {
        if ($productVariantId) {
            $rate = $this->repository->findVariantRate(
                $supplierId,
                $shippingZoneId,
                $productId,
                $productVariantId
            );

            if ($rate) {
                return $rate;
            }
        }

        $rate = $this->repository->findProductRate(
            $supplierId,
            $shippingZoneId,
            $productId
        );

        if ($rate) {
            return $rate;
        }

        return $this->repository->findSupplierRate(
            $supplierId,
            $shippingZoneId
        );
    }

    /**
     * Validar todos los datos de negocio.
     */
    private function validateData(
        ShippingRateDTO $dto
    ): void {
        $this->validateSpecificity(
            $dto->product_id,
            $dto->product_variant_id
        );

        $this->validateSupplierVariant(
            $dto->supplier_id,
            $dto->product_variant_id
        );
    }

    /**
     * Validar la especificidad de la tarifa.
     *
     * Una variante siempre debe pertenecer a un producto.
     */
    private function validateSpecificity(
        ?int $productId,
        ?int $productVariantId
    ): void {
        if ($productVariantId && ! $productId) {
            throw ValidationException::withMessages([
                'product_variant_id' =>
                'Una variante debe estar asociada a un producto.',
            ]);
        }
    }

    /**
     * Validar que la variante pertenezca al proveedor.
     */
    private function validateSupplierVariant(
        int $supplierId,
        ?int $productVariantId
    ): void {
        if (! $productVariantId) {
            return;
        }

        $exists = SupplierVariant::query()
            ->active()
            ->where('supplier_id', $supplierId)
            ->where('product_variant_id', $productVariantId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'product_variant_id' =>
                'La variante seleccionada no pertenece al proveedor indicado o no está activa.',
            ]);
        }
    }

    /**
     * Evitar tarifas duplicadas.
     */
    private function ensureUnique(
        int $supplierId,
        int $shippingZoneId,
        ?int $productId,
        ?int $productVariantId,
        ?int $ignoreId = null
    ): void {
        $query = ShippingRate::query()
            ->where('supplier_id', $supplierId)
            ->where('shipping_zone_id', $shippingZoneId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $productVariantId);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'shipping_zone_id' =>
                'Ya existe una tarifa con esta combinación de proveedor, zona, producto y variante.',
            ]);
        }
    }
}
