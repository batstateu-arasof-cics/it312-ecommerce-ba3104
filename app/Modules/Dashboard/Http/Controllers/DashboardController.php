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

    // TODO (Dashboard team): export(Request $request) - same report as a CSV download
}
