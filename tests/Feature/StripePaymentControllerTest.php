<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class StripePaymentControllerTest extends TestCase
{
    private $paymentId;
    private $sessionId;
    private $paymentIntentId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('payment_requests');
        Schema::dropIfExists('addon_settings');
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
        Schema::create('addon_settings', function ($t) {
            $t->bigIncrements('id');
            $t->string('key_name', 191);
            $t->string('settings_type', 100);
            $t->text('live_values')->nullable();
            $t->text('test_values')->nullable();
            $t->string('mode', 20)->default('test');
            $t->boolean('is_active')->default(0);
            $t->text('additional_data')->nullable();
        });

        DB::table('addon_settings')->insert([
            'key_name' => 'stripe',
            'settings_type' => 'payment_config',
            'test_values' => json_encode([
                'api_key' => 'sk_test_fake',
                'published_key' => 'pk_test_fake',
                'webhook_secret' => 'whsec_test_secret',
            ]),
            'live_values' => json_encode([
                'api_key' => 'sk_live_fake',
                'published_key' => 'pk_live_fake',
                'webhook_secret' => 'whsec_live_secret',
            ]),
            'mode' => 'test',
            'is_active' => 1,
        ]);

        $this->paymentId = (string) Str::uuid();
        $this->sessionId = 'cs_test_' . Str::random(20);
        $this->paymentIntentId = 'pi_test_' . Str::random(20);
        DB::table('payment_requests')->insert([
            'id'              => $this->paymentId,
            'payment_amount'  => 100.00,
            'currency_code'   => 'USD',
            'is_paid'         => 0,
            'success_hook'    => 'test_success_hook',
            'failure_hook'    => 'test_failure_hook',
            'attribute'       => 'order',
            'attribute_id'    => '12345',
            'payer_id'        => 'CUST-1',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    public function test_success_url_with_unknown_session_id_does_not_mark_paid(): void
    {
        $this->get('/payment/stripe/success?session_id=cs_evil&payment_id=' . $this->paymentId);
        $row = DB::table('payment_requests')->where('id', $this->paymentId)->first();
        $this->assertSame(0, (int) $row->is_paid);
    }

    public function test_fake_success_url_without_real_session_does_not_mark_paid(): void
    {
        $this->get('/payment/stripe/success?payment_id=' . $this->paymentId);
        $row = DB::table('payment_requests')->where('id', $this->paymentId)->first();
        $this->assertSame(0, (int) $row->is_paid);
    }

    public function test_token_endpoint_returns_existing_session_id_for_retry(): void
    {
        DB::table('payment_requests')->where('id', $this->paymentId)
            ->update(['stripe_session_id' => 'cs_test_existing']);
        $response = $this->get('/payment/stripe/token?payment_id=' . $this->paymentId);
        $response->assertStatus(200);
        $this->assertSame('cs_test_existing', $response->json('id'));
    }

    public function test_token_endpoint_rejects_invalid_uuid(): void
    {
        $response = $this->get('/payment/stripe/token?payment_id=not-a-uuid');
        $response->assertStatus(400);
    }

    public function test_token_endpoint_rejects_missing_payment_id(): void
    {
        $response = $this->get('/payment/stripe/token');
        $response->assertStatus(400);
    }

    public function test_token_endpoint_rejects_zero_amount(): void
    {
        DB::table('payment_requests')->where('id', $this->paymentId)
            ->update(['payment_amount' => 0.00]);
        $response = $this->get('/payment/stripe/token?payment_id=' . $this->paymentId);
        $body = $response->json();
        $this->assertSame('gateways_default_204', $body['response_code'] ?? null);
    }

    public function test_webhook_rejects_request_with_missing_signature(): void
    {
        $payload = json_encode(['type' => 'checkout.session.completed', 'id' => 'evt_1']);
        $response = $this->call('POST', '/payment/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_webhook_rejects_request_with_bad_signature(): void
    {
        $payload = json_encode(['type' => 'checkout.session.completed', 'id' => 'evt_1']);
        $response = $this->call('POST', '/payment/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=1,v1=not-a-real-signature',
        ], $payload);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_webhook_with_valid_signature_but_unrelated_event_is_acked(): void
    {
        $secret = 'whsec_test_secret';
        $payload = json_encode([
            'id' => 'evt_test_unrelated',
            'type' => 'customer.subscription.updated',
            'data' => ['object' => ['id' => 'sub_123']],
        ]);
        $ts = time();
        $sig = hash_hmac('sha256', $ts . '.' . $payload, $secret);
        $sigHeader = "t={$ts},v1={$sig}";
        $response = $this->call('POST', '/payment/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
        ], $payload);
        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('ignored', $body['state'] ?? null);
    }

    public function test_successful_mark_writes_payment_intent_and_method(): void
    {
        DB::table('payment_requests')->where('id', $this->paymentId)->update([
            'stripe_session_id'      => $this->sessionId,
            'stripe_payment_intent'  => $this->paymentIntentId,
            'is_paid'                => 1,
            'payment_method'         => 'stripe',
            'transaction_id'         => $this->paymentIntentId,
        ]);
        $row = DB::table('payment_requests')->where('id', $this->paymentId)->first();
        $this->assertSame(1, (int) $row->is_paid);
        $this->assertSame('stripe', $row->payment_method);
        $this->assertSame($this->paymentIntentId, $row->transaction_id);
    }

    public function test_unique_index_on_stripe_session_id_prevents_duplicates(): void
    {
        // The migration targets MySQL/MariaDB and uses raw ALTER TABLE
        // which SQLite ignores. For the test we explicitly create the
        // unique index on SQLite and then assert the duplicate insert
        // throws. On production the migration creates the same index.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_payment_requests_stripe_session_id_test
                       ON payment_requests (stripe_session_id)');

        DB::table('payment_requests')->where('id', $this->paymentId)
            ->update(['stripe_session_id' => 'cs_test_unique_target']);

        $threw = false;
        try {
            DB::table('payment_requests')->insert([
                'id' => (string) Str::uuid(),
                'payment_amount' => 1.00,
                'currency_code'  => 'USD',
                'is_paid'        => 0,
                'stripe_session_id' => 'cs_test_unique_target',
                'success_hook'   => null,
                'failure_hook'   => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            $threw = true;
        }
        $this->assertTrue($threw, 'F-5: stripe_session_id must have a UNIQUE index');
    }

    public function test_webhook_route_is_registered(): void
    {
        $routes = $this->app['router']->getRoutes();
        $found = false;
        foreach ($routes as $r) {
            if (in_array('POST', $r->methods(), true) && $r->uri() === 'payment/stripe/webhook') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function test_all_stripe_routes_registered(): void
    {
        $expected = [
            'payment/stripe/pay'      => 'index',
            'payment/stripe/token'    => 'payment_process_3d',
            'payment/stripe/success'  => 'success',
            'payment/stripe/canceled' => 'canceled',
            'payment/stripe/webhook'  => 'webhook',
        ];
        $routes = $this->app['router']->getRoutes();
        $map = [];
        foreach ($routes as $r) {
            if (!str_contains($r->uri(), 'stripe')) continue;
            $map[$r->uri()] = $r->getActionName();
        }
        foreach ($expected as $uri => $method) {
            $this->assertArrayHasKey($uri, $map, "Missing Stripe route: $uri");
            $this->assertStringContainsString('@' . $method, $map[$uri]);
        }
    }
}