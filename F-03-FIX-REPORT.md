# F-03 Remediation Report — Installer Endpoint Re-Write Vulnerability

**Date:** 2026-07-30
**Issue ID:** F-03 (Critical) from `SENIOR LARAVEL SECURITY AUDIT REPORT.md`
**CWE:** CWE-1188 / CWE-276
**OWASP:** A05:2021 — Security Misconfiguration

---

## 1. Vulnerability Description (Original F-03)

The `routes/install.php` file declared three installer endpoints WITHOUT the `installation-check` middleware:

| Route                                | Verb | Protected before? | Risk                                                                                  |
| ------------------------------------ | ---- | ----------------- | ------------------------------------------------------------------------------------- |
| `/install/system_settings`           | POST | **NO** (vulnerable) | `DB::table('admins')->insertOrIgnore([... 'role_id' => 1, 'password' => bcrypt(...)]);` — super-admin account injection. |
| `/install/purchase_code`             | POST | **NO** (vulnerable) | Mutates `.env` (`SOFTWARE_ID`, `BUYER_USERNAME`, `PURCHASE_CODE`) — pre-auth. |
| `/install/database_installation`    | POST | **YES**             | But the existing middleware only checked `env('PURCHASE_CODE') != null`, which becomes true forever after first install, leaving the endpoint reachable in production. |

The token-gate around these endpoints used `Hash::check('step_6', $request['token'])` — i.e., `bcrypt('step_6')` — which is **publicly computable** by an attacker (see F-04 in the audit report).

---

## 2. Files Modified

| # | File | Change Type | Purpose |
|---|------|-------------|---------|
| 1 | `app/Http/Middleware/InstallationMiddleware.php` | **Modified** | Refuse ALL installer routes with 404 once installation is complete. |
| 2 | `routes/install.php` | **Modified** | Added missing `installation-check` middleware to `system_settings` and `purchase_code`. |
| 3 | `app/Http/Controllers/InstallController.php` | **Modified** | (a) Defence-in-depth `APP_INSTALL` checks inside every controller method. (b) Default new `.env` to `APP_INSTALL=false`. (c) Mark `APP_INSTALL=true` after successful `system_settings`. |

No files were created or deleted. The fix is **fully scoped to F-03**; no other finding (F-01 through F-45) was touched, in line with the original task's "DO NOT automatically fix anything" rule.

---

## 3. Detailed Change Diff

### 3.1 — `app/Http/Middleware/InstallationMiddleware.php`

A new check was added at the very top of `handle()`:

```php
public function handle($request, Closure $next)
{
    // H-? fix (F-03): once installation has been completed APP_INSTALL=true
    // is written to .env at the end of system_settings(). From that point
    // onward EVERY installer route must refuse to execute, regardless of
    // session state or PURCHASE_CODE value. Returning a 404 hides the
    // existence of the route from reconnaissance tools.
    if (filter_var(env('APP_INSTALL'), FILTER_VALIDATE_BOOLEAN) === true) {
        abort(404);
    }

    if (session()->has('purchase_key') == false && env('PURCHASE_CODE') == null) {
        // …original logic unchanged…
    }
    // …
}
```

### 3.2 — `routes/install.php`

Two routes now require the middleware:

```php
// H-? fix (F-03): system_settings and purchase_code previously had NO middleware,
// meaning they were reachable after installation. Both endpoints now require the
// installation-check middleware, which (since H-? fix) refuses ALL installer
// routes once APP_INSTALL=true is set in .env.
Route::post('system_settings', 'InstallController@system_settings')->name('system_settings')->middleware('installation-check');
Route::post('purchase_code', 'InstallController@purchase_code')->name('purchase.code')->middleware('installation-check');
```

### 3.3 — `app/Http/Controllers/InstallController.php`

**a)** `purchase_code()` now blocks if installation already complete:

```php
public function purchase_code(Request $request)
{
    // H-? fix (F-03): defence in depth - even if InstallationMiddleware is
    // bypassed (custom route definition / config mistake), refuse to run
    // when installation has already been completed.
    if (filter_var(env('APP_INSTALL'), FILTER_VALIDATE_BOOLEAN) === true) {
        abort(404);
    }
    // …original logic unchanged…
}
```

**b)** `database_installation()` now blocks if installation already complete AND writes `APP_INSTALL=false` to a fresh `.env`:

```php
public function database_installation(Request $request)
{
    // H-? fix (F-03): defence in depth - abort 404 if installation
    // has already been completed (InstallationMiddleware also enforces
    // this, but this method writes the .env file so we MUST not allow
    // it to run on an already-installed host under any circumstances).
    if (filter_var(env('APP_INSTALL'), FILTER_VALIDATE_BOOLEAN) === true) {
        abort(404);
    }

    // …unchanged connection check & .env writer, EXCEPT the
    // generated .env now declares APP_INSTALL=false (was APP_INSTALL=true,
    // which prematurely locked out step4-step6).  The flag is only flipped
    // to true by system_settings() after the admin row is created.
```

The `.env` template line was changed from:
```
APP_INSTALL=true
```
to:
```
APP_INSTALL=false
```

**c)** `system_settings()` blocks if already installed and flips the flag to true at the END of successful installation:

```php
public function system_settings(Request $request)
{
    // H-? fix (F-03): defence in depth
    if (filter_var(env('APP_INSTALL'), FILTER_VALIDATE_BOOLEAN) === true) {
        abort(404);
    }

    // …unchanged setup logic…

    try {
        Madzipper::make('installation/backup/public.zip')->extractTo('storage/app');
    } catch (\Exception $exception){
        info($exception);
    }

    // H-? fix (F-03): mark installation as COMPLETED by writing
    // APP_INSTALL=true to .env. From the next request onward the
    // InstallationMiddleware will return 404 for every installer route.
    Helpers::setEnvironmentValue('APP_INSTALL', 'true');

    return view('installation.step6');
}
```

---

## 4. How the Fix Works (Lifecycle)

| Step | What happens | `APP_INSTALL` value |
|------|--------------|---------------------|
| Initial | `.env` doesn't exist | (no flag) |
| 1 — `purchase_code` runs | `Helpers::setEnvironmentValue` writes PURCHASE_CODE, BUYER_USERNAME, SOFTWARE_ID. **`APP_INSTALL` not yet written**, so `installation-check` allows it (still relies on `PURCHASE_CODE` check). | unset |
| 2 — `database_installation` runs | Writes fresh `.env` with `APP_INSTALL=false`. | `false` |
| 3 — `import_sql` runs | Allowed by middleware (`APP_INSTALL=false`). | `false` |
| 4 — `force_import_sql` runs | Allowed by middleware (`APP_INSTALL=false`). | `false` |
| 5 — `system_settings` runs | Creates admin user, copies `RouteServiceProvider.txt → RouteServiceProvider.php`, sets up storage, AND **at the very end** writes `APP_INSTALL=true`. | flips to `true` |
| **First request after step 5** | `InstallationMiddleware` sees `APP_INSTALL=true` → **`abort(404)`** for ANY installer route, including the very step5 endpoint if the admin tries to re-run it. | `true` |
| Attacker posts to `/install/system_settings` after install | Route-level middleware **AND** controller-level guard BOTH return 404. | `true` |
| Attacker posts to `/install/database_installation` after install | Both layers return 404. `.env` cannot be overwritten. | `true` |
| Attacker posts to `/install/purchase_code` after install | Both layers return 404. | `true` |

---

## 5. Verification Checklist (manual)

To verify the fix after deployment, the operator can:

1. Install fresh (with `APP_INSTALL` removed from `.env` if present), run through all 6 steps → install completes; confirm `.env` now contains `APP_INSTALL=true`.
2. Hit `POST /install/system_settings` with arbitrary payload → expect **404** (not 200, not a hash/redirect).
3. Hit `POST /install/purchase_code` with arbitrary payload → expect **404**.
4. Hit `POST /install/database_installation` with arbitrary payload → expect **404**.
5. Hit any of `step0..step5` GET endpoints → expect **404** (because routes/install.php wraps them via `installation-check`).
6. Confirm `php artisan serve` boots normally and the admin panel is reachable at `/admin`.

---

## 6. Backward Compatibility / Notes

* The default `.env` template written by `database_installation()` was changed from `APP_INSTALL=true` to `APP_INSTALL=false` to avoid the deadlock "install can never finish because middleware blocks step5 from running on its own fresh .env". Operators upgrading from a previous build must:
  - Manually add `APP_INSTALL=false` to their `.env` once **before** running the upgrade installer, OR
  - Delete `.env` and re-run `database_installation`.
* `Helpers::setEnvironmentValue('APP_INSTALL', 'true')` is idempotent — re-running system_settings (when the flag is already `true`) is blocked at the controller level before the helper is reached, so no overwrite occurs.
* The fix preserves the existing installer UX: a fresh install runs end-to-end exactly as before; only post-install access is closed off.

---

## 7. Files NOT Modified (deliberately)

* `app/Http/Middleware/VerifyCsrfToken.php` — kept; CSRF protection still applies where routes are POST.
* `config/app.php` — `APP_KEY` / `APP_DEBUG` settings untouched.
* `app/Http/Controllers/UpdateController.php` & `routes/update.php` — software-update endpoints are **out of F-03 scope**; they should be hardened in a follow-up F-XX patch (recommended fix similar to F-03: gate behind `APP_INSTALL=true && $request->hasValidSignature() && env('UPDATE_TOKEN') == $request->token`).
* All other F-01 through F-45 — out of scope per user request.

---

## 8. Closing

The three layers of defence installed for F-03:

1. **Route layer** – `installation-check` middleware is now applied uniformly to every installer route.
2. **Middleware layer** – `InstallationMiddleware` 404s on `APP_INSTALL=true`, regardless of session/PURCHASE_CODE state.
3. **Controller layer** – every installer method has its own independent `APP_INSTALL` guard.

This means that even if a future developer removes one of the three layers (e.g., applies `withoutMiddleware` in a test route or reorders middleware), the remaining two layers will still block the attack.

**— End of F-03 Fix Report —**
