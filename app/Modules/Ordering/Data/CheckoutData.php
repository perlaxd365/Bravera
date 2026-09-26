<?php

namespace App\Modules\Ordering\Data;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Collection;

class CheckoutData
{
    /**
     * @param  Collection<int, CartItem>  $items
     * @param  array<int, float>  $shippingPerItem  itemId => tarifa de envío cobrada
     */
    public function __construct(
        public readonly User $user,
        public readonly Cart $cart,
        public readonly Collection $items,
        public readonly CustomerAddress $address,
        public readonly float $subtotal,
        public readonly float $shippingTotal,
        public readonly float $discountTotal,
        public readonly float $total,
        public readonly float $costTotal,
        public readonly array $shippingPerItem,
        public readonly ?Coupon $coupon = null,
        public readonly ?string $notes = null,
    ) {}
}
