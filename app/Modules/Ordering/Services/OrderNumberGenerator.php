<?php

namespace App\Modules\Ordering\Services;

use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    /**
     * Genera el siguiente número de pedido:
     * BRV-20260920-0001
     */
    public function next(): string
    {
        $today = now()->format('Ymd');

        $prefix = 'BRV-'.$today.'-';

        $last = DB::table('orders')
            ->where('order_number', 'like', $prefix.'%')
            ->orderByDesc('order_number')
            ->value('order_number');

        $sequence = $last
            ? ((int) substr($last, -4)) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
