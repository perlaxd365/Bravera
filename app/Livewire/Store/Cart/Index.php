<?php

namespace App\Livewire\Store\Cart;

use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('store.layouts.app')]
class Index extends Component
{
    public array $quantities = [];

    public function mount(CartService $cart): void
    {
        foreach ($cart->items() as $item) {
            $this->quantities[$item->id] = $item->quantity;
        }
    }

    public function updateQuantity(int $itemId, CartService $cart): void
    {
        try {
            $cart->updateQuantity($itemId, (int) $this->quantities[$itemId]);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Carrito actualizado.']);
            $this->dispatch('refresh-cart');
        } catch (\Throwable $e) {
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

        return view('livewire.store.cart.index', compact('items', 'subtotal'));
    }
}
