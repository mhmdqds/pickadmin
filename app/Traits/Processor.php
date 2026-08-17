<?php

namespace App\Traits;

use App\CentralLogics\Helpers;
use Exception;
use App\Models\Setting;
use App\Models\PaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;

trait  Processor
{
    public function response_formatter($constant, $content = null, $errors = []): array
    {
        $constant = (array)$constant;
        $constant['content'] = $content;
        $constant['errors'] = $errors;
        return $constant;
    }

    public function error_processor($validator): array
    {
        $errors = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            $errors[] = ['error_code' => $index, 'message' => self::translate($error[0])];
        }
        return $errors;
    }

    public function translate($key)
    {
        try {
            App::setLocale('en');
            $lang_array = include(base_path('resources/lang/' . 'en' . '/lang.php'));
            $processed_key = ucfirst(str_replace('_', ' ', str_ireplace(['\'', '"', ',', ';', '<', '>', '?'], ' ', $key)));
            if (!array_key_exists($key, $lang_array)) {
                $lang_array[$key] = $processed_key;
                $str = "<?php return " . var_export($lang_array, true) . ";";
                file_put_contents(base_path('resources/lang/' . 'en' . '/lang.php'), $str);
                $result = $processed_key;
            } else {
                $result = __('lang.' . $key);
            }
            return $result;
        } catch (\Exception $exception) {
            return $key;
        }
    }

    public function payment_config($key, $settings_type): object|null
    {
        try {
            $config = DB::table('addon_settings')->where('key_name', $key)
                ->where('settings_type', $settings_type)->first();
        } catch (Exception $exception) {
            return new Setting();
        }

        return (isset($config)) ? $config : null;
    }
    public static function getDisk()
    {
        $config=\App\CentralLogics\Helpers::get_business_settings('local_storage');

        return isset($config)?($config==0?'s3':'public'):'public';
    }
    public function file_uploader(string $dir, string $format, $image = null, $old_image = null)
    {
        return Helpers::update($dir,$old_image, $format,$image);
    }

    /**
     * Validate that an external redirect link is safe (FIX-15).
     * Allows only https:// with a host that matches the configured
     * base URL of the application. All other redirects are coerced
     * to the local web success/fail/cancel routes.
     */
    private function isSafeExternalRedirect(?string $url): bool
    {
        if (!$url) {
            return false;
        }
        $parts = parse_url($url);
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
        // Allow the app's own host (config('app.url') is the canonical
        // base) and any host explicitly allow-listed via env.
        $appHost = parse_url((string)config('app.url'), PHP_URL_HOST);
        $allowed = (array)config('app.allowed_redirect_hosts', []);
        $allowed[] = $appHost;
        $allowed = array_filter(array_map('strtolower', $allowed));
        return in_array($host, $allowed, true);
    }

    public function payment_response($payment_info, $payment_flag): Application|JsonResponse|Redirector|RedirectResponse|\Illuminate\Contracts\Foundation\Application
    {
        // Null-safety: $payment_info may be null when the gateway
        // controller fails to resolve a row (e.g. invalid session_id,
        // not_found, mismatch). In that case we must still produce a
        // valid Laravel response — never a 500 due to a null property
        // access.
        $row = null;
        if (is_object($payment_info) && isset($payment_info->id)) {
            $row = PaymentRequest::find($payment_info->id);
        }

        // Normalize the flag to one of the three supported outcomes.
        // Any other value falls back to 'fail' so the route name
        // `payment-fail` is guaranteed to exist.
        $allowedFlags = ['success', 'fail', 'cancel'];
        if (!in_array($payment_flag, $allowedFlags, true)) {
            $payment_flag = 'fail';
        }

        $token_string = '';
        if ($row) {
            $token_string = 'payment_method=' . ($row->payment_method ?? '')
                . '&&attribute_id=' . ($row->attribute_id ?? '')
                . '&&transaction_reference=' . ($row->transaction_id ?? '');
        }
        $encodedToken = base64_encode($token_string);

        // External (allow-listed) redirect takes precedence.
        if ($row
            && in_array($row->payment_platform, ['web', 'app'], true)
            && !empty($row['external_redirect_link'])
            && $this->isSafeExternalRedirect($row['external_redirect_link'])
        ) {
            // FIX-15: only redirect to allow-listed hosts.
            return redirect($row['external_redirect_link'] . '?flag=' . $payment_flag . '&&token=' . $encodedToken);
        }

        // Fallback: local web route. The route name `payment-<flag>`
        // is guaranteed to exist for success/fail/cancel.
        return redirect()->route('payment-' . $payment_flag, ['token' => $encodedToken]);
    }
}
