<?php

namespace App\Modules\Shipping\Repositories;

use App\DTOs\ShippingRateDTO;
use App\Models\ShippingRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShippingRateRepository
{
    /**
     * Obtener tarifas paginadas.
     */
    public function paginate(
        int $perPage = 10,
        ?string $search = null
    ): LengthAwarePaginator {
        return ShippingRate::query()
            ->with([
                'shippingZone.location',
                'supplier',
                'product',
                'variant',
            ])
            ->when($search, function ($query) use ($search) {
                $search = trim($search);

                $query->where(function ($q) use ($search) {
                    $q->whereHas('shippingZone', function ($zone) use ($search) {
                        $zone->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    })
                    ->orWhereHas('supplier', function ($supplier) use ($search) {
                        $supplier->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    })
                    ->orWhereHas('product', function ($product) use ($search) {
                        $product->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    })
                    ->orWhereHas('variant', function ($variant) use ($search) {
                        $variant->where(
                            'sku',
                            'like',
                            "%{$search}%"
                        );
                    });
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Obtener todas las tarifas.
     */
    public function getAll(): Collection
    {
        return ShippingRate::query()
            ->with([
                'shippingZone.location',
                'supplier',
                'product',
                'variant',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * Obtener tarifas activas.
     */
    public function getActive(): Collection
    {
        return ShippingRate::query()
            ->active()
            ->with([
                'shippingZone.location',
                'supplier',
                'product',
                'variant',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * Buscar una tarifa por ID.
     */
    public function find(int $id): ?ShippingRate
    {
        return ShippingRate::query()
            ->with([
                'shippingZone.location',
                'supplier',
                'product',
                'variant',
            ])
            ->find($id);
    }

    /**
     * Crear una tarifa.
     */
    public function create(ShippingRateDTO $dto): ShippingRate
    {
        return ShippingRate::create(
            $dto->toArray()
        );
    }

    /**
     * Actualizar una tarifa.
     */
    public function update(
        ShippingRate $rate,
        ShippingRateDTO $dto
    ): ShippingRate {
        $rate->update(
            $dto->toArray()
        );

        return $rate->refresh();
    }

    /**
     * Cambiar estado de una tarifa.
     */
    public function toggleStatus(
        ShippingRate $rate
    ): ShippingRate {
        $rate->update([
            'status' => ! $rate->status,
        ]);

        return $rate->refresh();
    }

    /**
     * Eliminar una tarifa.
     */
    public function delete(
        ShippingRate $rate
    ): void {
        $rate->delete();
    }

    /**
     * Buscar tarifa específica de una variante.
     */
    public function findVariantRate(
        int $supplierId,
        int $shippingZoneId,
        int $productId,
        int $productVariantId
    ): ?ShippingRate {
        return ShippingRate::query()
            ->active()
            ->where('supplier_id', $supplierId)
            ->where('shipping_zone_id', $shippingZoneId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $productVariantId)
            ->first();
    }

    /**
     * Buscar tarifa específica de un producto.
     */
    public function findProductRate(
        int $supplierId,
        int $shippingZoneId,
        int $productId
    ): ?ShippingRate {
        return ShippingRate::query()
            ->active()
            ->where('supplier_id', $supplierId)
            ->where('shipping_zone_id', $shippingZoneId)
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->first();
    }

    /**
     * Buscar tarifa general del proveedor.
     */
    public function findSupplierRate(
        int $supplierId,
        int $shippingZoneId
    ): ?ShippingRate {
        return ShippingRate::query()
            ->active()
            ->where('supplier_id', $supplierId)
            ->where('shipping_zone_id', $shippingZoneId)
            ->whereNull('product_id')
            ->whereNull('product_variant_id')
            ->first();
    }
}