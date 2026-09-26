<?php

namespace App\Modules\Shipping\Services;

use App\Models\CartItem;
use App\Models\Location;
use App\Models\ShippingZone;
use Illuminate\Support\Collection;

class ShippingQuoteService
{
    public function __construct(
        protected ShippingZoneService $zoneService,
        protected ShippingRateService $rateService,
    ) {}

    /**
     * Calcula el costo de envío para el carrito hacia una ubicación.
     *
     * @param  Collection<int, CartItem>  $items
     * @return array{items: array<int, float>, total: float, zone: ShippingZone|null, warnings: array<int, string>}
     */
    public function quoteForCart(Collection $items, Location $location): array
    {
        $zone = $this->zoneService->resolveForLocation($location);

        $perItem = [];
        $warnings = [];

        foreach ($items as $item) {
            $shipping = (float) ($item->supplier_shipping_cost ?? 0) * $item->quantity;

            if ($zone) {
                $rate = $this->rateService->resolveRate(
                    supplierId: $item->supplierVariant?->supplier_id ?? 0,
                    shippingZoneId: (int) $zone->id,
                    productId: (int) $item->variant?->product_id,
                    productVariantId: (int) $item->product_variant_id,
                );

                if ($rate) {
                    $shipping = round((float) $rate->price, 2);
                }
            } else {
                $warnings[] = 'No se encontró zona de envío para la ubicación; se usará la tarifa del proveedor.';
            }

            $perItem[$item->id] = round($shipping, 2);
        }

        return [
            'items' => $perItem,
            'total' => round(array_sum($perItem), 2),
            'zone' => $zone,
            'warnings' => $warnings,
        ];
    }
}
