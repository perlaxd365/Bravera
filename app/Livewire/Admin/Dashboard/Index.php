<?php

namespace App\Livewire\Admin\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PageView;
use App\Models\SupplierOrder;
use App\Models\SupplierVariant;
use App\Models\User;
use App\Modules\Product\Services\ProductReadinessService;
use App\Services\GoogleAnalyticsService;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    public function boot(
        ProductReadinessService $readiness,
        GoogleAnalyticsService $analytics,
    ): void {
        $this->readiness = $readiness;
        $this->analytics = $analytics;
    }

    protected ProductReadinessService $readiness;

    protected GoogleAnalyticsService $analytics;

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

        $newCustomers = [
            'total' => (clone $customers)->count(),
        ];

        $latestCustomer = (clone $customers)
            ->latest('created_at')
            ->first();

        $traffic = [
            'visitorsToday' => 0,
            'visitorsYesterday' => 0,
            'pageViewsToday' => 0,
            'activeNow' => 0,
        ];

        if (Schema::hasTable('page_views')) {
            $viewsToday = PageView::query()
                ->where('viewed_at', '>=', today())
                ->where('viewed_at', '<', today()->addDay());

            $traffic = [
                'visitorsToday' => (clone $viewsToday)
                    ->distinct()
                    ->count('visitor_token'),
                'visitorsYesterday' => PageView::query()
                    ->where('viewed_at', '>=', today()->subDay())
                    ->where('viewed_at', '<', today())
                    ->distinct()
                    ->count('visitor_token'),
                'pageViewsToday' => (clone $viewsToday)->count(),
                'activeNow' => 0,
            ];
        }

        $trafficSource = 'local';

        if ($this->analytics->isConfigured()) {
            try {
                $traffic = $this->analytics->traffic();
                $trafficSource = 'ga4';
            } catch (Throwable $exception) {
                report($exception);
            }
        }

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
            'latestCustomer' => $latestCustomer,
            'traffic' => $traffic,
            'trafficSource' => $trafficSource,
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
