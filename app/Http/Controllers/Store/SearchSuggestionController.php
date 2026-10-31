<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $products = Product::query()
            ->active()
            ->visible()
            ->with(['category', 'variants.images'])
            ->where('name', 'like', '%'.$query.'%')
            ->whereHas('variants', fn ($variants) => $variants
                ->where('is_active', true)
                ->whereHas('supplierVariants', fn ($supplierVariants) => $supplierVariants
                    ->active()
                    ->whereColumn('stock', '>', 'reserved_stock')))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->limit(6)
            ->get();

        return response()->json(
            $products->map(fn (Product $product): array => [
                'name' => $product->localized('name') ?? $product->name,
                'category' => $product->category?->name,
                'url' => route('store.product', ['slug' => $product->slug]),
                'image' => $product->coverImageModel()?->sizedUrl(96),
            ])->values(),
        );
    }
}
