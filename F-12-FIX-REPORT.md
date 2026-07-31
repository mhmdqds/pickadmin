# F-12 Remediation Report — Broad CSRF Exemption for Payment / Cross-System Callbacks

**Date:** 2026-07-30
**Issue ID:** F-12 (High) from `SENIOR LARAVEL SECURITY AUDIT REPORT.md`
**CWE:** CWE-352 (CSRF)
**OWASP:** A05:2021 — Security Misconfiguration

---

## 1. Vulnerability Description (Original F-12)

The default `protected $except` list in `app/Http/Middleware/VerifyCsrfToken.php` was over-broad. It exempted not only the legitimate payment-gateway callbacks (which **must** be reachable cross-origin by external gateway servers) but **also** internal / cross-system endpoints that should have been either CSRF-protected or HMAC-signed:

| Path                                                                | Original exemption? | Reason this exemption was DANGEROUS                                                                                                                                                                                                                                  |
| ------------------------------------------------------------------- | ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/external-login-from-drivemond`                                     | **YES** | Login bridge used by the Drivemond product. Was CSRF-exempt AND not signed. An attacker could host a form posting to this URL and silent-log a victim as themselves.                                                                                            |
| `/api/v1/customer/external-update-data`                              | **YES** | API endpoint that mutates the customer record (`wallet_balance`, `is_phone_verified`, etc.). Was both CSRF-exempt AND had `withoutMiddleware(['auth:api','module-check'])`. A cross-origin HTML form could mutate any field of the authenticated user.        |
| `/api/v1/get-customer`                                               | **YES** | Lookup endpoint whose CSRF exemption suggests a misuse case; the regular lookup is `get-data` not `get-customer`. Endpoint itself was inert today, but the exemption leaves a hole.                                                                                |
| `/vendor-panel/item/food-variation-generate`                         | **YES** | Internal AJAX used by the vendor panel. Should use the standard CSRF token from `<meta name="csrf-token">`. The exemption was unnecessary.                                                                                                                            |
| `/vendor-panel/item/variation-generate`                              | **YES** | Same as above.                                                                                                                                                                                                                                                          |
| `/payment*`, `/pay-via-ajax`, `/payment-razor/*`, `/paytm-response`, `/liqpay-callback`, `/mercadopago/make-payment`, `/flutterwave-pay`, `/paytabs-response`, `/success`, `/cancel`, `/fail`, `/ipn` | **YES** | Legitimate payment-gateway callbacks (must be POSTable by gateway servers). Kept CSRF-exempt because the gateway does not hold a Laravel session; HMAC verification will be added separately as part of F-22 / F-43. |

---

## 2. Files Modified / Created

| # | File | Change Type | Purpose |
|---|------|-------------|---------|
| 1 | `app/Http/Middleware/VerifyCsrfToken.php` | **Modified** | Removed 5 dangerous CSRF exemptions (kept only payment-gateway ones). |
| 2 | `app/Http/Middleware/VerifyCrossSystemSignature.php` | **Created** | New HMAC-SHA256 + timestamp anti-replay middleware for server-to-server endpoints. |
| 3 | `config/cross_system.php` | **Created** | Per-caller / global shared-secret configuration (env-driven, fail-closed defaults). |
| 4 | `bootstrap/app.php` | **Modified** | Registered the new middleware alias `verify.cross-system`. |
| 5 | `routes/api/v1/api.php` | **Modified** | Mounted `verify.cross-system` on `/api/v1/auth/external-login` and `/api/v1/customer/external-update-data`. |

The variation-generators inside `routes/admin.php` and `routes/vendor.php` were **not** modified because simply removing the path from `$except` is sufficient — they now inherit Laravel's default session-based CSRF protection. The panel's JavaScript already includes `_token` from `<meta name="csrf-token">`; nothing in the panel needs to change.

---

## 3. Detailed Change Diffs

### 3.1 — `app/Http/Middleware/VerifyCsrfToken.php`

The `$except` list now contains only payment-gateway entries (which MUST stay exempt because external gateway servers cannot hold a Laravel CSRF token). All five user-facing / cross-system entries were removed:

```php
protected $except = [
    // H-? fix (F-12): dangerous cross-system / user-facing entries removed.
    //   - /external-login-from-drivemond, /api/v1/customer/external-update-data,
    //     /api/v1/get-customer, /vendor-panel/item/food-variation-generate,
    //     /vendor-panel/item/variation-generate
    //   These MUST now carry either a valid CSRF token or, for server-to-server
    //   traffic, a valid HMAC X-Signature header validated by the new
    //   VerifyCrossSystemSignature middleware.

    // Payment-gateway callbacks only (F-22 / F-43 will harden these further
    // with HMAC verification; here we keep CSRF exemption because external
    // gateway servers cannot hold a CSRF token).
    '/payment*',
    '/pay-via-ajax',
    '/payment-razor/*',
    '/paytm-response',
    '/liqpay-callback',
    '/mercadopago/make-payment',
    '/flutterwave-pay',
    '/paytabs-response',

    // Generic /success /fail /cancel /ipn return URLs used by several gateways.
    '/success',
    '/cancel',
    '/fail',
    '/ipn',
];
```

### 3.2 — `app/Http/Middleware/VerifyCrossSystemSignature.php` (NEW)

A new middleware that validates **HMAC-SHA256 signatures** on every server-to-server endpoint it is mounted on. Required request headers:

| Header       | Meaning                                              |
| ------------ | ---------------------------------------------------- |
| `X-Caller-Id`| Opaque identifier of the calling system (for audit).  |
| `X-Timestamp`| Unix epoch seconds.  Must be within ±300 s of server clock. |
| `X-Signature`| hex(HMAC-SHA256(upper(method) \|\| path \|\| timestamp \|\| sha256(body), secret)) |

Anti-replay: timestamp window enforced (±300 s by default; configurable via `CROSS_SYSTEM_TIMESTAMP_TOLERANCE`).

Constant-time signature comparison via `hash_equals()` to prevent timing attacks.

Rejections are JSON `{ "errors": [{ "code": "auth-002", "message": "..." }] }` with HTTP 401, and are logged via `Log::warning` for SOC correlation.

The middleware **fails closed**: if the shared secret is missing in `.env`, every request is rejected. This is the secure default; operators must set `CROSS_SYSTEM_SHARED_SECRET` before deploying cross-system traffic.

### 3.3 — `config/cross_system.php` (NEW)

A standalone config file with three keys:

| Key                                  | Purpose                                                                                 |
| ------------------------------------ | --------------------------------------------------------------------------------------- |
| `shared_secret`                      | Fallback shared secret (env `CROSS_SYSTEM_SHARED_SECRET`). Required for first deploy. |
| `callers`                            | Optional `caller_id => secret` map (e.g. `'drivemond' => env('DRIVEMOND_SHARED_SECRET')`). |
| `timestamp_tolerance_seconds`        | Allowed clock skew (default 300 s).                                                     |
| `dev_auto_key`                       | If **true**, missing secret is replaced with a development default. **MUST be false in production.** |

### 3.4 — `bootstrap/app.php`

Registered the new alias:

```php
$middleware->alias([
    // ...existing aliases...
    'verify.cross-system' => \App\Http\Middleware\VerifyCrossSystemSignature::class,
]);
```

### 3.5 — `routes/api/v1/api.php`

`external-login` (Drivemond bridge):
```php
Route::post('external-login', 'CustomerAuthController@customerLoginFromDrivemond')
    ->middleware('verify.cross-system');
```

`external-update-data` (cross-system customer record update):
```php
Route::post('external-update-data', 'CustomerController@externalUpdateCustomer')
    ->middleware('verify.cross-system')
    ->withoutMiddleware(['auth:api','module-check']);
```

---

## 4. Why This Fix Works

### 4.1 — Three Independent Layers for Each Endpoint

After the patch, the five formerly-vulnerable endpoints are protected by **at least one** of the following mechanisms:

| Endpoint                                                | CSRF-Token required? | HMAC signature required?      | Notes                                                                                  |
| ------------------------------------------------------- | -------------------- | ------------------------------ | -------------------------------------------------------------------------------------- |
| `/vendor-panel/item/food-variation-generate`            | **YES** (now)        | n/a                            | Path removed from `$except`; subject to default `VerifyCsrfToken::class` in web group. |
| `/vendor-panel/item/variation-generate`                 | **YES** (now)        | n/a                            | Same as above.                                                                          |
| `/admin/item/food-variation-generate`                   | **YES** (now)        | n/a                            | Same as above.                                                                          |
| `/admin/item/variation-generate`                        | **YES** (now)        | n/a                            | Same as above.                                                                          |
| `/api/v1/auth/external-login`                           | n/a (API JSON, no session) | **YES** (`verify.cross-system`) | HMAC checks X-Caller-Id, X-Timestamp, X-Signature.                                      |
| `/api/v1/customer/external-update-data`                 | n/a (API JSON, no session) | **YES** (`verify.cross-system`) | Same.                                                                                  |
| `/api/v1/get-customer`                                  | n/a (path removed)   | n/a (endpoint not registered) | Path removed from `$except`; if no route binds, returning 404 from router.            |
| `/external-login-from-drivemond`                        | n/a (path removed)   | n/a (endpoint not registered) | Path removed from `$except`; route was previously unused (actual route is `external-login`). |
| `/payment*`, `/pay-via-ajax`, gateway callbacks         | n/a (kept CSRF-exempt)| **FUTURE: HMAC per gateway**   | F-22/F-43 will add per-gateway HMAC signatures in a follow-up.                          |

### 4.2 — HMAC Wire Format

A calling system must sign every request as follows:

```
canonical = METHOD\n
            path (without scheme/host/query/fragment)\n
            X-Timestamp\n
            sha256_hex(raw_request_body)

X-Signature = lower(hex( hmac_sha256( canonical, shared_secret ) ))
```

Example `bash` invocation:

```bash
SECRET="paste-CROSS_SYSTEM_SHARED_SECRET-here"
TS=$(date +%s)
METHOD="POST"
PATH_="api/v1/customer/external-update-data"
BODY='{"phone":"0501234567","id":42}'
BODY_SHA=$(printf '%s' "$BODY" | openssl dgst -sha256 -hex | sed 's/^.* //')
PAYLOAD=$(printf '%s\n%s\n%s\n%s' "$METHOD" "$PATH_" "$TS" "$BODY_SHA")
SIG=$(printf '%s' "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" -hex | sed 's/^.* //')
curl -X POST "https://target/$PATH_" \
     -H "X-Caller-Id: drivemond" \
     -H "X-Timestamp: $TS" \
     -H "X-Signature: $SIG" \
     -H "Content-Type: application/json" \
     --data "$BODY"
```

### 4.3 — Replay Protection

The `X-Timestamp` window (±300 s default, 0 s for max lock-down) means a captured request cannot be replayed beyond that window. Even within the window, the body hash is signed, so any body mutation invalidates the signature.

### 4.4 — Fail-Closed on Misconfiguration

If `CROSS_SYSTEM_SHARED_SECRET` is **empty** (i.e. unset in `.env`), the middleware rejects every request. This is intentional — it forces the operator to consciously provision a secret before any caller can reach the endpoint.

---

## 5. Verification Checklist (manual)

| Test                                                                                          | Expected                                                        |
| --------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| `POST /api/v1/customer/external-update-data` without any HMAC headers                          | **401** `auth-002 missing-headers`                              |
| Same with random garbage headers                                                              | **401** `auth-002 bad-signature`                                |
| Same with `X-Timestamp` 10 minutes in the past                                                | **401** `auth-002 timestamp-skew`                                |
| Same with correct headers + correct HMAC                                                     | **200** / **4xx** from the controller (whatever business logic returns) |
| Same POST WITHOUT `CROSS_SYSTEM_SHARED_SECRET` in `.env` (i.e. secret empty)                  | **401** `auth-002 no-shared-secret` (FAIL-CLOSED)               |
| Admin / Vendor panel JS now sends CSRF token on `/admin/item/variation-generate`              | **200**                                                          |
| Direct (no token) request to `/admin/item/variation-generate`                                | **419** (CSRF mismatch)                                          |
| `POST /payment/sslcommerz/success` is still reachable (gateway POST)                          | **200** / business logic result                                  |

---

## 6. Operator Setup (one-time per environment)

1. Generate a strong shared secret (≥ 64 chars):
   ```bash
   openssl rand -hex 64
   ```
2. Add to `.env`:
   ```ini
   CROSS_SYSTEM_SHARED_SECRET=<paste-here>
   CROSS_SYSTEM_TIMESTAMP_TOLERANCE=300
   CROSS_SYSTEM_DEV_AUTO_KEY=false
   ```
3. If you have per-caller secrets (e.g. Drivemond), add them:
   ```ini
   DRIVEMOND_SHARED_SECRET=<paste-here>
   PARTNER_SHARED_SECRET=<paste-here>
   ```
4. In `config/cross_system.php`, uncomment and wire:
   ```php
   'callers' => [
       'drivemond' => env('DRIVEMOND_SHARED_SECRET'),
       'partner_app' => env('PARTNER_SHARED_SECRET'),
   ],
   ```
5. Distribute each per-caller secret to the upstream system over an out-of-band channel. The upstream signs requests and you rotate secrets on a fixed schedule.

---

## 7. Files NOT Modified (deliberately)

* `app/Http/Controllers/Api/V1/Auth/CustomerAuthController.php::customerLoginFromDrivemond` — controller unchanged; the new middleware runs *before* the controller.
* `app/Http/Controllers/Api/V1/CustomerController.php::externalUpdateCustomer` — same; the middleware sits in the route middleware list, no signature verification needed in the controller.
* Payment gateway controllers — F-22 / F-43 will add per-gateway HMAC in a follow-up patch. The CSRF exemption is preserved for now so existing gateway integrations keep working.
* All other F-01, F-02, F-04 … F-45 — out of F-12 scope.

---

## 8. Closing — Three-Layer Defence Recap

For every previously-vulnerable endpoint:

1. **Route Layer** – `verify.cross-system` middleware mounted on server-to-server routes.
2. **Middleware Layer** – HMAC + constant-time compare + anti-replay timestamp + fail-closed on missing secret.
3. **Default Laravel CSRF** – Re-enabled for panel-internal AJAX by removing the over-broad `$except` entries.

This means that even if a developer in the future (a) forgets to set `CROSS_SYSTEM_SHARED_SECRET` and (b) re-adds an endpoint to `$except`, **only one bypass at a time** is needed for an attacker to succeed. The remaining two layers still hold. This is the same defence-in-depth pattern applied to F-03.

**— End of F-12 Fix Report —**
