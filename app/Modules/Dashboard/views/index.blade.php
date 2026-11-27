@extends('layouts.app')

@section('title', 'Admin dashboard')

@section('content')
<h1 class="text-2xl font-bold mb-6">Admin dashboard</h1>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded shadow p-4">
        <p class="text-sm text-gray-500">Total sales</p>
        <p class="text-2xl font-bold">₱{{ number_format($summary['total_sales'], 2) }}</p>
    </div>
    <div class="bg-white rounded shadow p-4">
        <p class="text-sm text-gray-500">Orders</p>
        <p class="text-2xl font-bold">{{ $summary['order_count'] }}</p>
    </div>
    {{-- TODO (Dashboard team, Stretch): average order value --}}
</div>

{{-- Must 1: one box for each order status --}}
<section class="bg-white rounded shadow p-4 mb-6">
    <h2 class="font-semibold mb-3">Orders by status</h2>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        @foreach ($summary['orders_by_status'] as $status => $count)
            <div class="rounded border p-3">
                <p class="text-xs text-gray-500">{{ ucfirst($status) }}</p>
                <p class="text-xl font-bold">{{ $count }}</p>
            </div>
        @endforeach
    </div>
</section>

<div class="grid md:grid-cols-2 gap-6">
    <section class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-2">Low stock</h2>
        @forelse ($lowStock as $product)
            <p class="text-sm">{{ $product->name }} — {{ $product->stock }} left</p>
        @empty
            <p class="text-sm text-gray-500">No low-stock products.</p>
        @endforelse
    </section>

    {{-- Must 1: top 5 products by quantity sold --}}
    <section class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-2">Top products</h2>
        @forelse ($summary['top_products'] as $i => $product)
            <p class="text-sm">{{ $i + 1 }}. {{ $product['name'] }} — {{ $product['quantity_sold'] }} sold</p>
        @empty
            <p class="text-sm text-gray-500">No sales yet.</p>
        @endforelse
    </section>
</div>

{{-- TODO (Dashboard team): links to admin pages of other teams (products, orders, inventory, vouchers, reviews) as they are merged --}}
@endsection
