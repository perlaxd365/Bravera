<?php

namespace App\Livewire\Store\Components;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartBadge extends Component
{
    public int $count = 0;

    public float $subtotal = 0;

    public function boot(CartService $cart): void
    {
        $this->refresh();
    }

    #[On('refresh-cart')]
    public function refresh(): void
    {
        $cart = app(CartService::class);

        $this->count = $cart->count();
        $this->subtotal = $cart->subtotal();
    }

    public function render()
    {
        return view('livewire.store.components.cart-badge');
    }
}
