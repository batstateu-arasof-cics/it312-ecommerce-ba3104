<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Contracts\InventoryServiceInterface;
use App\Contracts\ReportServiceInterface;
use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\ReportService;
use Illuminate\Http\Request;

// Dashboard team (Group 8): admin dashboard and sales report. Admin only, see routes.php
class DashboardController extends Controller
{
    public function index(ReportServiceInterface $reports, InventoryServiceInterface $inventory)
    {
        $summary = $reports->salesSummary();
        $lowStock = $inventory->lowStock();

        return view('dashboard::index', compact('summary', 'lowStock'));
    }

    // Must 2: GET /admin/reports/sales?from=2026-03-01&to=2026-03-31
    public function report(Request $request, ReportService $reports)
    {
        // both dates are optional, and "to" can't be before "from"
        $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
        ]);

        $from = $request->from;
        $to = $request->to;

        $summary = $reports->salesSummary($from, $to);
        $daily = $reports->dailySales($from, $to);

        return view('dashboard::reports.sales', compact('summary', 'daily', 'from', 'to'));
    }

    // Must 3: GET /admin/reports/sales/export - the same report as a CSV file
    public function export(Request $request, ReportService $reports)
    {
        $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
        ]);

        $from = $request->from;
        $to = $request->to;

        $summary = $reports->salesSummary($from, $to);
        $daily = $reports->dailySales($from, $to);

        // build the CSV text: a header, one line per day, then the total
        $csv = "Date,Orders,Sales\n";

        foreach ($daily as $row) {
            $csv .= $row['date'].','.$row['order_count'].','.number_format($row['total_sales'], 2, '.', '')."\n";
        }

        $csv .= 'Total,'.$summary['order_count'].','.number_format($summary['total_sales'], 2, '.', '')."\n";

        // e.g. sales-report-2026-03-01-to-2026-03-31.csv
        $filename = 'sales-report-'.($from ?: 'start').'-to-'.($to ?: 'today').'.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
