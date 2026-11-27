<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Dashboard team (Group 8) - Must 1: orders by status and top 5 products on /admin */
class DashboardFiguresTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        return User::where('email', 'admin@shop.test')->first();
    }

    /** Saves an order directly (no checkout) so the figures are easy to check. */
    private function order(string $status, string $date, array $lines): Order
    {
        $total = collect($lines)->sum(fn ($line) => $line[0]->price * $line[1]);

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

        foreach ($lines as [$product, $quantity]) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $product->price,
            ]);
        }

        return $order;
    }

    /** Barako 220, Arabica 280, Tablea 150. */
    private function seedOrders(): void
    {
        $barako = Product::where('name', 'Kapeng Barako 250g')->first();
        $arabica = Product::where('name', 'Benguet Arabica 250g')->first();
        $tablea = Product::where('name', 'Tablea Chocolate 200g')->first();

        $this->order(Order::PENDING, '2026-03-01', [[$barako, 3]]);              // 660
        $this->order(Order::PAID, '2026-03-02', [[$arabica, 1], [$tablea, 2]]);  // 580
        $this->order(Order::COMPLETED, '2026-03-05', [[$barako, 1]]);            // 220
        $this->order(Order::CANCELLED, '2026-03-05', [[$tablea, 10]]);           // not counted in sales
    }

    /** Totals leave out the cancelled order; every status is counted, including cancelled. */
    public function test_dashboard_figures_match_the_orders(): void
    {
        $this->seedOrders();

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('summary', function (array $summary) {
                return $summary['total_sales'] === 1460.0
                    && $summary['order_count'] === 3
                    && $summary['orders_by_status'] === [
                        'pending' => 1, 'paid' => 1, 'shipped' => 0, 'completed' => 1, 'cancelled' => 1,
                    ];
            })
            ->assertSee('Orders by status')
            ->assertSee('₱1,460.00');
    }

    /** Ranking by quantity: Barako 4, Tablea 2, Arabica 1 (Tablea's cancelled 10 is ignored). */
    public function test_top_products_are_ranked_by_quantity_sold_without_cancelled_orders(): void
    {
        $this->seedOrders();

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('summary', function (array $summary) {
                $top = $summary['top_products']->map(fn ($p) => [$p['name'], $p['quantity_sold']])->all();

                // Tablea's 10 in the cancelled order must not count
                return $top === [
                    ['Kapeng Barako 250g', 4],
                    ['Tablea Chocolate 200g', 2],
                    ['Benguet Arabica 250g', 1],
                ];
            })
            ->assertSeeInOrder(['Top products', '1. Kapeng Barako 250g', '2. Tablea Chocolate 200g']);
    }

    /** Seven products sold, but only the top 5 are listed. */
    public function test_top_products_shows_at_most_five(): void
    {
        foreach (Product::take(7)->get() as $product) {
            $this->order(Order::PAID, '2026-03-01', [[$product, 1]]);
        }

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertViewHas('summary', fn (array $summary) => $summary['top_products']->count() === 5);
    }

    /** With no orders, all 5 statuses show 0 and the top products box says "No sales yet." */
    public function test_empty_shop_shows_zero_for_every_status(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $s) => array_sum($s['orders_by_status']) === 0
                && count($s['orders_by_status']) === 5
                && $s['top_products']->isEmpty())
            ->assertSee('No sales yet.');
    }
}
