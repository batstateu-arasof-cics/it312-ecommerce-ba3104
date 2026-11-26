<?php

namespace Tests\Feature\Integration;

use App\Contracts\OrderServiceInterface;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TEMPLATE for Platform team. Copy this file for each cross-module flow.
 *
 * Flow: customer places an order -> customer cancels -> stock comes back -> cancellation email logged.
 * Modules: Orders (Orders team), Inventory (Inventory team), Notifications (Notifications team).
 *
 * It is skipped until those teams merge their Must items. Remove the markTestSkipped() line then.
 */
class CancelOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_cancelling_an_order_restores_stock_and_notifies_the_customer(): void
    {
        $this->markTestSkipped('Enable after Orders team Must 2, Inventory team Must 1 and Notifications team Must 2 are merged.');

        // Arrange: a customer with one order for 2 units
        $customer = User::where('email', 'customer@shop.test')->first();
        $product = Product::first();
        $stockBefore = $product->stock;

        $this->actingAs($customer)->post(route('cart.add', $product), ['quantity' => 2]);
        $this->actingAs($customer)->post(route('checkout.store'), ['payment_method' => 'cod']);
        $order = Order::where('user_id', $customer->id)->firstOrFail();

        // Act: the customer cancels (route built by Orders team)
        $this->actingAs($customer)->post(route('orders.cancel', $order))->assertRedirect();

        // Assert: every module did its part
        $this->assertSame(Order::CANCELLED, $order->fresh()->status);                          // Orders team
        $this->assertSame($stockBefore, $product->fresh()->stock);                             // Inventory team
        $this->assertDatabaseHas('stock_movements', ['order_id' => $order->id, 'reason' => 'cancel']);
        $this->assertDatabaseHas('notification_logs', ['template' => 'order_cancelled']);     // Notifications team

        // Cancelling twice must not restore stock twice
        $this->expectException(\InvalidArgumentException::class);
        app(OrderServiceInterface::class)->updateStatus($order->id, Order::CANCELLED);
    }
}
