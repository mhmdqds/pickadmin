<?php
/**
 * Static security regression tests for the PayPal integration.
 *
 * TEXT-LEVEL tests: they don't boot Laravel, they don't hit PayPal.
 * They grep the controller and the migration and assert that:
 *   - The fix-related identifiers appear in the source.
 *   - The dangerous patterns the audit flagged are gone.
 *   - The migration adds the right columns with the right indexes.
 *
 * Run with:  php tests/PaypalSecurityStaticTest.php
 */
$root = __DIR__ . '/..';
$controllerPath = $root . '/app/Http/Controllers/PaypalPaymentController.php';
$processorPath  = $root . '/app/Traits/Processor.php';
$helpersPath    = $root . '/app/helpers.php';
$routesPath     = $root . '/routes/web.php';
$migrationPath  = $root . '/database/migrations/2026_08_15_000001_add_paypal_security_columns_to_payment_requests.php';

$results = [];
function chk(string $file, string $needle, string $label, bool $mustExist): void {
    global $results;
    $content = @file_get_contents($file);
    if ($content === false) { $results[] = ['FAIL', $label, "file not readable: $file"]; return; }
    $found = strpos($content, $needle) !== false;
    $ok = $mustExist ? $found : !$found;
    $results[] = [$ok ? 'PASS' : 'FAIL', $label, $ok
        ? ($mustExist ? "found in $file" : "absent from $file")
        : ($mustExist ? "missing '$needle' in $file" : "forbidden '$needle' still present in $file")];
}

chk($controllerPath, 'paypal_order_id', 'F-01 PayPal order id persisted (FIX-02)', true);
chk($controllerPath, 'hash_equals((string)$r->paypal_order_id, (string)$paypalOrderId', 'F-01 stored paypal_order_id compared with token (FIX-01)', true);
chk($controllerPath, 'captured_amount', 'F-03 captured amount extracted', true);
chk($controllerPath, 'capturedCurrency', 'F-03 captured currency extracted', true);
chk($controllerPath, '$capturedAmount !== $expectedAmount || $capturedCurrency !== $expectedCurrency', 'F-03 amount/currency inequality rejected', true);
chk($controllerPath, '$response->payment_amount * 100', 'F-04 hardcoded USD multiplier gone', false);
chk($controllerPath, "number_format((float)\$data->payment_amount, 2, '.', '')", 'F-04 decimal-safe amount formatting', true);
chk($controllerPath, "'currency_code' => 'USD',", 'F-05 hardcoded USD currency gone', false);
chk($controllerPath, 'isPayPalSupportedCurrency', 'F-05 currency allow-list present', true);
chk($controllerPath, 'lockForUpdate()', 'F-06 lockForUpdate() used', true);
chk($controllerPath, "where('is_paid', 0)", 'F-06 is_paid=0 guard', true);
chk($controllerPath, 'DB::transaction(function ()', 'F-06 DB::transaction wrapper', true);
chk($controllerPath, 'paypal_capture_id', 'F-08 paypal_capture_id persisted', true);
chk($migrationPath, 'paypal_order_id', 'F-09 migration adds paypal_order_id', true);
chk($migrationPath, 'paypal_capture_id', 'F-09 migration adds paypal_capture_id', true);
chk($migrationPath, 'captured_amount', 'F-09 migration adds captured_amount', true);
chk($migrationPath, 'captured_currency', 'F-09 migration adds captured_currency', true);
chk($migrationPath, 'paypal_webhook_event_id', 'F-09 migration adds paypal_webhook_event_id', true);
chk($migrationPath, 'uniq_payment_requests_paypal_order_id', 'F-10 unique on paypal_order_id', true);
chk($migrationPath, 'uniq_payment_requests_paypal_capture_id', 'F-10 unique on paypal_capture_id', true);
chk($migrationPath, 'uniq_payment_requests_paypal_webhook_event_id', 'F-10 unique on paypal_webhook_event_id', true);
chk($routesPath, "->middleware('throttle:60,1')", 'F-12 throttle on success', true);
chk($routesPath, "->middleware('throttle:120,1')", 'F-12 throttle on webhook', true);
chk($helpersPath, 'allowedSourceStates', 'F-13 order_place source-state guard', true);
chk($helpersPath, "['pending', 'failed', 'unpaid']", 'F-13 allowed source states explicit', true);
chk($controllerPath, 'call_user_func($data->success_hook', 'F-14 raw call_user_func on hook column gone (PayPal)', false);
chk($controllerPath, 'runWhitelistHookIfPresent', 'F-14 whitelist hook invoker in place', true);
chk($controllerPath, 'ALLOWED_HOOKS', 'F-14 ALLOWED_HOOKS whitelist declared', true);
chk($processorPath, 'isSafeExternalRedirect', 'F-15 external redirect host validator in place', true);
chk($controllerPath, 'public function webhook(Request $request)', 'F-16 webhook() method present', true);
chk($controllerPath, 'verify-webhook-signature', 'F-16 webhook signature verification', true);
chk($controllerPath, 'paypal_webhook_event_id', 'F-17 webhook event-id dedup', true);
chk($controllerPath, "Log::error('PayPal create-order returned malformed body', ['body' =>", 'F-22 response body not logged', false);

$pass = 0; $fail = 0;
foreach ($results as $r) {
    if ($r[0] === 'PASS') $pass++; else $fail++;
    echo $r[0] . "  " . str_pad($r[1], 65) . "  " . $r[2] . "\n";
}
echo "\n";
echo "Total: " . ($pass + $fail) . "  PASS: $pass  FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
