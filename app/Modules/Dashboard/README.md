# Admin Dashboard & Reports

> [!IMPORTANT]
> **BA 3104 · Group 8** owns this module.
> Your group also owns **Inventory & Stock** (`app/Modules/Inventory`).
> **Graded for your group:** Must 1–2 in this module and Must 1–2 in Inventory & Stock. Must 3–4 in both modules count as Stretch (bonus).

## Team — Group 8
| | |
| --- | --- |
| Group GitHub account | invited by school email |
| Members | 4 (names are in the class tracker) |

## Scope
The admin home page (`/admin`): sales figures, orders by status, top products, low-stock list, and a date-range sales report with CSV export. Also the admin navigation that links to every team's admin pages. Owns no tables.

## Progress
**Difficulty:** Medium–easy · starter about 25% done

> [!NOTE]
> **Already done in the starter: do not rebuild these.** Read this code first; your work builds on it.
>
> - [x] `/admin` page with total sales and order count
> - [x] `ReportService::salesSummary()` with optional date range
> - [x] Low-stock panel (shows seeded low-stock products)

**Your work: Must items (graded)**
- [x] 1. Orders by status and top 5 products by quantity sold on the dashboard
- [x] 2. Sales report page with a date range (`/admin/reports/sales`)
- [x] 3. CSV export of the report
- [x] 4. Admin menu partial (`dashboard::partials.admin-menu`) other teams add their links to + feature tests (403 for customers, figures match orders)

**Your work: Stretch items (bonus, after all Musts)**
- [ ] Daily sales chart (Chart.js from a CDN)
- [ ] Average order value and comparison with the previous period

## Interface provided — `App\Contracts\ReportServiceInterface`
| Method | Parameters | Returns | Status |
| --- | --- | --- | --- |
| `salesSummary` | `?string $from, ?string $to` (Y-m-d) | `array`: total_sales, order_count, orders_by_status (status => count, cancelled included), top_products (top 5 by quantity, cancelled left out) | Done |

## Admin menu (for other teams)
Put `@include('dashboard::partials.admin-menu')` at the top of your admin page. A link shows only when its route exists, so the planned admin routes (`admin.products.index`, `admin.orders.index`, `admin.shipments.index`, `admin.vouchers.index`, `admin.reviews.index`, …) appear by themselves once merged. Using a different route name? Add one line to the list in `views/partials/admin-menu.blade.php`.

## Events
- Fires: none
- Listens to: `StockLow` (optional, e.g. flash an alert)

## Dependencies used
| Module | Interface / method | Why |
| --- | --- | --- |
| Users team | `admin` middleware | Admin only |
| Catalog team | `Product` | Top products |
| Orders team | `Order`, `OrderItem` | Sales figures |
| Inventory team | `InventoryServiceInterface::lowStock` | Low-stock panel |

## Database
None.

## Routes
| Method | URI | Controller@action | Middleware |
| --- | --- | --- | --- |
| GET | `/admin` | `DashboardController@index` | auth, admin |
| GET | `/admin/reports/sales?from=Y-m-d&to=Y-m-d` | `DashboardController@report` | auth, admin |
| GET | `/admin/reports/sales/export?from=Y-m-d&to=Y-m-d` | `DashboardController@export` (CSV) | auth, admin |

## Setup and testing
- Manual test: log in as `admin@shop.test`, open `/admin`; place an order as the customer and refresh
- Sales report: open `/admin/reports/sales`, pick From and To dates, click "Show report"
- Automated tests: `php artisan test tests/Feature/Dashboard`
  - `DashboardFiguresTest`: figures match the orders, orders by status, top 5 ranking without cancelled orders (Must 1)
  - `SalesReportTest`: date range, no dates, empty range, bad dates rejected (Must 2)
  - `SalesExportTest`: CSV rows and totals match the report, file name, link from the report page (Must 3)
  - `AdminAccessTest`: 403 for customers, login for guests, admin menu on every page (Must 4)

## Changelog
| Date | Change | PR # | Author |
| --- | --- | --- | --- |
| | Starter code from instructor | — | Instructor |
| 2026-11-27 | Orders by status and top 5 products on `/admin` (Must 1) | | Aaron Justine Flores |
| 2026-11-27 | Sales report page with date range `/admin/reports/sales` (Must 2) | | Aaron Justine Flores |
| 2026-11-27 | CSV export of the sales report (Must 3) | | Aaron Justine Flores |
| 2026-11-27 | Admin menu partial + access feature tests (Must 4) | | Aaron Justine Flores |
