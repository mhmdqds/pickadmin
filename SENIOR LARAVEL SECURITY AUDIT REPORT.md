# 🔒 SENIOR LARAVEL SECURITY AUDIT REPORT
## Project: `pickadmin` (Laravel 12 — 6amtech multi-vendor food/parcel/ride-share platform)

**Audit Type:** Read-only — Black/Grey/White-box review
**Methodology:** OWASP Top 10 (2021), OWASP API Security Top 10 (2023), CWE, ASVS, Laravel 12 best practices
**Scope:** `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `public/`, `resources/`, `storage/`, `composer.json`, `package.json`, `.env.example`, middleware, models, services, providers, migrations (239 files)
**Date:** 2026-07-30
**Auditor:** Senior Laravel / OWASP Auditor

> **No source code was modified during this audit.** (Confirmed via `git status`; only pre-existing deleted test files were observed in the working tree, which were already removed before the audit and were not touched by this report.)

---

## SECURITY SCORE: **38 / 100**

| Component | Score |
|---|---|
| Authentication | 35/100 |
| Authorization / IDOR | 40/100 |
| Input Validation | 45/100 |
| Cryptography / Secrets | 55/100 |
| File Upload | 45/100 |
| API Security | 40/100 |
| Configuration | 30/100 |
| Logging / Error Handling | 50/100 |
| Dependencies | 60/100 |
| Payments / Webhooks | 50/100 |
| Business Logic | 45/100 |

### **Risk Level: 🔴 CRITICAL — NOT PRODUCTION READY**

| Severity | Count |
|---|---|
| 🔴 Critical | **6** |
| 🟠 High | **18** |
| 🟡 Medium | **24** |
| 🔵 Low | **17** |
| ℹ️ Info | **12** |

---

## 🧨 TOP 20 PRIORITIES (FIX FIRST)

1. **Remove `eval()` from `PaymentController::__construct` (C-1)** — Remote Code Execution risk
2. **Disable debug / set `APP_DEBUG=false` in production (C-2)** — Stack-trace disclosure
3. **Restrict CORS to trusted origins (C-3)** — Currently `*` on all `/api/*`
4. **Validate webhook signatures on every gateway (BEFORE marking `is_paid=1`) (C-4 / H-1..H-4)**
5. **Force HTTPS session cookies + rotate APP_KEY & invalidate all sessions (H-5)**
6. **Add ownership checks to chat/message/notification endpoints (H-7, H-8)** — IDOR
7. **Enforce authentication on every API controller — `RateLimiter`, `Order`, `Item`, `Customer` (H-6)**
8. **Enforce Server-Side Render (escape) — `e()` / `{{ }}` — never `{!! !!}` (H-9)**
9. **Move `APP_KEY` out of `.env.example` and into Vault (C-5)**
10. **Disable plain-text Pusher/Reverb credentials in `.env.example` (C-5)**
11. **Lock down `/image-proxy` SSRF + `/test` `Artisan::call` route (H-10, H-11)**
12. **Fix race condition in payment-success updates (DB::transaction + lock) (H-12)**
13. **Replace `APP_KEY=base64:…` static dev key + force new key on production (H-13)**
14. **Add CSRF to admin / vendor web panel critical mutations (H-14)**
15. **Migrate payment gateways to HTTPS / verify peer by default — turn off `CURLOPT_SSL_VERIFYPEER=0` (H-15)**
16. **Add SQL parameter binding for any remaining `whereRaw` (`available_time_starts`, `IF(((select count…)` etc.) (H-16)**
17. **Add explicit File-type / MIME / size validation on every image / file upload route (H-17)**
18. **Rate-limit login + password reset endpoints (H-18)**
19. **Strip sensitive data (`password`, `token`, `secret`) from logs — currently logged via `info()` (M-3)**
20. **Enforce PII masking in error/log output (M-4)**

---

## 🧾 FINDINGS (FULL REPORT)

Each finding includes: **Title · Severity · CWE · OWASP · File · Class · Method · Lines · Description · Attack Scenario · Impact · Evidence · Confidence · Recommendation · Example Fix (illustrative only — NOT applied).**

---

## 🔴 CRITICAL FINDINGS (6)

### C-1. Remote Code Execution via `eval()` in `PaymentController` constructor
- **Severity:** Critical
- **CWE-95:** Improper Neutralization of Directives in Dynamically Evaluated Code
- **OWASP:** A03:2021 – Injection
- **File:** `app/Http/Controllers/PaymentController.php`
- **Class:** `App\Http\Controllers\PaymentController`
- **Method:** `__construct()` → `extendWithPaymentGatewayTrait()` → `generateExtendedControllerClass()`
- **Lines:** 14–40 (and referenced at 24–26)
- **Description:** The constructor calls `eval($extendedControllerClass)` to dynamically load a `App\Traits\Payment` trait. Although the class string is locally generated, this `eval()` opens a code-execution sink and is a known dangerous anti-pattern. In addition, a future bug in string assembly could allow an attacker to control evaluated code.
- **Attack Scenario:** A future modification of `generateExtendedControllerClass()` (e.g., reading trait name from `config()` or DB) would allow trivial RCE.
- **Impact:** Full PHP code execution under web user.
- **Evidence:**
  ```php
  private function extendWithPaymentGatewayTrait()
  {
      $extendedControllerClass = $this->generateExtendedControllerClass();
      eval($extendedControllerClass);   // ← ARBITRARY CODE EXEC
  }
  ```
- **Confidence:** High (the construct itself is dangerous; risk is high).
- **Recommendation:** Replace `eval` with a direct `use App\Traits\Payment;` declaration in a dedicated subclass or via `class_uses_recursive()` runtime trait loading.
- **Example Fix (illustrative):**
  ```php
  // Move trait to dedicated subclass:
  // class MobilePaymentController extends Controller { use Payment; }
  // And remove the dynamic eval entirely.
  ```

---

### C-2. `APP_DEBUG=true` & `LOG_LEVEL=debug` in `.env.example` — Information Disclosure
- **Severity:** Critical
- **CWE-209:** Generation of Error Message Containing Sensitive Information
- **CWE-489:** Active Debug Code
- **OWASP:** A05:2021 – Security Misconfiguration
- **File:** `.env.example`
- **Lines:** 1–10
- **Description:** Debug mode exposes stack traces, environment variables, file paths, and SQL queries on errors. `LOG_LEVEL=debug` persists sensitive payloads in `storage/logs/laravel.log`.
- **Attack Scenario:** A forced error reveals DB host/credentials, secret keys, and source paths.
- **Impact:** Complete information disclosure, easier exploitation of other vulns.
- **Evidence:** `.env.example` lines 3, 9: `APP_DEBUG=true`, `LOG_LEVEL=debug`.
- **Confidence:** High
- **Recommendation:** Ship `.env.example` with `APP_DEBUG=false`, `LOG_LEVEL=error`, and document that production must rotate keys.
- **Example Fix:**
  ```dotenv
  APP_DEBUG=false
  LOG_LEVEL=error
  ```

---

### C-3. CORS Wildcard + Credentials Not Locked (`allowed_origins: ['*']`)
- **Severity:** Critical
- **CWE-942:** Permissive Cross-domain Policy
- **OWASP:** A05:2021 – Security Misconfiguration / API5:2023
- **File:** `config/cors.php`
- **Lines:** 18–32
- **Description:** `paths: ['api/*']` is exposed to any origin, any method, any header. Although `supports_credentials` is `false` here, sensitive API endpoints (orders, payments, profile, chat) are still callable cross-origin, allowing CSRF-like exfiltration of JSON responses via `fetch()`.
- **Attack Scenario:** A malicious site reads an authenticated user's orders, modifies cart, or triggers refunds if the user has an active session.
- **Impact:** Cross-origin data theft / write abuse.
- **Evidence:**
  ```php
  'paths' => ['api/*'],
  'allowed_methods' => ['*'],
  'allowed_origins' => ['*'],
  'allowed_headers' => ['*'],
  ```
- **Confidence:** High
- **Recommendation:** Replace with explicit allow-list; enable `supports_credentials` only when the API uses cookies and CSRF protection is in place.
- **Example Fix:**
  ```php
  'allowed_origins' => ['https://admin.example.com', 'https://app.example.com'],
  'allowed_methods' => ['GET','POST','PUT','DELETE'],
  ```

---

### C-4. Webhook Signature Validation Missing / Inconsistent — Replay / Free-Payment Attacks
- **Severity:** Critical
- **CWE-345:** Insufficient Verification of Data Authenticity
- **CWE-352:** Missing CSRF (cross-state webhook forgery)
- **OWASP:** A04:2021 – Insecure Design / API2:2023
- **Files / Methods / Lines:**
  - `BkashPaymentController::callback()` lines 134–183 — only checks `$obj->statusCode == '0000'` (no HMAC of payload)
  - `RazorPayController::payment()` lines 73–97 — never calls `api->utility->verifyPaymentSignature()`
  - `MercadoPagoController::callback()` lines 107–118 — trusts `?status=success` (URL controlled)
  - `SenangPayController::return_senang_pay()` lines 59–78 — trusts `?status_id=1`
  - `PaytmController::callback()` lines 227–249 — only checks `STATUS==TXN_SUCCESS`, no checksum re-verification
  - `StripePaymentController::success()` lines 102–128 — relies on session retrieval but never validates webhook signature
  - `PaystackController::handleGatewayCallback()` lines 103–125 — only checks `$paymentDetails['status'] == true` (rely on request body)
- **Description:** Many gateways mark `is_paid=1` and trigger `order_place` hook without a server-to-server signature verification. Replay, free-payment, and forced-failure attacks are possible.
- **Attack Scenario:** Attacker POSTs `?status=success&payment_id=…` to the MercadoPago callback URL and gets order confirmation without paying.
- **Impact:** Free orders, financial loss, race-conditions on `payment_request` row.
- **Evidence:** See above; Bkash does not even verify token; Paystack does not verify HMAC.
- **Confidence:** High
- **Recommendation:** All gateways must re-verify their signature (Stripe `Signature` header, Razorpay `verifyPaymentSignature`, Paymob `hmac`, Paystack `paystack-signature`, PayTabs `signature`, SSLCZ `verify_sign`) using `hash_equals()`.
- **Example Fix:** Use Laravel's built-in `WebhookSignature` middleware or implement `hash_equals($expected, $request->header('signature'))`.

---

### C-5. Static Secrets in `.env.example` (Pusher / Reverb / APP_KEY)
- **Severity:** Critical
- **CWE-798:** Use of Hard-coded Credentials
- **CWE-321:** Use of Hard-coded Cryptographic Key
- **OWASP:** A07:2021 – Identification & Authentication Failures
- **File:** `.env.example`
- **Lines:** 3, 44–57
- **Description:** The file ships with a real `APP_KEY=base64:/NC6CNBiDJb2vV4fRviEsMqy5gKbePRgk44JGkZFAYY=` and `REVERB_APP_ID=6ammart`, `REVERB_APP_KEY=6ammart`, `REVERB_APP_SECRET=6ammart`, `PUSHER_APP_ID=6ammart`, `PUSHER_APP_KEY=6ammart`, `PUSHER_APP_SECRET=6ammart`. If copied to production, these become public broadcast credentials and a known APP_KEY.
- **Attack Scenario:** Reverb/Pusher attackers can subscribe to topics and forge WebSocket events; known APP_KEY allows decrypting any existing encrypted data.
- **Impact:** Full broadcast spoofing, cryptographic key disclosure, session-token predictability (when encrypted with this key).
- **Evidence:** Lines 3 and 44–57.
- **Confidence:** High
- **Recommendation:** Replace placeholders with empty strings, document `php artisan key:generate`, and rotate any key shipped in repo.
- **Example Fix:**
  ```dotenv
  APP_KEY=
  REVERB_APP_ID=
  REVERB_APP_KEY=
  REVERB_APP_SECRET=
  ```

---

### C-6. Mass Assignment + Missing Ownership on `Order`, `Store`, `Item` API Controllers (combines IDOR + Mass Assignment)
- **Severity:** Critical
- **CWE-915:** Improperly Controlled Modification of Dynamically-Determined Object Attributes
- **CWE-639:** Authorization Bypass Through User-Controlled Key
- **OWASP:** A01:2021 – Broken Access Control / A04 Insecure Design
- **Files / Methods / Lines:**
  - `app/Http/Controllers/Api/V1/OrderController.php` (entire file; many endpoints accept `order_id` & `user` without ownership check)
  - `app/Http/Controllers/Api/V1/StoreController.php` (no auth on storefront endpoints, but `update_*` is unauthenticated on internal mutations)
  - `app/Http/Controllers/Api/V1/CustomerController.php` (`remove_account`, `update_profile` properly require `auth:api`; but `get-data` and `external-update-data` are explicitly unauth)
  - `app/Http/Controllers/Api/V1/WalletController.php` (`transfer-mart-from-drivemond` is `withoutMiddleware('auth:api')`)
- **Description:** Several endpoints accept a `user_id` / `order_id` and act on it without verifying ownership. Combined with the broad `protected $guarded = ['id']` on `User` and `Item`, an attacker can `update` arbitrary records.
- **Attack Scenario:** Attacker forges `POST /api/v1/customer/order/cancel {order_id: <victim>}` → cancels victim's order.
- **Impact:** Cross-tenant data modification, financial loss.
- **Evidence:** `OrderController` controllers do `Order::where('id', $request->order_id)->update(...)` and only check global rules; no `where user_id = auth()->id()`.
- **Confidence:** High
- **Recommendation:** Every record-touching action must constrain by `auth()->id()` (or `auth('vendor')->id()` etc.) and use policies.
- **Example Fix:**
  ```php
  $order = Order::where('id', $request->order_id)
               ->where('user_id', $request->user()->id)
               ->firstOrFail();
  ```

---

## 🟠 HIGH FINDINGS (18)

### H-1. `BkashPaymentController::callback()` trusts `$_GET['paymentID']` and `$_GET['token']` without verification
- **Severity:** High
- **CWE-345 / CWE-352**
- **File:** `app/Http/Controllers/BkashPaymentController.php`
- **Method:** `callback()` lines 134–183
- **Description:** Reads `$_GET['paymentID']` / `$_GET['token']`, uses them directly to call bKash API. Although the order is "marked paid" only when statusCode 0000 is returned, the function never authenticates the request — replay possible.
- **Confidence:** High
- **Recommendation:** Validate the bKash `Authorization` server-to-server and require signed webhook.

---

### H-2. `RazorPayController::payment()` never calls `verifyPaymentSignature`
- **Severity:** High
- **CWE-345**
- **File:** `app/Http/Controllers/RazorPayController.php`
- **Method:** `payment()` lines 73–97
- **Description:** Captures payment with the customer-supplied `razorpay_payment_id` without verifying the signature. `verifyPayment()` is only invoked in `verifyPayment()` but the production path doesn't always go through it.
- **Confidence:** High
- **Recommendation:** Always call `$api->utility->verifyPaymentSignature(...)` before marking `is_paid`.

---

### H-3. `MercadoPagoController::callback()` trusts client-controlled `?status=success`
- **Severity:** High
- **CWE-345**
- **File:** `app/Http/Controllers/MercadoPagoController.php`
- **Method:** `callback()` lines 107–118
- **Description:** Trusts `$request['status'] == 'success'` from query string.
- **Confidence:** High

---

### H-4. `SenangPayController::return_senang_pay()` trusts `?status_id=1`
- **Severity:** High
- **CWE-345**
- **File:** `app/Http/Controllers/SenangPayController.php`
- **Method:** `return_senang_pay()` lines 59–78
- **Confidence:** High

---

### H-5. Session Cookies not HTTPS-only by default; APP_KEY reuse / no rotation
- **Severity:** High
- **CWE-614 / CWE-757**
- **File:** `config/session.php`
- **Lines:** 34, 49, 171, 184, 199
- **Description:** `'secure' => env('SESSION_SECURE_COOKIE')` (null), `'same_site' => null`, `'encrypt' => false`. With no production override, session cookies travel over HTTP and are NOT encrypted. Sessions driver defaults to `file`; tokens stored in `storage/framework/sessions`.
- **Attack Scenario:** LAN/MitM steals admin session; integrity lost on shared host.
- **Confidence:** High
- **Recommendation:** Default `'secure' => true`, `'same_site' => 'lax'`, `'encrypt' => true`. Rotate APP_KEY & invalidate all sessions on suspected compromise.

---

### H-6. Authentication Middleware Missing / Bypassed on Critical API Routes
- **Severity:** High
- **CWE-306 / CWE-862**
- **File:** `routes/api/v1/api.php`
- **Description:** Many customer / store / order routes are inside the `Route::group(['middleware'=>['module-check']], …)` block **without** `auth:api`, `auth:vendor.api`, or `dm.api`. Examples:
  - `GET /api/v1/customer/saved-files`
  - `POST /api/v1/customer/saved-files/store`
  - `POST /api/v1/customer/external-update-data` (intentional `withoutMiddleware`)
  - `POST /api/v1/customer/wallet/transfer-mart-from-drivemond` (intentional `withoutMiddleware('auth:api')`)
  - `GET /api/v1/items/*` (read-only is OK, but `POST /api/v1/items/reviews/submit` is correctly guarded with `auth:api`)
- **Impact:** Anonymous data access / write.
- **Confidence:** High
- **Recommendation:** Move mutating endpoints under `auth:api`; never use `withoutMiddleware('auth:api')` for any production flow.

---

### H-7. Chat / Message IDOR (Customer, Vendor, Admin, Delivery-Man)
- **Severity:** High
- **CWE-639**
- **Files / Methods / Lines:**
  - `app/Http/Controllers/Api/V1/ConversationController.php`
    - `messages_store` lines 45–253: Accepts `conversation_id` without verifying the user is a participant; trusts `order_id` for first-support mirroring.
    - `conversations` lines 760–792: Filters only by `sender_id` / `receiver_id` but never validates ownership of the sender.
    - `messages` lines 838–947: Same.
    - `chat_image` lines 738–757: No validation, allows any file upload.
  - `app/Http/Controllers/Api/V1/Vendor/ConversationController.php`
    - `messages_store` lines 22–219: Uses `Conversation::find($request->conversation_id)` without ownership check; trusts user-supplied `receiver_id`/`receiver_type` to send messages as the vendor.
  - `app/Http/Controllers/Admin/ConversationController.php`
    - `view()` lines 48–64: Reads `Message::where('conversation_id', $id)->where('sender_id', $user_id)` and updates `is_seen`, but no CSRF / no per-admin permission.
  - `app/Http/Controllers/Admin/ConversationController.php`
    - `store()` lines 66–171: No validation of `$user_id`; admin can message any user.
- **Description:** Cross-user message injection, conversation enumeration.
- **Confidence:** High
- **Recommendation:** Add a Policy: `ConversationPolicy@view(User $u, Conversation $c)` verifying `$u->id === $c->sender_id || $u->id === $c->receiver_id` (or vendor/dm/admin). Pass through `Gate::authorize()`.
- **Example Fix:**
  ```php
  $this->authorize('view', $conversation);
  ```

---

### H-8. Notification API accepts arbitrary `fcm_token` with no scope
- **Severity:** High
- **CWE-862 / CWE-285**
- **File:** `routes/api/v1/api.php` lines 99–162 (`Route::group(['middleware'=>['dm.api']], …)`) and `app/Http/Controllers/Api/V1/DeliverymanController.php` `update_fcm_token`
- **Description:** The DM API uses the bearer token to find the DM, but never confirms device ownership. A token thief can register an attacker device.
- **Confidence:** High

---

### H-9. Blade `{!! !!}` Risk + ECHO of User-Controlled Data in Many Views
- **Severity:** High
- **CWE-79**
- **File:** `resources/views/admin-views/**`, `resources/views/vendor-views/**`, etc.
- **Description:** The codebase contains many `{{ }}` (safe) and likely `{!! !!}` for raw HTML rendering. Stored XSS via chat message, store name, or product name is possible if any field is rendered unescaped.
- **Recommendation:** Audit all `{!! !!}` occurrences; prefer `{{ }}` or `e()`. Apply Laravel CSP nonce and `Content-Security-Policy` header.
- **Confidence:** Medium (requires view-by-view audit; not all content was read).

---

### H-10. Open SSRF via `/image-proxy`
- **Severity:** High
- **CWE-918**
- **File:** `routes/web.php` lines 236–249 (`/image-proxy`)
- **Description:** Accepts arbitrary `?url=…` and proxies `Http::get($url)` with no allow-list, no protocol check (file://, gopher://), and no DNS pinning.
- **Attack Scenario:** Probe internal AWS metadata `http://169.254.169.254/`, internal services, or read `file:///etc/passwd` (depending on `Http` driver).
- **Impact:** Internal network reconnaissance / data theft.
- **Recommendation:** Allow-list trusted domains or signed URLs; block internal IPs via `Http::macro`.

---

### H-11. `Route::get('/test', fn () => Artisan::call('optimize:clear'))` exposed
- **Severity:** High
- **CWE-78** (OS Command Injection: indirect), CWE-94
- **File:** `routes/web.php` lines 200–206
- **Description:** Anonymous `GET /test` clears the entire cache and dumps output. In production this is a denial-of-service and reveals diagnostic info.
- **Impact:** Cache wipe (DoS); potential information disclosure.
- **Recommendation:** Remove the route, or restrict to `local` env via `app()->environment('local')`.

---

### H-12. Race Conditions on Payment / Order Status Updates
- **Severity:** High
- **CWE-362**
- **Files:**
  - `BkashPaymentController::callback()` lines 161–175
  - `RazorPayController::payment()` lines 79–90
  - `StripePaymentController::success()` lines 107–127
  - `PayPalPaymentController::success()` lines 178–191
  - `PlaceNewOrder::new_place_order()` lines 174–198 (`$lastId = Order::max('id') ?? 99999; $order->id = $lastId + 1;`)
- **Description:** `is_paid` is set without `lockForUpdate()`; multiple concurrent callbacks can each mark `is_paid=1` or trigger `success_hook` twice. Order ID assignment uses `max(id)+1` which is racy and may collide with concurrent inserts.
- **Recommendation:** Wrap inside `DB::transaction` with `lockForUpdate()`. Remove manual `max()+1` and use auto-incrementing ID.

---

### H-13. Production-Grade APP_KEY is Hard-Coded in `.env.example`
- **Severity:** High
- **CWE-321**
- **File:** `.env.example` line 3
- **Description:** See C-5. Same root cause.

---

### H-14. CSRF `VerifyCsrfToken` Excludes Payment + Webhook + Vendor Item Variation
- **Severity:** High
- **CWE-352**
- **File:** `app/Http/Middleware/VerifyCsrfToken.php` lines 14–17
- **Description:** `/payment*`, `/payment-razor/*`, `/paytm-response`, `/mercadopago/make-payment`, `/pay-via-ajax`, etc. are excluded from CSRF. These are GET-returning endpoints but the design of some (`/payment`) accepts POST without CSRF.
- **Recommendation:** Validate signature on webhook (preferred). For web flows, use signed routes or session-based CSRF.

---

### H-15. `CURLOPT_SSL_VERIFYHOST=0` and `CURLOPT_SSL_VERIFYPEER=0` for Pusher / SSLCommerz
- **Severity:** High
- **CWE-295**
- **Files:**
  - `config/broadcasting.php` lines 58–61
  - `SslCommerzPaymentController.php` line 124
- **Description:** TLS validation is disabled in pusher config and "demo/test/dev" environments in SSLCommerz, allowing MitM.
- **Recommendation:** Default `verify_peer => true`; gate `false` only behind explicit env and never in production.

---

### H-16. Raw SQL Fragments (no user input, but `whereRaw` patterns everywhere)
- **Severity:** High
- **CWE-89**
- **Files:** `app/CentralLogics/CategoryLogic.php`, `app/CentralLogics/StoreLogic.php`, `app/CentralLogics/Helpers.php`, `app/Models/Store.php`
- **Description:** Many `whereRaw('available_time_starts < available_time_ends AND TIME(?) BETWEEN …')` are bound, but `IF(((select count(*) from store_schedule …)) > 0)` in `Store::scopeWithOpen` is fully concatenated. If the schema is altered or a column receives unsanitized input, an injection becomes possible.
- **Recommendation:** Use bindings; add static analysis (`nunomaduro/collision` is already a dev dep).

---

### H-17. Insufficient File Validation on Image / File Upload Routes
- **Severity:** High
- **CWE-434 / CWE-436**
- **File:** `app/CentralLogics/Helpers.php` `upload()` lines 2575–2613
- **Description:** `upload()` accepts a `$format` argument from the caller and uses it to set the destination extension. Several callers (e.g. `FileManagerController`, `chat_image`, `ConversationController::messages_store`) do not pass `allowedExtensions` / `maxSizeMb`. The `validateFile` helper is invoked, but `IMAGE_FORMAT_FOR_VALIDATION` is a global; if not defined in this scope, file falls through.
- **Recommendation:** Always pass `allowedExtensions` and `maxSizeMb`; reject `php`, `phtml`, `phar`, `svg`, `htaccess` for image endpoints.
- **Evidence:** `Helpers.php` line 2580: `self::validateFile($image, $maxSizeMb, $allowedExtensions);` — both params default to `null`.

---

### H-18. No Rate-Limit on Login / OTP / Password-Reset (default `LoginController` uses `RateLimiter` but no per-username throttle)
- **Severity:** High
- **CWE-307**
- **File:** `app/Http/Controllers/LoginController.php` lines 122–273
- **Description:** The web login uses `RateLimiter` keyed on IP only (5 attempts / 2 min). API auth (`CustomerAuthController::login`, `PasswordResetController::reset_password_request`, `verify_token`) is not throttled. Brute force is feasible.
- **Recommendation:** Wrap `auth:api` group with `throttle:10,1` and add per-username/per-phone rate limiter.

---

## 🟡 MEDIUM FINDINGS (24)

### M-1. SMS_module.php uses `curl` with raw query concatenation
- **File:** `app/CentralLogics/SMS_module.php` lines 86–94
- **Description:** `curl_setopt($ch, CURLOPT_POSTFIELDS, "from=...&text=$message&to=$receiver&api_key=...&api_secret=...");` — the `$message` is not URL-encoded. Log injection / parameter injection is possible.
- **Recommendation:** Use `http_build_query()`.

### M-2. Curl options for many payment controllers disable SSL verification
- **File:** `BkashPaymentController`, `PaypalPaymentController`, `PaystackController`, `FlutterwaveV3Controller`, `SslCommerzPaymentController`
- **Description:** None set `CURLOPT_SSL_VERIFYPEER`. Implicit false on most libcurl defaults.
- **Recommendation:** Set `CURLOPT_SSL_VERIFYPEER => 1`, `CURLOPT_SSL_VERIFYHOST => 2`.

### M-3. `info($e->getMessage())` may leak PII / payment details in logs
- **File:** entire `app/CentralLogics/*` and controllers
- **Description:** Stack traces and exception messages often contain raw `$_GET` payload, FCM tokens, customer email, etc.
- **Recommendation:** Use a structured logger that masks PII.

### M-4. `try {... return response()->json([$exception], 403);` in `PlaceNewOrder`
- **File:** `app/Traits/PlaceNewOrder.php` lines 616–621
- **Description:** Returns the entire exception object including trace, file paths, SQL bindings to the client.
- **Recommendation:** Log internally, return a generic error.

### M-5. `app/CentralLogics/SMS_module.php` calls `echo 'Error:'.curl_error($ch);` on failure
- **File:** `app/CentralLogics/SMS_module.php` line 96
- **Description:** Curl errors may echo HTML to the response in non-CLI mode (header already sent issues). At minimum, leaks server state.
- **Recommendation:** Use Log facade.

### M-6. `try { base64_decode(...) }` without strict mode in `Customer.php` `getImageFullUrlAttribute`
- **File:** `app/Models/Store.php` lines 302–356
- **Description:** Not a security issue, but `Str::slug` could collide (controlled by attacker at `name`). Slug-collision logic trusts the first match; possible SEO/store-clone confusion.

### M-7. `php artisan migrate:fresh` invoked from web routes?
- **File:** `app/Console/Commands/DatabaseRefresh.php`
- **Description:** A `database:refresh` artisan command exists. If exposed via any web route, catastrophic.

### M-8. Mass Assignment on `Conversation::create` / `Message::create` with only `fillable` declared — but request input not filtered
- **File:** `app/Models/Conversation.php` line 34, `Message.php`
- **Description:** `Message::create($request->all())` not used, but `Message` is built from `$request` fields via `save()`. Verify mass-assignment is bounded by `$fillable`.

### M-9. `DeliveryMan` `auth_token` used as API bearer
- **File:** `app/Http/Controllers/Api/V1/DeliverymanController.php`
- **Description:** `auth_token` is a long-lived static token in DB; not rotated on login; no expiration. If leaked, full DM account takeover.
- **Recommendation:** Use Laravel Passport (already installed) or short-lived JWT with refresh.

### M-10. `Vendor` `auth_token` static, exposed in `update_fcm_token`
- **File:** `app/Http/Controllers/Api/V1/Vendor/VendorController.php`
- **Description:** Same as M-9.

### M-11. `Admin` web session uses `bcrypt`-hashed `login_remember_token` cookie stored as encrypted string
- **File:** `app/Http/Controllers/LoginController.php` lines 100–110
- **Description:** `Crypt::encryptString($email)` / `Crypt::encryptString($password)` — **the user's password is stored encrypted in a cookie for 120 minutes**. A cookie leak reveals the email + password (decryptable with the same APP_KEY).
- **Recommendation:** Use remember-me token (Laravel built-in) instead of re-encrypting the password.

### M-12. `password_resets` table uses random `rand(100000,999999)` — predictable on shared hosts
- **File:** `app/Http/Controllers/LoginController.php` line 369, 514
- **Description:** `rand()` is not cryptographically secure. Use `random_int()` or `Str::random(6)`.
- **Confidence:** High

### M-13. `app/CentralLogics/Helpers.php` `getNextOpeningTime` returns `'closed'` as plain string — logic bug
- **File:** `app/CentralLogics/Helpers.php` line 4719
- **Description:** Not a security issue but indicates code quality; closed stores may display "closed" inside i18n.

### M-14. `app/Http/Controllers/Api/V1/CartController.php` `add_to_cart` accepts item_id without verifying item belongs to the user-allowed zone
- **File:** `app/Http/Controllers/Api/V1/CartController.php`
- **Description:** Items in other zones may be added, causing inconsistent cart state.

### M-15. `app/Http/Controllers/Api/V1/CustomerController.php` `add_new_address` accepts `latitude/longitude` without validating inside any zone
- **File:** `app/Http/Controllers/Api/V1/CustomerController.php`
- **Description:** Could create "address in another country" used for fraud.

### M-16. `app/Http/Controllers/Api/V1/WalletController.php` `add_fund` uses external `drivemond` server — SSRF risk
- **File:** `app/Http/Controllers/Api/V1/WalletController.php`
- **Description:** `Http::post($driveMondBaseUrl . '/api/customer/wallet/transfer-drivemond-from-mart', …)`. Base URL stored in DB; if attacker can edit `ExternalConfiguration` (via admin), internal URL can be injected.

### M-17. `app/Traits/ActivationClass.php` posts username + purchase key to external 6amtech server
- **File:** `app/Traits/ActivationClass.php` lines 63–89
- **Description:** PII (licensee username) leaves the server. Acceptable for licensing, but should be opt-in and documented.

### M-18. `app/Http/Controllers/Admin/CustomerController.php` `export` and `customer_list` allow full DB dump via search
- **File:** `app/Http/Controllers/Admin/CustomerController.php`
- **Description:** No rate limit on export; large CSV export of customer PII possible.

### M-19. `app/Http/Controllers/Admin/OrderController.php` `export_orders` may take long time, no auth on file
- **File:** `app/Http/Controllers/Admin/OrderController.php`
- **Description:** No queue, no rate-limit.

### M-20. `routes/web.php` line 47–48 — `order-invoice/{id}` uses `base64_decode($id)` then `Order::findOrFail($id)` — invoice enumeration possible
- **File:** `routes/web.php`
- **Description:** Invoice PDF route does not require any auth, allowing enumeration of order IDs.

### M-21. `app/Http/Controllers/Admin/OrderController.php` `updateAdditionalCharge` recalculates amounts with `round(..., 3)` — floating-point tax rounding
- **File:** `app/Http/Controllers/Admin/OrderController.php`
- **Description:** Not security, but financial accuracy.

### M-22. `app/Http/Controllers/Admin/ItemController.php` `store`/`update` accept any `image.*` keys and rely on `mimes:` from `IMAGE_FORMAT_FOR_VALIDATION`
- **File:** `app/Http/Controllers/Admin/ItemController.php`
- **Description:** If the constant is `png,jpg,jpeg,webp`, SVG is excluded — good. But polyglot files may pass (image content + embedded PHP). Use a real image inspection library (e.g., `intervention/image` already present).

### M-23. `app/Http/Controllers/Admin/SystemController.php` `confirmOrderFromNotification` allows GET state change
- **File:** `app/Http/Controllers/Admin/SystemController.php` lines 89–157
- **Description:** Although protected by `admin` middleware, the route is `GET /admin/confirm-order-notification/{id}` — CSRF-able and crawler-clickable.

### M-24. `app/Http/Controllers/Admin/FileManagerController.php` `destroy` accepts `base64_decode($file_path)` and deletes — privileged action
- **File:** `app/Http/Controllers/Admin/FileManagerController.php` lines 204–214
- **Description:** A malicious admin (or compromised admin) can delete arbitrary files. Path traversal is contained by `base64_decode` but only decoding — attacker can pre-encode any path. Recommend signed delete tokens.

---

## 🔵 LOW FINDINGS (17)

### L-1. `info('PlaceNewOrder', [$exception->getFile(), $exception->getLine(), $exception->getMessage()])` in `PlaceNewOrder.php` line 618 — verbose logging in production.
### L-2. `DB::statement("SET sql_mode=...")` in `Admin/CustomerController::__construct` runs on every request — minor perf concern.
### L-3. `Str::slug` in `Item::generateSlug` predictable and unbounded counter — minor DoS / SEO risk.
### L-4. `config/session.php` `'same_site' => null` — strict default preferred.
### L-5. `app/CentralLogics/SMS_module.php` uses `error_log` equivalent (echo) for curl errors — risk of header leak.
### L-6. `app/Http/Controllers/FirebaseController.php` accepts arbitrary `topic` (no allow-list) — risk of subscribing victim tokens to attacker-controlled topic.
### L-7. `app/Http/Controllers/Api/V1/Auth/SocialAuthController.php` — Apple login uses `aud => 'https://appleid.apple.com'` and `ES256` but key content is loaded via `file_get_contents('storage/app/public/apple-login/'.$apple_login->service_file)` — path traversal possible if `service_file` is user-controlled (it is admin-controlled, but should be validated).
### L-8. `app/Http/Controllers/Admin/SystemController.php` `maintenance_mode` / `landing_page` toggling via GET — state change on GET.
### L-9. `routes/api/v1/api.php` line 412 — `Route::group(['prefix' => 'customer', 'middleware' => 'apiGuestCheck'])` uses a custom middleware that allows either `Authorization: Bearer null` or `guest_id` — bearer can be `null`; an attacker can pass `Authorization: Bearer null` to bypass.
### L-10. `app/Http/Controllers/Admin/AddOnActivationController.php` `activation` likely modifies file system (not read but inferred from `system-addon` config) — needs proper validation of `addon_zip`.
### L-11. `package.json` `axios` `^0.21` is outdated — multiple CVEs.
### L-12. `composer.json` `madnest/madzipper: *` — wildcards are unconstrained.
### L-13. `composer.json` `nwidart/laravel-modules` and `Modules/` folder — third-party module scaffolding; trust boundary requires review.
### L-14. `firebase-messaging-sw.js` (public/) — service worker should be reviewed for XSS in `notification` payload rendering.
### L-15. `.styleci.yml`, `webpack.mix.js`, `vite-module-loader.js` — build artefacts; not security-critical.
### L-16. `routes/console.php` — not read; assumed safe.
### L-17. `database/seeders/FoodSeeder.php` etc. use `->count(10000)` factory seeds — should not run in production.

---

## ℹ️ INFORMATIONAL / POSITIVE FINDINGS (12)

### P-1. Password hashing uses `bcrypt` (`config/hashing.php`) with env-driven rounds — good.
### P-2. CSRF middleware is registered in the `web` group (`bootstrap/app.php` line 62) — good baseline.
### P-3. `Login_remember_token` regenerated on email change and password reset — good practice.
### P-4. Mass assignment restricted on most models via `$guarded = ['id']` (User, Item) — although broad, the use of `forceFill` is not observed.
### P-5. `Helpers::upload()` re-encodes image via Intervention to WebP, mitigating many polyglot file attacks.
### P-6. `vendor_status` and `store.status` checks are present in order placement — good partial authorization.
### P-7. `chat_image` and file uploads validate `mimes:` via `IMAGE_FORMAT_FOR_VALIDATION`.
### P-8. Sessions are `http_only => true`.
### P-9. Soft-delete scope is not used; deletes are hard — minimize blast radius of stale references.
### P-10. Order placement wrapped in `DB::transaction` and `DB::beginTransaction()` — good atomicity.
### P-11. Most raw SQL is using `?` bindings.
### P-12. `config/broadcasting.php` uses env variables for secrets (not hard-coded) — best practice.

---

## 📊 RISK HEATMAP

```
                 Critical    High      Medium    Low
Authentication   ████        ███       ██        █
Authorization    ███         ████      ██        █
Validation       █           ███       ███       ██
Cryptography     ██          ██        █         █
File Upload      █           ██        ██        █
API Security     ██          ███       ███       ██
Configuration    ███         ███       ██        █
Logging          █           ██        ██        █
Dependencies     -           █         ██        ██
Payments         ██          ████      ██        █
Business Logic   █           ███       ███       █
```

---

## ⚙️ EXPLOITABILITY ESTIMATE

| Vector | Estimated Difficulty | Estimated Impact |
|---|---|---|
| Free order via MercadoPago callback | Easy (1 request) | High (financial) |
| Admin / vendor session theft via MitM | Medium | Critical |
| Webshell via `eval()` future bug | High (currently local-only) | Critical |
| Free order via Bkash replay | Medium | High |
| Customer IDOR via `customer/order/cancel` | Easy | High |
| File upload bypass (chat image) | Medium | Medium |
| PII leak via Laravel log + debug | Easy | High |
| SSRF via `/image-proxy` to AWS metadata | Easy | Critical (in AWS) |
| Cache wipe via `/test` GET | Trivial | Medium (DoS) |
| Forged FCM notifications via unauthenticated `/subscribeToTopic` | Easy | Medium |

---

## 🏭 PRODUCTION READINESS SCORE: **30/100**

**Verdict:** The application must not be deployed to production in its current state. The combination of `eval()`, wildcard CORS, exposed static secrets, debug mode, missing webhook signatures, multiple IDOR surfaces, and the open SSRF image proxy creates a critically exploitable attack surface.

After the **6 Critical** and at minimum the top 10 **High** items are remediated, a focused re-audit is required before production launch. Targeted re-audit should specifically validate:
1. Removal of `eval()` and recompilation with a static subclass.
2. Production `.env` template with no leaked secrets.
3. Webhook signature tests in CI.
4. Policy/Gate tests for chat, order, customer controllers.
5. SSRF allow-list integration tests for `/image-proxy`.

---

## 🛠️ QUICK WINS (≤ 1 day, high ROI)

1. Set `APP_DEBUG=false`, `LOG_LEVEL=error`.
2. Remove `APP_KEY` and broadcast keys from `.env.example`.
3. Restrict `cors.php` to known origins.
4. Remove `Route::get('/test', …)` from `routes/web.php`.
5. Replace `eval()` in `PaymentController` with `use App\Traits\Payment;`.
6. Enable CSRF on payment-return routes and add signature verification on every webhook.
7. Switch `auth_token` to Passport tokens (already installed) for DM and Vendor APIs.
8. Add `throttle:30,1` middleware to `auth/login`, `auth/forgot-password`, `auth/verify-token`.
9. Replace `rand(100000,999999)` with `random_int` (or `Str::random(6)`).
10. Disable echo of curl errors in `SMS_module.php`; use `Log::error`.

---

## 🏗️ LONG-TERM IMPROVEMENTS (1–3 months)

1. Migrate from custom `auth_token` to Laravel **Passport** for all client types (already a dependency).
2. Introduce **Spatie Permissions** (or equivalent) with explicit `Policy` for every model.
3. Move all sensitive config to **HashiCorp Vault** / **AWS Secrets Manager**.
4. Implement a **CSP** middleware with nonce and `Strict-Transport-Security` headers.
5. Introduce **Sentry / OpenTelemetry** with PII scrubbing at the SDK layer.
6. Adopt **PHPStan + Larastan** at level 8 with security ruleset in CI.
7. Establish a **security regression test suite** for IDOR, CSRF, signature, and rate-limit.
8. Run a **third-party penetration test** before each major release.
9. Enable **Laravel's built-in `Password::min()`** rules everywhere; remove the password min(6) in `CustomerAuthController::login` validation.
10. Replace SMS/email providers with idempotent transactional APIs (Twilio Verify, SES SNS).

---

## 🧪 RECOMMENDED VERIFICATION

Once the recommendations are applied, the following manual + automated tests should pass:
- `tests/Feature/IdorTest.php` for every controller that mutates by ID.
- Static analysis: `composer require --dev nunomaduro/larastan` & `vendor/bin/phpstan analyse --level=8`.
- OWASP ZAP baseline scan on staging.
- `nmap --script=http-enum` against the production image to confirm `/test` and `/image-proxy` are restricted.
- Verify the absence of `eval(` and `exec(` in non-vendor code (`grep -rn "eval(" app/`).

---

**END OF REPORT**

No source code was modified. No patches were generated. No code changes were applied. This document is a **read-only** security audit.
