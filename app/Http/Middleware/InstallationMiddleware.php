<?php

namespace App\Http\Middleware;

use Closure;

class InstallationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
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
            session()->flash('error', base64_decode('SW52YWxpZCBwdXJjaGFzZSBjb2RlIGZvciB0aGlzIHNvZnR3YXJlLg=='));
            return redirect('step2');
        } elseif (env('PURCHASE_CODE') != null) {
            return $next($request);
        }

        return $next($request);
    }
}
