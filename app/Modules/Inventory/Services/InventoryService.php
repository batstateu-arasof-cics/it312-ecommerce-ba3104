<?php

namespace App\Modules\Inventory\Services;

use App\Contracts\InventoryServiceInterface;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Inventory team (Group 8): the only place that changes products.stock.
 * Every change is also written to stock_movements so the history adds up.
 */
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

    /**
     * Puts a cancelled order's items back into stock (Inventory Must 1).
     *
     * Runs when the Orders team fires OrderCancelled. Each item's quantity is added back
     * and a 'cancel' movement is recorded. It runs only once per order: a second call does nothing.
     */
    public function restoreStock(Order $order): void
    {
        // Everything inside the transaction is saved together, or not at all if something fails.
        DB::transaction(function () use ($order) {
            // Lock the order row so two cancellations at the same time cannot both restore.
            Order::whereKey($order->id)->lockForUpdate()->first();

            // A 'cancel' movement for this order means it was already restored, so stop here.
            $alreadyRestored = StockMovement::where('order_id', $order->id)
                ->where('reason', 'cancel')
                ->exists();

            if ($alreadyRestored) {
                return;
            }

            // Add each item's quantity back to its product.
            foreach ($order->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                // Skip items whose product was deleted.
                if (! $product) {
                    continue;
                }

                $product->increment('stock', $item->quantity);

                // Record the change (+quantity) in the stock history.
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
