<?php

namespace App\Modules\Dashboard\Services;

use App\Contracts\ReportServiceInterface;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use Illuminate\Support\Collection;

// Dashboard team (Group 8): sales figures for the admin dashboard and reports.
// Dates are 'Y-m-d' strings, null means no limit on that side.
class ReportService implements ReportServiceInterface
{
    public function salesSummary(?string $from = null, ?string $to = null): array
    {
        $orders = Order::where('status', '!=', Order::CANCELLED)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        // TODO (Dashboard team, Stretch): average_order_value

        return [
            'total_sales' => (float) (clone $orders)->sum('total'),
            'order_count' => (clone $orders)->count(),
            'orders_by_status' => $this->ordersByStatus($from, $to),
            'top_products' => $this->topProducts($from, $to),
        ];
    }

    // Must 1: how many orders are in each status, e.g. ['pending' => 2, 'paid' => 1, ...]
    public function ordersByStatus(?string $from = null, ?string $to = null): array
    {
        $counts = [];

        foreach (Order::STATUSES as $status) {
            $counts[$status] = Order::where('status', $status)
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->count();
        }

        return $counts;
    }

    // Must 1: the best selling products by quantity (cancelled orders don't count)
    public function topProducts(?string $from = null, ?string $to = null, int $limit = 5): Collection
    {
        // get every item from the orders that are not cancelled
        $items = OrderItem::whereHas('order', function ($q) use ($from, $to) {
            $q->where('status', '!=', Order::CANCELLED)
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
        })->get();

        // add up the quantity and money of each product
        $products = [];

        foreach ($items as $item) {
            $id = $item->product_id;

            if (! isset($products[$id])) {
                $products[$id] = [
                    'product_id' => $id,
                    'name' => $item->product_name,
                    'quantity_sold' => 0,
                    'revenue' => 0.0,
                ];
            }

            $products[$id]['quantity_sold'] += $item->quantity;
            $products[$id]['revenue'] += $item->quantity * $item->unit_price;
        }

        // best seller first, then keep only the top 5
        return collect($products)->sortByDesc('quantity_sold')->take($limit)->values();
    }

    // Must 2: sales for each day in the range (cancelled orders don't count), oldest day first
    public function dailySales(?string $from = null, ?string $to = null): Collection
    {
        $orders = Order::where('status', '!=', Order::CANCELLED)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderBy('created_at')
            ->get();

        // group the orders by date and add up the count and the money
        $days = [];

        foreach ($orders as $order) {
            $date = $order->created_at->format('Y-m-d');

            if (! isset($days[$date])) {
                $days[$date] = [
                    'date' => $date,
                    'order_count' => 0,
                    'total_sales' => 0.0,
                ];
            }

            $days[$date]['order_count']++;
            $days[$date]['total_sales'] += $order->total;
        }

        return collect($days)->values();
    }
}
