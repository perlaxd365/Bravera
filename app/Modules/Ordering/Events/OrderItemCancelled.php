<?php

namespace App\Modules\Ordering\Events;

use App\Enums\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderItemCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderItem $item,
        public OrderItemStatus $previousStatus = OrderItemStatus::ACTIVE,
        public bool $wholeOrderCancelled = false,
    ) {}
}
