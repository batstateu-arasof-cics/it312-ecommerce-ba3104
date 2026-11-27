<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Dashboard team (Group 8) - Must 2: sales report page with a date range */
class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        return User::where('email', 'admin@shop.test')->first();
    }

    /** Saves one order on a given date (no checkout) so the expected figures are easy to work out. */
    private function order(string $status, string $date, Product $product, int $quantity): void
    {
        $total = $product->price * $quantity;

        $order = Order::create([
            'user_id' => User::where('email', 'customer@shop.test')->value('id'),
            'status' => $status,
            'subtotal' => $total,
            'discount' => 0,
            'shipping_fee' => 0,
            'total' => $total,
            'payment_method' => 'cod',
        ]);
        $order->forceFill(['created_at' => Carbon::parse($date.' 10:00:00')])->save();

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $product->price,
        ]);
    }

    /** Five orders over March 2026; the amounts are in the comments. */
    private function seedOrders(): void
    {
        $barako = Product::where('name', 'Kapeng Barako 250g')->first();   // 220
        $tablea = Product::where('name', 'Tablea Chocolate 200g')->first(); // 150

        $this->order(Order::PENDING, '2026-03-01', $barako, 3);   // 660
        $this->order(Order::PAID, '2026-03-02', $tablea, 2);      // 300
        $this->order(Order::PAID, '2026-03-02', $barako, 1);      // 220
        $this->order(Order::COMPLETED, '2026-03-05', $barako, 1); // 220
        $this->order(Order::CANCELLED, '2026-03-05', $tablea, 10); // not counted
    }

    /** 2 to 5 March: 220+300 on the 2nd and 220 on the 5th = 740 (the 1st and the cancelled order are left out). */
    public function test_sales_report_filters_by_date_range(): void
    {
        $this->seedOrders();

        $this->actingAs($this->admin())
            ->get(route('admin.reports.sales', ['from' => '2026-03-02', 'to' => '2026-03-05']))
            ->assertOk()
            ->assertViewHas('summary', fn (array $s) => $s['total_sales'] === 740.0 && $s['order_count'] === 3)
            ->assertViewHas('daily', fn ($daily) => $daily->all() === [
                ['date' => '2026-03-02', 'order_count' => 2, 'total_sales' => 520.0],
                ['date' => '2026-03-05', 'order_count' => 1, 'total_sales' => 220.0],
            ])
            ->assertSee('₱740.00');
    }

    /** No dates picked: every non-cancelled order counts (1400 from 4 orders). */
    public function test_sales_report_without_dates_shows_everything(): void
    {
        $this->seedOrders();

        $this->actingAs($this->admin())->get(route('admin.reports.sales'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $s) => $s['total_sales'] === 1400.0 && $s['order_count'] === 4)
            ->assertSee('from the first order');
    }

    /** Only a To date: everything up to that day. */
    public function test_sales_report_with_only_a_to_date(): void
    {
        $this->seedOrders();

        $this->actingAs($this->admin())
            ->get(route('admin.reports.sales', ['to' => '2026-03-01']))
            ->assertOk()
            ->assertViewHas('summary', fn (array $s) => $s['total_sales'] === 660.0 && $s['order_count'] === 1);
    }

    /** A month with no orders shows zeros and "No sales in this range." */
    public function test_a_range_with_no_orders_shows_an_empty_report(): void
    {
        $this->seedOrders();

        $this->actingAs($this->admin())
            ->get(route('admin.reports.sales', ['from' => '2025-01-01', 'to' => '2025-01-31']))
            ->assertOk()
            ->assertViewHas('summary', fn (array $s) => $s['total_sales'] === 0.0 && $s['order_count'] === 0)
            ->assertSee('No sales in this range.');
    }

    /** To before From, or a date in the wrong format, is rejected with an error. */
    public function test_sales_report_rejects_bad_dates(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.sales', ['from' => '2026-03-05', 'to' => '2026-03-01']))
            ->assertSessionHasErrors('to');

        $this->actingAs($this->admin())
            ->get(route('admin.reports.sales', ['from' => '03/01/2026']))
            ->assertSessionHasErrors('from');
    }

    /** Only admins can see the report. */
    public function test_customers_cannot_open_the_sales_report(): void
    {
        $customer = User::where('email', 'customer@shop.test')->first();

        $this->actingAs($customer)->get(route('admin.reports.sales'))->assertForbidden();
    }
}
