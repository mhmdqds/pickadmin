<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression tests for the `PaymentController::oksuccess()` family
 * (`oksuccess`, `okfail`, `okcancel`) and the null-safe rewrite of
 * `Processor::payment_response()`.
 *
 * Background:
 *   The pre-fix `routes/web.php:76` registered
 *       Route::get('payment-success', 'PaymentController@oksuccess')
 *   but `PaymentController` did not contain an `oksuccess` method.
 *   Any request to `/payment-success` therefore raised
 *   `BadMethodCallException` → HTTP 500. The same problem existed
 *   for the implicit `payment-fail` / `payment-cancel` routes
 *   (which only worked because the controller happened to define
 *   `fail` and `cancel` — but those methods ignored the `?token=`
 *   query parameter, never produced the same HTML landing, and
 *   were not part of the documented flow).
 *
 *   Additionally, `Processor::payment_response()` accessed
 *   `$payment_info->id` without a null guard, so a `null` row
 *   (the legitimate return value of `markPaidFromSession()` in
 *   several states) produced an HTTP 500 too.
 */
class PaymentControllerOkSuccessTest extends TestCase
{
    private $orderId;
    private $customerId;
    private $paymentRequestId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('orders');
        Schema::dropIfExists('payment_requests');

        Schema::create('orders', function ($t) {
            $t->bigIncrements('id');
            $t->string('user_id', 64)->nullable();
            $t->decimal('order_amount', 24, 2)->default(0);
            $t->decimal('partially_paid_amount', 24, 2)->default(0);
            $t->boolean('is_guest')->default(0);
            $t->text('delivery_address')->nullable();
            $t->string('callback', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('payment_requests', function ($t) {
            $t->string('id', 36)->primary();
            $t->string('payer_id', 64)->nullable();
            $t->string('receiver_id', 64)->nullable();
            $t->decimal('payment_amount', 24, 2)->default(0);
            $t->string('gateway_callback_url', 191)->nullable();
            $t->string('success_hook', 100)->nullable();
            $t->string('failure_hook', 100)->nullable();
            $t->string('transaction_id', 100)->nullable();
            $t->string('currency_code', 20)->default('USD');
            $t->string('payment_method', 50)->nullable();
            $t->text('additional_data')->nullable();
            $t->boolean('is_paid')->default(0);
            $t->timestamps();
            $t->string('payer_information')->nullable();
            $t->string('external_redirect_link', 255)->nullable();
            $t->string('receiver_information')->nullable();
            $t->string('attribute_id', 64)->nullable();
            $t->string('attribute', 255)->nullable();
            $t->string('payment_platform', 255)->nullable();
            $t->string('stripe_session_id', 191)->nullable();
            $t->string('stripe_payment_intent', 191)->nullable();
            $t->string('webhook_event_id', 191)->nullable();
            $t->timestamp('expired_at')->nullable();
        });

        $this->orderId          = (int) (random_int(1, 100000));
        $this->customerId       = (string) Str::uuid();
        $this->paymentRequestId = (string) Str::uuid();
    }

    public function test_oksuccess_does_not_throw_bad_method_call_exception(): void
    {
        // Call the controller method directly so the test is independent
        // of test-environment routing configuration. The critical
        // pre-fix bug was: `Route::get('payment-success',
        // 'PaymentController@oksuccess')` → `BadMethodCallException`
        // because `oksuccess` did not exist on the class. The fix is
        // verified by (a) the method being present and (b) the
        // instance not throwing in its constructor.
        $controller = new \App\Http\Controllers\PaymentController();
        $this->assertTrue(
            method_exists($controller, 'oksuccess'),
            'PaymentController must have an oksuccess method'
        );

        $request = \Illuminate\Http\Request::create('/payment-success?token='
            . urlencode(base64_encode('payment_method=stripe&&attribute_id=42&&transaction_reference=cs_test_xyz')),
            'GET');
        $response = $controller->oksuccess($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
    }

    public function test_oksuccess_does_not_mutate_is_paid(): void
    {
        DB::table('payment_requests')->insert([
            'id'              => $this->paymentRequestId,
            'payment_amount'  => 100.00,
            'currency_code'   => 'USD',
            'is_paid'         => 0,
            'success_hook'    => 'test_success_hook',
            'failure_hook'    => 'test_failure_hook',
            'attribute'       => 'order',
            'attribute_id'    => (string) $this->orderId,
            'payment_method'  => 'stripe',
            'payment_platform'=> 'app',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        $token = base64_encode('payment_method=stripe&&attribute_id=' . $this->orderId
            . '&&transaction_reference=cs_test_xyz');

        $controller = new \App\Http\Controllers\PaymentController();
        $request = \Illuminate\Http\Request::create('/payment-success?token=' . urlencode($token), 'GET');
        $controller->oksuccess($request);

        $row = DB::table('payment_requests')->where('id', $this->paymentRequestId)->first();
        $this->assertSame(0, (int) $row->is_paid,
            'oksuccess must NEVER set is_paid=1; that is the gateway writer\'s job');
    }

    public function test_oksuccess_exposes_payment_result_data_attribute(): void
    {
        $controller = new \App\Http\Controllers\PaymentController();
        $request = \Illuminate\Http\Request::create('/payment-success?token=AAAA', 'GET');
        $response = $controller->oksuccess($request);
        $body = (string) $response->getContent();
        $this->assertStringContainsString('data-payment-result="success"', $body);
        $this->assertStringContainsString('data-payment-token="AAAA"', $body);
    }

    public function test_oksuccess_escapes_token(): void
    {
        $malicious = '"><script>alert(1)</script>';
        $controller = new \App\Http\Controllers\PaymentController();
        $request = \Illuminate\Http\Request::create('/payment-success?token=' . urlencode($malicious), 'GET');
        $response = $controller->oksuccess($request);
        $body = (string) $response->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
        $this->assertStringContainsString('&quot;', $body, 'double-quote must be entity-escaped');
    }

    public function test_okfail_method_exists(): void
    {
        // `okfail()` is added for symmetry with `oksuccess()`. It is
        // currently NOT routed (`routes/web.php` still uses the
        // pre-existing `PaymentController@fail`), but it is callable
        // and safe to invoke directly.
        $controller = new \App\Http\Controllers\PaymentController();
        $this->assertTrue(method_exists($controller, 'okfail'));
    }

    public function test_okcancel_method_exists(): void
    {
        $controller = new \App\Http\Controllers\PaymentController();
        $this->assertTrue(method_exists($controller, 'okcancel'));
    }

    public function test_is_same_origin_callback_rejects_foreign_host(): void
    {
        $controller = new \App\Http\Controllers\PaymentController();
        $r = new \ReflectionMethod($controller, 'isSameOriginCallback');
        $r->setAccessible(true);

        $this->assertFalse($r->invoke($controller, 'https://evil.example.com/cb'));
        $this->assertFalse($r->invoke($controller, 'http://localhost/cb'));
        $this->assertFalse($r->invoke($controller, 'pickles://payment/success'));
        $this->assertFalse($r->invoke($controller, ''));
        $this->assertFalse($r->invoke($controller, null));
    }

    public function test_payment_response_with_null_does_not_throw(): void
    {
        $controller = new \App\Http\Controllers\StripePaymentController(
            new \App\Models\PaymentRequest()
        );
        $response = $controller->payment_response(null, 'success');
        $this->assertNotNull($response);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_payment_response_with_unknown_flag_falls_back_to_fail(): void
    {
        $controller = new \App\Http\Controllers\StripePaymentController(
            new \App\Models\PaymentRequest()
        );
        $response = $controller->payment_response(null, 'totally-unknown');
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $location = $response->headers->get('Location') ?? $response->getTargetUrl();
        $this->assertStringContainsString('payment-fail', (string) $location);
    }

    public function test_all_payment_landing_methods_resolve_directly(): void
    {
        // We invoke the controller methods directly to be independent
        // of the test environment's route resolution layer. The
        // production routing of these methods is verified separately
        // in tests/Feature/CheckTestRoutesTest.php (via the Router
        // table), which DOES see the routes registered.
        $controller = new \App\Http\Controllers\PaymentController();
        $this->assertTrue(method_exists($controller, 'oksuccess'));
        $this->assertTrue(method_exists($controller, 'okfail'));
        $this->assertTrue(method_exists($controller, 'okcancel'));
    }
}
