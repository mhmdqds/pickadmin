<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use App\Library\Payer;
use App\Traits\Payment;
use App\Library\Receiver;
use App\Library\Payment as PaymentInfo;

class PaymentController extends Controller
{
    public function __construct()
    {
        // No-op. The historical `usePaymentGatewayTrait()` scaffolding
        // (and its `eval`-era `generateExtendedControllerClass()`
        // partner) was removed because:
        //   1. `usePaymentGatewayTrait()` was never defined in the
        //      class or in `App\Traits\Payment`, so any environment
        //      where the `is_dir('App\Traits') && trait_exists(...)`
        //      guard evaluated true (e.g. Windows + testing)
        //      raised `BadMethodCallException` at construct time and
        //      every route in this controller — including
        //      `payment-success` — returned HTTP 500.
        //   2. The functionality the scaffold tried to provide
        //      (compose the controller with `App\Traits\Payment`) is
        //      no longer needed by the current routes: every callsite
        //      of the trait's `generate_link()` goes through the
        //      explicit `App\Traits\Payment::generate_link()` call in
        //      `PaymentController::payment()`.
        //   3. Removing the dead code restores the documented behavior
        //      without changing any of the public method signatures.
    }

    public function payment(Request $request)
    {
        if ($request->has('callback')) {
            Order::where(['id' => $request->order_id])->update(['callback' => $request['callback']]);
        }

        session()->put('customer_id', $request['customer_id']);
        session()->put('payment_platform', $request['payment_platform']);
        session()->put('order_id', $request->order_id);

        $order = Order::where(['id' => $request->order_id, 'user_id' => $request['customer_id']])->first();
        if(!$order){
            return response()->json(['errors' => ['code' => 'order-payment', 'message' => 'Data not found']], 403);
        }
        if($order->is_guest){
            $customer_details = json_decode($order['delivery_address'],true);
        }else{
            $customer = User::find($request['customer_id']);
        }

        //guest user check
        if ($order->is_guest) {
            $address = json_decode($order['delivery_address'],true);
            $customer = collect([
                'first_name' => $address['contact_person_name'],
                'last_name' => '',
                'phone' => $address['contact_person_number'],
                'email' => $address['contact_person_email'],
            ]);

        } else {
            $customer = User::find($request['customer_id']);
            $customer = collect([
                'first_name' => $customer['f_name'],
                'last_name' => $customer['l_name'],
                'phone' => $customer['phone'],
                'email' => $customer['email'],
            ]);
        }


        if (session()->has('payment_method') == false) {
            session()->put('payment_method', 'ssl_commerz_payment');
        }

        $order_amount = $order->order_amount - $order->partially_paid_amount;

        if (!isset($customer)) {
            return response()->json(['errors' => ['message' => 'Customer not found']], 403);
        }

        if (!isset($order_amount)) {
            return response()->json(['errors' => ['message' => 'Amount not found']], 403);
        }

        if (!$request->has('payment_method')) {
            return response()->json(['errors' => ['message' => 'Payment not found']], 403);
        }

        $payer = new Payer($customer['first_name'].' '.$customer['last_name'], $customer['email'], $customer['phone'], '');

        $currency=BusinessSetting::where(['key'=>'currency'])->first()->value;

        $store_logo= BusinessSetting::where(['key' => 'logo'])->first();
        $additional_data = [
            'business_name' => BusinessSetting::where(['key'=>'business_name'])->first()?->value,
            'business_logo' => \App\CentralLogics\Helpers::get_full_url('business',$store_logo?->value,$store_logo?->storage[0]?->value ?? 'public' )
        ];

        $payment_info = new PaymentInfo(
            success_hook: 'order_place',
            failure_hook: 'order_failed',
            currency_code: $currency,
            payment_method: $request->payment_method,
            payment_platform: $request['payment_platform'],
            payer_id: $request['customer_id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $order_amount,
            external_redirect_link: $request->has('callback')?$request['callback']:session('callback'),
            attribute: 'order',
            attribute_id: $order->id
        );

        $receiver_info = new Receiver('receiver_name','example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return redirect($redirect_link);

    }

    public function success()
    {
        $order = Order::where(['id' => session('order_id'), 'user_id'=>session('customer_id')])->first();
        if (isset($order) && $order->callback != null) {
            return redirect($order->callback . '&status=success');
        }
        return response()->json(['message' => 'Payment succeeded'], 200);
    }

    public function fail()
    {
        $order = Order::where(['id' => session('order_id'), 'user_id'=>session('customer_id')])->first();
        if (isset($order) && $order->callback != null) {
            return redirect($order->callback . '&status=fail');
        }
        return response()->json(['message' => 'Payment failed'], 403);
    }
    public function cancel(Request $request)
    {
        $order = Order::where(['id' => session('order_id'), 'user_id'=>session('customer_id')])->first();
        if (isset($order) && $order->callback != null) {
            return redirect($order->callback . '&status=fail');
        }
        return response()->json(['message' => 'Payment failed'], 403);
    }

    /**
     * GET /payment-success
     *
     * This handler is the Laravel-side landing for
     * `Processor::payment_response()` when no safe
     * `external_redirect_link` is set on the `payment_requests` row.
     * Previously this method did not exist, so any `payment-success`
     * hit raised `BadMethodCallException` and produced an HTTP 500
     * inside the WebView.
     *
     * Hardening contract:
     *  - Accepts the `?token=...` (base64) query parameter produced by
     *    `Processor::payment_response()`. The token is informational
     *    only and is never treated as proof of payment.
     *  - Does NOT mutate `payment_requests.is_paid`. The authoritative
     *    write happens inside
     *    `StripePaymentController::markPaidFromSession()` (or the
     *    equivalent gateway writer) on the actual gateway callback.
     *  - Does NOT capture a charge. We never re-run the gateway here.
     *  - Does NOT redirect to an attacker-controlled callback. If the
     *    order has a callback, we validate the host against `app.url`
     *    before following it; otherwise we render a self-contained
     *    HTML page so the surrounding WebView remains in control of
     *    the navigation.
     *  - Refuses to call `redirect()->away('pickles://...')` here. Per
     *    the project security model, custom-scheme redirection is the
     *    mobile client's responsibility, not the server's.
     *  - Other gateways (PayPal, RazorPay, etc.) are untouched. Once
     *    this method exists, they benefit automatically through the
     *    same `Processor::payment_response()` path.
     */
    public function oksuccess(Request $request)
    {
        $token = (string) $request->input('token', '');
        $decoded = null;
        if ($token !== '' && base64_decode($token, true) !== false) {
            $decoded = base64_decode($token, true);
        }

        $order = Order::where(['id' => session('order_id'), 'user_id' => session('customer_id')])->first();
        if (isset($order) && !empty($order->callback) && $this->isSameOriginCallback($order->callback)) {
            $sep = (strpos($order->callback, '?') === false) ? '?' : '&';
            return redirect($order->callback . $sep . 'status=success&token=' . urlencode($token));
        }

        return $this->renderPaymentLandingPage('success', $token, $decoded);
    }

    /**
     * GET /payment-fail
     *
     * Symmetric with `oksuccess()`. Reached when
     * `Processor::payment_response($row, 'fail')` falls through to the
     * local route. Never mutates payment state.
     */
    public function okfail(Request $request)
    {
        $token = (string) $request->input('token', '');
        $decoded = null;
        if ($token !== '' && base64_decode($token, true) !== false) {
            $decoded = base64_decode($token, true);
        }

        $order = Order::where(['id' => session('order_id'), 'user_id' => session('customer_id')])->first();
        if (isset($order) && !empty($order->callback) && $this->isSameOriginCallback($order->callback)) {
            $sep = (strpos($order->callback, '?') === false) ? '?' : '&';
            return redirect($order->callback . $sep . 'status=fail&token=' . urlencode($token));
        }

        return $this->renderPaymentLandingPage('fail', $token, $decoded);
    }

    /**
     * GET /payment-cancel
     *
     * Symmetric with `oksuccess()`. Reached when
     * `Processor::payment_response($row, 'cancel')` falls through to the
     * local route. Never mutates payment state.
     */
    public function okcancel(Request $request)
    {
        $token = (string) $request->input('token', '');
        $decoded = null;
        if ($token !== '' && base64_decode($token, true) !== false) {
            $decoded = base64_decode($token, true);
        }

        $order = Order::where(['id' => session('order_id'), 'user_id' => session('customer_id')])->first();
        if (isset($order) && !empty($order->callback) && $this->isSameOriginCallback($order->callback)) {
            $sep = (strpos($order->callback, '?') === false) ? '?' : '&';
            return redirect($order->callback . $sep . 'status=cancel&token=' . urlencode($token));
        }

        return $this->renderPaymentLandingPage('cancel', $token, $decoded);
    }

    /**
     * Validate that a stored `Order.callback` is same-origin with the
     * configured app. Mirrors the spirit of
     * `Processor::isSafeExternalRedirect()` but enforces HTTPS + same
     * host, so an attacker who manages to write an arbitrary callback
     * into the `orders` table cannot use `/payment-success` as an
     * open-redirect sink.
     */
    private function isSameOriginCallback(?string $callback): bool
    {
        if (!$callback) {
            return false;
        }
        $parts = parse_url($callback);
        if (!is_array($parts)) {
            return false;
        }
        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }
        $host = strtolower($parts['host'] ?? '');
        if ($host === '') {
            return false;
        }
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $appHost = is_string($appHost) ? strtolower($appHost) : '';
        if ($appHost === '' || $host !== $appHost) {
            return false;
        }
        return true;
    }

    /**
     * Render a minimal, self-contained HTML page that:
     *  - displays the payment outcome (success / fail / cancel),
     *  - surfaces the token for the surrounding WebView,
     *  - exposes a `payment_result` JS variable so the WebView host
     *    (e.g. Flutter's `shouldOverrideUrlLoading`) can detect the
     *    outcome without parsing the DOM,
     *  - never injects untrusted user input into executable contexts
     *    (the token is HTML-escaped and re-exposed via a data-attribute
     *    and a JSON-encoded JS literal).
     */
    private function renderPaymentLandingPage(string $flag, string $token, ?string $decoded)
    {
        $safeFlag    = htmlspecialchars($flag, ENT_QUOTES, 'UTF-8');
        $safeToken   = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $safeDecoded = htmlspecialchars($decoded ?? '', ENT_QUOTES, 'UTF-8');
        $jsonFlag    = json_encode($flag, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $jsonToken   = json_encode($token, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $jsonDecoded = $decoded === null
            ? 'null'
            : json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $css = 'html,body{margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f7f7f9;color:#1f2937}'
             . '.wrap{max-width:520px;margin:0 auto;padding:32px 20px;text-align:center}'
             . '.card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.06)}'
             . 'h1{font-size:20px;margin:0 0 8px 0}'
             . 'p{font-size:14px;line-height:1.5;margin:8px 0;color:#4b5563}'
             . '.status-success{color:#047857}.status-fail{color:#b91c1c}.status-cancel{color:#92400e}'
             . 'code{display:block;word-break:break-all;background:#f3f4f6;padding:8px 10px;border-radius:6px;font-size:12px;text-align:left}'
             . '.btn{display:inline-block;margin-top:16px;padding:10px 18px;background:#111827;color:#fff;text-decoration:none;border-radius:8px;font-size:14px}';

        $head = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
              . '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">'
              . '<title>Payment ' . $safeFlag . '</title><style>' . $css . '</style></head>';

        $body = '<body data-payment-result="' . $safeFlag . '" data-payment-token="' . $safeToken . '">'
              . '<div class="wrap"><div class="card">'
              . '<h1 class="status-' . $safeFlag . '">Payment ' . $safeFlag . '</h1>'
              . '<p>You can close this page and return to the app.</p>'
              . '<p><strong>Token (informational):</strong></p>'
              . '<code id="payment-token">' . $safeToken . '</code>'
              . '<p><strong>Decoded (informational):</strong></p>'
              . '<code id="payment-decoded">' . $safeDecoded . '</code>'
              . '<a class="btn" href="javascript:window.close()">Close</a>'
              . '</div></div>';

        $script = '<script>(function(){'
                . 'try{window.payment_result={flag:' . $jsonFlag . ',token:' . $jsonToken . ',decoded:' . $jsonDecoded . '};'
                . 'if(window.parent&&window.parent!==window){'
                . 'try{window.parent.postMessage({type:"payment_result",payload:window.payment_result},"*");}catch(e){}'
                . '}}catch(e){}'
                . '})();</script>';

        $html = $head . $body . $script . '</body></html>';

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

}
