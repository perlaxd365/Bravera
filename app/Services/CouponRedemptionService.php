<?php

namespace App\Services;

use App\Models\CouponUsage;
use App\Models\Order;

class CouponRedemptionService
{
    public function recordPaidOrder(Order $order): void
    {
        if (! $order->coupon_id || (float) $order->discount_total <= 0) {
            return;
        }

        CouponUsage::query()->firstOrCreate(
            [
                'coupon_id' => $order->coupon_id,
                'order_id' => $order->id,
            ],
            [
                'user_id' => $order->user_id,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_total,
            ],
        );
    }
}
