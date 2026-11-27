<?php

namespace Tests\Feature\Inventory;

use App\Events\OrderCancelled;
use App\Events\StockLow;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Inventory team (Group 8) - Must 4: the whole stock story through the real app.
 * Order deducts -> cancel restores once -> admin restocks -> a sale to the threshold fires StockLow.
 */
class InventoryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function customer(): User
    {
        return User::where('email', 'customer@shop.test')->first();
    }

    /** Uses the real cart and checkout pages as the customer, then returns the new order. */
    private function checkout(Product $product, int $quantity): Order
    {
        $this->actingAs($this->customer())->post(route('cart.add', $product), ['quantity' => $quantity]);
        $this->actingAs($this->customer())->post(route('checkout.store'), ['payment_method' => 'cod']);

        return Order::where('user_id', $this->customer()->id)->latest('id')->firstOrFail();
    }

    /** Checking out 2 drops stock 25 → 23 and records a 'sale' movement of -2. */
    public function test_placing_an_order_deducts_stock_and_records_a_sale(): void
    {
        $product = Product::where('stock', 25)->first();

        $order = $this->checkout($product, 2);

        $this->assertSame(23, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id, 'order_id' => $order->id, 'change' => -2, 'reason' => 'sale',
        ]);
    }

    /** One product through every Inventory feature in order; each step is checked before the next. */
    public function test_full_stock_flow_order_cancel_restock_and_low_stock_alert(): void
    {
        Event::fake([StockLow::class]);
        config(['inventory.low_stock_threshold' => 5]);
        $admin = User::where('email', 'admin@shop.test')->first();
        $product = Product::where('stock', 25)->first();

        // 1. Order 4 -> 21 left
        $order = $this->checkout($product, 4);
        $this->assertSame(21, $product->fresh()->stock);

        // 2. Cancel (twice on purpose) -> back to 25, restored once
        OrderCancelled::dispatch($order);
        OrderCancelled::dispatch($order);
        $this->assertSame(25, $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('order_id', $order->id)->where('reason', 'cancel')->count());

        // 3. Admin restocks 5 -> 30
        $this->actingAs($admin)->post(route('admin.inventory.restock', $product), ['quantity' => 5]);
        $this->assertSame(30, $product->fresh()->stock);

        // No alert yet: stock never went to 5 or below
        Event::assertNotDispatched(StockLow::class);

        // 4. A big order takes it to 5 -> StockLow fires for that product
        $this->checkout($product, 25);
        $this->assertSame(5, $product->fresh()->stock);
        Event::assertDispatched(StockLow::class, fn (StockLow $e) => $e->product->is($product));

        // Every change is in the history: sale, cancel, restock, sale
        $this->assertSame(
            ['sale', 'cancel', 'restock', 'sale'],
            StockMovement::where('product_id', $product->id)->orderBy('id')->pluck('reason')->all(),
        );
        // and the movements add up: 25 at the start, -4 +4 +5 -25 = -20, so 5 now
        $this->assertSame(-20, (int) StockMovement::where('product_id', $product->id)->sum('change'));
    }
}
