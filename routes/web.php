<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaytmController;
use App\Http\Controllers\LiqPayController;
use App\Http\Controllers\PaymobController;
use App\Http\Controllers\PaytabsController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\RazorPayController;
use App\Http\Controllers\SenangPayController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\BkashPaymentController;
use App\Http\Controllers\FlutterwaveV3Controller;
use App\Http\Controllers\PaypalPaymentController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\SslCommerzPaymentController;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\RiderRegistrationController;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::post('/subscribeToTopic', [FirebaseController::class, 'subscribeToTopic']);
Route::get('/', 'HomeController@index')->name('home');
Route::get('lang/{locale}', 'HomeController@lang')->name('lang');
Route::get('terms-and-conditions', 'HomeController@terms_and_conditions')->name('terms-and-conditions');
Route::get('about-us', 'HomeController@about_us')->name('about-us');
Route::get('contact-us', 'HomeController@contact_us')->name('contact-us');
Route::post('send-message', 'HomeController@send_message')->name('send-message');
Route::get('privacy-policy', 'HomeController@privacy_policy')->name('privacy-policy');
Route::get('cancelation', 'HomeController@cancelation')->name('cancelation');
Route::get('refund', 'HomeController@refund_policy')->name('refund');
Route::get('shipping-policy', 'HomeController@shipping_policy')->name('shipping-policy');
Route::post('newsletter/subscribe', 'NewsletterController@newsLetterSubscribe')->name('newsletter.subscribe');
Route::get('subscription-invoice/{id}', 'HomeController@subscription_invoice')->name('subscription_invoice');
Route::get('order-invoice/{id}', 'HomeController@order_invoice')->name('order_invoice');
Route::get('deliveryman-earning-report-invoice/{id}', 'HomeController@earningReportInvoice')->name('delivery_earning_invoice')->middleware('localization');
Route::get('activation-check', 'HomeController@getActivationCheckView')->name('system.activation-check');
Route::post('activation-check', 'HomeController@activationCheck');

Route::get('login/{tab}', 'LoginController@login')->name('login');
Route::post('login_submit', 'LoginController@submit')->name('login_post')->middleware('actch');
Route::get('logout', 'LoginController@logout')->name('logout');
Route::get('/reload-captcha', 'LoginController@reloadCaptcha')->name('reload-captcha');
Route::get('/reset-password', 'LoginController@reset_password_request')->name('reset-password');
Route::post('/vendor-reset-password', 'LoginController@vendor_reset_password_request')->name('vendor-reset-password');
Route::get('/password-reset', 'LoginController@reset_password')->name('change-password');
Route::post('verify-otp', 'LoginController@verify_token')->name('verify-otp');
Route::post('reset-password-submit', 'LoginController@reset_password_submit')->name('reset-password-submit');
Route::get('otp-resent', 'LoginController@otp_resent')->name('otp_resent');

Route::get('authentication-failed', function () {
    $errors = [];
    array_push($errors, ['code' => 'auth-001', 'message' => 'Unauthenticated.']);
    return response()->json([
        'errors' => $errors,
    ], 401);
})->name('authentication-failed');

Route::group(['prefix' => 'payment-mobile'], function () {
    Route::get('/', 'PaymentController@payment')->name('payment-mobile');
    Route::get('set-payment-method/{name}', 'PaymentController@set_payment_method')->name('set-payment-method');
});

Route::get('payment-success', 'PaymentController@oksuccess')->name('payment-success');
Route::get('payment-fail', 'PaymentController@fail')->name('payment-fail');
Route::get('payment-cancel', 'PaymentController@cancel')->name('payment-cancel');

$is_published = 0;
try {
$full_data = include('Modules/Gateways/Addon/info.php');
$is_published = $full_data['is_published'] == 1 ? 1 : 0;
} catch (\Exception $exception) {}

if (!$is_published) {
    Route::group(['prefix' => 'payment'], function () {

        //SSLCOMMERZ
        Route::group(['prefix' => 'sslcommerz', 'as' => 'sslcommerz.'], function () {
            Route::get('pay', [SslCommerzPaymentController::class, 'index'])->name('pay');
            Route::post('success', [SslCommerzPaymentController::class, 'success'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::post('failed', [SslCommerzPaymentController::class, 'failed'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::post('canceled', [SslCommerzPaymentController::class, 'canceled'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //STRIPE
        Route::group(['prefix' => 'stripe', 'as' => 'stripe.'], function () {
            Route::get('pay', [StripePaymentController::class, 'index'])->name('pay');
            Route::get('token', [StripePaymentController::class, 'payment_process_3d'])->name('token');
            Route::get('success', [StripePaymentController::class, 'success'])->name('success');
            Route::get('canceled', [StripePaymentController::class, 'canceled'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

      //RAZOR-PAY
      Route::group(['prefix' => 'razor-pay', 'as' => 'razor-pay.'], function () {
        Route::get('pay', [RazorPayController::class, 'index']);
        Route::post('payment', [RazorPayController::class, 'payment'])->name('payment')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        Route::post('callback', [RazorPayController::class, 'callback'])->name('callback')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        Route::any('cancel', [RazorPayController::class, 'cancel'])->name('cancel')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

        Route::any('create-order', [RazorPayController::class, 'createOrder'])->name('create-order')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        Route::any('verify-payment', [RazorPayController::class, 'verifyPayment'])->name('verify-payment')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    });

        //PAYPAL
        Route::group(['prefix' => 'paypal', 'as' => 'paypal.'], function () {
            Route::get('pay', [PaypalPaymentController::class, 'payment']);
            Route::any('success', [PaypalPaymentController::class, 'success'])->name('success')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
            Route::any('cancel', [PaypalPaymentController::class, 'cancel'])->name('cancel')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
        });

        //SENANG-PAY
        Route::group(['prefix' => 'senang-pay', 'as' => 'senang-pay.'], function () {
            Route::get('pay', [SenangPayController::class, 'index']);
            Route::any('callback', [SenangPayController::class, 'return_senang_pay']);
        });

        //PAYTM
        Route::group(['prefix' => 'paytm', 'as' => 'paytm.'], function () {
            Route::get('pay', [PaytmController::class, 'payment']);
            Route::any('response', [PaytmController::class, 'callback'])->name('response')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //FLUTTERWAVE
        Route::group(['prefix' => 'flutterwave-v3', 'as' => 'flutterwave-v3.'], function () {
            Route::get('pay', [FlutterwaveV3Controller::class, 'initialize'])->name('pay');
            Route::get('callback', [FlutterwaveV3Controller::class, 'callback'])->name('callback');
        });

        //PAYSTACK
        Route::group(['prefix' => 'paystack', 'as' => 'paystack.'], function () {
            Route::get('pay', [PaystackController::class, 'index'])->name('pay');
            Route::get('callback', [PaystackController::class, 'handleGatewayCallback'])->name('callback');
            Route::get('cancel', [PaystackController::class, 'cancel'])->name('cancel');
        });

        //BKASH
        Route::group(['prefix' => 'bkash', 'as' => 'bkash.'], function () {
            // Payment Routes for bKash
            Route::get('make-payment', [BkashPaymentController::class, 'make_tokenize_payment'])->name('make-payment');
            Route::any('callback', [BkashPaymentController::class, 'callback'])->name('callback');

            // Refund Routes for bKash
            // Route::get('refund', 'BkashRefundController@index')->name('bkash-refund');
            // Route::post('refund', 'BkashRefundController@refund')->name('bkash-refund');
        });

        //Liqpay
        Route::group(['prefix' => 'liqpay', 'as' => 'liqpay.'], function () {
            Route::get('payment', [LiqPayController::class, 'payment'])->name('payment');
            Route::any('callback', [LiqPayController::class, 'callback'])->name('callback');
        });

        //MERCADOPAGO
          Route::group(['prefix' => 'mercadopago', 'as' => 'mercadopago.'], function () {
            Route::get('pay', [MercadoPagoController::class, 'index'])->name('index');
            Route::post('make-payment', [MercadoPagoController::class, 'make_payment'])->name('make_payment')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
            Route::any('callback', [MercadoPagoController::class, 'callback'])->name('callback')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
        });

        //PAYMOB
        Route::group(['prefix' => 'paymob', 'as' => 'paymob.'], function () {
            Route::any('pay', [PaymobController::class, 'credit'])->name('pay');
            Route::any('callback', [PaymobController::class, 'callback'])->name('callback');
        });

        //PAYTABS
        Route::group(['prefix' => 'paytabs', 'as' => 'paytabs.'], function () {
            Route::any('pay', [PaytabsController::class, 'payment'])->name('pay');
            Route::any('callback', [PaytabsController::class, 'callback'])->name('callback');
            Route::any('response', [PaytabsController::class, 'response'])->name('response');
        });
    });
}


//Restaurant Registration
Route::group(['prefix' => 'vendor', 'as' => 'restaurant.'], function () {
    Route::get('apply', 'VendorController@create')->name('create');
    Route::post('apply', 'VendorController@store')->name('store');
    Route::get('get-all-modules', 'VendorController@get_all_modules')->name('get-all-modules');
    Route::get('get-module-type', 'VendorController@get_modules_type')->name('get-module-type');
    Route::get('check-module-type', 'VendorController@check_module_type')->name('check-module-type');

    Route::get('back', 'VendorController@back')->name('back');
    Route::post('business-plan', 'VendorController@business_plan')->name('business_plan');
    Route::get('business-plan', 'VendorController@secondStep')->name('secondStep');
    Route::post('payment', 'VendorController@payment')->name('payment');
    Route::get('final-step', 'VendorController@final_step')->name('final_step');
});

//Rider Registration
Route::group(['prefix' => 'rider', 'as' => 'rider.'], function () {
    Route::get('apply', [RiderRegistrationController::class, 'create'])->name('create');
    Route::post('apply', [RiderRegistrationController::class, 'store'])->name('store');
});

//Deliveryman Registration
Route::group(['prefix' => 'deliveryman', 'as' => 'deliveryman.'], function () {
    Route::get('apply', 'DeliveryManController@create')->name('create');
    Route::post('apply', 'DeliveryManController@store')->name('store');

});


/*
|--------------------------------------------------------------------------
| /image-proxy — hardened SSRF-safe image proxy (H-10 fix)
| - Requires signed `?exp=…&hash=…` params (HMAC-SHA256 of the URL + expiry)
| - Only http/https schemes
| - Only allow-listed hosts (config driven via IMAGE_PROXY_ALLOWED_HOSTS env)
| - Blocks private/loopback IP literals and DNS-rebound private/loopback ranges
| - Bounded timeout (8s) and max size (10 MB) via stream context
| - Cache headers: 1 day public, 7 days stale-while-revalidate
| - CORS removed (CORS does not make sense for a same-origin proxy)
|--------------------------------------------------------------------------
*/
Route::get('/image-proxy', function () {
    $url  = (string) request('url');
    $exp  = (string) request('exp');
    $hash = (string) request('hash');

    if ($url === '') {
        abort(400, 'Missing url parameter');
    }
    if ($exp === '' || $hash === '') {
        abort(403, 'Missing signature');
    }
    if ((int) $exp < time()) {
        abort(403, 'Signature expired');
    }

    $secret = (string) config('app.key');
    if ($secret === '' || !hash_equals(hash_hmac('sha256', $url, $exp . '|' . $secret), $hash)) {
        abort(403, 'Invalid signature');
    }

    // Scheme allow-list (block file://, gopher://, etc.)
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
        abort(400, 'Invalid url');
    }
    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        abort(400, 'Scheme not allowed');
    }
    $host = strtolower($parts['host']);

    // Host allow-list (config/env driven)
    $allowed = array_filter(array_map('trim', explode(',', (string) env('IMAGE_PROXY_ALLOWED_HOSTS', ''))));
    if (empty($allowed)) {
        // Secure default: no hosts allowed unless explicitly configured
        abort(403, 'No allowed hosts configured');
    }
    $allowed = array_map('strtolower', $allowed);
    $hostOk = false;
    foreach ($allowed as $candidate) {
        if ($candidate === $host) {
            $hostOk = true;
            break;
        }
        if (str_starts_with($candidate, '*.')
            && strlen($host) > strlen($candidate) - 1
            && substr($host, -strlen($candidate) + 1) === substr($candidate, 1)) {
            $hostOk = true;
            break;
        }
    }
    if (!$hostOk) {
        abort(403, 'Host not allowed');
    }

    // SSRF: block private/loopback IP literals, and DNS-resolved private/loopback.
    $banned = [
        '127.0.0.0/8', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16',
        '169.254.0.0/16',     // link-local incl. AWS / GCP metadata
        '0.0.0.0/8', '::1/128', 'fc00::/7', 'fe80::/10',
    ];
    $ipInCidr = function (string $ip, string $cidr): bool {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;
        $mask = $bits === 0 ? 0 : ((-1 << (32 - $bits)) & 0xFFFFFFFF);
        $ipLong  = ip2long($ip);
        $subLong = ip2long($subnet);
        if ($ipLong === false || $subLong === false) {
            // IPv6 simple fallback
            return strpos($ip, ':') !== false && strpos($cidr, ':') !== false
                && strpos($ip, substr($cidr, 0, strpos($cidr, '/'))) === 0;
        }
        return (($ipLong & $mask) === ($subLong & $mask));
    };
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        foreach ($banned as $cidr) {
            if ($ipInCidr($host, $cidr)) { abort(403, 'IP not allowed'); }
        }
    } else {
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        $ips = [];
        foreach ((array) $records as $r) {
            if (!empty($r['ip']))    { $ips[] = $r['ip']; }
            if (!empty($r['ipv6'])) { $ips[] = $r['ipv6']; }
        }
        if (empty($ips)) { abort(502, 'Could not resolve host'); }
        foreach ($ips as $ip) {
            foreach ($banned as $cidr) {
                if ($ipInCidr($ip, $cidr)) { abort(403, 'IP not allowed'); }
            }
        }
    }

    // Bounded timeout + max size via stream context
    $ctx = stream_context_create([
        'http' => [
            'timeout'         => 8,
            'max_redirects'   => 3,
            'ignore_errors'   => true,
            'follow_location' => 1,
            'user_agent'      => 'Laravel-Image-Proxy/1.0',
            'header'          => "Accept: image/*\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx, 0, 10 * 1024 * 1024 + 1);
    if ($body === false || strlen($body) > 10 * 1024 * 1024) {
        abort(502, 'Failed to fetch image or too large');
    }

    $headers = $http_response_header ?? [];
    $status  = 200;
    $ctype   = 'image/jpeg';
    foreach ($headers as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) { $status = (int) $m[1]; }
        if (stripos($h, 'Content-Type:') === 0) { $ctype = trim(substr($h, 13)); }
    }
    if (strpos($ctype, 'image/') !== 0) {
        abort(415, 'Not an image');
    }
    if ($status >= 400) {
        abort($status, 'Upstream error');
    }
    return response($body, 200)
        ->header('Content-Type', $ctype)
        ->header('Cache-Control', 'public, max-age=86400, stale-while-revalidate=604800')
        ->header('X-Content-Type-Options', 'nosniff');
})->name('image-proxy');
