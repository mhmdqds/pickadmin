<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // H-? fix (F-12): dangerous cross-system / user-facing entries removed.
        //   - /external-login-from-drivemond, /api/v1/customer/external-update-data,
        //     /api/v1/get-customer, /vendor-panel/item/food-variation-generate,
        //     /vendor-panel/item/variation-generate
        //   These MUST now carry either a valid CSRF token or, for server-to-server
        //   traffic, a valid HMAC X-Signature header validated by the new
        //   VerifyCrossSystemSignature middleware.

        // Payment-gateway callbacks only (F-22 / F-43 will harden these further
        // with HMAC verification; here we keep CSRF exemption because external
        // gateway servers cannot hold a CSRF token).
        '/payment*',
        '/pay-via-ajax',
        '/payment-razor/*',
        '/paytm-response',
        '/liqpay-callback',
        '/mercadopago/make-payment',
        '/flutterwave-pay',
        '/paytabs-response',

        // Generic /success /fail /cancel /ipn return URLs used by several gateways.
        '/success',
        '/cancel',
        '/fail',
        '/ipn',
    ];
}
