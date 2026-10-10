<?php

namespace App\Livewire\Store;

use App\Models\Product;
use App\Models\ProductReview;
use App\Services\CartService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class ProductDetail extends Component
{
    public ?Product $product = null;

    public int $quantity = 1;

    public int $reviewRating = 5;

    public string $reviewComment = '';

    public ?int $selectedVariantId = null;

    public array $selectedAttributes = [];

    public ?int $selectedSupplierVariantId = null;

    protected array $messages = [
        'quantity.min' => 'La cantidad mínima es 1.',
        'selectedVariantId.required' => 'Selecciona la talla para continuar.',
        'selectedSupplierVariantId.required' => 'Selecciona un proveedor para continuar.',
    ];

    public function mount(string $slug): void
    {
        $this->product = Product::query()
            ->active()
            ->visible()
            ->where('slug', $slug)
            ->with([
                'brand',
                'category',
                'variants.images',
                'variants.attributeValues.attribute',
                'variants.attributeValues.value',
                'variants.supplierVariants.supplier',
            ])
            ->first();

        abort_unless($this->product, 404);

        $requestedSku = request()->query('variant');
        if (is_string($requestedSku) && $requestedSku !== '') {
            $requestedVariant = $this->product->variants->firstWhere('sku', $requestedSku);

            if ($requestedVariant?->is_active && $requestedVariant->supplierVariants->contains(
                fn ($supplier) => $supplier->is_active && $supplier->availableStock() > 0
            )) {
                $this->selectedVariantId = $requestedVariant->id;
                $this->selectedAttributes = $requestedVariant->attributeValues
                    ->mapWithKeys(fn ($pivot) => [(int) $pivot->attribute_id => (int) $pivot->attribute_value_id])
                    ->all();
            }
        }

        $this->recordRecentView();
    }

    /**
     * Registra el producto actual en el historial de vistos recientemente
     * (lista en sesión, máx 8, sin duplicados, el actual primero).
     */
    private function recordRecentView(): void
    {
        $recent = (array) session('recently_viewed_product_ids', []);

        $recent = [(int) $this->product->id, ...array_diff($recent, [(int) $this->product->id])];

        session(['recently_viewed_product_ids' => array_slice($recent, 0, 8)]);
    }

    private ?array $matrixCache = null;

    private function variantMatrix(): array
    {
        if ($this->matrixCache !== null) {
            return $this->matrixCache;
        }

        [$variants, $attributes, $previewVariant] = $this->buildVariantMatrix();

        $this->matrixCache = [$variants, $attributes, $previewVariant];

        return $this->matrixCache;
    }

    public function updatedSelectedAttributes(): void
    {
        $this->resetErrorBag(['selectedVariantId', 'selectedSupplierVariantId']);

        [, $attributes] = $this->variantMatrix();

        $requiredGroups = array_keys($attributes);
        $chosen = array_keys(array_filter(
            $this->selectedAttributes,
            fn ($x) => $x !== null && $x !== ''
        ));

        sort($requiredGroups);
        sort($chosen);

        if ($chosen !== $requiredGroups) {
            $this->selectedVariantId = null;
            $this->selectedSupplierVariantId = null;

            return;
        }

        $this->resolveVariantFromAttributes();
    }

    public function updatedSelectedVariantId(): void
    {
        $this->resetErrorBag(['selectedVariantId', 'selectedSupplierVariantId']);

        if ($this->selectedVariantId === null || $this->selectedVariantId === '') {
            $this->selectedVariantId = null;
            $this->selectedAttributes = [];
            $this->selectedSupplierVariantId = null;

            return;
        }

        $this->selectedVariantId = (int) $this->selectedVariantId;

        $variant = $this->product->variants->first(
            fn ($v) => (int) $v->id === $this->selectedVariantId
        );

        if ($variant) {
            $this->selectedAttributes = $variant->attributeValues
                ->mapWithKeys(
                    fn ($pivot) => [(int) $pivot->attribute_id => (int) $pivot->attribute_value_id]
                )
                ->toArray();
        }

        $this->resolveSupplierForVariant();
    }

    public function selectVariant(int $variantId): void
    {
        $this->selectedVariantId = $variantId;
        $this->resolveSupplierForVariant();
    }

    public function selectSupplierVariant(int $variantId): void
    {
        $this->selectedSupplierVariantId = $variantId;
    }

    /**
     * Mantiene el proveedor elegido si sigue siendo válido para la variante
     * actual; en caso contrario toma el principal o el primero con stock,
     * de modo que el estado real coincida siempre con el radio marcado.
     */
    private function resolveSupplierForVariant(): void
    {
        $variant = $this->product->variants->first(
            fn ($v) => (int) $v->id === (int) $this->selectedVariantId
        );

        if (! $variant) {
            return;
        }

        $suppliers = $variant->supplierVariants
            ->filter(fn ($sv) => $sv->is_active && $sv->availableStock() > 0)
            ->values();

        if ($suppliers->isEmpty()) {
            $this->selectedSupplierVariantId = null;

            return;
        }

        $current = $suppliers->first(
            fn ($sv) => (int) $sv->id === (int) $this->selectedSupplierVariantId
        );

        if ($current) {
            return;
        }

        $preferred = $suppliers->firstWhere('is_default', true) ?? $suppliers->first();

        $this->selectedSupplierVariantId = (int) $preferred->id;
    }

    private function resolveVariantFromAttributes(): void
    {
        $emptyKeys = array_keys($this->selectedAttributes, '', true);

        foreach ($emptyKeys as $key) {
            unset($this->selectedAttributes[$key]);
        }

        [$matrixVariants] = $this->variantMatrix();

        $variant = $matrixVariants->first(function ($variant) {
            $attrs = $variant->attributeValues
                ->pluck('attribute_value_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            $selected = array_values(array_map('intval', $this->selectedAttributes));

            sort($attrs);
            sort($selected);

            return $attrs === $selected;
        });

        $this->selectedVariantId = $variant?->id;

        if ($variant) {
            $this->resolveSupplierForVariant();
        } else {
            $this->selectedSupplierVariantId = null;
        }
    }

    /**
     * Un producto con atributos (talla, color, etc.) exige que el cliente
     * elija una combinación completa antes de poder agregarlo al carrito.
     */
    private function requiresVariantSelection(): bool
    {
        return $this->product->variants
            ->filter(fn ($variant) => $variant->is_active)
            ->contains(fn ($variant) => $variant->attributeValues->isNotEmpty());
    }

    public function addToCart(CartService $cart): void
    {
        $rules = [
            'selectedSupplierVariantId' => ['required'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];

        if ($this->requiresVariantSelection()) {
            $rules['selectedVariantId'] = ['required', 'integer'];
        }

        $this->validate($rules);

        if (! $this->selectedVariantId) {
            $this->addError('selectedVariantId', $this->messages['selectedVariantId.required']);

            return;
        }

        try {
            $item = $cart->add(
                $this->selectedVariantId,
                $this->selectedSupplierVariantId,
                $this->quantity,
            );

            $item->loadMissing(['variant.product', 'variant.images']);

            $thumb = $item->variant?->images->firstWhere('is_primary', true)
                ?? $item->variant?->images->first();

            $this->dispatch('cart-added', [
                'product' => $item->variant?->product?->name ?? 'Producto agregado al carrito',
                'image' => $thumb?->secure_url ?? $thumb?->url ?? '',
                'count' => (int) $cart->items()->sum('quantity'),
            ]);

            $this->dispatch('refresh-cart');
        } catch (\Throwable $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function submitReview(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $this->validate([
            'reviewRating' => ['required', 'integer', 'between:1,5'],
            'reviewComment' => ['required', 'string', 'min:5', 'max:1500'],
        ], [
            'reviewComment.required' => 'Cuéntanos qué te pareció el producto.',
            'reviewComment.min' => 'El comentario debe tener al menos 5 caracteres.',
            'reviewComment.max' => 'El comentario no puede superar 1,500 caracteres.',
        ]);

        if ($this->product->reviews()->where('user_id', auth()->id())->exists()) {
            $this->addError('reviewComment', 'Ya publicaste una opinión para este producto.');

            return;
        }

        ProductReview::create([
            'product_id' => $this->product->id,
            'user_id' => auth()->id(),
            'rating' => $this->reviewRating,
            'comment' => trim($this->reviewComment),
            'status' => ProductReview::STATUS_PENDING,
        ]);

        $this->reset('reviewComment');
        $this->reviewRating = 5;
        $this->dispatch('notify', type: 'success', message: '¡Gracias! Tu opinión será publicada tras ser revisada.');
    }

    public function render(CartService $cart)
    {
        [$variants, $attributes, $previewVariant] = $this->variantMatrix();

        $requiresVariantSelection = count($attributes) > 0;

        $requiredGroups = array_keys($attributes);
        $chosen = array_keys(array_filter(
            $this->selectedAttributes,
            fn ($x) => $x !== null && $x !== ''
        ));

        $missing = array_values(array_diff($requiredGroups, $chosen));
        $missingAttributes = collect($missing)->map(
            fn ($id) => $attributes[$id]['name'] ?? ''
        )->filter()->values();

        $selectionComplete = $missing === [];

        $cartItems = $cart->items();

        $cartCount = (int) $cartItems->sum('quantity');

        $cartSubtotal = round(
            $cartItems->sum(fn ($item) => (float) $item->unit_price * (int) $item->quantity),
            2
        );

        $currentInCart = $this->selectedVariantId
            ? $cartItems->contains(fn ($item) => (int) $item->product_variant_id === (int) $this->selectedVariantId)
            : false;

        $relatedProducts = $this->relatedProducts();

        $recentlyViewed = $this->recentlyViewed();

        $reviews = $this->product->reviews()
            ->approved()
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get();
        $reviewCount = $this->product->reviews()->approved()->count();
        $reviewAverage = $this->product->reviews()->approved()->avg('rating');
        $hasReviewed = auth()->check()
            && $this->product->reviews()->where('user_id', auth()->id())->exists();

        $schemaVariant = $this->selectedVariantId
            ? $variants->firstWhere('id', $this->selectedVariantId)
            : $previewVariant;
        $seoTitle = $this->product->seo_title ?: $this->product->name.' | Brevare';
        $seoDescription = $this->product->seo_description
            ?: ($this->product->short_description ?: Str::limit(strip_tags((string) $this->product->description), 160));
        $seoDescription = $seoDescription ?: 'Compra '.$this->product->name.' en Brevare.';
        $seoImage = $this->product->coverImage();
        $canonicalUrl = route('store.product', ['slug' => $this->product->slug]);
        if ($schemaVariant && request()->query('variant') === $schemaVariant->sku) {
            $canonicalUrl .= '?variant='.rawurlencode($schemaVariant->sku);
        }

        $productImages = collect($this->product->variants)
            ->flatMap(fn ($variant) => $variant->images
                ->where('is_active', true)
                ->map(fn ($image) => $image->imageUrl()))
            ->merge([$seoImage])
            ->filter(fn ($image) => is_string($image) && filter_var($image, FILTER_VALIDATE_URL))
            ->unique()
            ->take(10)
            ->values()
            ->all();

        $productStructuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->product->name,
            'description' => $seoDescription,
            'url' => $canonicalUrl,
        ];

        if ($productImages !== []) {
            $productStructuredData['image'] = $productImages;
        }

        if ($this->product->category?->name) {
            $productStructuredData['category'] = $this->product->category->name;
        }

        if ($this->product->brand?->name) {
            $productStructuredData['brand'] = ['@type' => 'Brand', 'name' => $this->product->brand->name];
        }

        if ($schemaVariant) {
            $productStructuredData['sku'] = $schemaVariant->sku;
            $productStructuredData['offers'] = [
                '@type' => 'Offer',
                'url' => $canonicalUrl,
                'priceCurrency' => 'PEN',
                'price' => number_format((float) $schemaVariant->sale_price, 2, '.', ''),
                'availability' => $schemaVariant->total_available > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@type' => 'Organization', 'name' => 'Brevare'],
            ];
        }

        if ($reviewCount > 0 && $reviewAverage !== null) {
            $productStructuredData['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float) $reviewAverage, 1, '.', ''),
                'reviewCount' => $reviewCount,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return view('livewire.store.product-detail', compact(
            'variants',
            'attributes',
            'previewVariant',
            'requiresVariantSelection',
            'missingAttributes',
            'selectionComplete',
            'cartItems',
            'cartCount',
            'cartSubtotal',
            'currentInCart',
            'relatedProducts',
            'recentlyViewed',
            'reviews',
            'reviewCount',
            'reviewAverage',
            'hasReviewed',
            'productStructuredData',
        ))->title($seoTitle)->layoutData([
            'seoDescription' => $seoDescription,
            'seoImage' => $seoImage,
            'canonicalUrl' => $canonicalUrl,
            'productStructuredData' => $productStructuredData,
        ]);
    }

    /**
     * Productos de la misma categoría (activos, visibles y con stock),
     * excluyendo el producto actual.
     */
    private function relatedProducts(): Collection
    {
        if (! $this->product->category_id) {
            return collect();
        }

        return Product::query()
            ->active()
            ->visible()
            ->with(['brand', 'category', 'variants.images', 'variants.supplierVariants.supplier', 'variants.attributeValues.attribute', 'variants.attributeValues.value'])
            ->withCount(['reviews' => fn ($query) => $query->approved()])
            ->withAvg(['reviews' => fn ($query) => $query->approved()], 'rating')
            ->where('id', '!=', $this->product->id)
            ->where('category_id', $this->product->category_id)
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', function ($q) {
                        $q->active()->whereColumn('stock', '>', 'reserved_stock');
                    });
            })
            ->orderByDesc('is_featured')
            ->latest()
            ->limit(4)
            ->get();
    }

    /**
     * Productos vistos recientemente (desde la sesión), en el orden de visita.
     */
    private function recentlyViewed(): Collection
    {
        $ids = array_map(
            'intval',
            (array) session('recently_viewed_product_ids', [])
        );

        $ids = array_values(array_diff($ids, [(int) $this->product->id]));

        if (count($ids) === 0) {
            return collect();
        }

        $list = array_slice($ids, 0, 6);

        $products = Product::query()
            ->active()
            ->visible()
            ->with(['brand', 'category', 'variants.images', 'variants.supplierVariants.supplier', 'variants.attributeValues.attribute', 'variants.attributeValues.value'])
            ->withCount(['reviews' => fn ($query) => $query->approved()])
            ->withAvg(['reviews' => fn ($query) => $query->approved()], 'rating')
            ->whereIn('id', $list)
            ->whereHas('variants', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('supplierVariants', function ($q) {
                        $q->active()->whereColumn('stock', '>', 'reserved_stock');
                    });
            })
            ->get()
            ->keyBy('id');

        return collect($list)
            ->map(fn ($id) => $products->get($id))
            ->filter()
            ->values();
    }

    /**
     * Construye la matriz de atributos disponibles
     * y las variantes con stock por proveedor.
     *
     * Devuelve además la variante de solo vista previa: alimenta la galería y
     * el precio, pero NO cuenta como elección del cliente, por lo que no
     * habilita el botón de agregar al carrito.
     */
    private function buildVariantMatrix(): array
    {
        $attributes = [];

        $variants = $this->product->variants
            ->filter(fn ($v) => $v->is_active)
            ->map(function ($variant) {
                $suppliers = $variant->supplierVariants
                    ->filter(fn ($sv) => $sv->is_active && $sv->availableStock() > 0)
                    ->values();

                $variant->available_providers = $suppliers;

                $variant->total_available = $suppliers->sum(fn ($sv) => $sv->availableStock());

                return $variant;
            })
            ->filter(fn ($v) => $v->total_available > 0);

        foreach ($variants as $variant) {
            foreach ($variant->attributeValues as $pivot) {
                $attributeId = (int) $pivot->attribute_id;

                if (! isset($attributes[$attributeId])) {
                    $attributes[$attributeId] = [
                        'id' => $attributeId,
                        'name' => $pivot->attribute?->name ?? 'Atributo',
                        'values' => [],
                    ];
                }

                $attributes[$attributeId]['values'][(int) $pivot->attribute_value_id] = [
                    'id' => (int) $pivot->attribute_value_id,
                    'value' => $pivot->value?->value ?? '—',
                    'color' => $pivot->value?->color,
                ];
            }
        }

        $previewVariant = null;

        if ($this->selectedVariantId === null) {
            $candidate = $variants
                ->sortByDesc('is_default')
                ->first(fn ($variant) => $variant->images->isNotEmpty())
                ?? $variants->first();

            if ($candidate && $attributes === []) {
                // Sin atributos no hay nada que elegir: se toma la única
                // variante disponible para poder comprar directo.
                $this->selectedVariantId = (int) $candidate->id;
                $this->resolveSupplierForVariant();
            } else {
                $previewVariant = $candidate;
            }
        }

        return [$variants->values(), $attributes, $previewVariant];
    }
}
