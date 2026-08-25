# Message Central SMS Integration Report

**Project:** PickAdmin / Pickles & Pies
**Date:** 2026-08-25
**Author:** Cline / AI Coding Agent (acting as Senior Laravel Engineer)
**Scope:** Add Message Central as a new SMS gateway, alongside Twilio, Nexmo, 2factor, Msg91, Alphanet, etc.

---

## 1. Executive Summary

Message Central has been integrated as an additional SMS provider in PickAdmin using the existing SMS-gateway infrastructure. **No new database tables, no new routes, no new Blade view, no architectural changes** were introduced. PickAdmin still owns the OTP value (it is generated locally and embedded into the message body via the existing `#OTP#` substitution) — the integration does **not** use Verify Now managed-OTP.

The new provider is registered as `message_central` and is reachable via the existing admin page `admin/business-settings/third-party/sms-module` exactly like the other providers.

All required fields are configurable from the admin UI and stored in the existing `addon_settings` table under `settings_type = sms_config`. The provider can be enabled/disabled with the existing toggle, and activating any one SMS provider automatically deactivates the others (Twilio, Nexmo, 2factor, Msg91, Alphanet, **Message Central**) per the established business rule.

---

## 2. Current SMS Architecture

| Layer | File | Notes |
|---|---|---|
| Provider dispatch (primary) | `app/Traits/SmsGateway.php` | Chain: twilio, nexmo, 2factor, msg91, releans, hubtel, paradox, signal_wire, 019_sms, viatech, global_sms, akandit_sms, sms_to, alphanet_sms. Used when `addon_published_status('Gateways') == 1`. |
| Provider dispatch (legacy) | `app/CentralLogics/SMS_module.php` | Subset of providers. Used when the Gateways addon is disabled. Auto-loaded via `composer.json`. |
| Caller | `app/CentralLogics/Helpers.php` (lines 5080–5092) | Branches between the addon trait and the legacy class. |
| Auth-flow callers | `LoginController.php`, `Api/V1/CustomerController.php`, `Api/V1/Auth/CustomerAuthController.php`, `Api/V1/Auth/DMPaAuthController.php`, `Api/V1/Auth/PassportAuthController.php`, `Api/V1/Auth/SocialAuthController.php` | All call `Modules\Gateways\Traits\SmsGateway::send()` when the addon is enabled, fall back to `SMS_module::send()` otherwise. |
| Admin controller | `app/Http/Controllers/Admin/SMSModuleController.php` | `sms_index()` lists cards; `sms_update()` saves each provider; ensures single-active-provider rule. |
| Admin view | `resources/views/admin-views/business-settings/sms-index.blade.php` | Renders one card per `key_name`, one text input per `live_values` key, using `translate($key)` for labels. |
| Routes | `routes/admin.php` lines 510–512 | `GET .../sms-module`, `POST .../sms-module-update/{sms_module}`. |
| Storage | Table `addon_settings` (`app/Models/Setting.php`) | Columns: `key_name`, `live_values` (JSON cast), `test_values`, `settings_type`, `mode`, `is_active`, `additional_data`. |
| Reader helper | `app/helpers.php` line 298 — `config_settings($key, $settings_type)` | Generic SELECT on `addon_settings`. |
| Provider reader | `SmsGateway::get_settings()` line 615 | Decodes `live_values` from `config_settings('sms_config')`. |
| Lang | `resources/lang/en/messages.php` and `resources/lang/ar/messages.php` | Provides labels for every key rendered in the form. |

---

## 3. Files Inspected (Phase 1 Audit)

- `.cline_pickadmin_context.md` — project knowledge base
- `app/Traits/SmsGateway.php` — primary dispatch + all provider methods
- `app/CentralLogics/SMS_module.php` — legacy dispatch
- `app/CentralLogics/Helpers.php` — SMS dispatcher caller (line 5080+)
- `app/Http/Controllers/Admin/SMSModuleController.php` — admin save/list logic
- `app/Http/Controllers/LoginController.php` — auth-flow caller
- `app/Http/Controllers/Api/V1/CustomerController.php` — API caller
- `app/Http/Controllers/Api/V1/Auth/CustomerAuthController.php` — API caller
- `app/Http/Controllers/Api/V1/Auth/DMPaAuthController.php` — API caller
- `app/Http/Controllers/Api/V1/Auth/PassportAuthController.php` — API caller
- `app/Http/Controllers/Api/V1/Auth/SocialAuthController.php` — API caller
- `app/Models/Setting.php` — storage model
- `app/Models/BusinessSetting.php` — different `business_settings` table (not used by SMS)
- `app/helpers.php` — `config_settings()` helper
- `resources/views/admin-views/business-settings/sms-index.blade.php` — admin SMS page
- `routes/admin.php` — SMS routes
- `resources/lang/en/messages.php` — English labels
- `resources/lang/ar/messages.php` — Arabic labels
- `composer.json` — confirms `SMS_module.php` is auto-loaded
- `database/seeders/*` — confirmed: no SMS seeder exists
- `database/migrations/*` — confirmed: no migration needed
- `tests/*` — confirmed: no SMS tests exist (consistent with existing providers)

---

## 4. Files Modified

| # | File | Change |
|---|---|---|
| 1 | `app/Traits/SmsGateway.php` | (a) Added `message_central` entry to the dispatch chain; (b) added `message_central($receiver, $otp)` method. |
| 2 | `app/CentralLogics/SMS_module.php` | (a) Added `message_central` to the dispatch chain; (b) added the same provider method (legacy fallback). |
| 3 | `app/Http/Controllers/Admin/SMSModuleController.php` | Added `message_central` to the whitelist in `sms_index()`, the save branch in `sms_update()` (with validation), and the activation loop. |
| 4 | `resources/lang/en/messages.php` | Added labels: `message_central`, `message_central_sms`, `customer_id`, `auth_token`, `country_code`, `message_type`, `entity_id`. (`sender_id`, `template_id`, `otp_template` already existed.) |
| 5 | `resources/lang/ar/messages.php` | Added the same Arabic labels. |

---

## 5. Files Added

- `MESSAGE_CENTRAL_SMS_INTEGRATION_REPORT.md` (this report)
- Four temporary PowerShell audit helpers were created (`audit_helper*.ps1`) and have been left on disk for transparency; they are not part of the runtime codebase.

---

## 6. Files Intentionally Not Modified

| File | Reason |
|---|---|
| `routes/admin.php` | Existing route is generic; the new `key_name` works without changes. |
| `app/Http/Controllers/Admin/BusinessSettingsController.php` | Not involved in SMS gateway dispatch. |
| `app/CentralLogics/Helpers.php` | Caller side unchanged — branches transparently. |
| `app/Models/Setting.php`, `app/Models/BusinessSetting.php` | Schema already supports the storage pattern. |
| `database/migrations/*` | No new columns needed. |
| `database/seeders/*` | No SMS seeding exists. Providers appear in the admin UI only after first save. |
| `app/helpers.php` | The `config_settings()` helper already supports the new `key_name`. |
| All other providers' methods in `SmsGateway.php` and `SMS_module.php` | Preserved verbatim — no behavior change for Twilio, Nexmo, etc. |
| `Modules/Gateways/*` | Empty in this repository (addon not bundled); the same provider method exists in the trait for both code paths. |
| `resources/views/admin-views/business-settings/sms-index.blade.php` | The existing loop auto-renders any new `live_values` keys — no view change required. |

---

## 7. Database Changes

**None.** The `addon_settings` table already supports any `key_name` with a JSON `live_values` column. A new row is created on first save via `SMSModuleController::sms_update()` using `DB::table('addon_settings')->updateOrInsert([...])`.

---

## 8. Settings JSON Structure (live_values)

```json
{
  "gateway": "message_central",
  "mode": "live",
  "status": 0,
  "customer_id": "...",
  "auth_token": "...",
  "country_code": "91",
  "sender_id": "PickAdmin",
  "otp_template": "Your verification code is #OTP#",
  "message_type": "OTP",
  "template_id": "",
  "entity_id": ""
}
```

---

## 9. Message Central API Endpoints (used)

| Purpose | Method | URL |
|---|---|---|
| Send OTP SMS | `POST` | `https://cpaas.messagecentral.com/verification/v3/send` |

Token generation (`GET /auth/v1/authentication/token`) is **not** required — the admin can paste a long-lived `authToken` from the Message Central dashboard directly. This avoids the complexity of automatic token refresh in a transactional SMS flow.

---

## 10. Request Parameters

| Field | Source | Required | Notes |
|---|---|---|---|
| `customerId` | config | yes | From Message Central dashboard |
| `countryCode` | config | yes | Stripped of leading `+` |
| `mobileNumber` | runtime | yes | `+` and non-digits stripped from `$receiver` |
| `flowType` | constant | yes | Hard-coded `SMS` |
| `senderId` | config | yes | From Message Central |
| `message` | derived | yes | `otp_template` with `#OTP#` substituted by PickAdmin |
| `messageType` | config | no | Only included in payload when non-empty |
| `templateId` | config | no | Only included in payload when non-empty |
| `entityId` | config | no | Only included in payload when non-empty |

Header:
- `authToken: <auth_token>`
- `Content-Type: application/json`
- `Accept: application/json`

---

## 11. Authentication / Token Flow

Manual Token Mode only:
1. Admin retrieves an `authToken` from the Message Central dashboard.
2. Admin pastes it into the Message Central card in `admin/business-settings/third-party/sms-module`.
3. The provider sends the `authToken` header on every request.

This is consistent with how every other provider in the system is configured (Twilio, Nexmo, Msg91, etc.).

---

## 12. OTP Flow

```
1. PickAdmin generates an OTP (existing behavior; e.g. random_int(100000, 999999)).
2. PickAdmin calls SmsGateway::send($phone, $otp) — or SMS_module::send() if the Gateways addon is off.
3. The chain reaches message_central().
4. The method substitutes #OTP# in otp_template → message body contains the literal OTP value.
5. POST to Message Central with that exact message body.
6. If response is HTTP 200 AND responseCode == 200 → "success", else "error".
```

This is identical to the existing Twilio/Nexmo flow. **No managed-OTP (Verify Now) flow is used.**

---

## 13. UI Changes

No structural change. The Message Central card is generated automatically by the existing loop in `sms-index.blade.php`. The label "Message Central" comes from the new `messages.php` entry. Each `live_values` field (customer_id, auth_token, country_code, sender_id, otp_template, message_type, template_id, entity_id) renders as a labeled text input with the new translations.

Demo masking (`env('APP_ENV')=='demo'?'':$value`) is preserved automatically.

---

## 14. Error Handling

| HTTP status | Body | Action |
|---|---|---|
| 200 | `responseCode == 200` | `'success'` |
| 200 | `responseCode != 200` (e.g. 401/403/400/429) | `'error'`; logs sanitized errorMessage/message |
| 401/403/400/429 (or any non-200) | any | `'error'` |
| 5xx | any | `'error'` |
| Timeout / network | — | `'error'` (curl returns false) |
| Empty body | — | `'error'` (decoded is null) |
| Missing required config | — | Early return `'error'` (no API call) |

Credentials (`auth_token`, `customer_id`) are never written to logs.

---

## 15. Security Review

| Concern | Status |
|---|---|
| HTTPS only | ✅ |
| SSL verification enabled | ✅ (`CURLOPT_SSL_VERIFYPEER = true`, `CURLOPT_SSL_VERIFYHOST = 2`) |
| Timeout bounded (30s) | ✅ |
| HTTP 200 alone is not success | ✅ |
| Server-side validation in controller | ✅ (`required`, length caps) |
| Credentials not logged | ✅ |
| No `dd()`/`dump()`/`var_dump()` | ✅ |
| No hardcoded credentials | ✅ |
| Demo mode masking preserved | ✅ |
| No bypass of OTP generation | ✅ (PickAdmin still owns OTP) |

---

## 16. Tests and Results

| Check | Result |
|---|---|
| `php -l app/Traits/SmsGateway.php` | ✅ No syntax errors |
| `php -l app/CentralLogics/SMS_module.php` | ✅ No syntax errors |
| `php -l app/Http/Controllers/Admin/SMSModuleController.php` | ✅ No syntax errors |
| `php -l resources/lang/en/messages.php` | ✅ No syntax errors |
| `php -l resources/lang/ar/messages.php` | ✅ No syntax errors |
| Laravel Pint | Files pre-existed with the same style patterns. My additions conform to the surrounding code style. |
| Existing Twilio/Nexmo regression | ✅ Untouched — verified by reading the methods before/after the edit. |

Automated PHPUnit coverage for SMS providers was **not** added because the existing 13 other providers have **no tests** either. Adding a test infrastructure (database fixture, Http::fake harness) is out of scope for this PR and would be a follow-up.

---

## 17. Backward Compatibility

- No breaking changes to any existing provider method.
- No new dependency added.
- No new table or column.
- No new route.
- All callers (`Helpers::send_sms`, login controllers, API auth controllers) continue to work unchanged.
- The only schema impact is **additive**: a new row in `addon_settings` with `key_name='message_central'` is created on first save.

---

## 18. Deployment Instructions

1. Pull the branch.
2. Run `composer dump-autoload` (no changes required, but recommended).
3. (Optional) Clear config cache: `php artisan config:clear`.
4. Open `admin/business-settings/third-party/sms-module` in the admin panel.
5. Fill in the **Message Central** card:
 - Customer ID
 - Auth Token (long-lived token from the Message Central dashboard)
 - Country Code (e.g. `91`)
 - Sender ID
 - OTP Template (must contain `#OTP#`)
 - Message Type (e.g. `OTP`)
 - Template ID, Entity ID (only if your account requires them)
6. Choose **Active** and click **Update**.
7. The card will appear alongside Twilio, Nexmo, etc. Only one provider can be active at a time.

No env variables, no seeder, no migration. The `addon_settings` row is created on save.

---

## 19. Rollback Plan

The change is additive. To roll back:

1. Remove the `message_central` row from `addon_settings`:
 ```sql
 DELETE FROM addon_settings WHERE key_name='message_central' AND settings_type='sms_config';
 ```
2. `git revert` the commit (or the five file edits) — no other cleanup needed.

Because no other provider method was modified, reverting restores Twilio/Nexmo/etc. behavior to its prior state with zero side effects.

---

## 20. Exact Diff Summary

| File | Lines added |
|---|---|
| `app/Traits/SmsGateway.php` | ~110 lines (provider method + dispatch entry) |
| `app/CentralLogics/SMS_module.php` | ~90 lines (provider method + dispatch entry) |
| `app/Http/Controllers/Admin/SMSModuleController.php` | ~28 lines (whitelist + branch + activation loop) |
| `resources/lang/en/messages.php` | 7 new label entries |
| `resources/lang/ar/messages.php` | 7 new label entries |
| `MESSAGE_CENTRAL_SMS_INTEGRATION_REPORT.md` | this file |

Total: **~240 lines added across 6 files; 0 lines deleted.**

---

## 21. Known Limitations

1. **OTP in transit.** The OTP is included in the HTTP body that travels over HTTPS to Message Central. This is the same limitation every other provider in the system has and is industry standard for transactional SMS. The OTP is still generated by PickAdmin and never delegated to a third-party managed-OTP service.
2. **No delivery callbacks.** Per the brief, no callback URL was added. If delivery tracking is required in the future, a new endpoint on Message Central (`/verification/v3/delivery-report` or webhook) plus a new route in `routes/api.php` would be the extension point.
3. **No automatic token refresh.** The `authToken` is used as-is. If Message Central rotates/expires tokens in your account, you must update it in the admin UI. This matches the simplicity of every other provider in the project.
4. **Demo masking.** When `APP_ENV=demo`, fields render empty — already handled by the existing view.
5. **Pre-existing Pint style rules.** The repository uses its own `.styleci.yml` config; the bundled Laravel Pint would flag pre-existing patterns (`array()`, double spaces, etc.) that exist throughout the entire `SmsGateway.php` file. The new code matches the surrounding style.

---

*End of report.*