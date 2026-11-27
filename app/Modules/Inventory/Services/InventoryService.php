<?php

namespace App\Modules\Inventory\Services;

use App\Contracts\InventoryServiceInterface;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Inventory team (Group 8): the only class that changes products.stock.
// Every change is also saved in stock_movements so we have a history.
class InventoryService implements InventoryServiceInterface
{
    public function deductStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if (! $product) {
                    continue;
                }

                $product->decrement('stock', min($item->quantity, $product->stock));

                StockMovement::create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'change' => -$item->quantity,
                    'reason' => 'sale',
                ]);

                // TODO (Inventory team, Must 2): after the transaction, StockLow::dispatch($product) for every product
                // whose stock is now <= config('inventory.low_stock_threshold')
            }
        });
    }

    // Must 1: put the items of a cancelled order back into stock (only once per order)
    public function restoreStock(Order $order): void
    {
        // if this order already has a 'cancel' movement, the stock was already restored
        $alreadyRestored = StockMovement::where('order_id', $order->id)
            ->where('reason', 'cancel')
            ->exists();

        if ($alreadyRestored) {
            return;
        }

        // save all the changes together, so it's all or nothing
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);

                // skip the item if the product was deleted
                if (! $product) {
                    continue;
                }

                // add the quantity back and record it in the history
                $product->increment('stock', $item->quantity);

                StockMovement::create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'change' => $item->quantity,
                    'reason' => 'cancel',
                ]);
            }
        });
    }

    public function lowStock(): Collection
    {
        return Product::active()
            ->where('stock', '<=', config('inventory.low_stock_threshold'))
            ->orderBy('stock')
            ->get();
    }
}
