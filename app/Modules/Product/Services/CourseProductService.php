<?php

namespace App\Modules\Product\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use Illuminate\Support\Facades\DB;

class CourseProductService
{
    public function ensureDigitalVariant(Product $product): ?ProductVariant
    {
        if (! $product->isCourse() || (float) $product->course_price <= 0) {
            $existing = ProductVariant::query()
                ->where('sku', 'CURSO-'.$product->id)
                ->first();

            $existing?->update(['is_active' => false, 'is_default' => false]);

            return null;
        }

        return DB::transaction(function () use ($product): ProductVariant {
            $supplier = Supplier::withTrashed()->firstOrNew(
                ['code' => 'BREVARE-DIGITAL'],
            );
            $supplier->fill([
                'business_name' => 'Brevare',
                'trade_name' => 'Contenido digital',
                'status' => 'active',
                'is_internal' => true,
                'estimated_dispatch_days' => 0,
                'internal_notes' => 'Proveedor interno para cursos y productos digitales.',
            ]);
            $supplier->deleted_at = null;
            $supplier->save();

            $sku = 'CURSO-'.$product->id;
            $variant = ProductVariant::withTrashed()->firstOrNew(['sku' => $sku]);
            $variant->fill([
                'product_id' => $product->id,
                'barcode' => null,
                'cost_price' => 0,
                'sale_price' => (float) $product->course_price,
                'compare_price' => null,
                'discount_percent' => 0,
                'sync_enabled' => false,
                'is_default' => true,
                'is_active' => true,
            ]);
            $variant->deleted_at = null;
            $variant->save();

            $supplierVariant = SupplierVariant::withTrashed()->firstOrNew([
                'supplier_id' => $supplier->id,
                'product_variant_id' => $variant->id,
            ]);
            $supplierVariant->fill([
                'supplier_sku' => $sku,
                'cost_price' => 0,
                'shipping_cost' => 0,
                'supplier_sale_price' => (float) $product->course_price,
                'stock' => 1000000,
                'reserved_stock' => 0,
                'minimum_stock' => 0,
                'estimated_dispatch_days' => 0,
                'is_default' => true,
                'is_active' => true,
                'internal_notes' => 'Acceso digital ilimitado.',
            ]);
            $supplierVariant->deleted_at = null;
            $supplierVariant->save();

            return $variant->refresh();
        });
    }
}
