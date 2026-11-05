<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Models\Product;
use App\Modules\Product\Services\ProductPublicationApiService;
use App\Modules\Product\Services\ProductReadinessService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductReadinessService $readiness,
    ) {}

    public function store(
        StoreProductRequest $request,
        ProductPublicationApiService $publication,
    ): JsonResponse {
        $product = $publication->publish($request->validated());

        return response()->json([
            'message' => 'Producto publicado correctamente.',
            'data' => $this->payload($product),
        ], 201);
    }

    public function show(string $product): JsonResponse
    {
        $query = Product::query()->with($this->relations());
        $record = ctype_digit($product)
            ? $query->findOrFail((int) $product)
            : $query->where('slug', $product)->firstOrFail();

        return response()->json([
            'data' => $this->payload($record),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Product $product): array
    {
        $product->loadMissing($this->relations());
        $assessment = $this->readiness->assess($product);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'url' => route('store.product', ['slug' => $product->slug]),
            'status' => (bool) $product->status,
            'is_visible' => (bool) $product->is_visible,
            'is_featured' => (bool) $product->is_featured,
            'allows_shalom_cod' => (bool) $product->allows_shalom_cod,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'path' => $product->category->path,
            ] : null,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,
            'ready_for_publication' => $assessment['ready'],
            'readiness_percent' => $assessment['percent'],
            'missing' => $assessment['missing'],
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'sale_price' => (float) $variant->sale_price,
                'compare_price' => $variant->compare_price !== null
                    ? (float) $variant->compare_price
                    : null,
                'wholesale_price' => $variant->wholesale_price !== null
                    ? (float) $variant->wholesale_price
                    : null,
                'wholesale_min_quantity' => $variant->wholesale_min_quantity,
                'is_default' => (bool) $variant->is_default,
                'is_active' => (bool) $variant->is_active,
                'available_stock' => $variant->supplierVariants
                    ->filter(fn ($supplierVariant) => $supplierVariant->is_active
                        && $supplierVariant->supplier?->status === 'active')
                    ->sum(fn ($supplierVariant) => $supplierVariant->availableStock()),
                'attributes' => $variant->attributeValues
                    ->mapWithKeys(fn ($attributeValue) => [
                        $attributeValue->attribute?->name => $attributeValue->value?->value,
                    ])
                    ->filter(fn ($value, $name) => filled($name) && filled($value))
                    ->all(),
                'images' => $variant->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => $image->imageUrl(),
                    'is_primary' => (bool) $image->is_primary,
                    'sort_order' => $image->sort_order,
                ])->values()->all(),
                'suppliers' => $variant->supplierVariants->map(fn ($supplierVariant) => [
                    'code' => $supplierVariant->supplier?->code,
                    'name' => $supplierVariant->supplier?->business_name,
                    'available_stock' => $supplierVariant->availableStock(),
                    'cost_price' => (float) $supplierVariant->cost_price,
                    'shipping_cost' => (float) $supplierVariant->shipping_cost,
                    'estimated_dispatch_days' => $supplierVariant->estimated_dispatch_days,
                    'is_active' => (bool) $supplierVariant->is_active,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'category',
            'brand',
            'translations',
            'variants.images',
            'variants.attributeValues.attribute',
            'variants.attributeValues.value',
            'variants.supplierVariants.supplier',
        ];
    }
}
