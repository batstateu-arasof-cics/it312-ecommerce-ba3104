# Shipping & Tracking

> [!IMPORTANT]
> **BA 3104 · Group 9** owns this module.
> Your group also owns **Notifications** (`app/Modules/Notifications`).
> **Graded for your group:** Must 1–2 in this module and Must 1–2 in Notifications. Must 3–4 in both modules count as Stretch (bonus).

## Team — Group 9
| | |
| --- | --- |
| Group GitHub account | _not set yet: add it in your first PR_ |
| Members | 4 (names are in the class tracker) |

## Scope
Shipping fees by province or region, a shipment record per order (courier, tracking number, status), the admin "mark as shipped" step, and the customer tracking page.

## Progress
**Difficulty:** Medium · starter about 25% done

> [!NOTE]
> **Already done in the starter: do not rebuild these.** Read this code first; your work builds on it.
>
> - [x] `shipping_rates` and `shipments` tables and models
> - [x] Flat default fee (₱150) and `getTracking()`
> - [x] `CreateShipment` listener registered on `OrderPlaced`

**Your work: Must items (graded)**
- [ ] 1. Fee lookup by province, then region, then default; seeder with at least 4 provinces
- [ ] 2. `CreateShipment` listener body: one `preparing` shipment per order
- [ ] 3. Admin shipments list: enter courier and tracking number, mark shipped, fire `OrderShipped`
- [ ] 4. Customer tracking page (`/orders/{order}/tracking`), owner only + feature tests

**Your work: Stretch items (bonus, after all Musts)**
- [ ] Admin page to manage shipping rates
- [ ] Mark delivered (status `delivered`)

## Interface provided — `App\Contracts\ShippingServiceInterface`
| Method | Parameters | Returns | Status |
| --- | --- | --- | --- |
| `getShippingFee` | `?Address $address` | `float` | Flat fee only |
| `getTracking` | `int $orderId` | `?Shipment` | Done |

## Events
- Fires: `OrderShipped` (to do)
- Listens to: `OrderPlaced` → `CreateShipment`

## Dependencies used
| Module | Interface / method | Why |
| --- | --- | --- |
| Users team | `Address` (province, region) | Rate lookup |
| Orders team | `Order`, `OrderPlaced` | Shipment per order |

## Database
| Table | Columns | Migration file |
| --- | --- | --- |
| `shipping_rates` | province, region, fee | `database/migrations/2026_01_02_001100_create_shipping_tables.php` |
| `shipments` | order_id (unique), courier, tracking_number, status, shipped_at, delivered_at | same file |

## Routes
None yet.

## Setup and testing
- Manual test: checkout shows ₱150 shipping
- Automated tests:

## Changelog
| Date | Change | PR # | Author |
| --- | --- | --- | --- |
| | Starter code from instructor | — | Instructor |
