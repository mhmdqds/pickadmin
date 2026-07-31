<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * VerifyCrossSystemSignature
 * --------------------------
 *
 * H-? fix (F-12): server-to-server / cross-system endpoints that were
 * previously exempt from CSRF verification (e.g. /api/v1/customer/external-
 * update-data, /external-login-from-drivemond, /api/v1/get-customer, the
 * item variation-generators) MUST now authenticate the calling system via
 * a shared-secret HMAC signature rather than being unauthenticated and
 * CSRF-exempt.
 *
 * Required headers on every request that passes through this middleware:
 *   X-Caller-Id   : stable opaque identifier of the calling system (for
 *                   audit / log scoping only; not used for auth).
 *   X-Timestamp   : unix timestamp seconds, MUST be within +/- 300 s of
 *                   server clock to mitigate replay attacks.
 *   X-Signature   : hex-encoded HMAC-SHA256 of:
 *                       strtoupper(method) . "\n" .
 *                       $request->path() . "\n" .
 *                       $timestamp . "\n" .
 *                       sha256($rawBody)
 *                   keyed by hash_hmac('sha256', $payload, $secret).
 *
 * The shared secret is read from .env / config via the
 * CROSS_SYSTEM_SHARED_SECRET key (configurable per env). If the env key is
 * not set, the middleware FAILS CLOSED (rejects every request) so that a
 * misconfigured production environment cannot accidentally accept unauth'd
 * traffic.
 *
 * Configure allowed callers in config/cross_system.php (mapped below) so
 * that different X-Caller-Id values can carry distinct secrets.
 */
class VerifyCrossSystemSignature
{
    /**
     * Allowed drift in seconds between request timestamp and server clock.
     * Configurable via config('cross_system.timestamp_tolerance_seconds') or
     * env CROSS_SYSTEM_TIMESTAMP_TOLERANCE; defaults to 300 s.
     */
    private const TIMESTAMP_TOLERANCE_DEFAULT = 300;

    private function timestampTolerance(): int
    {
        $value = function_exists('config')
            ? config('cross_system.timestamp_tolerance_seconds', self::TIMESTAMP_TOLERANCE_DEFAULT)
            : self::TIMESTAMP_TOLERANCE_DEFAULT;
        return is_int($value) ? $value : self::TIMESTAMP_TOLERANCE_DEFAULT;
    }

    public function handle(Request $request, Closure $next)
    {
        $timestamp = (string) $request->header('X-Timestamp', '');
        $signature = (string) $request->header('X-Signature', '');
        $callerId  = (string) $request->header('X-Caller-Id', '');

        // 1. Reject malformed requests immediately (don't leak timing info).
        if ($timestamp === '' || $signature === '' || $callerId === '') {
            return $this->reject('missing-headers', 'Missing X-Caller-Id, X-Timestamp, or X-Signature header.');
        }

        // 2. Anti-replay: timestamp MUST be within +/- 300 s of the server clock.
        if (!ctype_digit($timestamp)) {
            return $this->reject('bad-timestamp-format', 'X-Timestamp must be a unix timestamp integer.');
        }
        $skew = abs(time() - (int) $timestamp);
        if ($skew > $this->timestampTolerance()) {
            return $this->reject('timestamp-skew', 'X-Timestamp is outside the acceptable window.');
        }

        // 3. Lookup secret per-caller from a static map; fall back to a single
        //    shared secret if no per-caller mapping exists. Fail-CLOSED if no
        //    secret is configured at all.
        $secret = $this->resolveSecret($callerId);
        if ($secret === null || $secret === '') {
            return $this->reject('no-shared-secret',
                'No shared secret configured for caller "'.$callerId.'". Set CROSS_SYSTEM_SHARED_SECRET in .env.');
        }

        // 4. Compute the expected HMAC over (method + path + timestamp + sha256(body))
        //    and compare it with the provided signature using a constant-time
        //    comparison to prevent timing attacks.
        $rawBody = (string) $request->getContent();
        $bodyHash = hash('sha256', $rawBody);
        $payload  = strtoupper($request->getMethod())."\n".
                    $request->path()."\n".
                    $timestamp."\n".
                    $bodyHash;
        $expected = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, strtolower($signature))) {
            return $this->reject('bad-signature', 'Invalid HMAC signature.');
        }

        // 5. Optional: tag the request with the verified caller id so downstream
        //    controllers / logs can attribute the action without trusting client
        //    input.
        $request->attributes->set('verified_caller_id', $callerId);

        return $next($request);
    }

    /**
     * Lookup the shared secret for a given caller id. Returns null if no
     * secret is configured for the caller AND no global fallback exists.
     */
    private function resolveSecret(string $callerId): ?string
    {
        // Optional per-caller map in config/cross_system.php (format:
        //   ['caller_id_1' => 'secret_1', 'caller_id_2' => 'secret_2', ...]
        // ). Missing file => empty array. Degraded gracefully.
        $map = (array) (function_exists('config') ? config('cross_system.callers', []) : []);

        if (isset($map[$callerId]) && is_string($map[$callerId]) && $map[$callerId] !== '') {
            return $map[$callerId];
        }

        // Fall back to a single shared secret covering every unlisted caller.
        $shared = (string) (function_exists('config') ? config('cross_system.shared_secret', env('CROSS_SYSTEM_SHARED_SECRET')) : env('CROSS_SYSTEM_SHARED_SECRET'));

        return $shared !== '' ? $shared : null;
    }

    private function reject(string $code, string $message)
    {
        // Log the rejection server-side so SOC can correlate abuse.
        if (function_exists('Log')) {
            Log::warning('VerifyCrossSystemSignature: rejected request', [
                'reason' => $code,
                'path'   => request()->path(),
                'ip'     => request()->ip(),
                'ua'     => substr((string) request()->userAgent(), 0, 200),
            ]);
        }
        return response()->json([
            'errors' => [[
                'code'    => 'auth-002',
                'message' => 'Invalid or missing cross-system signature.',
            ]],
        ], 401);
    }
}
