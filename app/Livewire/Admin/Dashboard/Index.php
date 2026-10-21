<?php

namespace App\Livewire\Admin\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupplierOrder;
use App\Models\SupplierVariant;
use App\Models\User;
use App\Modules\Product\Services\ProductReadinessService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    public function boot(ProductReadinessService $readiness): void
    {
        $this->readiness = $readiness;
    }

    protected ProductReadinessService $readiness;

    public function render()
    {
        // Ingresos y margen (pedidos pagados, no cancelados)
        $paid = Order::query()
            ->whereNotIn('status', [OrderStatus::CANCELLED->value, OrderStatus::REFUNDED->value])
            ->where('payment_status', PaymentStatus::PAID);

        $revenue = (float) (clone $paid)->sum('total');
        $margins = (clone $paid)->get()->sum(fn ($o) => $o->margin());

        $ordersToday = Order::query()
            ->whereDate('created_at', today())
            ->count();

        $customers = User::query()
            ->where('is_guest', false)
            ->whereHas('roles', fn ($query) => $query->where('name', 'Cliente'));

        $customersToday = (clone $customers)
            ->whereDate('created_at', today())
            ->count();

        $customersYesterday = (clone $customers)
            ->whereDate('created_at', today()->subDay())
            ->count();

        $newCustomers = [
            'today' => $customersToday,
            'yesterday' => $customersYesterday,
            'total' => (clone $customers)->count(),
            'change' => $customersToday - $customersYesterday,
        ];

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

        $pendingPublication = $this->readiness->pendingPublicationCount();

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
                'ordersToday' => $ordersToday,
                'completed' => Order::where('status', OrderStatus::DELIVERED)->count(),
            ],
            'newCustomers' => $newCustomers,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'pendingSupplierOrders' => $pendingSupplierOrders,
            'pendingSupplierAmount' => $pendingSupplierAmount,
            'pendingPublication' => $pendingPublication,
            'recentOrders' => $recentOrders,
            'bestProducts' => $bestProducts,
        ]);
    }
}
