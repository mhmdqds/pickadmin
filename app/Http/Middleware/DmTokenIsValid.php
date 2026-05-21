<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use App\Models\DeliveryMan;

class DmTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:delivery_men,auth_token'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 401);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->firstOrFail();
        auth()->guard('delivery_men')->login($dm);
        return $next($request);
    }
}
