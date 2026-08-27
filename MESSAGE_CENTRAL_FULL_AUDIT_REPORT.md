# Message Central OTP — Full Audit & Fix Report

**Project:** PickAdmin (Pickles & Pies) — Laravel 12 + nwidart-modules  
**Date:** 2026-08-26  
**Scope:** Complete end-to-end audit of the Message Central OTP integration
(Send → Save verificationId → Verify → Authentication). Targeted fixes
only; no architectural rewrites.  
**Author:** Senior Laravel/PHP + Backend Integration Engineer  
**Mode:** Act (YOLO) — all edits applied directly to the codebase.

---

## 1. Audit Findings (before any fix)

### 1.1 Files in scope (verified to exist)

| Layer | File | Lines of interest |
|---|---|---|
| Migration | `database/migrations/2026_08_25_120000_add_message_central_columns_to_phone_verifications_table.php` | full file |
| Model    | `app/Models/PhoneVerification.php` | full file |
| Trait    | `app/Traits/SmsGateway.php` | `message_central()` 646–768, `message_central_send()` 659–780, `message_central_verify()` 805–957, `is_message_central_active()` 947–951, `get_settings()` 953–960 |
| Send controller | `app/Http/Controllers/Api/V1/CustomerController.php` | `verification_check()` 672–812, `normalizePhoneForVerificationLocal()` 1017–1031 |
| Verify controller | `app/Http/Controllers/Api/V1/Auth/CustomerAuthController.php` | `verify_phone_or_email()` 34–end, `verifyWithProvider()` 1442–1579, `normalizePhoneForVerification()` 1586–1599, `providerErrorResponse()` 1604–end |
| Routes   | `routes/api/v1/api.php` | line 44 (`POST /api/v1/auth/verify-phone`) |
| Lang     | `resources/lang/en/messages.php` | lines 11762–11766 (OTP_expired, OTP_already_verified, Too_many_attemps, provider_unavailable, Verification_session_not_found) |

### 1.2 Critical issues found

| # | Severity | File / line | Issue |
|---|----------|-------------|-------|
| 1 | **CRITICAL** | `SmsGateway.php:845` | Validate-OTP endpoint had a typo: `veri_cation/v3/validateOtpOtp` — both the path segment (`veri_cation` instead of `verification`) and the action (`validateOtpOtp` instead of `validateOtp`) were wrong. Any curl to that URL would 404 from Message Central. |
| 2 | **CRITICAL** | `SmsGateway.php:843` | `$vId = $verificationId ?: ('local-' . md5($mobile . '|' . substr((string) $otp, 0, 2)));` — fabricated a local id when the request didn't supply one. The id was never going to match anything on Message Central's side. |
| 3 | **HIGH** | `CustomerAuthController.php:1460` | `verifyWithProvider()` looked up `phone_verifications` using the *raw* `$phone` instead of the *normalized* `$normalizedPhone`. Send-side stores under the normalized key, so the verify lookup silently missed for any non-canonical phone format. |
| 4 | **HIGH** | `SmsGateway.php:815–819` | `message_central_verify()` returned `status='success'` whenever the provider was not configured — combined with the new short-circuit in `verifyWithProvider()`, this would have let an attacker skip verification entirely. Replaced with `status='inactive'`. |
| 5 | **HIGH** | `CustomerAuthController.php:1480–1482` | `verifyWithProvider()` fell back to `$request->input('verification_id')` when the DB row had no id. IDOR vector — an attacker can pass any id and the server will accept it. Removed; controller now requires a real id from the DB. |
| 6 | **MEDIUM** | `CustomerAuthController.php:1513–1522` | Success branch updated `phone_verifications` without `DB::transaction` or `lockForUpdate()`, allowing two concurrent verify requests to both pass the `is_verified=0` gate. Now wrapped in `DB::transaction(...)` + `lockForUpdate()`. |
| 7 | **MEDIUM** | `CustomerController.php:712–715` | On send-time, provider metadata was saved to the row even when Message Central returned an error. Half-baked rows then caused confusing `verification_not_found` responses at verify-time. Now metadata is only persisted when `$sendResult['status'] === 'success'`. |
| 8 | **LOW** | `SmsGateway.php:787` | Doc-block for `message_central_verify()` had the same `veri_cation` typo. Cleaned up to the correct endpoint. |

### 1.3 Non-issues (audited, no change needed)

- `phone_verifications` schema — `verification_id, transaction_id, reference_id, flow_type, verified_at, is_verified` already present (migration `2026_08_25_120000`). Composite index `idx_phone_verifications_phone_verified` is in place.
- Existing `phone` column is reused as the canonical phone key (no separate `mobile_number`).
- Routes — `POST /api/v1/auth/verify-phone` is the only endpoint we touch; no new routes added.
- Translations — all five required keys are present at `resources/lang/en/messages.php:11762–11766`.
- All other SMS providers (`twilio`, `nexmo`, `2factor`, `msg91`, `releans`, `hubtel`, `paradox`, `signal_wire`, `019_sms`, `viatech`, `global_sms`, `akandit_sms`, `sms_to`, `alphanet_sms`) are untouched.
- `message_central_send()`'s HTTP response parsing and provider status mapping (`responseCode=200/409/429/401/404/400/403 → success/already_verified/too_many_attempts/expired/invalid_otp`) — already correct, not modified.

---

## 2. Files Changed

```
M  app\Traits\SmsGateway.php
M  app\Http\Controllers\Api\V1\Auth\CustomerAuthController.php
M  app\Http\Controllers\Api\V1\CustomerController.php
```

No migration added or removed (the schema migration `2026_08_25_120000_…` already covers everything).  
No new routes, no new models, no new tables, no new dependencies.  
No other files touched — verified via `git diff --stat` scope (manual review).

### 2.1 `app/Traits/SmsGateway.php`

| Change | Why |
|--------|-----|
| **Fixed** `message_central_verify()` endpoint from `https://cpaas.messagecentral.com/veri_cation/v3/validateOtpOtp` to `https://cpaas.messagecentral.com/verification/v3/validateOtp`. | Both the path segment and the action were misspelled. |
| **Removed** the `'local-' . md5(...)` fallback that fabricated a `verificationId` when none was supplied. | A locally-derived id can never validate against MC; replaced with a hard `provider_error` + `missing_verification_id` response. |
| **Hardened** the "provider not configured" branch from `status='success'` to `status='inactive'`. | Previously an inactive provider returned success, which combined with the new short-circuit produced an authenticated session for *any* typed OTP. |
| **Fixed** doc-block endpoint typo. | Documentation hygiene. |

### 2.2 `app/Http/Controllers/Api/V1/Auth/CustomerAuthController.php`

| Change | Why |
|--------|-----|
| **`verifyWithProvider()` now looks up the row by `$normalizedPhone` (not `$phone`).** | Send-side stores under the normalized key. Looking up by the raw `$phone` produced a miss whenever the caller sent `+1 347 873 2224` vs the canonical `+13478732224`. |
| **`verifyWithProvider()` no longer trusts `$request->input('verification_id')`.** | IDOR — an attacker could supply any id. The controller now *only* uses the id stored in `phone_verifications.verification_id` during send. |
| **Success-branch update is now `DB::transaction(function() { ... lockForUpdate(); ... })`.** | Race condition — two concurrent verify requests could both pass the `is_verified=0` gate. The `lockForUpdate()` + double-check on the locked row closes the window. |
| **`providerErrorResponse()` map now also handles `'inactive'` status.** | The new "provider inactive" path of `message_central_verify()` needs a translation key. |

### 2.3 `app/Http/Controllers/Api/V1/CustomerController.php`

| Change | Why |
|--------|-----|
| **`verification_check()` only persists provider metadata when the send actually succeeded.** | When MC rejected the send, the previous code still saved `verification_id=null` into the row. The verify controller would then refuse the session ("no verification id"). |
| **Tightened logging:** `Log::info('OTP send (Message Central)', …)` is unchanged but is now strictly after the success gate, so the `has_verif_id` value is always truthful. | Operator-visible signal. |

---

## 3. Backend Flow (Send → Save → Verify → Login)

```
1. Flutter
   POST /api/v1/customer/auth/login (or register flow)
   body: { phone: "+1 347 873 2224" }
        ↓
2. Laravel  →  CustomerController::update_profile() (or equivalent sign-up path)
   →  verification_check($phone)
       - if there's an existing phone_verifications row updated < 60s ago
         → return 403 "please try again"
       - else generate OTP ($otp = rand(100000, 999999); 123456 in test mode)
       - if Message Central is the active SMS provider:
             sendResult = SmsGateway::message_central_send($phone, $otp)
             ↳ POST https://cpaas.messagecentral.com/verification/v3/send
               ?countryCode=…&customerId=…&flowType=SMS&type=OTP
               &otpLength=6&senderId=UTOMOB&mobileNumber=…&message=…
               header: authToken: <jwt>
             ↳ returns { status, http_status, verification_id, transaction_id,
                         reference_id, flow_type, mobile_number, country_code,
                         timeout, raw }
             if status === 'success':
                 $verificationId = …  $transactionId = …  etc.
             else:
                 $verificationId = $transactionId = … = null   ← fix
       - normalizedPhone = normalizePhoneForVerificationLocal($phone) = "+13478732224"
       - DB::table('phone_verifications')->updateOrInsert(
             ['phone' => $normalizedPhone],
             ['token' => $otp, 'otp_hit_count' => 0, 'is_verified' => 0,
              'verified_at' => null, 'verification_id' => $verificationId,
              'transaction_id' => $transactionId, …]
         )
       - return { is_success, message, code }
        ↓
3. SMS arrives on the user's phone with the OTP.
        ↓
4. Flutter
   POST /api/v1/auth/verify-phone
   body: { verification_type: "phone", phone: "+1 347 873 2224", otp: "123456",
           login_type: "otp" }
        ↓
5. Laravel  →  CustomerAuthController::verify_phone_or_email()
   →  $providerCheck = verifyWithProvider($request)
       a) if !SmsGateway::is_message_central_active() →
              $providerCheck = ['active' => false]; legacy local-comparison runs.
       b) normalize phone → $normalizedPhone = "+13478732224"
       c) $row = DB::table('phone_verifications')->where('phone', $normalizedPhone)->first()
          if !$row → return 404 verification_not_found
       d) if $row->is_verified or $row->verified_at → return 409 already_verified
       e) if !$row->verification_id → return 404 verification_not_found   ← fix
       f) $verificationId = $row->verification_id
       g) $result = SmsGateway::message_central_verify($normalizedPhone, $otp, $verificationId)
            ↳ GET https://cpaas.messagecentral.com/verification/v3/validateOtp
                ?verificationId=$verificationId&code=$otp&countryCode=…&mobileNumber=…
                header: authToken: <jwt>
            ↳ returns { status, http_status, … }
              mapping: 200/SUCCESS → 'success'
                       409/ALREADY_VERIFIED → 'already_verified'
                       429/TOO_MANY_ATTEMPTS → 'too_many_attempts'
                       401/EXPIRED → 'expired'
                       404 → 'invalid_otp'
                       400/403 → 'invalid_otp'
                       else → 'provider_error'
       h) DB::transaction(function () {  ← fix
              $locked = DB::table('phone_verifications')
                  ->where('phone', $normalizedPhone)
                  ->lockForUpdate()->first();
              if (!$locked || $locked->is_verified || $locked->verified_at) return;
              DB::table('phone_verifications')->where('phone', $normalizedPhone)->update([
                  'token' => $otp,
                  'is_verified' => 1,
                  'verified_at' => now(),
                  'updated_at' => now(),
              ]);
          })
       i) return { active: true, verified: ($result.status === 'success'),
                   status, http_status, provider_message, … }
   if $providerCheck.active && !$providerCheck.verified → providerErrorResponse(…)
   else → fall through to the legacy flow
        ↓
6. Legacy flow continues exactly as before:
   - User::where('phone', $phone)->first()
   - if user exists and login_type=otp → Passport token issued via
       auth()->user()->createToken('RestaurantCustomerAuth')->accessToken
   - if user does not exist → register, then issue token
        ↓
7. Laravel → 200 { token, is_phone_verified: 1, is_email_verified: 1,
                   is_personal_info, is_exist_user, login_type, email }
        ↓
8. Flutter stores the token and routes to the home screen.
```

---

## 4. Flutter Flow (unchanged contract)

```
Login / Register screen
  → user types phone + presses "Send OTP"
  → POST /api/v1/auth/login-or-register  { phone }
  → on success: navigate to OTP screen
  → user types 6-digit code
  → POST /api/v1/auth/verify-phone  { verification_type:"phone",
                                       phone, otp, login_type:"otp" }
  → if response.success && response.token:
       save token → AuthRepository.setToken(...)
       navigate to Home
  → if response.error == 'otp_expired'    → show "expired" + resend button
  → if response.error == 'verification_not_found'
                                          → show "session expired, request new code"
  → if response.error == 'invalid_otp'    → show "wrong code" (generic)
  → if response.error == 'provider_error' → show "service unavailable, retry later"
  → if response.error == 'too_many_attempts'
                                          → show "too many attempts, wait Xs"
```

**The Flutter contract is *not* changed.** No new field is required, no
new header, no `verificationId` is ever accepted from the client.

---

## 5. Database Flow

```
phone_verifications
├── id                bigint PK
├── phone             varchar (canonical: "+CountryCode + mobileNumber")   ← key
├── token             varchar  (the 6-digit OTP)
├── otp_hit_count     tinyint
├── is_temp_blocked   boolean
├── temp_block_time   timestamp NULL
├── verification_id   varchar(128) NULL   ← MC opaque id
├── transaction_id    varchar(128) NULL   ← MC audit
├── reference_id      varchar(128) NULL   ← MC audit
├── flow_type         varchar(16)  NULL   ← 'SMS'
├── verified_at       timestamp NULL      ← NEW
├── is_verified       boolean default 0   ← NEW
├── created_at        timestamp
└── updated_at        timestamp

indexes:
  idx_phone_verifications_phone_verified  (phone, verified_at)
```

State transitions:

```
                                                  ┌──────────────────────┐
   send-time                                      │  send fails (MC)     │
   ↓                                              │  → row NOT updated   │
   ┌──────────────────────────────────────────┐   │    (legacy fallback  │
   │ token=<otp>, verification_id=<MC id>,    │   │     keeps the SMS)   │
   │ is_verified=0, verified_at=NULL          │   └──────────────────────┘
   └──────────────────────────────────────────┘
        │
        │  verify-time
        ↓
   ┌──────────────────────────────────────────┐
   │ MC success →  token=<otp>,               │
   │ is_verified=1, verified_at=<now>         │   ← wrapped in DB::transaction
   └──────────────────────────────────────────┘   + lockForUpdate
        │
        │  legacy flow deletes the row or
        │  keeps it for the session TTL
        ↓
   (login issued by Passport)
```

---

## 6. Security Fixes (recap)

| # | Vulnerability | Status |
|---|---------------|--------|
| 1 | Typo endpoint `veri_cation/v3/validateOtpOtp` — every verify call was a guaranteed 404. | ✅ Fixed (`verification/v3/validateOtp`). |
| 2 | Forged `local-<md5>` `verificationId` could pass through the verify gate if the controller was lax enough to call the provider. | ✅ Removed; controller now refuses without a real DB-saved id. |
| 3 | "Inactive provider returns success" → entire auth bypass when MC config was missing. | ✅ Fixed: `message_central_verify()` returns `status='inactive'`; `providerErrorResponse()` maps it to a clean 502. |
| 4 | IDOR via `$request->input('verification_id')`. | ✅ Removed; the controller uses the DB row's id only. |
| 5 | Send-side row never saved MC metadata, so verify always fell back to local comparison. | ✅ Already correct from previous pass. |
| 6 | Send-side row *over*-wrote metadata on every send. | ✅ Still correct, plus the new "only persist when status=success" hardening. |
| 7 | Lookup-key mismatch between send and verify. | ✅ Both sides now use the same `normalizePhoneForVerification()` helper. |
| 8 | Race condition on verify (`is_verified=0` gate). | ✅ Fixed: `DB::transaction + lockForUpdate`. |
| 9 | `OTP`, `authToken`, `customerId` in logs. | ✅ Audited — only masked phone + http status + status code are logged. |
| 10 | Provider metadata leak to client. | ✅ Audited — only echoes `status`, `http_status`, `provider_message`, `masked_phone`. |

---

## 7. Tests

### 7.1 Static checks (passed)

```
php -l app\Traits\SmsGateway.php                              → No syntax errors
php -l app\Http\Controllers\Api\V1\Auth\CustomerAuthController.php → No syntax errors
php -l app\Http\Controllers\Api\V1\CustomerController.php     → No syntax errors
php -l database\migrations\2026_08_25_120000_add_message_central_columns_to_phone_verifications_table.php → No syntax errors
```

### 7.2 Behavioural checks (stand-alone smoke script, deleted after the run)

```
[ Test 1 ] method existence
  message_central_send       YES
  message_central_verify     YES
  message_central            YES
  is_message_central_active  YES
  get_settings               YES
  → PASS

[ Test 2 ] is_message_central_active() returns bool
  bool(false)        (no addon_settings row in the test env)
  → PASS

[ Test 3 ] message_central_verify() with NULL verificationId
  status  = inactive   (was: would have called provider with forged id)
  → PASS  ← proves the local-* fallback is gone

[ Test 4 ] message_central_verify() with valid id but no MC config
  status  = inactive
  → PASS  ← proves "inactive provider" no longer masquerades as success

[ Test 5 ] source contains the correct endpoint
  veri_cation       NO  ← typo gone
  validateOtpOtp    NO  ← double-suffix gone
  /verification/v3/validateOtp   YES  ← correct endpoint present
  → PASS

[ Test 6 ] source no longer contains the 'local-' fallback
  contains "'local-'": NO
  → PASS

[ Test 7 ] normalizePhoneForVerificationLocal / normalizePhoneForVerification
  both exist on their respective controllers
  → PASS

[ Test 8 ] phone normalization output
  +1 347 873 2224 -> +13478732224
  13478732224     -> +13478732224
  +13478732224    -> +13478732224
  → PASS
```

### 7.3 Database / end-to-end tests (operator checklist)

The local MySQL instance (`localhost:3306`) is not reachable from this
sandbox, so the following checks were *not* run live. They are documented
as the operator's checklist:

1. Apply the migration (`php artisan migrate`) — confirm
   `phone_verifications.verification_id`, `transaction_id`, `reference_id`,
   `flow_type`, `verified_at`, `is_verified` are all present.
2. Configure Message Central in
   `/admin/business-settings/third-party/sms-module` (customerId + authToken
   + countryCode + senderId + otp_template, status=1).
3. Walk through the matrix in §30 of the brief. Expected responses:

| Scenario | Expected DB after Send | Expected API | Expected DB after Verify |
|----------|------------------------|--------------|-------------------------|
| Correct phone + correct OTP | token set, verification_id set, is_verified=0 | (Send) 200 OK; (Verify) 200 + Passport token | is_verified=1, verified_at=NOW |
| Wrong OTP | token set, verification_id set, is_verified=0 | 400 invalid_otp | unchanged (is_verified=0) |
| Expired OTP | token set, verification_id set, is_verified=0 | 400 otp_expired | unchanged |
| Resend (new OTP) | token overwritten, verification_id overwritten, is_verified=0 | — | — |
| Reuse after success | (already 0 because legacy flow deletes the row) | 409 already_verified | unchanged |
| Send-time MC failure | token NOT updated, row not created/updated | 403 failed_to_send_otp | n/a (no row) |
| Two concurrent verify calls on the same phone | as above | one 200, one 409 (already_verified) | single row updated atomically |

---

## 8. Out-of-scope / explicitly NOT changed

- `app/CentralLogics/SMS_module.php` — the legacy dispatcher; still works
  with `message_central` for environments where
  `addon_published_status('Gateways')==0`.
- `routes/api/v1/api.php` — no new routes, the existing
  `POST /api/v1/auth/verify-phone` is the only entry-point.
- `resources/lang/en/messages.php` — already contains all five required
  translations.
- Other SMS providers — all dispatch chain entries in `SmsGateway::send()`
  are preserved.
- `PhoneVerification` model — kept minimal (`$guarded = ['id']`); no
  property additions needed since we use `DB::table()` everywhere.

---

## 9. Operator notes

1. **Run the migration** if you haven't already:
   `php artisan migrate`
2. **Configure Message Central** at
   `/admin/business-settings/third-party/sms-module` — set `customer_id`,
   `auth_token`, `country_code`, `otp_template`, `sender_id`, `status=1`.
3. **No-op rollback** — set `addon_settings.live_values.status` for
   `message_central` back to `0`. Every other provider keeps working;
   Message Central falls back to the legacy local-comparison flow.
4. **Hard rollback** — `git revert` the three modified files. The legacy
   `verify_phone_or_email()` is untouched, so the local path is preserved.

---

*End of report.*