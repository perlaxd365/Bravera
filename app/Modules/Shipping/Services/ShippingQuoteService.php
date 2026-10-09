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

        $supplierCharges = [];
        $warnings = [];

        foreach ($items as $item) {
            $shipping = (float) ($item->supplier_shipping_cost ?? 0);

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

            $supplierId = $item->supplierVariant?->supplier_id;
            $groupKey = $supplierId
                ? 'supplier:'.$supplierId
                : 'item:'.$item->id;

            // Se cobra una sola tarifa por proveedor. Si las líneas del mismo
            // proveedor tienen tarifas distintas, se aplica la mayor para
            // cubrir el envío de todo el paquete sin duplicarlo por producto.
            $supplierCharges[$groupKey]['amount'] = max(
                $supplierCharges[$groupKey]['amount'] ?? 0,
                $shipping
            );
            $supplierCharges[$groupKey]['item_ids'][] = $item->id;
        }

        // Se asigna cada cargo a una sola línea para que el total del pedido
        // coincida con el monto mostrado en checkout.
        $perItem = array_fill_keys($items->pluck('id')->all(), 0.0);

        foreach ($supplierCharges as $charge) {
            $firstItemId = $charge['item_ids'][0] ?? null;

            if ($firstItemId !== null) {
                $perItem[$firstItemId] = round((float) $charge['amount'], 2);
            }
        }

        return [
            'items' => $perItem,
            'total' => round(array_sum($perItem), 2),
            'zone' => $zone,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }
}
