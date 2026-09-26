<?php

namespace App\Livewire\Account;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.account')]
class Orders extends Component
{
    public function render()
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->with('payment')
            ->orderByDesc('created_at')
            ->paginate(10);

        $stats = [
            'total' => Order::where('user_id', auth()->id())->count(),
            'spent' => Order::where('user_id', auth()->id())->sum('total'),
            'latest' => Order::where('user_id', auth()->id())->latest('created_at')->value('created_at'),
        ];

        return view('livewire.account.orders', compact('orders', 'stats'));
    }
}
