<?php

namespace Modules\Rental\Http\Controllers\Api\Provider;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Models\StoreSchedule;
use App\CentralLogics\Helpers;
use Illuminate\Support\Carbon;
use App\Models\BusinessSetting;
use App\Traits\FileManagerTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Models\SubscriptionTransaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Modules\Rental\Entities\VehicleBrand;
use Illuminate\Contracts\Support\Renderable;
use Modules\Rental\Entities\VehicleCategory;
use App\Models\SubscriptionBillingAndRefundHistory;
use Modules\Rental\Entities\Trips;

class ProviderController extends Controller
{
    use FileManagerTrait;
    private VehicleCategory $category;
    private Store $store;
    private StoreSchedule $storeSchedule;
    private SubscriptionTransaction $subscriptionTransaction;
    private SubscriptionBillingAndRefundHistory $subscriptionBillingAndRefundHistory;
    private VehicleBrand $brand;
    private Helpers $helpers;
    private BusinessSetting $businessSetting;
    private Trips $trip;

    public function __construct(VehicleCategory $category, Trips $trip,VehicleBrand $brand, Store $store, Helpers $helpers, BusinessSetting $businessSetting, SubscriptionTransaction $subscriptionTransaction, SubscriptionBillingAndRefundHistory $subscriptionBillingAndRefundHistory, StoreSchedule $storeSchedule)
    {
        $this->trip = $trip;
        $this->category = $category;
        $this->brand = $brand;
        $this->helpers = $helpers;
        $this->businessSetting = $businessSetting;
        $this->store = $store;
        $this->subscriptionTransaction = $subscriptionTransaction;
        $this->subscriptionBillingAndRefundHistory = $subscriptionBillingAndRefundHistory;
        $this->storeSchedule = $storeSchedule;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $vendor = $request['vendor'];
        $minAmountToPayStore = $this->businessSetting->where('key' , 'min_amount_to_pay_store')->first()->value ?? 0;
        $store = $this->helpers->store_data_formatting($vendor->stores[0], false);
        $discount = $this->helpers->get_store_discount($vendor->stores[0]);
        $vendor->stores[0]['pickup_zone_id']= json_decode($vendor->stores[0]['pickup_zone_id'], true);
        unset($store['discount']);

        $store['discount'] = $discount;
        $store['schedules'] = $store->schedules()->get();
        $store['module'] = $store->module;

        $vendor['order_count'] =$this->trip->where('provider_id' , $store->id)->whereIn('trip_status', ['refunded', 'completed'])
        ->count();

        $vendor['todays_order_count'] = $this->trip->where('provider_id' , $store->id)->whereDate('completed',now())
        ->whereIn('trip_status', ['refunded', 'completed'])->count();

        $vendor['this_week_order_count'] =$this->trip->where('provider_id' , $store->id)->whereBetween('completed', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->whereIn('trip_status', ['refunded', 'completed'])->whereYear('completed', date('Y'))->count();

        $vendor['this_month_order_count'] = $this->trip->where('provider_id' , $store->id)->whereMonth('completed', date('m'))->whereYear('completed', date('Y'))
            ->whereIn('trip_status', ['refunded', 'completed'])->count();

        $vendor['member_since_days'] = $vendor->created_at->diffInDays();

        $vendor['cash_in_hands'] = $vendor->wallet
            ? (float) $vendor->wallet->collected_cash
            : 0;

        $vendor['balance'] = $vendor->wallet
            ? (float) $vendor->wallet->balance
            : 0;

        $vendor['total_earning'] = $vendor->wallet
            ? (float) $vendor->wallet->total_earning
            : 0;

        $vendor['todays_earning'] = (float) $store->todays_trip_earning()->sum('store_amount');
        $vendor['this_week_earning'] = (float) $store->this_week_trip_earning()->sum('store_amount');
        $vendor['this_month_earning'] = (float) $store->this_month_trip_earning()->sum('store_amount');

        if($vendor['balance']  < 0){
            $vendor['balance']  = 0 ;
        }

        $vendor['Payable_Balance'] = (float) ($vendor?->wallet?->balance  < 0 ? abs($vendor?->wallet?->balance): 0 );
        $walletEarning = round($vendor?->wallet?->total_earning -($vendor?->wallet?->total_withdrawn + $vendor?->wallet?->pending_withdraw) , 8);
        $vendor['withdraw_able_balance'] = (float) $walletEarning ;

        if(($vendor?->wallet?->balance > 0 && $vendor?->wallet?->collected_cash > 0 ) || ($vendor?->wallet?->collected_cash != 0 && $walletEarning !=  0))
        {
            $vendor['adjust_able'] = true;
        }
        elseif($vendor?->wallet?->balance == $walletEarning  )
        {
            $vendor['adjust_able'] = false;
        }
        else
        {
            $vendor['adjust_able'] = false;
        }

        $vendor['show_pay_now_button'] = false;
        $digitalPayment = $this->helpers->get_business_settings('digital_payment');

        if ($minAmountToPayStore <= $vendor?->wallet?->collected_cash && $digitalPayment['status'] == 1 &&  $vendor?->wallet?->collected_cash  >  $vendor?->wallet?->balance ){
            $vendor['show_pay_now_button'] = true;
        }

        $vendor['pending_withdraw'] = (float)$vendor?->wallet?->pending_withdraw ?? 0;
        $vendor['total_withdrawn'] = (float)$vendor?->wallet?->total_withdrawn ?? 0;

        if($vendor['balance'] > 0 ){
            $vendor['dynamic_balance'] = (float) abs($walletEarning);
            if($vendor?->wallet?->balance == $walletEarning){
                $vendor['dynamic_balance_type'] = translate('messages.Withdrawable_Balance') ;
            } else{
                $vendor['dynamic_balance_type'] = translate('messages.Balance').' '.(translate('Unadjusted')) ;
            }

        } else{
            $vendor['dynamic_balance'] = (float) abs($vendor?->wallet?->collected_cash) ?? 0;
            $vendor['dynamic_balance_type'] = translate('messages.Payable_Balance') ;
        }

        $PayableBalance = $vendor?->wallet?->collected_cash  > 0 ? 1: 0;

        $cashInHandOverflow = $this->businessSetting->where('key' ,'cash_in_hand_overflow_store')->first()?->value;
        $cashInHandOverflow_store_amount = $this->businessSetting->where('key' ,'cash_in_hand_overflow_store_amount')->first()?->value;
        $val = $cashInHandOverflow_store_amount - (($cashInHandOverflow_store_amount * 10)/100);

        $vendor['over_flow_warning'] = false;

        if($PayableBalance == 1 &&  $cashInHandOverflow &&  $vendor?->wallet?->balance < 0 &&  $val <=  abs($vendor?->wallet?->collected_cash))
        {
            $vendor['over_flow_warning'] = true;
        }

        $vendor['over_flow_block_warning'] = false;

        if ($PayableBalance == 1 &&  $cashInHandOverflow &&  $vendor?->wallet?->balance < 0 &&  $cashInHandOverflow_store_amount < abs($vendor?->wallet?->collected_cash))
        {
            $vendor['over_flow_block_warning'] = true;
        }

        $vendor["stores"] = $store;
        $st = $this->store->withoutGlobalScope('translate')->findOrFail($store['id']);
        $vendor["translations"] = $st->translations;

        if ($request['vendor_employee'])
        {
            $vendorEmployee = $request['vendor_employee'];
            $role = $vendorEmployee->role ? json_decode($vendorEmployee->role->modules):[];
            $vendor["roles"] = $role;
            $vendor["employee_info"] = json_decode($request['vendor_employee']);
        }

        unset($vendor['orders']);
        unset($vendor['rating']);
        unset($vendor['todaysorders']);
        unset($vendor['this_week_orders']);
        unset($vendor['wallet']);
        unset($vendor['todaysorders']);
        unset($vendor['this_week_orders']);
        unset($vendor['this_month_orders']);

        $vendor['subscription_transactions'] = (boolean) $this->subscriptionTransaction->where('store_id',$store->id)->count() > 0;

        if(isset($st?->store_sub_update_application)){
            $vendor['subscription'] = $st?->store_sub_update_application;

            if($vendor['subscription']->max_product == 'unlimited' )
            {
                $maxProductUploads = -1;
            }
            else
            {
                $maxProductUploads = $vendor['subscription']->max_product - $st?->vehicles?->count() > 0?  $vendor['subscription']->max_product - $st?->vehicles?->count() : 0 ;
            }

            $pendingBill = $this->subscriptionBillingAndRefundHistory->where(['store_id'=>$store->id,
                'transaction_type'=>'pending_bill', 'is_success' =>0])?->sum('amount') ?? 0;

            $vendor['subscription_other_data'] =  [
                'total_bill'=>  (float) $vendor['subscription']->package?->price * ($vendor['subscription']->total_package_renewed + 1),
                'max_product_uploads' => (int) $maxProductUploads,
                'pending_bill' => (float) $pendingBill,
            ];
        }


        // if( $st?->storeConfig?->minimum_stock_for_warning > 0)
        // {
        //     $items = $st?->items()->where('stock' ,'<=' , $st?->storeConfig?->minimum_stock_for_warning );
        // }
        // else
        // {
        //     $items = $st?->items()->where('stock',0 );
        // }

        // $outOfStockCount = $st?->module->module_type != 'food' ? $items->orderby('stock')->latest()->count() : 0;
        $vendor['out_of_stock_count'] = (int) 0;


        return response()->json($vendor, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function profileUpdate(Request $request): JsonResponse
    {
        $vendor = $request['vendor'];
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'phone' => 'required|unique:vendors,phone,'.$vendor->id,
            'password' => ['nullable', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'l_name.required' => translate('messages.Last name is required!'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        if ($request->has('image')) {
            $imageName = $this->updateAndUpload('vendor/', $vendor->image, 'png', $request->file('image'));
        } else {
            $imageName = $vendor->image;
        }

        if ($request['password'] != null && strlen($request['password']) > 5) {
            $pass = bcrypt($request['password']);
        } else {
            $pass = $vendor->password;
        }

        $vendor->f_name = $request->f_name;
        $vendor->l_name = $request->l_name;
        $vendor->phone = $request->phone;
        $vendor->image = $imageName;
        $vendor->password = $pass;
        $vendor->save();

        return response()->json(['message' => translate('messages.profile_updated_successfully')], 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function categoryList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $limit = $request['limit'];
        $offset = $request['offset'];

        $categories = $this->category->latest()->paginate($limit, ['*'], 'page', $offset);
        $data = $this->helpers->preparePaginatedResponse(pagination:$categories, limit:$limit, offset:$offset, key:'categories', extraData:[]);

        return response()->json($data, 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function brandList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $limit = $request['limit'];
        $offset = $request['offset'];

        $brands = $this->brand->latest()->paginate($limit, ['*'], 'page', $offset);
        $data = $this->helpers->preparePaginatedResponse(pagination:$brands, limit:$limit, offset:$offset, key:'brands', extraData:[]);

        return response()->json($data, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function scheduleStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),[
            'opening_time' => 'required|date_format:H:i:s',
            'closing_time' => 'required|date_format:H:i:s|after:opening_time',
        ],[
            'closing_time.after' => translate('messages.End time must be after the start time')
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $store = $request['vendor']->stores[0];
        $temp = $this->storeSchedule->where('day', $request->day)->where('store_id',$store->id)
            ->where(function($q)use($request){
                return $q->where(function($query)use($request){
                    return $query->where('opening_time', '<=' , $request->opening_time)->where('closing_time', '>=', $request->opening_time);
                })->orWhere(function($query)use($request){
                    return $query->where('opening_time', '<=' , $request->closing_time)->where('closing_time', '>=', $request->closing_time);
                });
            })
            ->first();

        if(isset($temp))
        {
            return response()->json(['errors' => [
                ['code'=>'time', 'message'=>translate('messages.schedule_overlapping_warning')]
            ]], 400);
        }

        $storeSchedule = $this->storeSchedule->insertGetId(['store_id' => $store->id, 'day' => $request->day, 'opening_time' => $request->opening_time, 'closing_time' => $request->closing_time]);

        return response()->json(['message' => translate('messages.Schedule added successfully'), 'id' => $storeSchedule], 200);
    }

    /**
     * @param Request $request
     * @param $storeSchedule
     * @return JsonResponse
     */
    public function scheduleDelete(Request $request, $id): JsonResponse
    {
        $store = $request['vendor']->stores[0];
        $schedule = $this->storeSchedule->where('store_id', $store->id)->find($id);
        if(!$schedule)
        {
            return response()->json([
                'error'=>[
                    ['code' => 'not-fond', 'message' => translate('messages.Schedule not found')]
                ]
            ],404);
        }
        $schedule->delete();

        return response()->json(['message' => translate('messages.Schedule removed successfully')], 200);
    }


}
