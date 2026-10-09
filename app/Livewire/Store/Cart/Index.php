<?php

namespace App\Livewire\Store\Cart;

use App\Services\CartService;
use App\Modules\Ordering\Services\OrderService;
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
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Producto eliminado del carrito.']);
        $this->dispatch('refresh-cart');
    }

    #[On('refresh-cart')]
    public function refreshCart(): void
    {
        $cart = app(CartService::class);

        $this->quantities = $cart->items()
            ->mapWithKeys(fn ($item) => [$item->id => $item->quantity])
            ->toArray();
    }

    public function render(CartService $cart)
    {
        $items = $cart->items();
        $subtotal = $cart->subtotal();
        $productDiscount = round($items->sum(function ($item) {
            $regularPrice = max(
                (float) ($item->variant?->compare_price ?? $item->unit_price),
                (float) $item->unit_price
            );

            return ($regularPrice - (float) $item->unit_price) * (int) $item->quantity;
        }), 2);

        return view('livewire.store.cart.index', compact('items', 'subtotal', 'productDiscount'));
    }
}
