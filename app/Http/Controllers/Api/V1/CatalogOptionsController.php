<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;

class CatalogOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'categories' => Category::query()
                ->where('is_visible', true)
                ->orderBy('path')
                ->get(['id', 'name', 'path'])
                ->map(fn (Category $category) => $category->only(['id', 'name', 'path']))
                ->values(),
            'brands' => Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Brand $brand) => $brand->only(['id', 'name', 'slug']))
                ->values(),
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('business_name')
                ->get(['id', 'code', 'business_name'])
                ->map(fn (Supplier $supplier) => $supplier->only(['id', 'code', 'business_name']))
                ->values(),
            'attributes' => Attribute::query()
                ->where('is_active', true)
                ->with(['values' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('value')])
                ->orderBy('name')
                ->get()
                ->map(fn (Attribute $attribute) => [
                    'name' => $attribute->name,
                    'values' => $attribute->values->pluck('value')->values(),
                ])
                ->values(),
            'limits' => [
                'max_image_bytes' => 5 * 1024 * 1024,
                'max_images_per_variant' => 20,
                'max_variants' => 100,
            ],
            'notes' => [
                'La API crea y publica productos físicos en una sola operación.',
                'Cada variante necesita al menos una imagen Base64 y stock de proveedor mayor que cero.',
                'Usa primero un proveedor existente desde suppliers o créalo con POST /api/v1/suppliers.',
            ],
        ]);
    }
}
