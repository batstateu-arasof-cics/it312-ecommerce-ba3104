<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Dashboard team (Group 8) - Must 4: admin menu partial + who can open the admin pages */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** Every page the Dashboard module owns. */
    private const DASHBOARD_ROUTES = ['admin.dashboard', 'admin.reports.sales', 'admin.reports.export'];

    /** A logged-in customer is blocked (403) from every dashboard page. */
    public function test_customers_get_403_on_every_dashboard_page(): void
    {
        $customer = User::where('email', 'customer@shop.test')->first();

        foreach (self::DASHBOARD_ROUTES as $route) {
            $this->actingAs($customer)->get(route($route))->assertForbidden();
        }
    }

    /** Someone not logged in is sent to the login page. */
    public function test_guests_are_sent_to_login(): void
    {
        foreach (self::DASHBOARD_ROUTES as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    /** The admin can open all of them. */
    public function test_admins_can_open_every_dashboard_page(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        foreach (self::DASHBOARD_ROUTES as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    /** The menu appears on both pages and highlights the one you are on. */
    public function test_admin_menu_shows_on_dashboard_pages_and_marks_the_current_page(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        // On /admin the current page (aria-current) is Dashboard, before the Sales report link
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['aria-label="Admin menu"', 'aria-current="page"', '>Dashboard</a>', '>Sales report</a>'], false);

        // On the report page the current page is Sales report
        $this->actingAs($admin)->get(route('admin.reports.sales'))
            ->assertOk()
            ->assertSeeInOrder(['aria-label="Admin menu"', '>Dashboard</a>', 'aria-current="page"', '>Sales report</a>'], false);
    }

    /** Other teams' links (Orders, Vouchers) show only once their pages exist. */
    public function test_admin_menu_hides_links_whose_pages_do_not_exist_yet(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        // Planned pages of other teams only show once their route is merged
        foreach (['admin.orders.index' => 'admin/orders', 'admin.vouchers.index' => 'admin/vouchers'] as $route => $path) {
            $link = 'href="'.url($path).'"';

            if (Route::has($route)) {
                $response->assertSee($link, false);
            } else {
                $response->assertDontSee($link, false);
            }
        }
    }
}
