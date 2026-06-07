<?php

namespace Modules\Rental\Http\Controllers\Api\Provider;


use App\Models\Coupon;
use App\Models\Translation;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    private Coupon $coupon;
    private Helpers $helpers;

    public function __construct(Coupon $coupon, Helpers $helpers)
    {
        $this->coupon = $coupon;
        $this->helpers = $helpers;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $limit = $request['limit']??25;
        $offset = $request['offset']??1;
        $store_id = $request->vendor->stores[0]->id;
        $key = explode(' ', $request['search']);

        $coupons =  $this->coupon->where('created_by','vendor')
            ->where('store_id',$store_id)
            ->when(isset($key), function($query)use($key){
                $query->where( function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('title', 'like', "%{$value}%")
                            ->orWhere('code', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->paginate($limit, ['*'], 'page', $offset);

        $data = $this->helpers->preparePaginatedResponse(pagination:$coupons, limit:$limit, offset:$offset, key:'coupons', extraData:[]);


        return response()->json($data,200);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:coupons|max:100',
            'start_date' => 'required',
            'expire_date' => 'required',
            'coupon_type' => 'required|in:free_delivery,default',
            'discount' => 'required_if:coupon_type,default'
        ]);

        $data = json_decode($request->translations, true);

        if (count($data) < 1) {
            $validator->getMessageBag()->add('translations', translate('messages.Title in english is required'));
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $customerId  = $request->customer_ids ?? ['all'];
        $storeId = $request->vendor->stores[0]->id;
        $moduleId = $request->vendor->stores[0]->module_id;

        $coupon = $this->coupon;
        $coupon->title = $data[0]['value'];
        $coupon->code = $request->code;
        $coupon->limit = $request->coupon_type == 'first_order' ? 1 : $request->limit;
        $coupon->coupon_type = $request->coupon_type;
        $coupon->start_date = $request->start_date;
        $coupon->expire_date = $request->expire_date;
        $coupon->min_purchase = $request->min_purchase ?? 0;
        $coupon->max_discount = $request->max_discount ?? 0;
        $coupon->discount = $request->discount ?? 0;
        $coupon->discount_type = $request->discount_type ?? '';
        $coupon->status = 1;
        $coupon->created_by = 'vendor';
        $coupon->store_id = $storeId;
        $coupon->customer_id = json_encode($customerId);
        $coupon->module_id = $moduleId;
        $coupon->save();


        foreach ($data as $key=>$item) {
            Translation::updateOrInsert(
                ['translationable_type' => 'App\Models\Coupon',
                    'translationable_id' => $coupon->id,
                    'locale' => $item['locale'],
                    'key' => $item['key']],
                ['value' => $item['value']]
            );
        }

        return response()->json(['message' => translate('messages.coupon_created_successfully')], 200);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function edit(Request $request, $id): JsonResponse
    {
        $coupon = $this->coupon->withoutGlobalScope('translate')->with('translations')->find($id);

        if ($coupon) {
            $coupon->load('translations');
            $coupon['data'] = json_decode($coupon['data'],true);
            $coupon['customer_id'] = json_decode($coupon['customer_id'],true);

            return response()->json($coupon, 200);
        }

        return response()->json(['message' => translate('messages.coupon_not_found.')], 400);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|max:100|unique:coupons,code,'.$id,
            'start_date' => 'required',
            'expire_date' => 'required',
            'coupon_type' => 'required|in:free_delivery,default',
            'discount' => 'required_if:coupon_type,default'
        ]);

        $data = json_decode($request->translations, true);

        if (count($data) < 1) {
            $validator->getMessageBag()->add('translations', translate('messages.Title in english is required'));
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $customerId  = $request->customer_ids ?? ['all'];

        $coupon = $this->coupon->findOrFail($id);
        $coupon->title = $data[0]['value'];
        $coupon->code = $request->code;
        $coupon->limit = $request->coupon_type == 'first_order' ? 1 : $request->limit;
        $coupon->coupon_type = $request->coupon_type;
        $coupon->start_date = $request->start_date;
        $coupon->expire_date = $request->expire_date;
        $coupon->min_purchase = $request->min_purchase ?? 0;
        $coupon->max_discount = $request->max_discount ?? 0;
        $coupon->discount = $request->discount ?? 0;
        $coupon->discount_type = $request->discount_type ?? '';
        $coupon->customer_id = json_encode($customerId);
        $coupon->save();

        foreach ($data as $key=>$item) {
            Translation::updateOrInsert(
                ['translationable_type' => 'App\Models\Coupon',
                    'translationable_id' => $coupon->id,
                    'locale' => $item['locale'],
                    'key' => $item['key']],
                ['value' => $item['value']]
            );
        }

        return response()->json(['message' => translate('messages.coupon_updated_successfully')], 200);
    }


    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function status(Request $request, $id): JsonResponse
    {
        $coupon = $this->coupon->find($id);

        if ($coupon) {
            $coupon->update(['status' => !$coupon->status]);
            return response()->json(['message' => translate('messages.coupon_status_updated.')], 200);
        }

        return response()->json(['message' => translate('messages.coupon_not_found.')], 400);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $driver = $this->coupon->find($id);

        if ($driver) {
            $driver->translations()->delete();
            $driver->delete();

            return response()->json(['message' => translate('messages.driver_deleted_successfully.')], 200);
        }

        return response()->json(['message' => translate('messages.failed_to_delete_driver.')], 400);
    }
}
