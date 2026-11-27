<?php

namespace Tests\Feature\Inventory;

use App\Contracts\InventoryServiceInterface;
use App\Events\OrderCancelled;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Inventory team (Group 8) - Must 1: restore stock when an order is cancelled */
class RestoreStockTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** An order for $quantity of $product whose stock was already deducted. */
    private function placedOrder(Product $product, int $quantity): Order
    {
        $order = Order::create([
            'user_id' => User::where('email', 'customer@shop.test')->value('id'),
            'status' => Order::PENDING,
            'subtotal' => $product->price * $quantity,
            'discount' => 0,
            'shipping_fee' => 0,
            'total' => $product->price * $quantity,
            'payment_method' => 'cod',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $product->price,
        ]);

        app(InventoryServiceInterface::class)->deductStock($order->load('items'));

        return $order;
    }

    public function test_cancelling_an_order_puts_the_stock_back(): void
    {
        $product = Product::where('stock', 25)->first();
        $order = $this->placedOrder($product, 3);
        $this->assertSame(22, $product->fresh()->stock);

        // The Orders team fires this event when an order is cancelled
        OrderCancelled::dispatch($order);

        $this->assertSame(25, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id, 'order_id' => $order->id, 'change' => 3, 'reason' => 'cancel',
        ]);
    }

    public function test_cancelling_twice_restores_only_once(): void
    {
        $product = Product::where('stock', 25)->first();
        $order = $this->placedOrder($product, 3);

        OrderCancelled::dispatch($order);
        OrderCancelled::dispatch($order);

        $this->assertSame(25, $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('order_id', $order->id)->where('reason', 'cancel')->count());
    }

    public function test_every_item_in_the_order_is_restored(): void
    {
        [$first, $second] = Product::where('stock', 25)->take(2)->get();
        $order = $this->placedOrder($first, 2);
        $order->items()->create([
            'product_id' => $second->id,
            'product_name' => $second->name,
            'quantity' => 4,
            'unit_price' => $second->price,
        ]);
        $second->update(['stock' => 21]); // as if the second line had been deducted too

        app(InventoryServiceInterface::class)->restoreStock($order->fresh('items'));

        $this->assertSame(25, $first->fresh()->stock);
        $this->assertSame(25, $second->fresh()->stock);
    }
}
