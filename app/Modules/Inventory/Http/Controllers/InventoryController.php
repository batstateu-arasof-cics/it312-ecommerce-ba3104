<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\Request;

// Inventory team (Group 8): admin stock page (Must 3). Admin only, see routes.php
class InventoryController extends Controller
{
    // GET /admin/inventory - shows the stock of every product
    public function index()
    {
        // lowest stock first, so the products that need restocking are on top
        $products = Product::orderBy('stock')->orderBy('name')->get();
        $threshold = config('inventory.low_stock_threshold');

        return view('inventory::index', compact('products', 'threshold'));
    }

    // POST /admin/inventory/{product}/restock - adds stock to one product
    public function restock(Request $request, Product $product, InventoryService $inventory)
    {
        // the quantity must be a whole number from 1 to 10000
        $request->validate([
            'quantity' => 'required|integer|min:1|max:10000',
        ]);

        $quantity = (int) $request->quantity;
        $inventory->restock($product, $quantity, auth()->id());

        $newStock = $product->fresh()->stock;

        return redirect()->route('admin.inventory.index')
            ->with('success', "Added $quantity to {$product->name}. New stock: $newStock.");
    }
}
