<?php

namespace App\Livewire\Admin\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupplierOrder;
use App\Models\SupplierVariant;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    public function render()
    {
        // Ingresos y margen (pedidos pagados, no cancelados)
        $paid = Order::query()
            ->whereNotIn('status', [OrderStatus::CANCELLED->value, OrderStatus::REFUNDED->value])
            ->where('payment_status', PaymentStatus::PAID);

        $revenue = (float) (clone $paid)->sum('total');
        $margins = (clone $paid)->get()->sum(fn ($o) => $o->margin());

        $today = Order::query()
            ->whereDate('created_at', today())
            ->count();

        $lowStock = SupplierVariant::query()
            ->where('stock', '<=', 5)
            ->where('stock', '>', 0)
            ->count();

        $outOfStock = SupplierVariant::query()
            ->where('stock', 0)
            ->count();

        $pendingSupplierOrders = SupplierOrder::query()
            ->where('status', 'pending')
            ->count();

        $pendingSupplierAmount = (float) SupplierOrder::query()
            ->where('status', 'pending')
            ->sum('total_cost');

        $recentOrders = Order::query()
            ->with(['items', 'user'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $bestProducts = OrderItem::query()
            ->selectRaw('product_name, SUM(quantity) as total_qty, SUM(line_subtotal) as total_sales')
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return view('livewire.admin.dashboard.index', [
            'kpis' => [
                'revenue' => $revenue,
                'margins' => $margins,
                'orders' => Order::count(),
                'ordersToday' => $today,
                'completed' => Order::where('status', OrderStatus::DELIVERED)->count(),
            ],
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'pendingSupplierOrders' => $pendingSupplierOrders,
            'pendingSupplierAmount' => $pendingSupplierAmount,
            'recentOrders' => $recentOrders,
            'bestProducts' => $bestProducts,
        ]);
    }
}
