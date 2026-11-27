@extends('layouts.app')

@section('title', 'Sales report')

@section('content')
<h1 class="text-2xl font-bold mb-6">Sales report</h1>

{{-- date filter, sends ?from= and ?to= back to this page --}}
<form method="GET" action="{{ route('admin.reports.sales') }}" class="bg-white rounded shadow p-4 mb-6 flex flex-wrap items-end gap-4">
    <label class="text-sm">
        <span class="block text-gray-500 mb-1">From</span>
        <input type="date" name="from" value="{{ $from }}" class="rounded border px-2 py-1">
    </label>
    <label class="text-sm">
        <span class="block text-gray-500 mb-1">To</span>
        <input type="date" name="to" value="{{ $to }}" class="rounded border px-2 py-1">
    </label>
    <button class="rounded bg-gray-900 text-white px-4 py-1.5 text-sm">Show report</button>
    {{-- download the same dates as a CSV file --}}
    <a href="{{ route('admin.reports.export', ['from' => $from, 'to' => $to]) }}"
       class="rounded border px-4 py-1.5 text-sm hover:bg-gray-100">Download CSV</a>
    @if ($from || $to)
        <a href="{{ route('admin.reports.sales') }}" class="text-sm text-gray-500 hover:underline">Clear dates</a>
    @endif
</form>

<p class="text-sm text-gray-500 mb-4">
    Showing {{ $from ? 'from '.$from : 'from the first order' }} {{ $to ? 'to '.$to : 'to today' }}. Cancelled orders are not counted in sales.
</p>

{{-- totals for the chosen dates --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded shadow p-4">
        <p class="text-sm text-gray-500">Total sales</p>
        <p class="text-2xl font-bold">₱{{ number_format($summary['total_sales'], 2) }}</p>
    </div>
    <div class="bg-white rounded shadow p-4">
        <p class="text-sm text-gray-500">Orders</p>
        <p class="text-2xl font-bold">{{ $summary['order_count'] }}</p>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    {{-- one row for each day that had sales --}}
    <section class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-3">Sales per day</h2>
        @if ($daily->isEmpty())
            <p class="text-sm text-gray-500">No sales in this range.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr><th class="py-1">Date</th><th class="py-1">Orders</th><th class="py-1 text-right">Sales</th></tr>
                </thead>
                <tbody>
                    @foreach ($daily as $row)
                        <tr class="border-t">
                            <td class="py-1">{{ $row['date'] }}</td>
                            <td class="py-1">{{ $row['order_count'] }}</td>
                            <td class="py-1 text-right">₱{{ number_format($row['total_sales'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    {{-- best sellers and orders per status for the same dates --}}
    <section class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-3">Top products</h2>
        @forelse ($summary['top_products'] as $i => $product)
            <p class="text-sm">{{ $i + 1 }}. {{ $product['name'] }} — {{ $product['quantity_sold'] }} sold (₱{{ number_format($product['revenue'], 2) }})</p>
        @empty
            <p class="text-sm text-gray-500">No sales in this range.</p>
        @endforelse

        <h2 class="font-semibold mt-6 mb-3">Orders by status</h2>
        @foreach ($summary['orders_by_status'] as $status => $count)
            <p class="text-sm">{{ ucfirst($status) }}: {{ $count }}</p>
        @endforeach
    </section>
</div>
@endsection
