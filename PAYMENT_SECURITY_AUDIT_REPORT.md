# PickAdmin — Comprehensive Payment Gateway & Financial Security Audit

**Mode:** READ-ONLY AUDIT (no file was modified, deleted or rewritten)
**Date:** 2026-08-14
**Target:** `c:\xampp\htdocs\pickadmin` (Laravel 12.x / PHP 8.2+)
**Method:** Direct source inspection of every route, controller, trait, helper, model, migration and Blade view in the payment path. `.cline_pickadmin_context.md` was used **only** as a search index — every claim below is backed by a real file + line.

---

## 0. EXECUTIVE SUMMARY

| | |
|---|---|
| **Overall Payment Security Score** | **22 / 100** |
| **Production Readiness** | **CRITICAL PAYMENT SECURITY ISSUES** |
| Critical | **10** |
| High | **14** |
| Medium | **8** |
| Low | **3** |
| Info | **4** |

**The three most dangerous facts discovered:**

1. **`StripePaymentController::success()` never binds the Stripe Checkout Session to the local payment row, and has no replay guard.** A 1.00 USD session created by the attacker can mark *any* `payment_id` as paid, and re-loading the same success URL re-fires `wallet_success` → **unlimited free wallet credit**.
2. **`PaytmController::callback()` and `LiqPayController::callback()` perform zero signature verification.** A plain unauthenticated `GET`/`POST` with `STATUS=TXN_SUCCESS` (or `status=success`) marks any order paid. Both routes are CSRF-exempt.
3. **`APIGuestMiddleware` accepts any attacker-supplied `guest_id` integer with no secret.** `Guest` ids are sequential auto-increment values returned by an open endpoint → full enumeration and takeover of every guest order, including its payment endpoints.

Three gateways (**Razorpay, MercadoPago, bKash**) and **SenangPay** have already been hardened correctly and serve as the reference implementation for fixing the rest.

---

## 1. PAYMENT GATEWAYS ACTUALLY PRESENT IN THE CODE

`Modules/Gateways/` **does not exist** on disk (verified: `Test-Path` → `False`), even though `modules_statuses.json` lists `"Gateways": true`. Because `routes/web.php:80-86` wraps all gateway routes in `if (!$is_published)`, the **core 13 controllers in `app/Http/Controllers/` are the live implementation**.

### 1.1 Implemented & routed (13)

| # | Gateway | Controller | Verified |
|---|---------|-----------|----------|
| 1 | Stripe | `app/Http/Controllers/StripePaymentController.php` | YES |
| 2 | PayPal | `app/Http/Controllers/PaypalPaymentController.php` | YES |
| 3 | Razorpay | `app/Http/Controllers/RazorPayController.php` | YES |
| 4 | SSLCommerz | `app/Http/Controllers/SslCommerzPaymentController.php` | YES |
| 5 | Paystack | `app/Http/Controllers/PaystackController.php` | YES |
| 6 | Flutterwave v3 | `app/Http/Controllers/FlutterwaveV3Controller.php` | YES |
| 7 | Paymob Accept | `app/Http/Controllers/PaymobController.php` | YES |
| 8 | PayTabs | `app/Http/Controllers/PaytabsController.php` | YES |
| 9 | Paytm | `app/Http/Controllers/PaytmController.php` | YES |
| 10 | LiqPay | `app/Http/Controllers/LiqPayController.php` | YES |
| 11 | SenangPay | `app/Http/Controllers/SenangPayController.php` | YES |
| 12 | MercadoPago | `app/Http/Controllers/MercadoPagoController.php` | YES |
| 13 | bKash | `app/Http/Controllers/BkashPaymentController.php` | YES |

### 1.2 Declared in the router map but NOT implemented (23)

`app/Traits/Payment.php:39-76` maps 36 method keys to URLs. The following 23 have **no controller and no route** — selecting them returns a URL that 404s: `fatoorah`, `xendit`, `amazon_pay`, `iyzi_pay`, `hyper_pay`, `foloosi`, `ccavenue`, `pvit`, `moncash`, `thawani`, `tap`, `viva_wallet`, `hubtel`, `maxicash`, `esewa`, `swish`, `momo`, `payfast`, `worldpay`, `sixcash`, `phonepe`, `cashfree`, `instamojo`.

> **Iyzico, PhonePe, Xendit — requested in scope — are `N/A`: declared only, never implemented.** This is an availability bug, not a security bug, but a customer who picks one is left with an unpaid `failed` order.

### 1.3 Offline / non-gateway money paths

| Path | File |
|---|---|
| Offline bank payment | `Api/V1/OrderController@offline_payment` |
| Wallet payment for order | `Api/V1/OrderController@walletPayment` |
| Wallet top-up | `Api/V1/WalletController@add_fund` |
| Cross-system wallet transfer | `Api/V1/WalletController@transferMartFromDrivemondWallet` |
| Vendor subscription | `CentralLogics/Helpers::subscriptionPayment` |
| Rental trip payment | `Modules/Rental/.../User/TripController@makePayment` |


---

## 2. PAYMENT ARCHITECTURE

### 2.1 The universal flow (all 13 gateways)

```
Flutter App / Web
        |
        v
Order created  (OrderController@place_order -> PlaceNewOrder trait)
   order_amount computed SERVER-SIDE          [SECURE]
   order_status = 'failed', payment_status = 'unpaid'
        |
        v
GET /payment-mobile?order_id&customer_id&payment_method&callback   [NO AUTH]
   PaymentController@payment
        |
        v
App\Library\Payment (DTO)  ->  App\Traits\Payment::generate_link()
   INSERT payment_requests (uuid, payment_amount FROM DB, success_hook, attribute_id)
        |
        v
redirect  /payment/{gateway}/pay?payment_id={uuid}
        |
        v
Gateway hosted page  (Stripe Checkout / PayPal / Razorpay / ...)
        |
        v
Browser return  ->  /payment/{gateway}/{success|callback}
   [ <-- ALL VULNERABILITIES LIVE HERE ]
        |
        v
UPDATE payment_requests SET is_paid=1, transaction_id=...
        |
        v
call_user_func($data->success_hook, $data)   // order_place | wallet_success
                                             // | sub_success | trip_payment_success
        |
        v
order_place():  order_status='confirmed', payment_status='paid'
                OrderLogic::update_unpaid_order_payment()
        |
        v
Processor::payment_response() -> redirect($external_redirect_link)  [UNVALIDATED]
```

### 2.2 Critical architectural observation — **there are no webhooks**

A full-project grep for `Stripe-Signature`, `constructEvent`, `webhook` returned **only comments** inside `MercadoPagoController` and `RazorPayController`. There is:

* **No** `POST /stripe/webhook`
* **No** `POST /paypal/webhook`
* **No** `POST /razorpay/webhook`
* **No** `POST /paystack/webhook`
* **No** `POST /flutterwave/webhook`

**Every gateway relies exclusively on the user's browser returning to a URL.** Consequences:

* If the customer closes the tab after paying, the money is captured at the gateway but the order stays `failed` forever. There is **no reconciliation job**.
* The "callback" is therefore an *unauthenticated, user-driven, CSRF-exempt* HTTP request — which is exactly why the findings in §4 are so severe.

### 2.3 Per-gateway map

| Gateway | Init route | Return route | Server-side verification | Signature | Bound to `payment_id`? |
|---|---|---|---|---|---|
| Stripe | `GET /payment/stripe/pay` → `token` | `GET /payment/stripe/success` | `Session::retrieve()` | none | **NO** |
| PayPal | `GET /payment/paypal/pay` | `ANY /payment/paypal/success` | `/v2/checkout/orders/{token}/capture` | none | **NO** |
| Razorpay | `GET /payment/razor-pay/pay` | `POST payment` / `ANY verify-payment` | `payment->fetch()` + amount + currency | **HMAC verified** | YES |
| SSLCommerz | `GET /payment/sslcommerz/pay` | `POST /payment/sslcommerz/success` | `SSLCOMMERZ_hash_verify` | **MD5 verified** | **NO** |
| Paystack | `GET /payment/paystack/pay` | `GET /payment/paystack/callback` | `/transaction/verify/{ref}` | none | YES (metadata) |
| Flutterwave | `GET /payment/flutterwave-v3/pay` | `GET .../callback` | `/transactions/{id}/verify` | none | **NO** |
| Paymob | `ANY /payment/paymob/pay` | `ANY /payment/paymob/callback` | HMAC only | **SHA512 verified** | **session (broken)** |
| PayTabs | `ANY /payment/paytabs/pay` | `ANY /payment/paytabs/callback` | `payment/query` | **HMAC verified** | **NO (cart_id unchecked)** |
| Paytm | `GET /payment/paytm/pay` | `ANY /payment/paytm/response` | **NONE** | **NONE** | **NO** |
| LiqPay | `GET /payment/liqpay/payment` | `ANY /payment/liqpay/callback` | **NONE** | **NONE** | **NO** |
| SenangPay | `GET /payment/senang-pay/pay` | return URL | status + amount + currency | **HMAC verified** | YES |
| MercadoPago | `GET /payment/mercadopago/pay` | `ANY .../callback` | `PaymentClient::get()` | IP allow-list | YES (`external_reference`) |
| bKash | `GET /payment/bkash/make-payment` | `ANY /payment/bkash/callback` | fresh token + execute/query | IP allow-list | YES (paymentID echo) |


---

## 3. ROUTES SECURITY TABLE

All gateway routes live in `routes/web.php:87-196` under the `web` middleware group. **None** has `auth`, **none** has `throttle`, **none** has authorization.

| Route | Method | Auth | Authz | CSRF | Rate limit | Note |
|---|---|---|---|---|---|---|
| `/payment-mobile` | GET | **NONE** | **NONE** | n/a | **NONE** | `PaymentController@payment` |
| `/payment-success` | GET | **NONE** | **NONE** | n/a | **NONE** | `@oksuccess` *(method missing)* |
| `/payment-fail` | GET | **NONE** | **NONE** | n/a | **NONE** | session-based |
| `/payment-cancel` | GET | **NONE** | **NONE** | n/a | **NONE** | session-based |
| `/payment/sslcommerz/success` | POST | **NONE** | **NONE** | **EXEMPT** | **NONE** | signature OK |
| `/payment/stripe/token` | GET | **NONE** | **NONE** | n/a | **NONE** | creates Checkout Session |
| `/payment/stripe/success` | GET | **NONE** | **NONE** | n/a | **NONE** | **NO binding (C-1)** |
| `/payment/razor-pay/payment` | POST | **NONE** | **NONE** | **EXEMPT** | **NONE** | signature OK |
| `/payment/razor-pay/verify-payment` | ANY | **NONE** | **NONE** | **EXEMPT** | **NONE** | signature OK |
| `/payment/razor-pay/create-order` | ANY | **NONE** | **NONE** | **EXEMPT** | **NONE** | H-9 |
| `/payment/paypal/success` | ANY | **NONE** | **NONE** | **EXEMPT** | **NONE** | **NO binding (C-4)** |
| `/payment/paytm/response` | ANY | **NONE** | **NONE** | **EXEMPT** | **NONE** | **NO verification (C-2)** |
| `/payment/liqpay/callback` | ANY | **NONE** | **NONE** | **EXEMPT** | **NONE** | **NO verification (C-3)** |
| `/payment/flutterwave-v3/callback` | GET | **NONE** | **NONE** | n/a | **NONE** | **NO binding (C-5)** |
| `/payment/paystack/callback` | GET | **NONE** | **NONE** | n/a | **NONE** | binding OK |
| `/payment/bkash/callback` | ANY | **NONE** | **NONE** | **EXEMPT** | **NONE** | binding OK |
| `/payment/mercadopago/make-payment` | POST | **NONE** | **NONE** | **EXEMPT** | **NONE** | H-8 |
| `/payment/mercadopago/callback` | ANY | **NONE** | **NONE** | **EXEMPT** | **NONE** | binding OK |
| `/payment/paymob/callback` | ANY | **NONE** | **NONE** | **EXEMPT** | **NONE** | session binding (H-2) |
| `/payment/paytabs/callback` | ANY | **NONE** | **NONE** | **EXEMPT** | **NONE** | **NO binding (H-1)** |
| `/api/v1/customer/wallet/add-fund` | POST | `auth:api` | owner OK | n/a | **NONE** | OK |
| `/api/v1/.../transfer-mart-from-drivemond` | POST | **`withoutMiddleware('auth:api')`** | **NONE** | n/a | **NONE** | **C-8** |
| `/api/v1/customer/order/wallet-payment` | POST | `apiGuestCheck` | **NONE** | n/a | **NONE** | **C-6** |
| `/api/v1/customer/order/offline-payment` | PUT | `apiGuestCheck` | **NONE** | n/a | **NONE** | **C-7** |
| `/api/v1/customer/order/offline-payment-update` | PUT | `apiGuestCheck` | **NONE** | n/a | **NONE** | H-6 |
| `/api/v1/vendor/business_plan` | POST | **NONE** | **NONE** | n/a | **NONE** | **C-9** |
| `/api/v1/vendor/subscription/payment/api` | POST | **NONE** | **NONE** | n/a | **NONE** | method missing |
| `/vendor/payment` | POST | **NONE** | **NONE** | required | **NONE** | `VendorController@payment` |


---

## 4. DETAILED FINDINGS — CRITICAL

---

### C-1 — Stripe success handler does not bind the Checkout Session to the payment record

```
Finding:                Stripe success() trusts an arbitrary attacker-supplied session_id
                        and never verifies the session belongs to the payment_id being paid.
Severity:               CRITICAL
CVSS-like Score:        9.8  (AV:N/AC:L/PR:N/UI:N/S:C/C:H/I:H/A:H)
Gateway:                Stripe
File:                   app/Http/Controllers/StripePaymentController.php
Controller:             StripePaymentController
Method:                 success()
Route:                  GET /payment/stripe/success   (routes/web.php:104)
Line:                   102-128  (decision 107, write 109-113)
Vulnerable Parameter:   session_id, payment_id
```

**Current Behavior**

```php
// StripePaymentController.php:104-113
Stripe::setApiKey($this->config_values->api_key);
$session = Session::retrieve($request->get('session_id'));

if ($session->payment_status == 'paid' && $session->status == 'complete') {
    $this->payment::where(['id' => $request['payment_id']])->update([
        'payment_method' => 'stripe',
        'is_paid' => 1,
        'transaction_id' => $session->payment_intent,
    ]);
```

The code checks only *that some session in this Stripe account is paid*. It never checks that the session was created for this `payment_id`; that `amount_total` equals `payment_requests.payment_amount`; that `currency` matches `currency_code`; or that the row was `is_paid = 0` (**no replay guard, no row lock**).

`payment_process_3d()` at line 95 appends `&payment_id=` to `success_url`, but that value is echoed back by the browser and is fully attacker-controlled.

**Security Impact**

* **Amount tampering** — pay 1.00 USD, satisfy a 5,000.00 USD order.
* **Cross-order IDOR** — any `payment_id` uuid can be redeemed with the attacker's own cheap session.
* **Replay / infinite money** — re-requesting the URL re-runs `success_hook`. For `wallet_success` (`app/helpers.php:224-243`) each replay calls `create_wallet_transaction(..., 'add_fund', ...)` → **unbounded wallet credit from one 1.00 payment**.

**Attack Scenario**

1. Place a 5,000.00 order → `/payment/stripe/pay?payment_id=UUID-A`; record `UUID-A`.
2. Start a second flow for a 1.00 wallet top-up, pay it for real → Stripe issues `cs_live_xxx` with `payment_status=paid`.
3. `GET /payment/stripe/success?session_id=cs_live_xxx&payment_id=UUID-A`.
4. `UUID-A` → `is_paid=1`; `order_place()` sets the 5,000.00 order to `confirmed`/`paid`.
5. Repeat step 3 against a wallet `payment_id`, N times → N × credit.

**Why It Happens** — `Session::retrieve()` was used as a boolean gate rather than an assertion source; Stripe's `client_reference_id`/`metadata` correlation was not used.

**Secure Expected Behavior**

1. Set `'client_reference_id' => $data->id` and `'metadata' => ['payment_id' => $data->id]` in `Session::create()`.
2. On return re-fetch the session and require `client_reference_id === payment_id`.
3. Require `amount_total` to equal the stored amount (same cents rule as line 86) and `currency` to match.
4. Require the expanded `payment_intent.status === 'succeeded'`.
5. Wrap the update in `DB::transaction` + `lockForUpdate()`, return early when `is_paid === 1`.

**Recommended Fix** — mirror `RazorPayController::payment()` (lines 240-309), which already implements all five steps.

```
Requires Database Change:          NO (unique index on transaction_id recommended)
Requires Flutter Change:           NO
Requires Gateway Dashboard Change: NO (a Stripe webhook is strongly recommended)

---

### C-2 — Paytm callback accepts `STATUS=TXN_SUCCESS` with no verification whatsoever

```
Finding:                Payment is marked paid purely from a query-string value.
Severity:               CRITICAL
CVSS-like Score:        10.0 (AV:N/AC:L/PR:N/UI:N/S:C/C:H/I:H/A:H)
Gateway:                Paytm
File:                   app/Http/Controllers/PaytmController.php
Controller:             PaytmController
Method:                 callback()
Route:                  ANY /payment/paytm/response  (routes/web.php:143-144, CSRF exempt)
Line:                   227-249  (decision 229)
Vulnerable Parameter:   STATUS, TXNID, payment_id
```

**Current Behavior**

```php
// PaytmController.php:229-234
if ($request["STATUS"] == "TXN_SUCCESS") {
    $this->payment::where(['id' => $request['payment_id']])->update([
        'payment_method' => 'paytm',
        'is_paid' => 1,
        'transaction_id' => $request['TXNID'],
    ]);
```

The class *contains* `generateSignature()` / `verifysignature()` (line 83 onward) and uses them when **initiating** (line 196) — but the callback never calls them. No Transaction Status API call is made either, despite `PAYTM_STATUS_QUERY_URL` being configured at line 50.

**Security Impact** — Complete payment bypass. Free orders, free wallet balance, free vendor subscriptions; replayable without limit.

**Attack Scenario**

```
GET /payment/paytm/response?payment_id=<uuid>&STATUS=TXN_SUCCESS&TXNID=ANYTHING
```
One unauthenticated request; nothing else needed. When `attribute` is `wallet_payments`, repeating it N times credits the wallet N times.

**Why It Happens** — The callback was written as a happy-path stub; signature utilities were applied to the outbound leg only.

**Secure Expected Behavior**

1. Verify the Paytm checksum over the whole response with `verifysignature($params, $merchantKey, $paytmChecksum)`.
2. Independently call Paytm's Transaction Status API and require `TXN_SUCCESS`.
3. Compare `TXNAMOUNT` with `payment_amount` and `CURRENCY` with `currency_code`.
4. Compare `ORDERID` with the id used at initiation — note line 175 uses `$ORDER_ID = time()` which is **never persisted**, so this correlation is impossible today and must be fixed first.
5. Atomic `lockForUpdate()` update with an `is_paid` replay guard.

```
Requires Database Change:          YES (persist the Paytm ORDERID on payment_requests)
Requires Flutter Change:           NO
Requires Gateway Dashboard Change: NO
```

---

### C-3 — LiqPay callback accepts `status=success` with no signature check

```
Finding:                LiqPay callback trusts `status` and `transaction_id` from the request.
Severity:               CRITICAL
CVSS-like Score:        10.0 (AV:N/AC:L/PR:N/UI:N/S:C/C:H/I:H/A:H)
Gateway:                LiqPay
File:                   app/Http/Controllers/LiqPayController.php
Controller:             LiqPayController
Method:                 callback()
Route:                  ANY /payment/liqpay/callback  (routes/web.php:174; CSRF exempt via '/payment*')
Line:                   326-345  (decision 328)
Vulnerable Parameter:   status, transaction_id, payment_id
```

**Current Behavior**

```php
// LiqPayController.php:328-333
if ($request['status'] == 'success') {
    $this->payment::where(['id' => $request['payment_id']])->update([
        'payment_method' => 'liqpay',
        'is_paid' => 1,
        'transaction_id' => $request['transaction_id'],
    ]);
```

The bundled `LiqPay` SDK class in the same file exposes `str_to_sign()` and `decode_params()` (lines 249-277) — exactly the primitives needed to validate LiqPay's `data` + `signature` pair. **Neither is called.** LiqPay posts `data` (base64 JSON) and `signature = base64(sha1(private_key + data + private_key))`; the handler ignores both and reads a flat `status` field LiqPay does not send in that form.

**Security Impact** — identical to C-2: unauthenticated total payment bypass, replayable.

**Attack Scenario**

```
GET /payment/liqpay/callback?payment_id=<uuid>&status=success&transaction_id=1
```

**Secure Expected Behavior**

1. Read `data` and `signature` from the POST body.
2. Recompute `base64_encode(sha1($private_key . $data . $private_key, 1))`, compare with `hash_equals()`.
3. `decode_params($data)`; require `status ∈ {success, sandbox}`, `order_id === attribute_id`, `amount === payment_amount`, `currency === currency_code`.
4. Atomic update with replay guard.

```
Requires Database Change:          NO
Requires Flutter Change:           NO
Requires Gateway Dashboard Change: NO
```


---

### C-4 — PayPal capture is not bound to the payment record; amount and currency never verified

```
Finding:                success() captures an attacker-chosen PayPal order token and applies
                        the result to an attacker-chosen local payment_id.
Severity:               CRITICAL
CVSS-like Score:        9.8  (AV:N/AC:L/PR:N/UI:N/S:C/C:H/I:H/A:H)
Gateway:                PayPal
File:                   app/Http/Controllers/PaypalPaymentController.php
Controller:             PaypalPaymentController
Method:                 success()
Route:                  ANY /payment/paypal/success  (routes/web.php:128-129, CSRF exempt)
Line:                   202-248  (capture 209-217, decision 227, write 228-232)
Vulnerable Parameter:   token (PayPal order id), payment_id
```

**Current Behavior**

```php
// PaypalPaymentController.php:209-232
curl_setopt_array($ch, $this->paypalCurlOptions(
    $this->base_url . "/v2/checkout/orders/{$request->token}/capture", 'POST', ...));
...
if (isset($response->status) && $response->status === 'COMPLETED') {
    $this->payment::where(['id' => $request['payment_id']])->update([
        'payment_method' => 'paypal', 'is_paid' => 1, 'transaction_id' => $response->id,
    ]);
```

`token` and `payment_id` are two independent, attacker-controlled query parameters. The capture response contains `purchase_units[0].amount.value` and `.currency_code` — **neither is read**. No `is_paid` replay guard, no row lock. (TLS verification at lines 46-47 *is* correct — that part is fine.)

**Security Impact** — Pay 1.00 for a 5,000.00 order; redeem another user's `payment_id`; replay to multiply wallet credit.

**Attack Scenario**

1. Create a genuine 1.00 PayPal order via the app's own `pay` endpoint → token `5O190127TN364715T`.
2. Approve it in PayPal.
3. Request `/payment/paypal/success?token=5O190127TN364715T&payment_id=<victim-or-large-uuid>`.
4. Capture returns `COMPLETED` → target row flips to paid.

**Secure Expected Behavior**

1. Persist the PayPal order id returned by `/v2/checkout/orders` onto the local row at creation.
2. On return require `request.token === stored_paypal_order_id`.
3. Require `purchase_units[0].amount.value == payment_amount` and `currency_code == currency_code`.
4. Require `purchase_units[0].payments.captures[0].status === 'COMPLETED'`.
5. Atomic `lockForUpdate()` + `is_paid` guard.
6. Add a PayPal webhook (`PAYMENT.CAPTURE.COMPLETED`) with `verify-webhook-signature`.

```
Requires Database Change:          YES (column to store the PayPal order id)
Requires Flutter Change:           NO
Requires Gateway Dashboard Change: YES (register the webhook)
```

---

### C-5 — Flutterwave callback: amount is compared against gateway-echoed metadata, not the DB

```
Finding:                The "amount to pay" used for comparison comes from the Flutterwave
                        response itself, and the verified transaction is never bound to payment_id.
Severity:               CRITICAL
CVSS-like Score:        9.1  (AV:N/AC:L/PR:N/UI:N/S:C/C:H/I:H/A:N)
Gateway:                Flutterwave v3
File:                   app/Http/Controllers/FlutterwaveV3Controller.php
Controller:             FlutterwaveV3Controller
Method:                 callback()
Route:                  GET /payment/flutterwave-v3/callback  (routes/web.php:150)
Line:                   147-205  (decision 182-185, write 186-190)
Vulnerable Parameter:   transaction_id, payment_id
```

**Current Behavior**

```php
// FlutterwaveV3Controller.php:182-190
if (isset($res->status) && $res->status && isset($res->data->charged_amount, $res->data->meta->price)) {
    $amountPaid  = $res->data->charged_amount;
    $amountToPay = $res->data->meta->price;      // <-- from the gateway response
    if ($amountPaid >= $amountToPay) {
        $this->payment::where(['id' => $request->input('payment_id')])->update([
            'payment_method' => 'flutterwave', 'is_paid' => 1, 'transaction_id' => $txid,
        ]);
```

Three defects:

1. `$amountToPay` is read from `$res->data->meta->price` — the gateway echoing back metadata — instead of `payment_requests.payment_amount`. The comparison is therefore self-referential and always true.
2. `transaction_id` and `payment_id` are independent query parameters: any *other* successfully-verified Flutterwave transaction can be replayed against any `payment_id`.
3. No `is_paid` guard, no lock, no currency check. `tx_ref` is `(string) time()` (line 91) and is never persisted, so correlation is impossible today.

**Security Impact** — cross-transaction redemption + amount tampering + replay.

**Attack Scenario** — Attacker completes any 1.00 Flutterwave charge, notes `transaction_id=99887766`, then calls
`/payment/flutterwave-v3/callback?payment_id=<big-order-uuid>&transaction_id=99887766&status=successful`.

**Secure Expected Behavior**

1. Persist `tx_ref` on the payment row at initialisation and require `res.data.tx_ref === stored_tx_ref`.
2. Compare `res.data.amount` (or `charged_amount`) with **`payment_requests.payment_amount` read from the DB**.
3. Require `res.data.status === 'successful'` and `res.data.currency === currency_code`.
4. Atomic `lockForUpdate()` + replay guard.

```
Requires Database Change:          YES (persist tx_ref)
Requires Flutter Change:           NO
Requires Gateway Dashboard Change: NO (webhook with verif-hash recommended)
```


---

### C-6 — Wallet-payment endpoint has no ownership check and debits the victim's wallet

```
Finding:                walletPayment() loads the order by id only and debits order->user_id,
                        with no verification that the caller owns it.
Severity:               CRITICAL
CVSS-like Score:        9.1  (AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H)
Gateway:                N/A (internal wallet)
File:                   app/Http/Controllers/Api/V1/OrderController.php
Controller:             Api\V1\OrderController
Method:                 walletPayment()
Route:                  POST /api/v1/customer/order/wallet-payment  (routes/api/v1/api.php:440)
Line:                   778-803  (lookup 787, debit 792)
Vulnerable Parameter:   order_id
```

**Current Behavior**

```php
// OrderController.php:787-797
$order = Order::where(['id' => $request->order_id])->first();
if($order->payment_status == 'paid'){ ... }

$walletTransaction = CustomerLogic::create_wallet_transaction(
    $order->user_id, $order->order_amount, 'order_place', $order->id);   // victim's wallet
if($walletTransaction){
    $order->order_status  = 'confirmed';
    $order->payment_status = 'paid';
```

There is **no** `where('user_id', $request->user->id)`. Contrast with `cancel_order()` at line 220 which *does* scope by `user_id` — proving the pattern was known and simply omitted here.

Additional defects in the same 26 lines:

* `$order` is not null-checked → `$order->payment_status` on null throws a 500 (info leak / DoS).
* `create_wallet_transaction` (CustomerLogic.php:16-82) does **not** check for sufficient balance for the `order_place` branch — it computes `balance = current - debit` and saves, so **the wallet can be driven negative**.
* No `DB::transaction` around the order mutation; no lock → two parallel requests both pass the `paid` check (see H-13).

**Security Impact**

* Any authenticated customer (or any guest with a guessed `guest_id`) can force **another customer's wallet** to pay for **their own** order.
* Combined with the missing balance check, a user can confirm orders worth more than their balance, leaving a negative wallet the platform must absorb.

**Attack Scenario**

1. Attacker places order #5001 (their own), value 900.00.
2. Attacker enumerates order ids and finds #5000 belonging to a victim with a funded wallet.
3. `POST /api/v1/customer/order/wallet-payment {"order_id": 5000}` → the victim's wallet is debited and the victim's order is confirmed — griefing.
4. `POST {"order_id": 5001}` from an account with 0.00 balance → wallet goes to −900.00 and the order is confirmed as **paid**.

**Secure Expected Behavior**

1. Resolve the order with `->where('user_id', $authenticatedUserId)` and `firstOrFail()`.
2. Reject guests explicitly (guests have no wallet).
3. Re-read `order_amount` from the DB (already done — good) and check `wallet_balance >= order_amount` **before** debiting.
4. Perform the balance check, the debit and the order update inside one `DB::transaction` with `lockForUpdate()` on both `users` and `orders`.

```
Requires Database Change:          NO
Requires Flutter Change:           NO
Requires Gateway Dashboard Change: NO
```

---

### C-7 — Offline payment endpoint: any user can mark any order as offline-paid

```
Finding:                offline_payment() resolves the order with findOrFail($request->order_id)
                        and mutates its payment_method/order_status without an ownership check.
Severity:               CRITICAL
CVSS-like Score:        8.6  (AV:N/AC:L/PR:L/UI:N/S:U/C:L/I:H/A:H)
Gateway:                Offline / manual
File:                   app/Http/Controllers/Api/V1/OrderController.php
Controller:             Api\V1\OrderController
Method:                 offline_payment()
Route:                  PUT /api/v1/customer/order/offline-payment  (routes/api/v1/api.php:436)
Line:                   483-553  (lookup 502, mutation 528-530)
Vulnerable Parameter:   order_id, method_id, arbitrary method_information fields
```

**Current Behavior**

```php
// OrderController.php:502 and 520-531
$order = Order::findOrFail($request->order_id);          // <-- no user scoping
...
$OfflinePayments = OfflinePayments::firstOrNew(['order_id' => $order->id]);
$OfflinePayments->payment_info = json_encode($offline_payment_info);
...
$order->order_status   = 'pending';
$order->payment_method = 'offline_payment';
$order->save();
```

The attacker fully controls the free-text `payment_info` payload (lines 508-517 copy arbitrary request keys), and an admin later approves that record to release the order.

**Security Impact** — Move any order (including another customer's `failed` digital-payment order) into `pending` with `offline_payment`, injecting fabricated bank-reference text that the admin panel presents as truth. Combined with a busy operations team, this is a practical goods-theft path.

**Attack Scenario**
```
PUT /api/v1/customer/order/offline-payment
{"order_id": 5000, "method_id": 1, "trx_id": "TXN-99887766", "customer_note": "paid via bank"}
```

**Secure Expected Behavior** — Scope by `user_id`/`guest_id`, reject when `payment_status === 'paid'`, whitelist accepted fields against `method_informations`, and require the order to be in a payable state.

```
Requires Database Change:          NO
Requires Flutter Change:           NO
Requires Gateway Dashboard Change: NO
```


---

### C-8 — Guest identity is an unauthenticated sequential integer (`apiGuestCheck` bypass)

```
Finding:                APIGuestMiddleware authenticates a "guest" by the mere presence of a
                        client-supplied guest_id, which is a public auto-increment id.
Severity:               CRITICAL
CVSS-like Score:        9.1  (AV:N/AC:L/PR:N/UI:N/S:C/C:H/I:H/A:N)
Gateway:                N/A (platform-wide)
File:                   app/Http/Middleware/APIGuestMiddleware.php
Controller:             (middleware) + Api\V1\Auth\CustomerAuthController@guest_request
Method:                 handle()
Route:                  all routes under 'apiGuestCheck'  (routes/api/v1/api.php:422-453)
Line:                   APIGuestMiddleware.php:19-26 ; CustomerAuthController.php:404-420
Vulnerable Parameter:   guest_id
```

**Current Behavior**

```php
// APIGuestMiddleware.php:19-26
if($request->header('Authorization') && $request->header('Authorization') !== 'Bearer null'
   && app('auth')->guard('api')) {
    $request->merge(['user'=>auth('api')->user()]);
    return $next($request);
}
elseif($request->guest_id) {        // <-- ANY non-empty value passes
    return $next($request);
}
```

```php
// CustomerAuthController.php:406-414 — issues the "credential"
$guest = new Guest();
$guest->ip_address = $request->ip();
$guest->save();
return response()->json(['guest_id' => $guest->id], 200);   // sequential integer
```

`guest_id` is an auto-increment primary key handed out by an open endpoint. No token, no signature, no expiry, no device binding. Downstream code uses it directly as identity: `PlaceNewOrder.php:189` sets `$order->user_id = $request['guest_id']`; `OrderController@cancel_order:210` and `@update_payment_method:384` scope queries by it.

**Second defect in the same block:** the first branch tests `app('auth')->guard('api')`, which returns a *guard object* and is therefore **always truthy**, instead of `auth('api')->check()`. A request with a garbage `Authorization: Bearer xxxx` header passes with `user === null` and falls through to code that treats a null user as a guest.

**Security Impact**

* Enumerate `guest_id = 1..N` → read, cancel, change the payment method of, and drive the payment flow for **every guest order ever placed**.
* `update_payment_method` (line 388) can flip another guest's unpaid digital order to `cash_on_delivery` + `pending`, releasing goods with no payment.
* Guest orders carry `contact_person_name/number/email` inside `delivery_address` → **PII disclosure at scale** via `get_order_details`.

**Attack Scenario**
```
GET /api/v1/customer/order/details?order_id=4999&guest_id=1
GET /api/v1/customer/order/details?order_id=5000&guest_id=2
PUT /api/v1/customer/order/payment-method {"order_id":5000,"guest_id":2}
```

**Secure Expected Behavior**

1. Issue an opaque high-entropy guest **token** (e.g. `Str::random(64)`), store only its hash, require it on every guest call.
2. Bind the token to the order at creation; verify it on every subsequent access.
3. Fix the auth branch to `auth('api')->check()`.
4. Expire guest tokens (e.g. 24 h) and rate-limit `guest/request`.

```
Requires Database Change:          YES (guest token hash column + index)
Requires Flutter Change:           YES (store and send the token instead of the raw id)
Requires Gateway Dashboard Change: NO
```


---

### C-9 — Vendor subscription purchase has no authentication and no ownership check

```
Finding:                business_plan() accepts an arbitrary store_id and package_id from an
                        unauthenticated request and starts/settles a subscription for that store.
Severity:               CRITICAL
CVSS-like Score:        9.1  (AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H)
Gateway:                All (subscription flow)
File:                   app/Http/Controllers/Api/V1/Vendor/SubscriptionController.php
Controller:             Api\V1\Vendor\SubscriptionController
Method:                 business_plan()
Route:                  POST /api/v1/vendor/business_plan  (routes/api/v1/api.php:79 — no middleware)
Line:                   32-108  (store lookup 48, wallet branch 65-81, commission branch 93-98)
Vulnerable Parameter:   store_id, package_id, payment_gateway
```

**Current Behavior**

The route group at `routes/api/v1/api.php:77` declares `['prefix' => 'vendor','namespace' => 'Vendor']` — **no `auth:vendor.api` middleware**. Inside:

```php
// SubscriptionController.php:48
$store = Store::Where('id', $request->store_id)->first();   // attacker-chosen

// 65-75  wallet branch — spends the victim store's wallet
if ($request->payment_gateway == 'wallet') {
    $wallet = StoreWallet::firstOrNew(['vendor_id' => $store->vendor_id]);
    if ($balance > $package?->price) {
        Helpers::subscription_plan_chosen(store_id: $store->id, ...);
        $wallet->total_withdrawn = $wallet?->total_withdrawn + $package->price;

// 93-98  commission branch — cancels the victim's subscription outright
$store->store_business_model = 'commission';
$store->save();
StoreSubscription::where(['store_id' => $store->id])->update(['status' => 0]);
```

`$store` is never null-checked (500 on a bad id).

**Security Impact**

* **Unauthenticated wallet theft** — drain any vendor's `StoreWallet` by buying them the most expensive package.
* **Unauthenticated business sabotage** — flip any store to `commission` and set every `StoreSubscription.status = 0`, disabling paid vendors platform-wide.
* **Free-trial abuse** — `payment_gateway=free_trial` (line 83) grants a plan with no payment and no eligibility check.

Note the sibling route `POST /api/v1/vendor/subscription/payment/api` (api.php:80) points at `SubscriptionController@subscription_payment_api`, **which does not exist** in the class (verified: only `package_view`, `business_plan`, `transaction`, `cancelSubscription`, `checkProductLimits`) — it 500s.

`VendorController@payment` (`app/Http/Controllers/VendorController.php:346-358`, `POST /vendor/payment`) has the same missing-ownership defect on the web flow.

**Secure Expected Behavior**

1. Apply `auth:vendor.api` to the whole group.
2. Derive `store_id` from the authenticated vendor — never from the request body.
3. Re-read `package->price` from the DB (already done) and use `>=` rather than `>` for the balance test.
4. Wrap wallet debit + plan activation in one `DB::transaction` with `lockForUpdate()`.

```
Requires Database Change:          NO
Requires Flutter Change:           YES (stop sending store_id; rely on the bearer token)
Requires Gateway Dashboard Change: NO
```


---

### C-10 — Cross-system wallet credit endpoint is explicitly unauthenticated

```
Finding:                transferMartFromDrivemondWallet credits wallet_balance and is registered
                        with ->withoutMiddleware('auth:api').
Severity:               CRITICAL
CVSS-like Score:        9.3  (AV:N/AC:L/PR:N/UI:N/S:C/C:H/I:H/A:N)
Gateway:                N/A (internal wallet handshake)
File:                   app/Http/Controllers/Api/V1/WalletController.php
Controller:             Api\V1\WalletController
Method:                 transferMartFromDrivemondWallet()
Route:                  POST /api/v1/customer/wallet/transfer-mart-from-drivemond
                        (routes/api/v1/api.php:412 — withoutMiddleware('auth:api'))
Line:                   ~240-317  (credit at 271-281)
Vulnerable Parameter:   amount, token, external_base_url, external_token, bearer_token
```

**Current Behavior**

```php
// WalletController.php:271-281
$user->wallet_balance += $request->amount;              // client-supplied amount
$user->save();
$wallet_transaction = new WalletTransaction();
$wallet_transaction->transaction_id   = Str::uuid();    // local uuid, NOT the remote id
$wallet_transaction->transaction_type = 'wallet_transfer_mart_from_drivemond';
$wallet_transaction->credit  = $request->amount;
$wallet_transaction->balance = $user->wallet_balance;
$wallet_transaction->save();
```

The route removes `auth:api`. The only gate is `Helpers::checkSelfExternalConfiguration()` + `checkExternalConfiguration($request->external_base_url, $request->external_token, $request->token)` — a **static shared secret supplied in the request body**, combined with a caller-controlled `external_base_url` the server then calls outbound (see M-5, SSRF).

Sub-defects:

* `amount` is taken verbatim from the request; the remote system is never asked *how much* to transfer.
* `transaction_id` is a locally generated UUID → **no idempotency key tied to the remote transfer**; the same transfer can be replayed indefinitely.
* The credit and the transaction insert are **not** inside `DB::transaction()`.
* `$drivemondCustomerResponse` is dereferenced at line 300 outside the `if ($response->successful())` block → undefined-variable 500 on the failure path.

**Security Impact** — Anyone who learns the static shared token (stored in `external_configurations`, transmitted in request bodies) can mint unlimited wallet balance for any phone number. Even without the token, the idempotency gap lets one legitimate transfer be replayed.

**Secure Expected Behavior**

1. Require an HMAC signature over the body with timestamp + nonce — the project already ships `VerifyCrossSystemSignature` and applies it at api.php:42-43 for `auth/external-login`; apply the same here.
2. Take the amount from the **remote** system's authoritative response, not the request.
3. Use the remote transfer id as `transaction_id` with a **unique index** for idempotency.
4. Wrap in `DB::transaction()` with `lockForUpdate()` on the user row.

```
Requires Database Change:          YES (unique index on wallet_transactions.transaction_id)
Requires Flutter Change:           NO
Requires Gateway Dashboard Change: NO
```


---

## 5. DETAILED FINDINGS — HIGH

---

### H-1 — PayTabs: signature is verified but the transaction is never bound to `payment_id`

```
Severity: HIGH   CVSS-like: 8.1
File: app/Http/Controllers/PaytabsController.php   Method: callback()   Line: 143-181
Route: ANY /payment/paytabs/callback (routes/web.php:193, CSRF exempt)
Vulnerable Parameter: payment_id (query) vs cart_id (signed body)
```

The HMAC check (`is_valid_redirect`, lines 51-65) and the server-side `payment/query` re-verification (line 162) are both correct. **But** the row updated at line 165 is selected by `$request['payment_id']` — a *query-string* value — while the signed payload carries the true identity in `cart_id` (set to `$payment_data->id` at line 102). The two are never compared.

An attacker replays a genuine, correctly-signed 1.00 PayTabs callback while appending `?payment_id=<expensive-uuid>`. Signature passes, `payment/query` returns `response_status === 'A'`, and the wrong row is marked paid. No `is_paid` guard → also replayable.

**Fix:** require `$verify_result['cart_id'] === $request['payment_id']`, compare `cart_amount`/`cart_currency` with the DB row, add a lock + replay guard.

---

### H-2 — Paymob: payment row is resolved from the PHP session, not from the signed callback

```
Severity: HIGH   CVSS-like: 7.5
File: app/Http/Controllers/PaymobController.php   Method: callback()   Line: 188-244
Route: ANY /payment/paymob/callback (routes/web.php:187, CSRF exempt)
Vulnerable Parameter: session('payment_id'), amount_cents
```

The SHA-512 HMAC (lines 216-224) is computed correctly. However the update at line 226 targets `session('payment_id')`, stored back at line 95. Consequences:

* Paymob's server-to-server callback carries **no cookie**, so `session('payment_id')` is `null` there → the row is never updated on the S2S leg; the flow only works via the browser redirect.
* An attacker can open payment A (session now points to A), then replay a valid signed callback for a cheap payment B → **A is marked paid using B's HMAC**, because the callback body is never correlated with the session value.
* `transaction_id` is set to `session('payment_id')` (line 229) — the *local* uuid, not Paymob's transaction id. The real gateway reference is lost, breaking reconciliation and refunds.
* `amount_cents` from the signed body is never compared with `payment_amount`.
* `cURL()`/`GETcURL()` (lines 36-78) do **not** set `CURLOPT_SSL_VERIFYPEER`/`VERIFYHOST` — they rely on defaults and set no timeouts (see M-4).

**Fix:** resolve the row from the signed `merchant_order_id`/`order` field, compare `amount_cents` to `payment_amount * 100`, store Paymob's `id` as `transaction_id`, add lock + replay guard.

---

### H-3 — SSLCommerz: signature verified, but `payment_id` is unbound and amount is unchecked

```
Severity: HIGH   CVSS-like: 7.5
File: app/Http/Controllers/SslCommerzPaymentController.php   Method: success()   Line: 198-220
Route: POST /payment/sslcommerz/success (routes/web.php:92-93, CSRF exempt)
Vulnerable Parameter: payment_id
```

`SSLCOMMERZ_hash_verify()` (lines 169-196) is a correct `hash_equals` MD5 verification. But line 209 updates `where(['id' => $request['payment_id']])` — again a request-controlled value that is **not part of the signed `verify_key` set** unless SSLCommerz happens to echo it. There is no comparison of `$request['amount']`/`currency` with the DB row, no `is_paid` guard and no lock.

Additionally `tran_id` is `uniqid()` (line 97) and is never persisted, so the local row cannot be correlated with the gateway transaction afterwards.

**Fix:** pass `payment_id` as `value_a` (SSLCommerz echoes it signed), verify it, compare `amount`+`currency`, add lock + replay guard, persist `tran_id`.

---

### H-4 — Paystack: no amount or currency verification on an otherwise-correct callback

```
Severity: HIGH   CVSS-like: 7.4
File: app/Http/Controllers/PaystackController.php   Method: handleGatewayCallback()   Line: 155-184
Route: GET /payment/paystack/callback (routes/web.php:156)
Vulnerable Parameter: reference
```

Binding is done correctly via `data.metadata.payment_id` (line 167) — good. But the handler never checks `data.status === 'success'`, never compares `data.amount` (kobo) with `payment_amount * 100`, and never compares `data.currency`. `$paymentDetails['status']` at line 160 is the **API envelope** flag (`"status": true` merely means the verify call succeeded), *not* the transaction outcome — an abandoned or failed transaction still returns `status: true` with `data.status: "failed"`.

There is also no `is_paid` guard and no lock, so re-visiting the callback URL re-fires `success_hook`.

**Fix:** require `data.status === 'success'`, compare amount and currency, add lock + replay guard.

---

### H-5 — No idempotency anywhere: `payment_requests` has no unique constraint on `transaction_id`

```
Severity: HIGH   CVSS-like: 8.1
File: database/partial/payment_requests.sql
Line: transaction_id varchar(100) DEFAULT NULL   (no UNIQUE, no index)
```

The `payment_requests` table is created from a raw SQL dump (loaded by `UpdateController.php:138-139`), **not** from a migration. It defines:

```sql
`transaction_id` varchar(100) DEFAULT NULL,
`is_paid` tinyint(1) NOT NULL DEFAULT 0,
```

with **no unique index on `transaction_id`** and no `event_id`/`nonce` column at all. The same is true of `wallet_transactions.transaction_id` (`2022_03_31_103418_...php`) and `order_payments`.

Consequently there is **no database-level defence** behind any of the application-level replay gaps (C-1, C-2, C-3, C-4, C-5, H-1..H-4). Even where `is_paid` is checked in PHP, two concurrent requests can both pass the check before either commits.

**Fix:** add `UNIQUE(transaction_id)` on `payment_requests` and `wallet_transactions`; add a `gateway_event_id` column with a unique index for future webhook processing; convert the raw-SQL table into a proper migration.


---

### H-6 — `/payment-mobile` is unauthenticated and writes an attacker URL before the ownership check

```
Severity: HIGH   CVSS-like: 7.5
File: app/Http/Controllers/PaymentController.php   Method: payment()   Line: 41-131
Route: GET /payment-mobile (routes/web.php:72 — no auth, no throttle)
Vulnerable Parameter: customer_id, order_id, callback
```

```php
// PaymentController.php:44-51
if ($request->has('callback')) {
    Order::where(['id' => $request->order_id])->update(['callback' => $request['callback']]);
}
session()->put('customer_id', $request['customer_id']);
$order = Order::where(['id' => $request->order_id, 'user_id' => $request['customer_id']])->first();
```

Line 44 writes an **attacker-controlled URL** into `orders.callback` for **any** `order_id`, *before* any ownership check — the check on line 51 runs afterwards and does not undo the write. This is a stored open-redirect primitive (see M-6) against other users' orders.

The pairing `id + user_id` on line 51 does require a valid `(order_id, customer_id)` pair, but `customer_id` is a sequential integer and the endpoint is unauthenticated and unthrottled, so the pair is brute-forceable.

**Mitigating fact (verified):** the amount at line 86 is `$order->order_amount - $order->partially_paid_amount`, read from the DB — **the client cannot set the amount here.** This is the single most important thing the code gets right.

**Fix:** require `auth:api` or a signed URL; move the `callback` write after the ownership check; validate `callback` against an allow-list; add throttling.

---

### H-7 — Success/failure hooks are invoked by name via `call_user_func` on a DB-sourced string

```
Severity: HIGH   CVSS-like: 7.2
File: app/Traits/Processor.php + every gateway controller
Pattern: if (function_exists($data->success_hook)) call_user_func($data->success_hook, $data);
Line: e.g. StripePaymentController.php:117-118, PaypalPaymentController.php:236-237
```

`payment_requests.success_hook` / `failure_hook` are `varchar(100)` columns holding a **global function name** that is later executed. Today the values are set server-side (`order_place`, `wallet_success`, `sub_success`, `trip_payment_success`), so this is not directly injectable from a request. However:

* Any SQL injection or admin-panel write reaching these columns escalates to **arbitrary function execution** with the payment row as the argument.
* `function_exists()` accepts *any* defined global function including PHP builtins, so a value such as `phpinfo` or `session_destroy` would execute.

**Fix:** replace with an explicit whitelist (`match($data->success_hook) { 'order_place' => ..., }`) or dispatch typed Job classes.

---

### H-8 — MercadoPago `make_payment`: charge amount is taken from the client

```
Severity: HIGH   CVSS-like: 7.3
File: app/Http/Controllers/MercadoPagoController.php   Method: make_payment()   Line: 70-147
Route: POST /payment/mercadopago/make-payment (routes/web.php:180, CSRF exempt)
Vulnerable Parameter: transaction_amount, payment_id, token
```

The handler validates input types and — correctly — cross-checks the *returned* `transaction_amount` against the local row (lines 124-131) before marking paid. That guard is good.

The problem is what is sent **to** MercadoPago at line 101: `"transaction_amount" => (float) $request['transaction_amount']` — the **client's** number, not `$paymentRequest->payment_amount`. An attacker charges their own card 1.00 against a 5,000.00 row; MercadoPago approves the 1.00; the local guard then rejects it (422). No fraud completes, but the card **is charged** and the money captured at MercadoPago with no local record — a real reconciliation and chargeback exposure.

Also `x-idempotency-key` is `uniqid("mp_", true)` (line 87) — a fresh value per call, which defeats the purpose entirely; it must be derived deterministically from `payment_id`.

**Fix:** send `$paymentRequest->payment_amount` from the DB; derive the idempotency key from `payment_id`; add auth or a signed-URL nonce.

---

### H-9 — Razorpay `create-order`: amount and currency taken from the request body

```
Severity: HIGH   CVSS-like: 6.5
File: app/Http/Controllers/RazorPayController.php   Method: createOrder()
Route: ANY /payment/razor-pay/create-order (routes/web.php:119-120, CSRF exempt)
View: resources/views/payment-views/razor-pay.blade.php:83-87
```

The Blade view posts `payment_request_id`, **`payment_amount`** and **`currency_code`** from the browser.

This is **contained** by the strong verification in `payment()` (lines 262-286), which re-reads `payment_amount` from the DB and rejects mismatches — end-to-end fraud is blocked. Residual risk equals H-8: a real card charge for the wrong amount that subsequently fails local validation, leaving an orphaned capture at Razorpay.

**Fix:** ignore `payment_amount`/`currency_code` from the request; read both from the DB row identified by `payment_request_id`.


---

### H-10 — No rate limiting on any payment, wallet, or order endpoint

```
Severity: HIGH   CVSS-like: 7.5
File: bootstrap/app.php:91 (throttle alias registered) ; routes/*.php (never applied)
```

Verified by grep: `throttle` appears **only** as an alias registration at `bootstrap/app.php:91`. It is applied to **zero** routes across `routes/web.php`, `routes/api/v1/api.php`, `routes/admin.php`, `routes/vendor.php`. The `api` middleware group is just `[SubstituteBindings::class]` (lines 67-69) — Laravel's default `throttle:api` was removed.

| Endpoint | Abuse enabled |
|---|---|
| `/payment-mobile` | brute-force `(order_id, customer_id)` pairs |
| `/payment/*/callback` | mass replay of the C-1..C-5 bypasses |
| `/api/v1/customer/wallet/add-fund` | payment-request table flooding |
| `/api/v1/auth/guest/request` | mint unlimited guest ids for C-8 enumeration |
| `/api/v1/customer/order/place` | order spam / inventory lock-up |
| `/api/v1/vendor/business_plan` | mass subscription sabotage (C-9) |

**Fix:** apply `throttle:60,1` to the API group and `throttle:10,1` to payment initiation, callbacks, guest issuance and wallet endpoints.

---

### H-11 — Wallet debit has no balance check and permits a negative balance

```
Severity: HIGH   CVSS-like: 7.1
File: app/CentralLogics/CustomerLogic.php   Method: create_wallet_transaction()   Line: 16-82
Vulnerable Parameter: $amount (caller-supplied)
```

```php
// CustomerLogic.php:49-61
} else if (in_array($transaction_type, ['order_place','trip_booking','ride_booking'])) {
    $debit = $amount;
}
...
$wallet_transaction->balance = $current_balance + $credit + $admin_bonus - $debit;
$user->wallet_balance        = $current_balance + $credit + $admin_bonus - $debit;
```

There is **no** `if ($current_balance < $debit) return false;`. The function writes a negative `wallet_balance` without complaint. Callers are inconsistent:

* `PlaceNewOrder.php:507` **does** pre-check (`wallet_balance < order_amount` → error). Good.
* `TripController.php:706` **does** pre-check. Good.
* `OrderController@walletPayment:792` (C-6) **does not** → negative balance.

The read-modify-write is also performed **outside** any lock: `$current_balance` is read at line 20 and written at 61-65, while `DB::beginTransaction()` only opens at line 64. Two concurrent requests read the same starting balance → **lost update / double spend** (H-13).

**Fix:** enforce a balance check inside the function for every debit type; `lockForUpdate()` the user row *inside* the transaction; use an atomic conditional `UPDATE ... WHERE wallet_balance >= ?`.

---

### H-12 — Refund logic has no idempotency, no state guard, and no gateway refund call

```
Severity: HIGH   CVSS-like: 7.4
File: app/CentralLogics/OrderLogic.php   Methods: refund_before_delivered() / refund_order()
Line: 530-554 and 556-613
Callers: OrderController@cancel_order:267 ; OrderLogic:846, 880
```

```php
// OrderLogic.php:538-544
if (($order->payment_status == "paid")) {
    $adminWallet->digital_received = $adminWallet->digital_received - $order->order_amount;
    $adminWallet->save();
    if (BusinessSetting::where('key','wallet_add_refund')->first()->value == 1 && $order->is_guest == 0) {
        CustomerLogic::create_wallet_transaction($order->user_id, $order->order_amount, 'order_refund', $order->id);
    }
}
```

Defects:

1. **No idempotency.** Nothing records that a refund already occurred; `order_refund` rows are not unique per order.
2. **No refunded-state transition.** `refund_before_delivered()` never sets `payment_status = 'refunded'`, so the guard on line 538 stays true forever → repeat refunds and the `REFUNDED → PAID` transition of §11 are both reachable.
3. **No gateway refund.** No `Refund::create()` (Stripe), no `/v2/payments/captures/{id}/refund` (PayPal), no refund call for any gateway. Money is credited to the customer's *internal wallet* while the original charge stays captured at the PSP — **double loss** if a chargeback follows.
4. Two `->save()` calls with **no** `DB::transaction()` wrapper (contrast `refund_order()` at line 586, which does use one).
5. No amount cap: `refund > payment` is possible for partially-paid orders because `order_amount` is used instead of the actually-captured sum from `order_payments`.

**Fix:** add a `refunds` table with `UNIQUE(order_id, gateway_refund_id)`; set `payment_status='refunded'` in the same transaction; call the gateway refund API and store its id; cap at `SUM(order_payments.amount WHERE payment_status='paid')`.


---

### H-13 — Race conditions: no row locks on the order/wallet payment paths

```
Severity: HIGH   CVSS-like: 7.0
Files: OrderController@walletPayment:787-797 ; CustomerLogic:16-82 ;
       StripePaymentController@success:107-113 ; PaypalPaymentController@success:227-232 ;
       PaytmController@callback:229-234 ; LiqPayController@callback:328-333 ;
       PaystackController@handleGatewayCallback:173-177 ; SslCommerz@success:209-213
```

Only four handlers use `DB::transaction()` + `lockForUpdate()`: **Razorpay** (241-309), **MercadoPago** (~300-350), **bKash** (~420-464) and **SenangPay** (167-247). Every other money-mutating path performs an unguarded check-then-write.

Concretely reachable races:

* **Double wallet spend** — two parallel `walletPayment` calls both read `wallet_balance = 100`, both debit 100 → balance −100, two orders confirmed.
* **Double success-hook** — two parallel Stripe `success` requests both observe `is_paid = 0` → `order_place()` runs twice; with `wallet_success` the wallet is credited twice.
* **Browser retry** — no webhooks exist, but refreshing a slow callback URL reproduces the webhook-replay effect exactly.
* **Double refund** — two parallel `cancel_order` calls both see `payment_status = 'paid'` → two `order_refund` credits.

**Fix:** wrap every state transition in `DB::transaction()` with `lockForUpdate()` on the target row, backed by the unique constraints from H-5.

---

### H-14 — Financial models declare no `$fillable` / `$guarded`

```
Severity: HIGH   CVSS-like: 6.5
Files: app/Models/Order.php:15-40 (only $casts) ; app/Models/WalletPayment.php:8-11 (empty) ;
       app/Models/PaymentRequest.php:9-15 (only $table) ; app/Models/OrderPayment.php ;
       app/Models/WalletTransaction.php
```

Verified by grep: across `Order`, `OrderPayment`, `WalletPayment`, `WalletTransaction` and `PaymentRequest`, **not one** declares `$fillable` or `$guarded`. Only `User` does (`$guarded = ['id']`, User.php:31) — which still leaves `wallet_balance` and `loyalty_point` mass-assignable.

Laravel's default `$guarded = ['*']` makes `create()`/`fill()` throw rather than silently assign, so this is **not currently exploitable** through the audited controllers (they assign property-by-property). It is a latent landmine: the first `Order::create($request->all())` or `$user->update($request->all())` yields `payment_status`, `order_amount`, `transaction_id` and `wallet_balance` injection.

**Fix:** declare explicit `$fillable` on all financial models; never include `payment_status`, `order_status`, `order_amount`, `transaction_id`, `is_paid`, `wallet_balance`, `balance`, `credit`, `debit`.


---

## 6. DETAILED FINDINGS — MEDIUM

### M-1 — Money stored and computed as floating point

```
Severity: MEDIUM   CVSS-like: 5.3
Files: app/Models/Order.php:19-30 ($casts => 'float') ;
       database/migrations/2023_07_06_144944_create_order_payments_table.php:18 ;
       database/migrations/2023_07_09_143746_create_wallet_payments_table.php:18 ;
       app/Traits/PlaceNewOrder.php:380-506 ; app/CentralLogics/CustomerLogic.php:58
```

`Order` casts `order_amount`, `delivery_charge`, `coupon_discount_amount`, `total_tax_amount` and five more to **`float`** — every read/compare/sum in PHP is IEEE-754. The DB columns are inconsistent:

* `wallet_transactions`: `decimal(24,3)` — 3 decimals
* `payment_requests.payment_amount`: `decimal(24,2)` — 2 decimals
* `order_payments.amount` / `wallet_payments.amount`: bare `decimal()` → **MySQL default `decimal(8,2)`**, a hard ceiling of **999,999.99** with silent truncation/error above it

The 3-vs-2 decimal mismatch means a wallet balance of `10.005` becomes `10.01` or `10.00` depending on path. Gateway amount comparisons use `abs($a - $b) > 0.01` (Razorpay 265, MercadoPago 124) — a deliberate float tolerance that also **permits a 1-cent discrepancy per transaction**.

**Fix:** store money as integer minor units or a consistent `decimal(24,2)`; replace `'float'` casts with `'decimal:2'`; compare with `bccomp()`.

### M-2 — `Processor::payment_response()` redirects to an unvalidated stored URL

```
Severity: MEDIUM   CVSS-like: 6.1
File: app/Traits/Processor.php   Method: payment_response()   Line: 78-86
```

```php
if (in_array($payment_info->payment_platform, ['web','app']) && $payment_info['external_redirect_link'] != null) {
    return redirect($payment_info['external_redirect_link'] . '?flag=' . $payment_flag . '&&token=' . base64_encode($token_string));
}
```

`external_redirect_link` originates from `$request['callback']` (PaymentController.php:120, WalletController.php:125) with **no allow-list validation**. Every gateway's success/fail/cancel path funnels through here, so `/payment-mobile?...&callback=https://evil.example` yields a redirect to the attacker carrying `token=base64(payment_method&&attribute_id&&transaction_reference)`.

**Fix:** validate the host against an allow-list — the project already implements exactly this pattern for the image proxy at `routes/web.php:276-294`; reuse it. Or restrict to registered deep-link schemes.

### M-3 — Session-based `/payment-success` `/payment-fail` `/payment-cancel`

```
Severity: MEDIUM   CVSS-like: 4.3
File: app/Http/Controllers/PaymentController.php:133-157 ; routes/web.php:76-78
```

`success()`, `fail()` and `cancel()` resolve the order from `session('order_id')` + `session('customer_id')` — values written by the unauthenticated `payment()` at lines 47-49 — then `redirect($order->callback . '&status=success')`.

Two issues: (a) a second unvalidated-redirect sink; (b) `routes/web.php:76` maps `payment-success` to `PaymentController@oksuccess`, **a method that does not exist** in the class (only `success`, `fail`, `cancel` are defined) → every `payment-success` hit is a 500.

Importantly these handlers do **not** write payment state, so `/payment-success` is *not* a fake-success vector on its own.

**Fix:** implement `oksuccess` (or repoint the route) and validate `$order->callback` before redirecting.

### M-4 — Paymob and Paytm HTTP clients omit TLS verification and timeouts

```
Severity: MEDIUM   CVSS-like: 5.9
File: app/Http/Controllers/PaymobController.php:36-78 ; app/Http/Controllers/PaytmController.php:209-214
```

`PaymobController::cURL()` / `GETcURL()` set only `RETURNTRANSFER`, `POST`, `POSTFIELDS`, `HTTPHEADER`; `PaytmController::payment()` (209-213) is the same. Neither sets `CURLOPT_SSL_VERIFYPEER`, `CURLOPT_SSL_VERIFYHOST`, `CURLOPT_CONNECTTIMEOUT` or `CURLOPT_TIMEOUT`.

libcurl verifies peers by default, so this is not an automatic MITM — but it is environment-dependent, and the missing timeouts let a hung PSP block a PHP-FPM worker indefinitely.

By contrast PayPal (46-49), Paystack (58-61), Flutterwave (45-48), SSLCommerz (64-67) and bKash (93-94) **all** explicitly enable verification and timeouts. Paymob and Paytm are the two stragglers.

**Fix:** apply the same hardened option set the other five controllers already use.


### M-5 — SSRF via `external_base_url` in the cross-system wallet handshake

```
Severity: MEDIUM   CVSS-like: 6.5
File: app/Http/Controllers/Api/V1/WalletController.php:255-264
```

```php
$response = Http::withToken($request->bearer_token)->post($driveMondBaseUrl . '/api/customer/get-data', [...]);
```

`$driveMondBaseUrl` is read from `external_configurations`, but reaching this line requires `checkExternalConfiguration($request->external_base_url, ...)` to pass — and `external_base_url` is request-supplied. Any path that lets a caller influence the compared/stored URL turns this into a server-side request to an attacker-chosen destination with an attacker-chosen bearer token. No scheme check, no private-IP block, no timeout.

The project **already has** a correct SSRF defence (`routes/web.php:276-332`: host allow-list, RFC1918 + `169.254.0.0/16` blocking, DNS-resolution checks, bounded timeout) — it is simply not applied here.

**Fix:** reuse the image-proxy SSRF guard for outbound cross-system calls; pin the base URL to server configuration only.

### M-6 — Stored open redirect via `orders.callback`

```
Severity: MEDIUM   CVSS-like: 5.4
File: app/Http/Controllers/PaymentController.php:44 (write) ; :137,146,154 (read + redirect)
```

Mechanically covered by H-6/M-2; recorded separately because it is *stored* rather than reflected: the attacker writes `orders.callback` for an order they do not own, and the **victim** is redirected off-site the next time they complete or cancel a payment.

**Fix:** allow-list validation at write time and at redirect time.

### M-7 — `translate()` writes to a PHP file on every unknown key

```
Severity: MEDIUM   CVSS-like: 5.3
File: app/Traits/Processor.php:36-54 ; app/helpers.php:21-48
```

```php
if (!array_key_exists($key, $lang_array)) {
    $lang_array[$key] = $processed_key;
    $str = "<?php return " . var_export($lang_array, true) . ";";
    file_put_contents(base_path('resources/lang/en/lang.php'), $str);
}
```

Any request producing a validation error with an unseen message triggers a **write to a PHP file inside the application**, which is executed on later requests. `Processor::error_processor()` feeds validator output straight into `translate()`, and every gateway controller calls it on a 400. There is no locking, so concurrent writes can corrupt the file — a self-inflicted DoS on the payment path.

**Fix:** never write language files at runtime; log missing keys instead.

### M-8 — No reconciliation or expiry for abandoned payments

```
Severity: MEDIUM   CVSS-like: 5.0
File: routes/console.php (no payment command) ; payment_requests has no expiry column
```

With no webhooks (§2.2), a customer who pays then closes the browser leaves `payment_requests.is_paid = 0` and the order `failed` forever while the PSP holds the money. No scheduled job polls pending rows and the table has no TTL — it grows unbounded, and every stale uuid stays a live target for the C-1/C-4/C-5 binding bugs.

**Fix:** add a scheduled reconciliation command that queries each PSP for pending rows older than N minutes; expire `payment_requests` after a TTL.


---

## 7. DETAILED FINDINGS — LOW / INFO

### L-1 — Gateway secret rendered into the Blade scope

`resources/views/payment-views/senang-pay.blade.php:18,30` loads `$config->secret_key` into the view and computes `hash_hmac('sha256', $secretkey . ..., $secretkey)` **inside the template**. The digest is fine to expose, but the raw secret sits in the render context — any future `{{ $secretkey }}`, debug dump or exception page leaks it.

`payment-view-marcedo-pogo.blade.php:36` and `stripe.blade.php:14` expose only **publishable/public** keys — correct and expected.

**Fix:** compute the SenangPay hash in the controller; pass only the digest to the view.

### L-2 — Unchecked gateway-response dereferences

`PaymobController::getPaymentToken()` returns `$response->token` (line 185) with no null check; `PaytabsController::callback()` reads `$verify_result['payment_result']['response_status']` (line 163) without confirming the keys exist. A PSP outage yields a 500 stack trace rather than a clean failure. `PaytabsController::payment()` also uses raw `header('Location: ...'); exit();` (lines 139-140), bypassing Laravel's response pipeline entirely.

### L-3 — Dead `eval`-era scaffolding in `PaymentController`

`app/Http/Controllers/PaymentController.php:28-40` still contains `generateExtendedControllerClass()`, which builds a class-definition **string** — a remnant of a prior `eval()` implementation (referenced in `SMARTWEBYE_Security_Report.md:62`). It is unreachable today (line 22 calls `usePaymentGatewayTrait()` instead), and `usePaymentGatewayTrait()` is **not defined** in the class or in `App\Traits\Payment` — so if `trait_exists('App\Traits\Payment')` returns true, the constructor at lines 17-19 fatals. Dead but dangerous; delete it.

### I-1 — APP_DEBUG is correctly disabled

`.env:2,4,7` → `APP_ENV=live`, **`APP_DEBUG=false`**, `APP_MODE=live`. **PASS.**

Minor note: `APP_ENV=live` is non-standard (Laravel expects `production`), so any `app()->environment('production')` check returns false.

### I-2 — `.env` is not tracked by git; no hardcoded secrets found

Verified: `git check-ignore -v .env` → matched by `.gitignore:6`; `git ls-files --error-unmatch .env` → *"did not match any file(s) known to git"*. **PASS.**

Grep for `sk_live_`, `sk_test_`, `STRIPE_SECRET`, `PAYPAL_SECRET` across `config/` and `.env` returned only `config/paypal.php:5` — `env('PAYPAL_SECRET','')`, a safe reference, not a literal.

Gateway credentials live in the `addon_settings` table as JSON (`Processor::payment_config()`, lines 56-66), read per request. Acceptable — but DB read access equals full PSP compromise.

**Logging check:** the hardened controllers log only ids, amounts, statuses and error messages (RazorPay 116-119, 266-270; bKash 438-441). No `Log::` call in the payment path writes a card number, CVV, secret key or Authorization header. **PASS.**

### I-3 — PCI DSS scope is favourable

The application uses **hosted checkout / tokenisation** throughout: Stripe Checkout redirect, PayPal hosted order, Razorpay Checkout.js, MercadoPago SDK tokenisation, bKash tokenised checkout, PayTabs hosted page, SSLCommerz gateway page. Grep found **no** PAN/CVV/card-number handling in Laravel. **PASS — SAQ-A / SAQ-A-EP scope, not SAQ-D.**

Caveat: `PaymobController::callback()` includes `source_data_pan` in the HMAC field list (line 210). Paymob sends this **masked** (`xxxx-xxxx-xxxx-1234`), so it is not full-PAN exposure — but it is card data transiting the callback and must never be logged. It currently is not; keep it that way.

### I-4 — No SQL injection found in the payment path

Grep for `DB::raw`, `whereRaw`, `orderByRaw`, `selectRaw`, `DB::statement` across `PaymentController`, `WalletController`, `OrderController`, all 13 gateway controllers, `CustomerLogic`, `OrderLogic` and `Processor` returned **only** two hits, both in `Admin/OrderController.php:69,1662`: `whereRaw('created_at <> schedule_at')` — a static string with no user input. Every payment query uses the Eloquent builder with bound parameters. **PASS.**


---

## 8. AMOUNT TAMPERING ANALYSIS

**The good news, verified in source:** the client **cannot** set the order total.

`app/Traits/PlaceNewOrder.php` computes everything server-side:

| Step | Line | Evidence |
|---|---|---|
| `order_amount` from request is **discarded** | 43 | `// 'order_amount' => 'required',` commented out |
| Item prices from DB | 380 | `$total_price = $product_price + $total_addon_price - $store_discount_amount - ...` |
| Negative clamp | 391 | `$total_price = max($total_price, 0);` |
| Tax computed server-side | 462 | `round($total_price + $tax_amount + $order->delivery_charge, ...)` |
| Delivery charge server-side | 201 | `round($delivery_charge, config('round_up_to_digit'))` |
| Wallet sufficiency | 507 | `wallet_balance < $order->order_amount` → error |
| COD ceiling | 523 | `maximum_cod_order_amount` check |

Line 190 (`$order->order_amount = $request['order_amount'] ?? 0;`) looks alarming but is **overwritten** at lines 462/468/500/506 before the order is saved.

The amount then flows `Order::order_amount` → `PaymentController:86` → `PaymentInfo` → `payment_requests.payment_amount`, and `Traits\Payment::generate_link():14` rejects `<= 0`.

**Test matrix (logical evaluation against the code):**

| Injected amount | Result |
|---|---|
| `100.00 → 1.00` | **BLOCKED at order creation** — request value discarded (PlaceNewOrder:43) |
| `100.00 → 0` | **BLOCKED** — `generate_link()` throws on `<= 0` (Payment.php:14) |
| `100.00 → 0.01` | **BLOCKED at order creation** |
| `100.00 → -1` | **BLOCKED** — `max($total_price, 0)` (PlaceNewOrder:391) + `<= 0` guard |
| `100.00 → 999999` | Accepted but self-harming; `order_payments.amount` is `decimal(8,2)` so ≥1,000,000 truncates (M-1) |

**BUT — the amount is defeated later, at the callback.** Because Stripe (C-1), PayPal (C-4), Flutterwave (C-5), Paytm (C-2), LiqPay (C-3), PayTabs (H-1), Paymob (H-2), SSLCommerz (H-3) and Paystack (H-4) never compare the *paid* amount with `payment_requests.payment_amount`, an attacker pays 1.00 through a legitimate cheap transaction and redeems it against the 100.00 row.

**Verdict: Amount Tampering = FAIL** — not at order creation (which is correctly implemented), but at payment confirmation.

---

## 9. IDOR / AUTHORIZATION ANALYSIS

| Endpoint | Ownership check | Verdict |
|---|---|---|
| `PaymentController@payment` | `where(['id'=>…,'user_id'=>…])` — both from the request, unauthenticated | **PARTIAL** (H-6) |
| `OrderController@get_order_details` | `->when($request->user, …)` — **guests skip the scope** | **FAIL** |
| `OrderController@cancel_order` | `where(['user_id'=>…,'id'=>…])` | **PASS** (C-8 caveat) |
| `OrderController@update_payment_method` | `where(['user_id'=>…,'id'=>…])` | **PASS** (C-8 caveat) |
| `OrderController@walletPayment` | **none** | **FAIL (C-6)** |
| `OrderController@offline_payment` | **none** — `findOrFail()` | **FAIL (C-7)** |
| `OrderController@update_offline_payment_info` | **none** | **FAIL** |
| `OrderController@parcelReturn` | **none** | **FAIL** |
| `CustomerController@orderPaymentFailed` | **none when `order_id` supplied** (357-359) | **FAIL** |
| `WalletController@add_fund` | `$request->user()->id` | **PASS** |
| `WalletController@transferMartFromDrivemond` | auth removed | **FAIL (C-10)** |
| `Vendor\SubscriptionController@business_plan` | **none** | **FAIL (C-9)** |
| `VendorController@payment` | **none** | **FAIL** |
| `TripController@makePayment` | `where(['user_id'=>…,'is_guest'=>…,'id'=>…])` | **PASS** — best in codebase |

`get_order_details` deserves emphasis. Lines 171-177:

```php
->when(isset($request->user), fn($q) => $q->where('is_guest', 0))
->when($request->user,        fn($q) => $q->where('user_id', $user_id))
->findOrFail($request->order_id);
```

When the caller is a **guest**, `$request->user` is null, both `when()` clauses are skipped, and the query degrades to `findOrFail($order_id)` — **any guest can read any order**, including registered customers' orders with full delivery address and contact PII.

**Customer ID tampering:** `customer_id` is trusted only in `PaymentController@payment`, and there it is paired with `order_id` in the same `where`, so a mismatched pair returns 403 (lines 52-54). Setting `customer_id=1/2/999999` alone grants nothing. **PARTIAL** — the real exposure is unauthenticated brute-forceability (H-6), not direct tampering.


---

## 10. WEBHOOK / REPLAY / IDEMPOTENCY ANALYSIS

**Webhooks: N/A across the board** — none exist (§2.2). Every "callback" is a browser redirect.

**Replay protection**, verified per handler:

| Handler | `is_paid` guard | Row lock | Verdict |
|---|---|---|---|
| Razorpay `payment()` | line 253 | line 244 `lockForUpdate()` | **PASS** |
| Razorpay `verifyPayment()` | line 605 | line 601 | **PASS** |
| MercadoPago `callback()` | inside txn | `lockForUpdate()` | **PASS** |
| bKash `callback()` | inside txn | `lockForUpdate()` | **PASS** |
| SenangPay `return_senang_pay()` | line 176 | line 167 | **PASS** |
| Stripe `success()` | **none** | **none** | **FAIL** |
| PayPal `success()` | **none** | **none** | **FAIL** |
| Paystack `handleGatewayCallback()` | **none** | **none** | **FAIL** |
| SSLCommerz `success()` | **none** | **none** | **FAIL** |
| Flutterwave `callback()` | **none** | **none** | **FAIL** |
| Paymob `callback()` | **none** | **none** | **FAIL** |
| PayTabs `callback()` | **none** | **none** | **FAIL** |
| Paytm `callback()` | **none** | **none** | **FAIL** |
| LiqPay `callback()` | **none** | **none** | **FAIL** |

**Database-level idempotency: absent everywhere** (H-5). Neither `payment_requests.transaction_id`, `wallet_transactions.transaction_id` nor `order_payments` carries a unique index, and there is no `event_id` column anywhere.

Replaying a Stripe/PayPal/Paystack success URL 3× against a `wallet_payments` attribute therefore produces **3 wallet credits, 3 `wallet_transactions` rows and 3 emails** — exactly the failure mode §12 of the brief describes.

---

## 11. FINANCIAL STATE MACHINE

**Intended:**

```
INITIATED ──> PENDING ──> PROCESSING ──> PAID
    │            │                        │
    ├──> FAILED  ├──> CANCELLED           ├──> REFUNDED
    └──> EXPIRED                          └──> PARTIALLY_REFUNDED
```

**Actual:** `orders.order_status` ∈ {`pending`, `confirmed`, `failed`, `canceled`, `delivered`, `refund_requested`, `refunded`, `returned`}; `orders.payment_status` ∈ {`unpaid`, `paid`, `partially_paid`}. There is **no `refunded` value for `payment_status`**, and no state-machine class — transitions are ad-hoc assignments scattered across `helpers.php`, `OrderLogic` and controllers.

**Illegal transitions reachable today:**

| Transition | Reachable via | Why |
|---|---|---|
| `FAILED → PAID` | `order_place()` (helpers.php:126-135) | Sets `confirmed`/`paid` unconditionally; digital orders start at `failed` (PlaceNewOrder:184), so any fake callback performs `FAILED → PAID` |
| `PAID → PAID` (replay) | Stripe/PayPal/Paystack/etc. | No `is_paid` guard (§10) |
| `REFUNDED → PAID` | `refund_before_delivered()` never records a refunded payment state | `payment_status` stays `paid`, so a later callback re-confirms |
| `REFUNDED → REFUNDED` | `cancel_order` → `refund_before_delivered()` | No idempotency, no state guard (H-12) |
| `CANCELLED → PAID` | `order_place()` | No check of current `order_status` before overwriting |
| `PAID → COD/pending` | `update_payment_method()` (OrderController:388) | Checks only `payment_method != 'partial_payment'`; does **not** check `payment_status` |

`order_place()` is the crux — 10 lines with no guard at all:

```php
// app/helpers.php:126-135
$order = Order::find($data->attribute_id);
$order->order_status = 'confirmed';
if ($order->payment_method != 'partial_payment') { $order->payment_method = $data->payment_method; }
$order->payment_status = 'paid';
$order->confirmed = now();
$order->save();
```

No check that the order is not already paid, cancelled, refunded or delivered; no `DB::transaction()`; no lock; `Order::find()` result never null-checked.

**Fix:** introduce an explicit transition table, reject illegal moves inside a locked transaction, and add `refunded`/`partially_refunded` to `payment_status`.


---

## 12. FINAL AUDIT MATRIX

Legend: **PASS** / **FAIL** / **PARTIAL** / **NOT VERIFIED** / **N/A**

| Gateway | Initialization | Amount Validation | Authorization | Callback | Webhook | Signature | Replay Protection | Idempotency | Refund | Status |
|---|---|---|---|---|---|---|---|---|---|---|
| Stripe | PASS | **FAIL** | **FAIL** | **FAIL** | N/A | **FAIL** | **FAIL** | **FAIL** | **FAIL** | **CRITICAL** |
| PayPal | PASS | **FAIL** | **FAIL** | **FAIL** | N/A | **FAIL** | **FAIL** | **FAIL** | **FAIL** | **CRITICAL** |
| Razorpay | PARTIAL | PASS | PARTIAL | PASS | N/A | **PASS** | PASS | PARTIAL | **FAIL** | **GOOD** |
| Paytabs | PASS | **FAIL** | **FAIL** | PARTIAL | N/A | PASS | **FAIL** | **FAIL** | **FAIL** | **HIGH** |
| Paymob | PASS | **FAIL** | **FAIL** | **FAIL** | N/A | PASS | **FAIL** | **FAIL** | **FAIL** | **HIGH** |
| Paystack | PASS | **FAIL** | PARTIAL | PARTIAL | N/A | **FAIL** | **FAIL** | **FAIL** | **FAIL** | **HIGH** |
| Flutterwave | PASS | **FAIL** | **FAIL** | **FAIL** | N/A | **FAIL** | **FAIL** | **FAIL** | **FAIL** | **CRITICAL** |
| MercadoPago | PARTIAL | PASS | PARTIAL | PASS | N/A | PARTIAL (IP) | PASS | PARTIAL | **FAIL** | **GOOD** |
| SSLCommerz | PASS | **FAIL** | **FAIL** | PARTIAL | N/A | PASS | **FAIL** | **FAIL** | **FAIL** | **HIGH** |
| bKash | PASS | PASS | PARTIAL | PASS | N/A | PARTIAL (IP) | PASS | PARTIAL | N/A (commented out) | **GOOD** |
| LiqPay | PASS | **FAIL** | **FAIL** | **FAIL** | N/A | **FAIL** | **FAIL** | **FAIL** | **FAIL** | **CRITICAL** |
| Paytm | PASS | **FAIL** | **FAIL** | **FAIL** | N/A | **FAIL** | **FAIL** | **FAIL** | **FAIL** | **CRITICAL** |
| SenangPay | PASS | PASS | PARTIAL | PASS | N/A | PASS | PASS | PARTIAL | **FAIL** | **GOOD** |
| Iyzico | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | **NOT IMPLEMENTED** |
| PhonePe | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | **NOT IMPLEMENTED** |
| Xendit | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | **NOT IMPLEMENTED** |

*"Authorization: PARTIAL" for the hardened gateways reflects that the `payment_id` uuid is the sole bearer of authority — knowing it is sufficient. The uuid is unguessable, so this is acceptable, but it is not a true authorization check.*

---

## 13. GLOBAL FINANCIAL SECURITY MATRIX

| Security Area | Status | Severity |
|---|---|---|
| Amount Tampering | **FAIL** (at callback; PASS at order creation) | **CRITICAL** |
| Order IDOR | **FAIL** | **CRITICAL** |
| Customer IDOR | **PARTIAL** | HIGH |
| Guest Payment | **FAIL** | **CRITICAL** |
| Payment Status Manipulation | **FAIL** | **CRITICAL** |
| Fake Success | **FAIL** (Paytm, LiqPay directly; Stripe/PayPal/Flutterwave via cheap-payment redemption) | **CRITICAL** |
| Webhook Signature | **N/A** — no webhooks exist | **HIGH** (architectural gap) |
| Replay Attack | **FAIL** (9 of 13 gateways) | **CRITICAL** |
| Idempotency | **FAIL** — no unique constraints anywhere | **CRITICAL** |
| Race Condition | **FAIL** (9 of 13 gateways + wallet) | HIGH |
| Refund Security | **FAIL** — no gateway refund, no idempotency, no state guard | HIGH |
| Wallet Security | **FAIL** — negative balance, no lock, unauth credit endpoint | **CRITICAL** |
| Subscription Security | **FAIL** — unauthenticated `business_plan` | **CRITICAL** |
| Currency Manipulation | **PARTIAL** — currency is server-side from `BusinessSetting`, but never re-verified at callback except Razorpay/MercadoPago/SenangPay | MEDIUM |
| Coupon Manipulation | **PASS** — `CouponLogic` re-validates server-side; discount recomputed in `PlaceNewOrder:380` | LOW |
| Secret Leakage | **PASS** — `.env` untracked, no literals; **PARTIAL** for the SenangPay view (L-1) | LOW |
| Debug Mode | **PASS** — `APP_DEBUG=false` | INFO |
| Rate Limiting | **FAIL** — zero routes throttled | HIGH |
| CSRF | **PARTIAL** — exemptions are architecturally necessary, but 5 of them lack a compensating signature | HIGH |
| Authentication | **FAIL** — payment, subscription and one wallet endpoint are unauthenticated | **CRITICAL** |
| Authorization | **FAIL** — no ownership checks on 7 money endpoints | **CRITICAL** |
| SQL Injection | **PASS** | INFO |
| SSRF | **PARTIAL** — image proxy is hardened; cross-system wallet call is not | MEDIUM |
| Open Redirect | **FAIL** — stored + reflected via `callback` | MEDIUM |
| PCI DSS Exposure | **PASS** — hosted checkout / tokenisation throughout | INFO |
| Database Integrity | **FAIL** — no unique constraints, float money, `decimal(8,2)` ceiling | HIGH |

### CSRF exemption review (§18 of the brief)

| Exempt route | Why CSRF is off | What authenticates it | Verdict |
|---|---|---|---|
| `/payment/sslcommerz/*` | external gateway POST | MD5 `verify_sign` | **ACCEPTABLE** (but H-3 binding gap) |
| `/payment/razor-pay/payment` | external POST | Razorpay HMAC | **ACCEPTABLE** |
| `/payment/razor-pay/verify-payment` | external POST | Razorpay HMAC | **ACCEPTABLE** |
| `/payment/razor-pay/create-order` | same-origin AJAX | **nothing** | **HIGH** — should keep CSRF (the view already sends the token) |
| `/payment/paypal/success` | external return | **nothing** | **CRITICAL (C-4)** |
| `/payment/paytm/response` | external return | **nothing** | **CRITICAL (C-2)** |
| `/liqpay-callback`, `/payment*` | external return | **nothing** | **CRITICAL (C-3)** |
| `/payment/mercadopago/make-payment` | same-origin AJAX | **nothing** | **HIGH (H-8)** |
| `/payment/mercadopago/callback` | external IPN | IP allow-list + S2S fetch | **ACCEPTABLE** |
| `/payment/bkash/callback` | external return | IP allow-list + S2S execute | **ACCEPTABLE** |
| `/payment/paymob/callback` | external POST | SHA-512 HMAC | **ACCEPTABLE** (but H-2 session gap) |
| `/payment/paytabs/callback` | external POST | HMAC + S2S query | **ACCEPTABLE** (but H-1 binding gap) |
| `/success`, `/fail`, `/cancel`, `/ipn` | generic wildcards | **nothing** | **HIGH** — over-broad; these patterns exempt any route ending in those segments |


---

## 14. FINAL SCORE

```
Overall Payment Security Score: 22/100
```

| Dimension | Weight | Score | Notes |
|---|---|---|---|
| Server-side amount calculation at order creation | 15 | 14/15 | Genuinely well implemented |
| Callback verification | 25 | 4/25 | 4 of 13 gateways correct |
| Authentication / authorization | 20 | 2/20 | Payment + subscription endpoints unauthenticated |
| Idempotency / replay | 15 | 2/15 | No DB constraints; 9 of 13 gateways replayable |
| Wallet integrity | 10 | 1/10 | Negative balance, no lock, unauth credit path |
| Refund security | 5 | 0/5 | No gateway refund, no idempotency |
| Secrets / debug / PCI / SQLi | 10 | 9/10 | Genuinely good |

```
Critical: 10
High:     14
Medium:    8
Low:       3
Info:      4
```

**Critical:** C-1 Stripe binding · C-2 Paytm no-verify · C-3 LiqPay no-verify · C-4 PayPal binding · C-5 Flutterwave metadata-amount · C-6 walletPayment IDOR · C-7 offline_payment IDOR · C-8 guest_id forgery · C-9 unauthenticated subscription · C-10 unauthenticated wallet credit

**High:** H-1 PayTabs · H-2 Paymob session · H-3 SSLCommerz binding · H-4 Paystack amount · H-5 no unique constraints · H-6 `/payment-mobile` · H-7 `call_user_func` hooks · H-8 MercadoPago client amount · H-9 Razorpay create-order · H-10 no rate limiting · H-11 negative wallet · H-12 refund · H-13 races · H-14 mass assignment

---

## 15. PRODUCTION READINESS

```
CRITICAL PAYMENT SECURITY ISSUES
```

**This system must not process real money in its current state.** Two gateways (Paytm, LiqPay) can be defrauded with a single unauthenticated GET request and no payment at all. Three more (Stripe, PayPal, Flutterwave) can be defrauded with one legitimate 1.00 payment redeemed against an arbitrary amount, repeatedly.

---

## 16. RECOMMENDED REMEDIATION ORDER

### Phase 1 — Stop the bleeding (before any live traffic)

1. **C-2, C-3** — Add signature + server-side status verification to Paytm and LiqPay. If that cannot ship immediately, **disable both gateways** in `addon_settings`.
2. **C-1, C-4, C-5** — Bind the gateway transaction to `payment_id`; verify amount + currency for Stripe, PayPal, Flutterwave.
3. **C-6, C-7** — Add ownership scoping to `walletPayment` and `offline_payment`.
4. **C-9, C-10** — Apply `auth:vendor.api` to the vendor group; restore auth + HMAC on `transfer-mart-from-drivemond`.
5. **H-5** — Add `UNIQUE(transaction_id)` to `payment_requests` and `wallet_transactions`.

### Phase 2 — Structural (same sprint)

6. **C-8** — Replace `guest_id` with an opaque token (requires a coordinated Flutter release).
7. **H-1..H-4** — Close the binding/amount gaps in PayTabs, Paymob, SSLCommerz, Paystack.
8. **H-11, H-13** — Balance checks + `lockForUpdate()` on every wallet and order transition.
9. **H-10** — Apply throttling.
10. **§11** — Explicit state machine; `order_place()` must reject illegal source states.

### Phase 3 — Architecture

11. Implement **real webhooks** with signature verification for Stripe, PayPal, Razorpay, Paystack, Flutterwave, plus a reconciliation command (M-8).
12. **H-12** — Build a refund subsystem that calls the gateway and is idempotent.
13. **M-1** — Migrate money to integer minor units or consistent `decimal(24,2)`; fix the `decimal(8,2)` ceiling.
14. **H-7, H-14, M-2, M-5, M-6, M-7, L-1..L-3** — Remaining hardening.

### Reference implementations already in this codebase

Do not invent a new pattern — copy these:

| Concern | Copy from |
|---|---|
| Signature + S2S verify + lock + amount/currency check | `RazorPayController::payment()` lines 104-335 |
| S2S fetch + `external_reference` binding + IP allow-list | `MercadoPagoController::callback()` |
| Fresh-token negotiation + echo-check + lock | `BkashPaymentController::callback()` |
| URL allow-list + SSRF blocking | `routes/web.php:270-332` (image proxy) |
| Cross-system HMAC middleware | `app/Http/Middleware/VerifyCrossSystemSignature.php` (already used at `api.php:42-43`) |


---

## 17. AUDIT SCOPE & METHOD

**Files read in full or in relevant part:**

`routes/web.php`, `routes/api/v1/api.php`, `routes/admin.php`, `bootstrap/app.php`, all 13 gateway controllers, `PaymentController`, `Api/V1/OrderController`, `Api/V1/WalletController`, `Api/V1/CustomerController`, `Api/V1/Auth/CustomerAuthController`, `Api/V1/Vendor/SubscriptionController`, `VendorController`, `app/Traits/Payment.php`, `app/Traits/Processor.php`, `app/Traits/PlaceNewOrder.php`, `app/Library/Payment.php`, `app/helpers.php`, `app/CentralLogics/CustomerLogic.php`, `app/CentralLogics/OrderLogic.php`, `app/CentralLogics/Helpers.php` (payment sections), `app/Http/Middleware/APIGuestMiddleware.php`, `app/Http/Middleware/VerifyCsrfToken.php`, models (`Order`, `OrderPayment`, `WalletPayment`, `WalletTransaction`, `PaymentRequest`, `User`), `database/partial/payment_requests.sql`, payment migrations, `resources/views/payment-views/*`, `Modules/Rental/.../TripController.php`, `.env`, `modules_statuses.json`.

**Testing performed:** static source analysis and logical evaluation only. **No requests were sent to any environment, no test transactions were created, and no gateway sandbox was contacted.** Every attack scenario is derived from code paths, not from execution — consistent with §42 of the brief.

**Compliance with the READ-ONLY constraint:** no existing file was modified, deleted or rewritten. The only file created is this report.

---

## 18. THINGS I COULD NOT VERIFY

Marked **NOT VERIFIED** rather than PASS, per §45 of the brief:

1. **Runtime `addon_settings` contents** — which gateways are actually enabled, and whether each is in `test` or `live` mode, is database state I did not query. The real-world severity of C-2/C-3 depends on whether Paytm and LiqPay are enabled in production.
2. **`Helpers::checkExternalConfiguration()` internals** — I read its call sites but not its body; the exact strength of the cross-system token comparison in C-10 is unconfirmed.
3. **Admin refund/transaction endpoints** — `routes/admin.php:333-346` exposes `parcelRefund` and `order_refund_rejection`. I confirmed they sit inside the `admin` middleware group but did not audit their bodies or the per-role permission checks.
4. **`RazorPayController::createOrder()` body** — I read the route, the Blade view that calls it, and the downstream verification, but not the method body; H-9's precise behaviour is inferred from the client payload.
5. **Queue workers** — no payment processing is queued today (all callbacks are synchronous), so §39 of the brief is largely N/A, but I did not audit `app/Jobs/` exhaustively.
6. **Flutter client** — outside this repository. Statements about what the app sends are inferred from the API contracts.

---

*End of report. No remediation has been applied. Awaiting approval before any code change.*

```
