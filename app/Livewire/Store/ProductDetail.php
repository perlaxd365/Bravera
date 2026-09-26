<?php

namespace App\Livewire\Store;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class ProductDetail extends Component
{
    public ?Product $product = null;

    public int $quantity = 1;

    public ?int $selectedVariantId = null;

    public array $selectedAttributes = [];

    public ?int $selectedSupplierVariantId = null;

    protected array $messages = [
        'quantity.min' => 'La cantidad mínima es 1.',
        'selectedVariantId.required' => 'Selecciona una variante del producto.',
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

    public function updatedSelectedAttributes(): void
    {
        $this->resolveVariantFromAttributes();
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
     * Auto-selecciona el proveedor principal cuando la variante elegida
     * tiene un único proveedor con stock disponible.
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

        if ($suppliers->count() === 1) {
            $this->selectedSupplierVariantId = (int) $suppliers->first()->id;
        }
    }

    private function resolveVariantFromAttributes(): void
    {
        $emptyKeys = array_keys($this->selectedAttributes, '', true);

        foreach ($emptyKeys as $key) {
            unset($this->selectedAttributes[$key]);
        }

        if (count($this->selectedAttributes) === 0) {
            $this->selectedVariantId = null;
            $this->selectedSupplierVariantId = null;

            return;
        }

        $variant = $this->product->variants->first(function ($variant) {
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

    public function addToCart(CartService $cart): void
    {
        $this->validate([
            'selectedSupplierVariantId' => ['required'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

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

    public function render(CartService $cart)
    {
        [$variants, $attributes] = $this->buildVariantMatrix();

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

        return view('livewire.store.product-detail', compact(
            'variants',
            'attributes',
            'cartItems',
            'cartCount',
            'cartSubtotal',
            'currentInCart',
            'relatedProducts',
            'recentlyViewed',
        ));
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
            ->with(['brand', 'category', 'variants.images', 'variants.supplierVariants'])
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
            ->with(['brand', 'category', 'variants.images', 'variants.supplierVariants'])
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

        // Al entrar sin selección, se preselecciona la primera variante
        // disponible que tenga fotos (prefiriendo la variante por defecto),
        // para que la galería nunca quede vacía.
        if ($this->selectedVariantId === null) {
            $initial = $variants
                ->sortByDesc('is_default')
                ->first(fn ($variant) => $variant->images->isNotEmpty())
                ?? $variants->first();

            if ($initial) {
                $this->selectedVariantId = (int) $initial->id;
                $this->resolveSupplierForVariant();

                if (count($this->selectedAttributes) === 0) {
                    $this->selectedAttributes = $initial->attributeValues
                        ->mapWithKeys(
                            fn ($pivot) => [(int) $pivot->attribute_id => (int) $pivot->attribute_value_id]
                        )
                        ->toArray();
                }
            }
        }

        return [$variants->values(), array_values($attributes)];
    }
}
