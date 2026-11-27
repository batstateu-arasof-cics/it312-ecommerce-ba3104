@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
{{-- admin menu from the Dashboard module (shows once it is merged) --}}
@includeIf('dashboard::partials.admin-menu')

<h1 class="text-2xl font-bold mb-2">Inventory</h1>
<p class="text-sm text-gray-500 mb-6">Products with {{ $threshold }} or fewer in stock are marked low.</p>

{{-- one row per product, lowest stock first --}}
<div class="bg-white rounded shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Product</th>
                <th class="px-4 py-2">Stock</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Restock</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $product->name }}</td>
                    <td class="px-4 py-2 font-semibold">{{ $product->stock }}</td>
                    <td class="px-4 py-2">
                        @if ($product->stock <= $threshold)
                            <span class="rounded bg-red-100 text-red-800 px-2 py-0.5 text-xs">Low stock</span>
                        @else
                            <span class="rounded bg-green-100 text-green-800 px-2 py-0.5 text-xs">OK</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        {{-- restock form, handled by InventoryController@restock --}}
                        <form method="POST" action="{{ route('admin.inventory.restock', $product) }}" class="flex gap-2">
                            @csrf
                            <input type="number" name="quantity" min="1" max="10000" value="10" required
                                   class="w-20 rounded border px-2 py-1">
                            <button class="rounded bg-gray-900 text-white px-3 py-1">Add</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
