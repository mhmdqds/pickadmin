<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Static / syntactic checks on the Stripe controller.
 *
 * Pure unit test — does not boot Laravel. The HTTP-flow tests live in
 * tests/Feature/StripePaymentControllerTest.php and use an in-memory
 * SQLite database + a fake Stripe client.
 */
class StripePaymentControllerValidationTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Http\Controllers\StripePaymentController::class));
    }

    public function test_controller_uses_processor_and_paymentrequest(): void
    {
        $rc = new \ReflectionClass(\App\Http\Controllers\StripePaymentController::class);
        $this->assertTrue($rc->hasProperty('config_values'));
        $this->assertTrue($rc->hasProperty('payment'));
    }

    public function test_controller_exposes_expected_public_endpoints(): void
    {
        $expected = ['index', 'payment_process_3d', 'success', 'canceled', 'webhook'];
        foreach ($expected as $name) {
            $this->assertTrue(
                method_exists(\App\Http\Controllers\StripePaymentController::class, $name),
                "StripePaymentController must expose {$name}()"
            );
        }
    }

    public function test_controller_has_a_private_mark_paid_writer(): void
    {
        $this->assertTrue(
            method_exists(\App\Http\Controllers\StripePaymentController::class, 'markPaidFromSession'),
            'markPaidFromSession is the shared secure writer used by success() and webhook()'
        );
    }
}
