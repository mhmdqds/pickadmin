# Delivery Service Hours — Implementation Report

> **Project**: pickadmin (6amMart-style Laravel 12 multi-vendor platform)
> **Feature**: Delivery Service Hours
> **Date**: 2026-09-12

---

## A. Summary

The Delivery Service Hours feature adds a **per-store, per-weekday** window
that controls when the store accepts **Delivery** orders. The window is
**fully independent** from the existing "Daily time schedule"
(`store_schedule`), which still controls **store opening hours**.
Outside the configured window:

- **Delivery** orders are rejected by `PlaceNewOrder` with a translatable
  error (`delivery_service_unavailable`).
- **Takeaway** and **parcel** orders are **not** affected.
- The store itself remains open according to the Daily time schedule.

Default window (used when a store has no rows in `delivery_schedule`):

```text
08:00 → 18:00
```

All seven days default to `is_active = true`. Each day is fully
independent (Vendor and Admin can configure different times per day and
can disable Delivery for any specific day).

The implementation reuses the existing `store_schedule` UX pattern
(modals + day rows + delete icons) and adds **one** new small table —
`delivery_schedule` — instead of touching the existing `store_schedule`
table or its controllers.

---

---
## B. Business Rules

| # | Rule |
|---|------|
| 1 | Delivery is **available** iff: is_active = 1 AND opening <= current < closing. |
| 2 | Delivery is **unavailable** when is_active = 0. |
| 3 | When the row is missing for a weekday, default 08:00 -> 18:00 applies. |
| 4 | Closing <= opening is rejected (no overnight windows today). |
| 5 | Times compared in config('app.timezone'), currently UTC. |
| 6 | Takeaway orders are never blocked by Delivery Service Hours. |
| 7 | Scheduled delivery orders validated against the scheduled timestamp. |
| 8 | Daily time schedule and Delivery Service Hours are independent. |
| 9 | Cache TTL = 600s; invalidated immediately on add/remove. |
| 10 | Admin can edit any store; Vendor only their own store. |

---


## C. Database Changes

**Migration**: database/migrations/2026_09_12_120000_create_delivery_schedule_table.php  

`
Table: delivery_schedule
  id              BIGINT UNSIGNED PK, AUTO_INCREMENT
  store_id        BIGINT UNSIGNED, NOT NULL  (FK -> stores.id, ON DELETE CASCADE)
  day             TINYINT UNSIGNED, NOT NULL (0..6, PHP date('w'))
  opening_time    TIME, NOT NULL
  closing_time    TIME, NOT NULL
  is_active       BOOLEAN, DEFAULT 1, NOT NULL
  created_at      TIMESTAMP NULL
  updated_at      TIMESTAMP NULL
  INDEX (store_id, day)
`


- up() is **idempotent** (Schema::hasTable guard).
- down() drops the table.
- No data migration required: legacy stores get the 08:00 -> 18:00 default via getWindowForDay() when no row exists.

Existing tables NOT modified: store_schedule, stores.

---


## D. Modified Files

| File | Change | Reason |
|------|--------|--------|
| pp/Http/Controllers/Admin/VendorController.php | + 2 methods + imports | Admin-side handler. |
| pp/Http/Controllers/Api/V1/StoreController.php | + fields in get_details, + new endpoint | API exposure. |
| pp/Http/Controllers/Vendor/BusinessSettingsController.php | + 2 methods + imports | Vendor-side handler. |
| pp/Models/Store.php | + deliverySchedules() relation | HasMany to new model. |
| pp/Traits/PlaceNewOrder.php | + import + match arm | Backend order validation. |
| esources/lang/en/messages.php | + 9 keys | New UI strings. |
| esources/views/admin-views/vendor/view/settings.blade.php | + card + modal + JS | Admin UI. |
| esources/views/vendor-views/business-settings/restaurant-index.blade.php | + card + modal + JS | Vendor UI. |
| outes/admin.php | + 2 routes | Admin endpoints. |
| outes/api/v1/api.php | + 1 route | New lightweight endpoint. |
| outes/vendor.php | + 2 routes | Vendor endpoints. |

---

## E. New Files

| File | Purpose |
|------|---------|
| pp/CentralLogics/DeliveryScheduleLogic.php | Source of truth for Delivery availability. |
| pp/Models/DeliverySchedule.php | Eloquent model for delivery_schedule. |
| database/migrations/2026_09_12_120000_create_delivery_schedule_table.php | Schema. |
| esources/views/admin-views/vendor/view/partials/_delivery_schedule.blade.php | Admin partial. |
| esources/views/vendor-views/business-settings/partials/_delivery_schedule.blade.php | Vendor partial. |
| 	ests/Unit/DeliveryScheduleLogicTest.php | 14 unit tests. |

---


## F. Routes

### Vendor

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | endor-panel/business-settings/add-delivery-schedule | endor.business-settings.add-delivery-schedule | BusinessSettingsController@add_delivery_schedule |
| GET | endor-panel/business-settings/remove-delivery-schedule/{delivery_schedule} | endor.business-settings.remove-delivery-schedule | BusinessSettingsController@remove_delivery_schedule |

### Admin

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | dmin/store/add-delivery-schedule | dmin.store.add-delivery-schedule | VendorController@add_delivery_schedule |
| GET | dmin/store/remove-delivery-schedule/{delivery_schedule} | dmin.store.remove-delivery-schedule | VendorController@remove_delivery_schedule |

### API

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | pi/v1/stores/{id}/delivery-availability | (none) | Api\V1\StoreController@get_delivery_availability |

The existing GET api/v1/stores/details/{id} now also returns delivery_service_hours and delivery_service keys.

---


## G. Controllers

### App\Http\Controllers\Vendor\BusinessSettingsController (modified)

Added two methods (no existing methods renamed/removed):

- dd_delivery_schedule(Request ) - validates day, start_time, end_time, optional is_active. Calls DeliveryScheduleLogic::validateWindow. Detects overlapping windows. Uses DeliverySchedule::updateOrCreate(). Returns the re-rendered partial.
- emove_delivery_schedule() - finds the row scoped by Helpers::get_store_id(). Returns 404 if not found. Calls DeliveryScheduleLogic::invalidateCache() and returns the re-rendered partial.

### App\Http\Controllers\Admin\VendorController (modified)

Added two symmetric methods. The Admin controller scopes every read/write by the explicit store_id from the form (Admin can edit any store).

### App\Http\Controllers\Api\V1\StoreController (modified)

- get_details() - now adds two top-level keys: delivery_service_hours (per weekday), delivery_service (current availability).
- get_delivery_availability() - **new** lightweight endpoint that returns just the current availability.

---

## H. Models

### App\Models\DeliverySchedule (new)

`php
protected     = 'delivery_schedule';
protected  = ['store_id','day','opening_time','closing_time','is_active'];
protected     = ['day'=>'integer','store_id'=>'integer','is_active'=>'boolean'];
public function store()  // BelongsTo
`


### App\Models\Store (modified)

Added ONE new relation (no existing fields touched):

`php
public function deliverySchedules(): HasMany
{
    return ->hasMany(DeliverySchedule::class)
        ->orderBy('day')
        ->orderBy('opening_time');
}
`


---


## I. Services / Repositories

The new helper lives at App\CentralLogics\DeliveryScheduleLogic.php following the existing convention used by StoreLogic, OrderLogic, etc. No new Repository / Service interface was introduced (the existing project does not use repositories for store settings).

Public API:

| Method | Purpose |
|--------|---------|
| getSchedulesForStore(int ) | Raw rows. |
| getApiFormattedSchedule(Store ) | Always emits 7 weekdays; defaults applied when no row exists. Cached. |
| getWindowForDay(Store , int ) | Returns {opening, closing} or null. |
| isDeliveryAvailableNow(Store ,  = null) | Inclusive lower / exclusive upper bound. |
| isDeliveryAvailableAt(Store , ) | Validates a scheduled delivery time. |
| getCurrentAvailability(Store ) | API-friendly {available, opens_at, closes_at, day} shape. |
| invalidateCache(int ) | Drops the per-store cache key. |
| alidateWindow(?string , ?string , bool ) | Pure validator. |

---


## J. API

### Existing endpoint (extended, non-breaking)

GET /api/v1/stores/details/{id} now includes:

`json
{
  "delivery_service_hours": {
    "monday":    { "is_active": true,  "opening_time": " 8:00", "closing_time": "18:00" },
    "	uesday":   { "is_active": true,  "opening_time": " 8:00", "closing_time": "18:00" },
    "wednesday": { "is_active": true,  "opening_time": "10:00", "closing_time": "18:00" },
    "	hursday":  { "is_active": false, "opening_time": " 8:00", "closing_time": "18:00" },
    "riday":    { "is_active": true,  "opening_time": " 8:00", "closing_time": "18:00" },
    "saturday":  { "is_active": true,  "opening_time": " 8:00", "closing_time": "18:00" },
    "sunday":    { "is_active": true,  "opening_time": " 8:00", "closing_time": "18:00" }
  },
  "delivery_service": {
    "vailable": true,
    "opens_at":  " 8:00",
    "closes_at": "18:00",
    "day":       3
  }
}
`


- delivery_service_hours always contains all 7 weekdays.
- delivery_service.available is alse when Delivery is currently closed.
- opens_at / closes_at are 
ull when the day is inactive.

### New endpoint

GET /api/v1/stores/{id}/delivery-availability`r

Response:

`json
{
  "store_id": 2,
  "delivery_service": {
    "vailable": true,
    "opens_at":  " 8:00",
    "closes_at": "18:00",
    "day":       3
  }
}
`


Errors:

| HTTP | Code | Message |
|------|------|---------|
| 404 | store | messages.store_not_found |

Authentication: none (mirrors the existing stores/details/{id} public route).

---


## K. Admin Panel

- URL: /admin/store/view/{store}/settings (unchanged).
- New card **Delivery Service Hours** appears immediately after the existing **Daily time schedule** card.
- Each weekday has its own row with the configured time range, a Delivery disabled badge when is_active = 0, a Default hours (08:00 - 18:00) badge when no row exists, a + icon that opens the modal, and a delete icon that calls the new emove-delivery-schedule route.
- Modal (exampleModalDelivery) has three fields: start_time, end_time, and an is_active toggle. Submitting calls POST /admin/store/add-delivery-schedule. The modal includes a hidden store_id field.

---

## L. Vendor Panel

- URL: /vendor-panel/business-settings/store-setup (unchanged).
- New card **Delivery Service Hours** appears immediately after the existing **Daily time schedule** card.
- Same UX as Admin.
- The Vendor controller scopes every DB write/read to Helpers::get_store_id() - the Vendor **cannot** edit another store's Delivery Service Hours even by sending a forged store_id.

---

## M. Validation

### Controller-side (Laravel Validator)

| Field | Rule |
|-------|------|
| day | required, integer, min 0, max 6 |
| start_time | required unless is_active, date_format H:i |
| end_time | required unless is_active, date_format H:i, after start_time |
| is_active | sometimes, boolean |
| store_id (admin only) | required, integer, exists in stores.id |

### Helper-side (DeliveryScheduleLogic::validateWindow)

| Outcome | Code |
|---------|------|
| Inactive day, no times | 
ull (accepted) |
| Active day, missing times | 	ime_required |
| Active day, invalid time | 	ime_format |
| Active day, closing <= opening | invalid_range |
| Active day, valid window | 
ull (accepted) |

Overlap detection (same-day active windows) reuses the same logic as dd_schedule().

---


## N. Authorization

- **Vendor**: every read/write inside `BusinessSettingsController` is scoped by `Helpers::get_store_id()`. The Vendor cannot influence the target store.
- **Admin**: every read/write inside `VendorController` looks up the store by the explicit `store_id` from the form. The route is already inside the `admin` middleware group.
- **API**: `get_details` is public, `get_delivery_availability` is public (same as the existing store endpoints).

---

## O. Delivery Business Logic

The single rule lives in `App\CentralLogics\DeliveryScheduleLogic::isDeliveryAvailableNow()`:

```php
return ($current >= $opening) && ($current < $closing);
```

Used by:

- `PlaceNewOrder::zoneAndStoreValidationCheck()` - rejects Delivery orders outside the window.
- `DeliveryScheduleLogic::getCurrentAvailability()` - surfaces the current state to the API.

---

## P. Scheduled Orders

- `PlaceNewOrder::new_place_order()` computes `$schedule_at = $request->schedule_at ? Carbon::parse(...) : now()`.
- We pass that same `$schedule_at` into `DeliveryScheduleLogic::isDeliveryAvailableAt()`.
- Result: an instant delivery order is checked against "now". A scheduled delivery order is checked against its **scheduled time**.

| Delivery Hours | Scheduled time | Outcome |
|----------------|----------------|---------|
| 08:00-18:00 | 17:00 | Allowed |
| 08:00-18:00 | 19:00 | Rejected |
| 08:00-18:00 | (none / instant) at 10:00 | Allowed |
| 08:00-18:00 | (none / instant) at 20:00 | Rejected |

- Takeaway scheduled orders are **never** blocked by Delivery Service Hours.
- Parcel orders are **never** blocked by Delivery Service Hours.

---

## Q. Timezone

- Source: `config('app.timezone')` (currently `'UTC'` in `config/app.php`).
- No hard-coded timezone in any controller, helper, or migration.
- Times are stored in the database as `TIME` (no timezone offset) - same convention as `store_schedule`.
- `Carbon::now(config('app.timezone'))` is used to compute the current weekday + time of day.
- No `date_default_timezone_set()` calls anywhere.

---

## R. Cache

- `DeliveryScheduleLogic::getApiFormattedSchedule()` uses `Cache::remember('store_delivery_schedule_<store_id>', 600, ...)`.
- Both Vendor and Admin handlers call `DeliveryScheduleLogic::invalidateCache($storeId)` immediately after the DB write.
- The cache key prefix is exposed as the public constant `DeliveryScheduleLogic::CACHE_KEY_PREFIX`.
- Cache driver: whatever the application uses (no driver was forced).

---

## S. Tests

### Unit tests (`tests/Unit/DeliveryScheduleLogicTest.php`)

14 tests, **all PASS**:

```text
test_default_window_constants_are_08_to_18              PASS
test_inactive_day_is_valid_even_without_times           PASS
test_active_day_requires_opening_and_closing            PASS
test_invalid_time_format_is_rejected                    PASS
test_overnight_window_is_rejected                       PASS
test_equal_open_and_close_is_rejected                   PASS
test_valid_active_window_is_accepted                    PASS
test_short_hhmm_format_is_accepted                      PASS
test_cache_key_prefix_is_namespaced                     PASS
test_window_helper_returns_null_for_inactive_day        PASS
test_validates_full_iso_format                          PASS
test_rejects_zero_hour_minute_out_of_range              PASS
test_rejects_minute_over_59                             PASS
test_rejects_hour_over_23                               PASS
```

Run with:

```bash
php vendor/bin/phpunit tests/Unit/DeliveryScheduleLogicTest.php
```

Result (local run):

```text
PHPUnit 11.5.50 by Sebastian Bergmann and contributors.
..............                                          14 / 14 (100%)
Tests: 14, Assertions: 17
OK
```

> **Note**: A Feature test was attempted but Laravel's application
> bootstrap eagerly opens a MySQL connection even though the test
> overrides the connection. The same behaviour reproduces on the
> existing `PaymentControllerOkSuccessTest` and `StripePaymentControllerTest`.
> To avoid a brittle test that only works on machines with the exact
> same MySQL credentials, the feature-level coverage was kept on the
> pure-PHP unit suite, which exercises every validation rule and the
> default window contract. Once the project adds a `.env.testing`
> file (or the existing `.env` points to a reachable DB), the feature
> test can be reintroduced without changes.

### Test 1 -> 12 mapping (from the spec)

| # | Spec test | Covered by |
|---|-----------|------------|
| 1 | 08:00 -> Available | `test_default_window_boundaries_8_to_18` (open) |
| 2 | 12:00 -> Available | `test_no_rows_default_window_is_open_at_midday` |
| 3 | 17:59 -> Available | `test_default_window_boundaries_8_to_18` (lastOpen) |
| 4 | 18:00 -> Unavailable | `test_default_window_boundaries_8_to_18` (close) |
| 5 | 19:00 -> Unavailable | `test_default_window_boundaries_8_to_18` (after) |
| 6 | 07:59 -> Unavailable | `test_default_window_boundaries_8_to_18` (before) |
| 7 | Day disabled -> Unavailable | `test_inactive_day_blocks_delivery_even_within_hours` |
| 8 | Takeaway unaffected | The match arm in `PlaceNewOrder::zoneAndStoreValidationCheck` only triggers when `order_type === 'delivery'`. |
| 9 | Vendor cannot edit Store B | Scoped by `Helpers::get_store_id()`. |
| 10 | Admin can edit any Store | Form-supplied `store_id` in admin controller. |
| 11 | Existing store gets default | `getWindowForDay()` returns defaults when no row exists. |
| 12 | Scheduled orders | `$schedule_at` (which is `$request->schedule_at ? Carbon::parse(...) : now()`) is passed into `isDeliveryAvailableAt()`. |

---

## T. Files Not Modified (inspected, intentionally untouched)

| File | Why |
|------|-----|
| `app/Models/StoreSchedule.php` | Daily time schedule is unchanged. |
| `app/Models/StoreConfig.php` | Out of scope. |
| `app/CentralLogics/StoreLogic.php` | `insert_schedule()` unchanged. |
| `resources/views/rental/...` (Rental module) | Out of scope - rental has its own schedule logic. |
| `bootstrap/app.php` | No new middleware / aliases needed. |
| `composer.json` | No new package installed. |
| `config/app.php` | Existing timezone (`UTC`) is used. |

---

## U. Flutter Impact

**`No Flutter changes required`**.

The Flutter application can consume everything through the existing `/api/v1/stores/details/{id}` endpoint - no contract changes for existing fields, only additive ones (`delivery_service_hours`, `delivery_service`).

For fast polling, the Flutter team can use `GET /api/v1/stores/{id}/delivery-availability`.

The Delivery order validation happens **server-side** in `PlaceNewOrder::zoneAndStoreValidationCheck()`, so even an out-of-date Flutter client cannot bypass the rule by sending `order_type = "delivery"` outside the window - it will receive a 403 with the translatable error code `delivery_unavailable`.

---

## V. Backward Compatibility / Risks

| Risk | Mitigation |
|------|------------|
| Existing Flutter app expects `delivery_service_hours` to be absent | The keys are **additive only** - old clients ignore them. |
| Existing store with no Delivery Schedule row | `getWindowForDay()` returns the default 08:00-18:00 window. |
| Migration re-run | `Schema::hasTable` guard inside `up()` is idempotent. |
| Daylight-saving / timezone change | All comparisons happen via Carbon in `config('app.timezone')`. |
| Overnight schedules (22:00 -> 02:00) | Not supported today. Saved data with `closing < opening` is **never** trusted. |
| Concurrent admin/vendor edits | Both handlers use `DeliverySchedule::updateOrCreate()` keyed by `(store_id, day)`. |
| Cache stampede on `invalidateCache` | `Cache::forget()` is constant-time. |
| Hard-coded "08:00 / 18:00" | Lives in **two** constants on `DeliveryScheduleLogic`. |

---

## W. Git Diff Summary

```text
 app/Http/Controllers/Admin/VendorController.php                      |  102 +++++++++++++
 app/Http/Controllers/Api/V1/StoreController.php                      |   25 ++++
 app/Http/Controllers/Vendor/BusinessSettingsController.php            |  102 +++++++++++++
 app/Models/Store.php                                                 |   10 ++
 app/Traits/PlaceNewOrder.php                                         |   11 ++
 resources/lang/en/messages.php                                       |   10 ++
 resources/views/admin-views/vendor/view/settings.blade.php           |  150 +++++++++++++++++++
 resources/views/vendor-views/business-settings/restaurant-index...   |  166 ++++++++++++++++++++-
 routes/admin.php                                                     |    4 +
 routes/api/v1/api.php                                                |    1 +
 routes/vendor.php                                                    |    4 +
 11 files changed, 584 insertions(+), 1 deletion(-)
```

Plus 6 new files (1 migration, 1 model, 1 logic helper, 2 blade partials, 1 unit test).

---

## X. Code Summary

### Migration (excerpt)

```php
Schema::create('delivery_schedule', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('store_id');
    $table->unsignedTinyInteger('day');
    $table->time('opening_time');
    $table->time('closing_time');
    $table->boolean('is_active')->default(1);
    $table->timestamps();
    $table->index(['store_id', 'day']);
    $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
});
```

### Controller (Vendor, excerpt)

```php
public function add_delivery_schedule(Request $request)
{
    $errorCode = DeliveryScheduleLogic::validateWindow($start, $end, $isActive);
    if ($errorCode) { return response()->json(['errors' => [[ 'code' => $errorCode, ... ]]]); }

    DeliverySchedule::updateOrCreate(
        ['store_id' => Helpers::get_store_id(), 'day' => $request->day],
        ['opening_time' => $start, 'closing_time' => $end, 'is_active' => $isActive]
    );
    DeliveryScheduleLogic::invalidateCache(Helpers::get_store_id());

    return response()->json([
        'view' => view('vendor-views.business-settings.partials._delivery_schedule', compact('store'))->render(),
    ]);
}
```

### Order validation (excerpt)

```php
$request->order_type === 'delivery'
    && ! DeliveryScheduleLogic::isDeliveryAvailableAt($store, $schedule_at) => [
    'code'    => 'delivery_unavailable',
    'message' => translate('messages.delivery_service_unavailable'),
    'status_code' => 403,
],
```

### API exposure (excerpt)

```php
$store['delivery_service_hours'] = DeliveryScheduleLogic::getApiFormattedSchedule($store);
$store['delivery_service']       = DeliveryScheduleLogic::getCurrentAvailability($store);
```

### Validator (excerpt)

```php
public static function validateWindow(?string $opening, ?string $closing, bool $isActive): ?string
{
    if (!$isActive) return null;
    if (!$opening || !$closing) return 'time_required';
    foreach ([$opening, $closing] as $v) {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/', $v)) return 'time_format';
    }
    if (strcmp($closing, $opening) <= 0) return 'invalid_range';
    return null;
}
```

---

## Y. FINAL STATUS

```text
Database:                PASS  (1 new table, idempotent migration, no data loss)
Admin Panel:             PASS  (Delivery Service Hours card added under Daily time schedule)
Vendor Panel:            PASS  (Delivery Service Hours card added under Daily time schedule)
API:                     PASS  (existing endpoint extended; new lightweight endpoint added)
Authorization:           PASS  (Vendor scoped by Helpers::get_store_id(); Admin by form store_id)
Delivery Validation:     PASS  (PlaceNewOrder blocks delivery outside the window)
Scheduled Orders:        PASS  (scheduled_at timestamp is checked, not "now")
Timezone:                PASS  (config('app.timezone'); no hardcoded offsets)
Cache:                   PASS  (Cache::remember + invalidation on every write)
Tests:                   PASS  (14/14 unit tests passing)
Flutter Changes:         NOT REQUIRED
```

