<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Dashboard team (Group 8) - Must 3: CSV export of the sales report */
class SalesExportTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        return User::where('email', 'admin@shop.test')->first();
    }

    /** Saves one order on a given date (no checkout) so the expected CSV is easy to work out. */
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

    /**
     * Splits the downloaded CSV text into rows of cells.
     *
     * @return array<int, array<int, string>>
     */
    private function csvRows(string $csv): array
    {
        return array_values(array_map('str_getcsv', array_filter(explode("\n", trim($csv)))));
    }

    /** March only: 660 + 440 = 1100. The cancelled order and the April order are not in the file. */
    public function test_csv_export_matches_the_report(): void
    {
        $barako = Product::where('name', 'Kapeng Barako 250g')->first(); // 220
        $this->order(Order::PENDING, '2026-03-01', $barako, 3);   // 660
        $this->order(Order::PAID, '2026-03-02', $barako, 2);      // 440
        $this->order(Order::CANCELLED, '2026-03-02', $barako, 9); // not counted
        $this->order(Order::PAID, '2026-04-10', $barako, 1);      // outside the range

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.export', ['from' => '2026-03-01', 'to' => '2026-03-31']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('sales-report-2026-03-01-to-2026-03-31.csv', $response->headers->get('Content-Disposition'));

        $this->assertSame([
            ['Date', 'Orders', 'Sales'],
            ['2026-03-01', '1', '660.00'],
            ['2026-03-02', '1', '440.00'],
            ['Total', '2', '1100.00'],
        ], $this->csvRows($response->getContent()));
    }

    /** With no orders the file still has the header and a Total row of 0. */
    public function test_csv_export_with_no_orders_has_header_and_zero_total(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.reports.export'));

        $response->assertOk();
        $this->assertStringContainsString('sales-report-start-to-today.csv', $response->headers->get('Content-Disposition'));
        $this->assertSame([
            ['Date', 'Orders', 'Sales'],
            ['Total', '0', '0.00'],
        ], $this->csvRows($response->getContent()));
    }

    /** The "Download CSV" button on the report page keeps the chosen dates. */
    public function test_report_page_links_to_the_csv_with_the_same_dates(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.sales', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->assertOk()
            ->assertSee('Download CSV')
            ->assertSee(route('admin.reports.export', ['from' => '2026-03-01', 'to' => '2026-03-31']));
    }

    /** Only admins can download the sales file. */
    public function test_customers_cannot_download_the_csv(): void
    {
        $customer = User::where('email', 'customer@shop.test')->first();

        $this->actingAs($customer)->get(route('admin.reports.export'))->assertForbidden();
    }
}
