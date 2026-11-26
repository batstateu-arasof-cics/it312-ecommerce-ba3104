# Search & Filters

> [!IMPORTANT]
> **BA 3104 · Group 5** owns this module.

## Team — Group 5
| | |
| --- | --- |
| Group GitHub account | @Emjhay6 |
| Members | 3 (names are in the class tracker) |

## Scope
Keyword search, filters (category, price range, rating), sorting, and pagination for products. Reads the `products` table; owns no tables.

## Progress
**Difficulty:** Easy · starter about 25% done

> [!NOTE]
> **Already done in the starter: do not rebuild these.** Read this code first; your work builds on it.
>
> - [x] `/search?q=` keyword search on name and description
> - [x] Paginated results page

**Your work: Must items (graded)**
- [ ] 1. Category filter
- [ ] 2. Price range filter (min, max)
- [ ] 3. Sorting: price low–high, high–low, newest
- [ ] 4. Filter inputs that keep their values, validated in `SearchController` + feature tests combining filters

**Your work: Stretch items (bonus, after all Musts)**
- [ ] Rating filter using Reviews team's reviews
- [ ] Search suggestions while typing

## Interface provided — `App\Contracts\SearchServiceInterface`
| Method | Parameters | Returns | Status |
| --- | --- | --- | --- |
| `search` | `string $query, array $filters, int $perPage = 12` | `LengthAwarePaginator` of `Product` | Keyword only; filters **to do** |

## Events
- Fires: none
- Listens to: none

## Dependencies used
| Module | Interface / method | Why |
| --- | --- | --- |
| Catalog team | `Product`, `Category` models | Data being searched |
| Reviews team | `ReviewServiceInterface::getAverageRating` or `reviews` table | Rating filter |

## Database
None.

## Routes
| Method | URI | Controller@action | Middleware |
| --- | --- | --- | --- |
| GET | `/search` | `SearchController@index` | — |

## Setup and testing
- Manual test: `/search?q=coffee`
- Automated tests:

## Changelog
| Date | Change | PR # | Author |
| --- | --- | --- | --- |
| | Starter code from instructor | — | Instructor |
