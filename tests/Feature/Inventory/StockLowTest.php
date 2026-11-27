<?php

namespace Tests\Feature\Inventory;

use App\Contracts\InventoryServiceInterface;
use App\Events\StockLow;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** Inventory team (Group 8) - Must 2: fire StockLow when a sale reaches the threshold */
class StockLowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** Fake only StockLow (so we can check it fired) and fix the threshold at 5. */
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([StockLow::class]);
        config(['inventory.low_stock_threshold' => 5]);
    }

    /** Saves an order for $quantity of $product and runs the stock deduction on it. */
    private function sell(Product $product, int $quantity): void
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
    }

    /** Exactly at the threshold (6 → 5) counts as low. */
    public function test_stock_low_fires_when_a_sale_reaches_the_threshold(): void
    {
        $product = Product::where('stock', 25)->first();
        $product->update(['stock' => 6]);

        $this->sell($product, 1); // 6 -> 5

        Event::assertDispatched(StockLow::class, fn (StockLow $e) => $e->product->is($product) && $e->product->stock === 5);
    }

    /** A product already below the threshold still alerts on each new sale. */
    public function test_stock_low_fires_when_a_sale_goes_below_the_threshold(): void
    {
        $product = Product::where('name', 'Sagada Coffee 500g')->first(); // seeded with 3

        $this->sell($product, 1); // 3 -> 2

        Event::assertDispatched(StockLow::class, 1);
    }

    /** Plenty of stock left (25 → 23): no alert. */
    public function test_stock_low_does_not_fire_while_stock_stays_above_the_threshold(): void
    {
        $product = Product::where('stock', 25)->first();

        $this->sell($product, 2); // 25 -> 23

        Event::assertNotDispatched(StockLow::class);
    }
}
