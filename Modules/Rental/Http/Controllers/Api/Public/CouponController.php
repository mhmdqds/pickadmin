<?php

namespace Modules\Rental\Http\Controllers\Api\Public;

use App\Models\Store;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\CentralLogics\CouponLogic;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;


class CouponController extends Controller
{
    public function __construct(private Coupon $coupon, private Store $store)
    {
        $this->coupon = $coupon;
        $this->store = $store;
    }

    public function list(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $customer_id = Auth::user()?->id ?? $request->customer_id ?? null;
        $zone_id = $request->header('zoneId');
        $data = [];

        $coupons = $this->coupon->with('store:id,name')->active()
            ->wherehas('module', function ($query) {
                $query->where('module_type', 'rental');
            })
            ->whereDate('expire_date', '>=', date('Y-m-d'))->whereDate('start_date', '<=', date('Y-m-d'))->get();

        foreach ($coupons as $key => $coupon) {
            if ($coupon->coupon_type == 'store_wise') {
                $temp = $this->store->active()
                    ->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                        if (!config('module.current_module_data')['all_zone_service']) {
                            $query->whereIn('zone_id', json_decode($zone_id, true));
                        }
                    })
                    ->whereIn('id', json_decode($coupon->data, true))->first();
                if ($temp && (in_array("all", json_decode($coupon->customer_id, true)) || in_array($customer_id, json_decode($coupon->customer_id, true)))) {
                    $coupon->data = $temp->name;
                    $coupon['store_id'] = (int)$temp->id;
                    $data[] = $coupon;
                }
            } else if ($coupon->coupon_type == 'zone_wise') {
                if (count(array_intersect(json_decode($zone_id, true), json_decode($coupon->data, true)))) {
                    $data[] = $coupon;
                }
            } else if (isset($coupon->store_id)) {
                $temp = $this->store->active()->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                    if (!config('module.current_module_data')['all_zone_service']) {
                        $query->whereIn('zone_id', json_decode($zone_id, true));
                    }
                })->where('id', $coupon->store_id)->exists();

                if ($temp) {
                    $data[] = $coupon;
                }
            } else {
                if ((in_array("all", json_decode($coupon->customer_id, true)) || in_array($customer_id, json_decode($coupon->customer_id, true)))) {
                    $data[] = $coupon;
                }
            }
        }
        return response()->json($data, 200);
    }



    public function apply(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'provider_id' => 'required',
        ]);

        if ($validator->errors()->count()>0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $coupon = Coupon::active()->where(['code' => $request['code']])->wherehas('module', function ($query) {
                $query->where('module_type', 'rental');
            })->first();
            if (isset($coupon)) {
                $staus = CouponLogic::is_valide($coupon, $request->user()->id ,$request['provider_id']);

                switch ($staus) {
                case 200:
                    return response()->json($coupon, 200);
                case 406:
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.coupon_usage_limit_over')]
                        ]
                    ], 406);
                case 407:
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.coupon_expire')]
                        ]
                    ], 407);
                case 408:
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.You_are_not_eligible_for_this_coupon')]
                        ]
                    ], 403);
                default:
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.coupon_not_found')]
                        ]
                    ], 404);
                }
            } else {
                return response()->json([
                    'errors' => [
                        ['code' => 'coupon', 'message' => translate('messages.coupon_not_found')]
                    ]
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }


}
