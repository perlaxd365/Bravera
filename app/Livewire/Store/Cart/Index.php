<?php

namespace App\Livewire\Store\Cart;

use App\Models\ProductVariant;
use App\Modules\Ordering\Services\OrderService;
use App\Modules\Product\Repositories\StoreCatalogRepository;
use App\Services\CartService;
use App\Services\MetaConversionsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('store.layouts.app')]
class Index extends Component
{
    public array $quantities = [];

    public function mount(CartService $cart, OrderService $orders): void
    {
        $cart->restorePendingCartForEditing($orders);
        $cart->currentCart()
            ->forceFill(['last_activity_at' => now()])
            ->saveQuietly();

        foreach ($cart->items() as $item) {
            $this->quantities[$item->id] = $item->quantity;
        }
    }

    public function updateQuantity(int $itemId, CartService $cart): void
    {
        $quantity = max(1, (int) ($this->quantities[$itemId] ?? 1));
        $this->quantities[$itemId] = $quantity;

        $this->persistQuantity($itemId, $quantity, $cart);
    }

    public function adjustQuantity(int $itemId, int $amount, CartService $cart): void
    {
        $quantity = max(1, (int) ($this->quantities[$itemId] ?? 1) + $amount);
        $this->quantities[$itemId] = $quantity;

        $this->persistQuantity($itemId, $quantity, $cart);
    }

    private function persistQuantity(int $itemId, int $quantity, CartService $cart): void
    {
        try {
            $cart->updateQuantity($itemId, $quantity);
            $this->dispatch('refresh-cart');
        } catch (\Throwable $e) {
            $currentItem = $cart->items()->firstWhere('id', $itemId);

            if ($currentItem) {
                $this->quantities[$itemId] = $currentItem->quantity;
            }

            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function removeItem(int $itemId, CartService $cart): void
    {
        $cart->remove($itemId);
        unset($this->quantities[$itemId]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('storefront.cart.removed')]);
        $this->dispatch('refresh-cart');
    }

    public function addRecommendedProduct(int $variantId, CartService $cart): void
    {
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->active()->visible())
            ->firstOrFail();

        try {
            $item = $cart->add($variant->id, null, 1);
            $item->loadMissing(['variant.product', 'variant.images']);

            $this->quantities[$item->id] = $item->quantity;
            $this->dispatch('refresh-cart');

            $metaData = [
                'content_ids' => [(string) ($item->variant?->sku ?? $item->product_variant_id)],
                'content_name' => $item->variant?->product?->name,
                'content_type' => 'product',
                'value' => round((float) $item->unit_price * (int) $item->quantity, 2),
                'currency' => 'PEN',
                'quantity' => (int) $item->quantity,
                'contents' => [[
                    'id' => (string) ($item->variant?->sku ?? $item->product_variant_id),
                    'quantity' => (int) $item->quantity,
                    'item_price' => round((float) $item->unit_price, 2),
                ]],
            ];
            $eventId = implode('_', [
                'add_to_cart',
                $item->cart_id,
                $item->id,
                $item->quantity,
                $item->updated_at?->timestamp,
            ]);

            $this->dispatch('brevare-meta-event', [
                'name' => 'AddToCart',
                'data' => $metaData,
                'event_id' => $eventId,
            ]);
            app(MetaConversionsService::class)->track('AddToCart', $eventId, $metaData);

            $image = $item->variant?->images->firstWhere('is_primary', true)
                ?? $item->variant?->images->first();

            $this->dispatch('cart-added', [
                'product' => $item->variant?->product?->name ?? 'Producto agregado',
                'image' => $image?->secure_url ?? $image?->url ?? '',
                'count' => (int) $cart->items()->sum('quantity'),
            ]);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('storefront.cart.added_recommendation'),
            ]);
        } catch (\Throwable $exception) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    #[On('refresh-cart')]
    public function refreshCart(): void
    {
        $cart = app(CartService::class);

        $this->quantities = $cart->items()
            ->mapWithKeys(fn ($item) => [$item->id => $item->quantity])
            ->toArray();
    }

    public function render(CartService $cart, StoreCatalogRepository $catalog)
    {
        $items = $cart->items();
        $subtotal = $cart->subtotal();
        $productDiscount = round($items->sum(function ($item) {
            $wholesaleActive = $item->variant?->appliesWholesalePrice((int) $item->quantity) ?? false;
            $retailPrice = (float) ($item->variant?->sale_price ?? $item->unit_price);
            $regularPrice = $wholesaleActive
                ? $retailPrice
                : max(
                    (float) ($item->variant?->compare_price ?? $retailPrice),
                    $retailPrice
                );

            return ($regularPrice - (float) $item->unit_price) * (int) $item->quantity;
        }), 2);

        $currentProductIds = $items
            ->map(fn ($item) => (int) ($item->variant?->product_id ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $categoryIds = $items
            ->map(fn ($item) => (int) ($item->variant?->product?->category_id ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $recommendations = $catalog->cartRecommendations(
            excludeProductIds: $currentProductIds,
            categoryIds: $categoryIds,
            limit: 4,
        )->map(function ($product): ?array {
            $variant = $product->variants->first(function ($variant): bool {
                return $variant->is_active
                    && $variant->supplierVariants->contains(fn ($supplierVariant) => $supplierVariant->is_active
                        && $supplierVariant->availableStock() > 0);
            });

            if (! $variant) {
                return null;
            }

            $image = $variant->images->firstWhere('is_primary', true)
                ?? $variant->images->first();

            return [
                'product' => $product,
                'variant' => $variant,
                'image' => $image?->secure_url ?? $image?->url,
            ];
        })->filter()->values();

        return view('livewire.store.cart.index', compact(
            'items',
            'subtotal',
            'productDiscount',
            'recommendations',
        ));
    }
}
