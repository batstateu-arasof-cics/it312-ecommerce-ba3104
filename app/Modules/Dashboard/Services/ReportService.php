<?php

namespace App\Modules\Dashboard\Services;

use App\Contracts\ReportServiceInterface;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Dashboard team (Group 8): all sales figures for the admin dashboard and reports.
 * Reads orders only (never writes). Dates are 'Y-m-d' strings; null means no limit on that side.
 */
class ReportService implements ReportServiceInterface
{
    /**
     * Totals for a date range: total_sales and order_count (cancelled orders left out),
     * plus orders_by_status and top_products (Dashboard Must 1).
     */
    public function salesSummary(?string $from = null, ?string $to = null): array
    {
        // Orders in the date range that still count as sales.
        $orders = $this->ordersBetween($from, $to)->where('status', '!=', Order::CANCELLED);

        // TODO (Dashboard team, Stretch): average_order_value and comparison with the previous period

        // clone: each figure gets its own copy of the query so they don't affect each other.
        return [
            'total_sales' => (float) (clone $orders)->sum('total'),
            'order_count' => (clone $orders)->count(),
            'orders_by_status' => $this->ordersByStatus($from, $to),
            'top_products' => $this->topProducts($from, $to),
        ];
    }

    /** Number of orders in each status (cancelled included), every status listed even when 0. */
    public function ordersByStatus(?string $from = null, ?string $to = null): array
    {
        // One database query: count orders per status, e.g. ['pending' => 2, 'paid' => 1].
        $counts = $this->ordersBetween($from, $to)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        // Go through every known status so ones with no orders still show as 0.
        $result = [];
        foreach (Order::STATUSES as $status) {
            $result[$status] = (int) ($counts[$status] ?? 0);
        }

        return $result;
    }

    /** Best sellers by quantity sold, cancelled orders left out. */
    public function topProducts(?string $from = null, ?string $to = null, int $limit = 5): Collection
    {
        return OrderItem::query()
            // Only items from orders in the range that were not cancelled.
            ->whereHas('order', function (Builder $q) use ($from, $to) {
                $q->where('status', '!=', Order::CANCELLED);
                $this->applyDateRange($q, $from, $to);
            })
            // Add up quantity and money per product, best seller first (ties sorted by name).
            ->selectRaw('product_id, product_name, SUM(quantity) as quantity_sold, SUM(quantity * unit_price) as revenue')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('quantity_sold')
            ->orderBy('product_name')
            ->limit($limit)
            ->get()
            // Turn each row into a simple array for the views.
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'name' => $row->product_name,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (float) $row->revenue,
            ]);
    }

    /** All orders created between $from and $to (any status). */
    private function ordersBetween(?string $from, ?string $to): Builder
    {
        return $this->applyDateRange(Order::query(), $from, $to);
    }

    /** Adds "created on or after $from" and "on or before $to" to a query; skips a side that is null. */
    private function applyDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
    }
}
