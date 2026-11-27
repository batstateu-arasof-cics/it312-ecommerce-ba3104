<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Inventory team (Group 8) - Must 3: admin stock page with restock form */
class StockPageTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        return User::where('email', 'admin@shop.test')->first();
    }

    /** The page lists all products and marks the seeded low-stock ones. */
    public function test_admin_sees_every_product_and_low_stock_badges(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.inventory.index'))->assertOk();

        foreach (Product::all() as $product) {
            $response->assertSee($product->name);
        }

        $response->assertSee('Low stock'); // Sagada Coffee 500g is seeded with 3
    }

    /** Customers get 403 on both the page and the restock form, and stock stays the same. */
    public function test_customers_cannot_open_the_stock_page_or_restock(): void
    {
        $customer = User::where('email', 'customer@shop.test')->first();
        $product = Product::first();

        $this->actingAs($customer)->get(route('admin.inventory.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.inventory.restock', $product), ['quantity' => 5])->assertForbidden();

        $this->assertSame($product->stock, $product->fresh()->stock);
    }

    /** Adding 10 to a product with 3 gives 13 and a 'restock' row with the admin's id. */
    public function test_admin_restock_adds_stock_and_records_a_restock_movement(): void
    {
        $product = Product::where('name', 'Sagada Coffee 500g')->first(); // 3

        $this->actingAs($this->admin())
            ->post(route('admin.inventory.restock', $product), ['quantity' => 10])
            ->assertRedirect(route('admin.inventory.index'))
            ->assertSessionHas('success');

        $this->assertSame(13, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id, 'user_id' => $this->admin()->id, 'change' => 10, 'reason' => 'restock',
        ]);
    }

    /** 0, negative, empty and non-number quantities are rejected and change nothing. */
    public function test_restock_rejects_zero_negative_or_missing_quantities(): void
    {
        $product = Product::first();

        foreach ([0, -3, null, 'abc'] as $quantity) {
            $this->actingAs($this->admin())
                ->post(route('admin.inventory.restock', $product), ['quantity' => $quantity])
                ->assertSessionHasErrors('quantity');
        }

        $this->assertSame($product->stock, $product->fresh()->stock);
        $this->assertDatabaseMissing('stock_movements', ['reason' => 'restock']);
    }
}
