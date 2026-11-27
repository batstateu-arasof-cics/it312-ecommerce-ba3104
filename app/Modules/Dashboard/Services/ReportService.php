<?php

namespace App\Modules\Dashboard\Services;

use App\Contracts\ReportServiceInterface;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService implements ReportServiceInterface
{
    public function salesSummary(?string $from = null, ?string $to = null): array
    {
        $orders = $this->ordersBetween($from, $to)->where('status', '!=', Order::CANCELLED);

        // TODO (Dashboard team, Stretch): average_order_value and comparison with the previous period

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
        $counts = $this->ordersBetween($from, $to)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

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
            ->whereHas('order', function (Builder $q) use ($from, $to) {
                $q->where('status', '!=', Order::CANCELLED);
                $this->applyDateRange($q, $from, $to);
            })
            ->selectRaw('product_id, product_name, SUM(quantity) as quantity_sold, SUM(quantity * unit_price) as revenue')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('quantity_sold')
            ->orderBy('product_name')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'name' => $row->product_name,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (float) $row->revenue,
            ]);
    }

    private function ordersBetween(?string $from, ?string $to): Builder
    {
        return $this->applyDateRange(Order::query(), $from, $to);
    }

    private function applyDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
    }
}
