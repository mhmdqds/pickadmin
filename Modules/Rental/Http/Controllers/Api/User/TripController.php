<?php

namespace Modules\Rental\Http\Controllers\Api\User;


use App\Models\User;
use App\Models\Zone;
use App\Models\Store;
use App\Library\Payer;
use App\Traits\Payment;
use App\Library\Receiver;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\CashBackHistory;
use App\CentralLogics\StoreLogic;
use App\CentralLogics\CouponLogic;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Entities\Trips;
use App\CentralLogics\CustomerLogic;
use Illuminate\Support\Facades\Mail;
use Modules\Rental\Entities\Vehicle;
use App\Library\Payment as PaymentInfo;
use Modules\Rental\Entities\RentalCart;
use Modules\Rental\Entities\TripDetails;
use Illuminate\Support\Facades\Validator;
use Modules\Rental\Traits\TripLogicTrait;
use Modules\Rental\Entities\PartialPayment;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\Rental\Emails\TripBooking;
use Modules\Rental\Entities\RentalCartUserData;
use Modules\Rental\Entities\VehicleReview;
use Modules\Rental\Traits\RentalPushNotification;

class TripController extends Controller
{
    use TripLogicTrait , RentalPushNotification;
    public function __construct(
        private RentalCart $cart,
        private Trips $trips,
        private RentalCartUserData $user_data,
        private Helpers $helpers,
        private Vehicle $vehicle,
    ) {
        $this->cart = $cart;
        $this->$trips = $trips;
        $this->user_data = $user_data;
        $this->helpers = $helpers;
        $this->vehicle = $vehicle;
    }

    public function tripBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_amount' => 'required|numeric',
            'trip_type' => 'required|in:hourly,distance_wise',
            'provider_id' => 'required|numeric',
            'contact_person_name' => $request->user ? 'nullable' : 'required',
            'contact_person_number' => $request->user ? 'nullable' : 'required',
            'contact_person_email' => $request->user ? 'nullable' : 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;


        $schedule_at = $request->schedule_at ? \Carbon\Carbon::parse($request->schedule_at) : now();

        $user_data =  $this->user_data->where('user_id', $user_id)->where('is_guest', $is_guest)->first() ?? null;



        $carts = $this->cart->where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->with('vehicle')->get();

        if (count($carts) == 0) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart', 'message' => translate('Your_cart_is_empty')]
                ]
            ], 403);
        }
        if (!$user_data) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart', 'message' => translate('Your_location_is_empty')]
                ]
            ], 403);
        }
        $estimated_trip_end_time = $schedule_at->copy()->addHours(
            ceil($user_data->rental_type == 'hourly' ? $user_data->estimated_hours ?? 1 : $user_data->destination_time ?? 1));

        $trip_validation_check =  $this->tripValidationCheck($request, $schedule_at,$user_data);

        if (data_get($trip_validation_check, 'status_code') === 403) {

            return response()->json([
                'errors' => [
                    ['code' => data_get($trip_validation_check, 'code'), 'message' => data_get($trip_validation_check, 'message')]
                ]
            ], data_get($trip_validation_check, 'status_code'));
        } else {
            $provider = $trip_validation_check['store'];
            $pickup_zone = $trip_validation_check['pickup_zone'];
        }

        DB::beginTransaction();

        if ($request['coupon_code']) {
            $coupon_check =  $this->couponCheck($request);
            if (data_get($coupon_check, 'code') === 'coupon') {
                DB::rollBack();
                return response()->json([
                    'errors' => [
                        ['code' => data_get($coupon_check, 'code'), 'message' => data_get($coupon_check, 'message')]
                    ]
                ], data_get($coupon_check, 'status_code'));
            } else {
                $coupon = data_get($coupon_check, 'coupon');
                $coupon_discount_by = data_get($coupon_check, 'coupon_discount_by');
            }
        }

        $tax_included = BusinessSetting::where(['key' => 'tax_included'])->first()?->value ?? 0;

        $details_data =  $this->tripDetails(request: $request, user_data: $user_data, carts: $carts, schedule_at: $schedule_at, estimated_trip_end_time: $estimated_trip_end_time, tax: $provider->tax, is_include: $tax_included, provider: $provider);

        if (data_get($details_data, 'code') === 'details_data') {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    ['code' => data_get($details_data, 'code'), 'message' => data_get($details_data, 'message')]
                ]
            ], data_get($details_data, 'status_code'));
        } else {
            $price = data_get($details_data, 'price');
            $discount_on_trip = data_get($details_data, 'discount');
            $quantity = data_get($details_data, 'quantity');
            $discount_on_trip_by = data_get($details_data, 'discount_on_trip_by');
            $details_data = data_get($details_data, 'details_data');
        }

        $discount_on_trip =$this->helpers->minDiscountCheck(productPrice: $price, discount: $discount_on_trip)['discount_applied'];
        $price -=  $discount_on_trip;
        $coupon_discount_amount = isset($coupon) ? CouponLogic::get_discount($coupon, $price) : 0;
        $coupon_discount_amount =$this->helpers->minDiscountCheck(productPrice: $price, discount: $coupon_discount_amount)['discount_applied'];
        $price -=  $coupon_discount_amount;

        if ($is_guest == 0 && $user_id) {
            $user = User::withcount('trips')->find($user_id);
            $discount_data = $this->helpers->getCusromerFirstOrderDiscount(order_count: $user->trips_count, user_creation_date: $user->created_at, refby: $user->ref_by, price: $price);
            if (data_get($discount_data, 'is_valid') == true &&  data_get($discount_data, 'calculated_amount') > 0) {
                $ref_bonus_amount = data_get($discount_data, 'calculated_amount');
                $ref_bonus_amount =$this->helpers->minDiscountCheck(productPrice: $price, discount: $ref_bonus_amount)['discount_applied'];
                $price -= $ref_bonus_amount;
            }
        }
        $tax_status = 'excluded';
        if ($tax_included  == 1) {
            $tax_status = 'included';
        }
        $orignal_tax_amount = $this->helpers->product_tax($price, $provider->tax, $tax_status == 'included');
        $tax_amount = $tax_status == 'included' ? 0 : $orignal_tax_amount;
        $claculated_tax= $orignal_tax_amount;

        $additional_charge =  0;
        if (BusinessSetting::where('key', 'additional_charge_status')->first()?->value == 1) {
            $additional_charge = BusinessSetting::where('key', 'additional_charge')->first()?->value ?? 0;
        }

        $price= max(0, $price)+$tax_amount + $additional_charge;

        $user_info = [
            'contact_person_name' => $request->contact_person_name ? $request->contact_person_name : ($request->user?$request->user->f_name . ' ' . $request->user->l_name:''),
            'contact_person_number' => $request->contact_person_number ? $request->contact_person_number : ($request->user?$request->user->phone:''),
            'contact_person_email' => $request->contact_person_email ? $request->contact_person_email : ($request->user?$request->user->email:''),
        ];

        $make_trip_data = [
            'user_id' => $user_id,
            'pickup_zone_id' => $pickup_zone->id,
            'is_guest' => $is_guest,
            'schedule_at' => $schedule_at,
            'estimated_trip_end_time' => $estimated_trip_end_time,
            'user_data' => $user_data,
            'provider' => $provider,
            'coupon' => $coupon ?? null,
            'tax_included' => $tax_included,
            'discount_on_trip' => $discount_on_trip ?? 0,
            'coupon_discount_amount' => $coupon_discount_amount ?? 0,
            'coupon_discount_by' => $coupon_discount_by ?? 'none',
            'coupon_code' => $coupon?->code ?? null,
            'tax_amount' => $claculated_tax ?? 0,
            'tax_status' => $tax_status,
            'trip_amount' => $price,
            'discount_on_trip_by' => $discount_on_trip_by ?? 'none',
            'additional_charge' => $additional_charge ?? 0,
            'distance' => $user_data->distance ?? 0,
            'estimated_hours' => $user_data->estimated_hours ?? 0,
            'ref_bonus_amount' => $ref_bonus_amount ?? 0,
            'cash_back_id' => $cash_back_id ?? null,
            'quantity' => $quantity ?? 1,
            'pickup_location' => json_encode($user_data->pickup_location),
            'destination_location' => json_encode($user_data->destination_location),
            'user_info' => json_encode($user_info),
        ];


        $trip = $this->makeTrip($request, $make_trip_data);

        foreach ($details_data as $key => $item) {
            $details_data[$key]['trip_id'] = $trip->id;
        }
        TripDetails::insert($details_data);


        $carts->each(function ($cart) {
            $cart->delete();
        });

        if($trip->is_guest  == 0 && $trip->user_id ){
            $this->createCashBackHistory($trip->trip_amount, $trip->user_id,$trip->id);
        }

        $user_data->delete();


        DB::commit();

        $this->sentTripNotification($trip);
        return response()->json($trip->id, 200);
    }

    private function createCashBackHistory($trip_amount, $user_id,$trip_id){
        $cashBack =  Helpers::getCalculatedCashBackAmount(amount:$trip_amount, customer_id:$user_id,type:1);
        if(data_get($cashBack,'calculated_amount') > 0){
            $CashBackHistory = new CashBackHistory();
            $CashBackHistory->user_id = $user_id;
            $CashBackHistory->trip_id = $trip_id;
            $CashBackHistory->calculated_amount = data_get($cashBack,'calculated_amount');
            $CashBackHistory->cashback_amount = data_get($cashBack,'cashback_amount');
            $CashBackHistory->cash_back_id = data_get($cashBack,'id');
            $CashBackHistory->cashback_type = data_get($cashBack,'cashback_type');
            $CashBackHistory->min_purchase = data_get($cashBack,'min_purchase');
            $CashBackHistory->max_discount = data_get($cashBack,'max_discount');
            $CashBackHistory->save();

            $CashBackHistory?->trip()->update([
                'cash_back_id'=> $CashBackHistory->id
            ]);
        }
        return true;
    }


    private function makeTrip($request, $make_trip_data)
    {
        $trip = $this->trips;
        $trip->user_id = $make_trip_data['user_id'];
        $trip->provider_id = $make_trip_data['provider']['id'];
        $trip->zone_id = $make_trip_data['provider']['zone_id'];
        $trip->pickup_zone_id = $make_trip_data['pickup_zone_id'];
        $trip->module_id =  $make_trip_data['provider']['module_id'];
        $trip->cash_back_id = $make_trip_data['cash_back_id'];
        $trip->trip_amount = $make_trip_data['trip_amount'];
        $trip->discount_on_trip = $make_trip_data['discount_on_trip'];
        $trip->discount_on_trip_by = $make_trip_data['discount_on_trip_by'];
        $trip->coupon_discount_amount = $make_trip_data['coupon_discount_amount'];
        $trip->coupon_discount_by = $make_trip_data['coupon_discount_by'];
        $trip->coupon_code = $make_trip_data['coupon_code'];
        $trip->trip_status = 'pending';
        $trip->payment_status = 'unpaid';
        $trip->tax_amount = $make_trip_data['tax_amount'];
        $trip->tax_status = $make_trip_data['tax_status'];
        $trip->tax_percentage = $make_trip_data['provider']['tax'];
        $trip->trip_type = $request->trip_type;
        $trip->additional_charge = $make_trip_data['additional_charge'];
        $trip->distance = $make_trip_data['distance'];
        $trip->estimated_hours = $make_trip_data['estimated_hours'];
        $trip->ref_bonus_amount = $make_trip_data['ref_bonus_amount'];
        $trip->trip_note = $request->additional_note;
        $trip->otp = rand(1000, 9999);
        $trip->is_guest = $make_trip_data['is_guest'];
        $trip->scheduled = $request->scheduled ?? 0;
        $trip->schedule_at = $make_trip_data['schedule_at'];
        $trip->quantity = $make_trip_data['quantity'];
        $trip->estimated_trip_end_time = $make_trip_data['estimated_trip_end_time'];
        $trip->destination_location = $make_trip_data['destination_location'];
        $trip->pickup_location = $make_trip_data['pickup_location'];
        $trip->pending = now();
        $trip->user_info = $make_trip_data['user_info'];
        $trip->save();
        return $trip;
    }



    private function tripValidationCheck($request, $schedule_at,$user_data)
    {

        // $settings_key = ['dine_in_order_option'];

        // $settings =  array_column(BusinessSetting::whereIn('key', $settings_key)->get()->toArray(), 'value', 'key');

        $longitude=  $user_data->pickup_location['lng']?? 0;
        $latitude= $user_data->pickup_location['lat']?? 0;

        $store = Store::with(['discount', 'store_sub'])->selectRaw('*, IF(((select count(*) from `store_schedule` where `stores`.`id` = `store_schedule`.`store_id` and `store_schedule`.`day` = ' . $schedule_at->format('w') . ' and `store_schedule`.`opening_time` < "' . $schedule_at->format('H:i:s') . '" and `store_schedule`.`closing_time` >"' . $schedule_at->format('H:i:s') . '") > 0), true, false) as open')->where('id', $request->provider_id)->first();


        $zone_id = isset($store) ? [$store->zone_id] : json_decode($request->header('zoneId'), true);

        $pickup_zone = Zone::where('id', $zone_id)->whereContains('coordinates', new Point($latitude, $longitude, POINT_SRID))->first('id');


        $response = match (true) {
            !$store => [
                'code' => 'provider',
                'message' =>  'provider_not_found',
                'status' => 403
            ],
            !$pickup_zone  => [
                'code' => 'Zone',
                'message' =>  'out_of_zone',
                'status' => 403
            ],
            in_array($store->store_business_model, ['unsubscribed', 'none']) || (in_array($store->store_business_model, ['subscription']) && $store?->store_sub == null) || (in_array($store->store_business_model, ['subscription']) && $store?->store_sub?->max_order != "unlimited" && $store?->store_sub?->max_order <= 0) => [
                'code' => 'provider',
                'message' =>  'Sorry_the_provider_is_unable_to_take_any_trip',
                'status' => 403
            ],
            $request->scheduled == 1  && !$store->schedule_order => [
                'code' => 'schedule_at',
                'message' => 'schedule_trip_not_available',
                'status' => 403
            ],
            $store->open == false => [
                'code' => 'schedule_at',
                'message' => 'This provider isn\'t available at the selected time',
                'status' => 403
            ],
            $request->scheduled == 1  && $schedule_at < now() => [
                'code' => 'trip_time',
                'message' =>  'you_can_not_schedule_a_trip_in_past',
                'status' => 403
            ],
            default => null
        };

        if ($response) {
            return ['code' => $response['code'], 'message' => translate($response['message']), 'status_code' => $response['status']];
        }

        return ['store'=> $store , 'pickup_zone'=> $pickup_zone ];
    }


    private function tripDetails($request, $user_data, $carts, $schedule_at, $estimated_trip_end_time, $tax, $is_include, $provider)
    {
        $price = 0;
        $discount_on_trip = 0;
        $quantity = 0;
        $details_data = [];
        $discount_on_trip_by = 'vendor';
        foreach ($carts as $cart) {

            $vehicle = $this->vehicle->where('id', $cart->vehicle_id)->active()
            ->withCount([
                'vehicleIdentities as total_vehicle_count' => function ($query) use ($schedule_at) {
                    $query->DynamicVehicleQuantity($schedule_at);
                },
            ])->first();


            $response = match (true) {
                !$vehicle => [
                    'code' => 'details_data',
                    'message' =>  'Vehicle_not_found',
                    'status' => 404
                ],
                !$cart->vehicle => [
                    'code' => 'details_data',
                    'message' =>  'Vehicle_not_found',
                    'status' => 404
                ],
                $cart->vehicle->status != 1 => [
                    'code' => 'details_data',
                    'message' =>  'Vehicle_is_unavailable',
                    'status' => 403
                ],
                $vehicle->total_vehicle_count <= 0 => [
                    'code' => 'details_data',
                    'message' =>  'This_Vehicle_is_not_available_on_this_pickup_time',
                    'status' => 403
                ],
                $vehicle->total_vehicle_count < $cart->quantity => [
                    'code' => 'details_data',
                    'message' =>  'Quantity_not_available',
                    'status' => 403
                ],

                default => null
            };

            if ($response) {
                return ['code' => $response['code'], 'message' => translate($response['message']), 'status_code' => $response['status']];
            }


            $discount_data = $this->getDiscount(price: $user_data->rental_type == 'hourly' ? $cart->vehicle->hourly_price *  $user_data->estimated_hours : $cart->vehicle->distance_price *  $user_data->distance, discount_type: $cart->vehicle->discount_type, discount: $cart->vehicle->discount_price);

            $trip_details_data = [
                'vehicle_id' => $cart->vehicle_id,
                'quantity' => $cart->quantity,
                'tax_percentage' => $tax,
                'discount_on_trip_by' => 'vendor',
                'discount_percentage' => $cart->vehicle->discount_type == 'amount' ? 0 : $cart->vehicle->discount_price,

                'price' => round($discount_data['price'], config('round_up_to_digit')) *  $cart->quantity,
                'original_price' => round($discount_data['price'], config('round_up_to_digit')),
                'calculated_price' => round($discount_data['price'], config('round_up_to_digit')) *  $cart->quantity,

                'discount_on_trip' => round($discount_data['discount'], config('round_up_to_digit')),
                'discount_type' => $cart->vehicle->discount_type,
                'tax_amount' => round($this->helpers->product_tax($discount_data['price'] - round($discount_data['discount'], config('round_up_to_digit')), $tax, $is_include), config('round_up_to_digit')),
                'tax_status' => $is_include == 1 ? 'included' : 'excluded',
                'vehicle_details' => json_encode($cart->vehicle),
                'rental_type' => $user_data->rental_type,
                'estimated_hours' => $user_data->estimated_hours,
                'distance' => $user_data->distance,
                'scheduled' => $request->scheduled ?? 0,
                'schedule_at' => $schedule_at,
                'estimated_trip_end_time' => $estimated_trip_end_time,

            ];
            $cart->vehicle->increment('total_trip', $cart->quantity);
            $details_data[] = $trip_details_data;

            $price += $trip_details_data['price'];
            $discount_on_trip += $trip_details_data['discount_on_trip'] * $cart->quantity;
            $quantity += $cart->quantity;
        }

        $discount = $discount_on_trip;
        $provider_discount = $this->helpers->get_store_discount($provider);
        if (isset($provider_discount)) {
            $admin_discount = $this->checkAdminDiscount(price: $price, discount: $provider_discount['discount'], max_discount: $provider_discount['max_discount'], min_purchase: $provider_discount['min_purchase']);

            $discount = max($discount_on_trip,$admin_discount);

            if($admin_discount > 0 &&  $discount == $admin_discount )  {
                $discount_on_trip_by = 'admin';
                foreach ($details_data as $key => $trip_data) {
                    $details_data[$key]['discount_on_trip_by'] = $discount_on_trip_by;
                    $details_data[$key]['discount_type'] = 'precentage';
                    $details_data[$key]['discount_percentage'] = $provider_discount['discount'];
                    $details_data[$key]['discount_on_trip'] =  $this->checkAdminDiscount(price: $price, discount: $provider_discount['discount'], max_discount: $provider_discount['max_discount'], min_purchase: $provider_discount['min_purchase'], vehicle_wise_price: $trip_data['price']);
                    $details_data[$key]['tax_amount'] = round($this->helpers->product_tax($trip_data['price'] - $trip_data['discount_on_trip'], config('round_up_to_digit')), $tax, $is_include);
                }
            }
        }

        if (count($details_data) > 0) {
            return ['details_data' => $details_data, 'price' => $price,  'discount' => $discount, 'quantity' => $quantity, 'discount_on_trip_by' => $discount_on_trip_by];
        }
        return ['code' => 'details_data', 'message' => translate('messages.details_data_not_found'), 'status_code' => 404];
    }

    private function getDiscount($price, $discount_type, $discount)
    {
        if ($price > 0 &&  $discount > 0) {
            $discount =  $discount_type == 'percent' ? ($price * $discount) / 100 :  $discount;
        }
        return ['price' => $price, 'discount' => $discount ?? 0];
    }
    private function checkAdminDiscount($price, $discount, $max_discount, $min_purchase, $vehicle_wise_price = null)
    {
        if ($price > 0 &&  $discount > 0) {
            $discount = ($price  * $discount) / 100;
            $discount = $discount > $max_discount ? $max_discount : $discount;
            $discount = $price >= $min_purchase ? $discount : 0;
        }

        if ($discount > 0 && $vehicle_wise_price > 0) {
            $discount = ($vehicle_wise_price / $price) * $discount;
        }

        return $discount ?? 0;
    }
    private function sentTripNotification($trip)
    {
        $order_mail_status = Helpers::get_mail_status('rental_place_order_mail_status_user');
        try {
            if( $trip->provider?->is_valid_subscription == 1 && $trip->provider?->store_sub?->max_order != "unlimited" && $trip->provider?->store_sub?->max_order > 0){
                $trip->provider?->store_sub?->decrement('max_order' , 1);
            }
            if(config('mail.status') && $order_mail_status == '1' && Helpers::getRentalNotificationStatusData('customer','customer_trip_notification','mail_status')){
                if (!$trip->is_guest && $trip->customer ) {
                    Mail::to($trip->customer->email)->send(new TripBooking($trip->id));
                }elseif($trip->is_guest == 1  && isset($trip->user_info['contact_person_email'])){
                    Mail::to($trip->user_info['contact_person_email'])->send(new TripBooking($trip->id));
                }
            }

        } catch (\Exception $ex) {
            info($ex->getMessage());
        }

        $this->sendTripNotificationToAll($trip);
        return true;
    }



    public function getTripList(Request $request,$type)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
            'trip_status' => 'nullable|in:pending,confirmed,ongoing,completed,canceled,payment_failed',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;
        $limit = $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;
        $trips = $this->trips->where(['user_id' => $user_id, 'is_guest' => $is_guest])

        ->with('provider:id,name,logo,cover_photo,phone')
            ->when($request->search, function ($query) use ($request) {
                $keys = explode(' ', $request->search);
                $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('id', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($request->trip_status, function ($query) use ($request) {
                $query->where('trip_status', $request->trip_status);
            })
            ->when($type == 'completed', function ($query) {
                $query->whereIn('trip_status', ['completed','canceled']);
            })
            ->when($type == 'running', function ($query) {
                $query->whereIn('trip_status', ['pending','confirmed','ongoing','payment_failed']);
            })
            ->latest()->paginate($limit, ['*'], 'page', $offset);
        $data = $this->helpers->preparePaginatedResponse(pagination: $trips, limit: $limit, offset: $offset, key: 'trips', extraData: []);
        return response()->json($data, 200);
    }
    public function getTripDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
            'trip_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $trip = $this->trips->where(['user_id' => $user_id, 'is_guest' => $is_guest, 'id' => $request->trip_id])
            ->with([
                'trip_details',
                'vehicle_identity.driver_data:id,first_name,last_name,email,phone,image',
                'vehicle_identity.vehicle_identity_data:id,vin_number,license_plate_number',
                'vehicle_identity.vehicles:id,name,thumbnail',
                'provider' => function ($query) {
                    $query->select('id', 'name', 'logo', 'cover_photo', 'rating', 'phone','reviews_section')->with('store_sub')
                        ->withCount('vehicle_identity as total_vehicles');
                }
            ])
            ->first();
            if(!$trip){
                return response()->json(['errors' => translate('trip_data_not_found')], 404);
            }

            $trip->trip_details->each(function ($detail) {
                $detail->license_plate_number = $detail->tripVehicleDetails
                    ->pluck('vehicle_identity_data.license_plate_number')
                    ->filter()
                    ->values()
                    ->toArray();
                    unset($detail->tripVehicleDetails);
                });


            $trip->vehicle_identity->each(function ($identity) {
                $review=null;
                $identity->license_plate_number = $identity?->vehicle_identity_data?->license_plate_number;
                $identity->vehicle_name = $identity?->vehicles?->name;
                $identity->vehicle_thumbnail= $identity?->vehicles?->thumbnail_full_url;
                $review=  VehicleReview::where(['vehicle_identity_id'=> $identity->vehicle_identity_data?->id ,'trip_id' => $identity->trip_id])->first();
                    $identity->rating = $review?->rating;
                    $identity->comment = $review?->comment;
                    $identity->reply = $review?->reply;
                    $identity->replied_at = $review?->replied_at;
                });
                if(isset($trip['provider'])){
                    $ratings = StoreLogic::calculate_store_rating($trip['provider']['rating']);
                    $trip['provider']['avg_rating'] =$ratings['rating'];
                    $trip['provider']['rating_count'] =$ratings['total'];
                    if(isset($trip['provider']->store_sub)){
                        $trip['provider']['chat'] =$trip?->provider?->store_sub?->chat ?? 0;
                        $trip['provider']['reviews_section'] =$trip?->provider?->store_sub?->review ??  $trip['provider']['reviews_section'] ?? 0;
                        unset($trip['provider']->store_sub);
                    } else{
                        $trip['provider']['chat'] =1;
                        $trip['provider']['reviews_section'] = $trip['provider']['reviews_section'] ?? 0;
                    }

                }

        return response()->json($trip, 200);
    }


    public function cancelTrip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
            'trip_id' => 'required',
            'cancellation_reason' => 'nullable|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $trip = $this->trips->where(['user_id' => $user_id, 'is_guest' => $is_guest, 'id' => $request->trip_id])->with('trip_details.vehicle')->first();

        if(!$trip){
            return response()->json(['errors' => translate('trip_data_not_found')], 404);
        }

        if($trip->trip_status !== 'pending'){
            return response()->json(['errors' => translate('You_can_not_cancal_this_trip')], 403);
        }

        $trip->trip_status = 'canceled';
        $trip->canceled_by = 'user';
        $trip->cancellation_reason = $request->cancellation_reason;
        $trip->canceled = now();
        $trip->save();
        foreach($trip->trip_details as $detail){
            $detail?->vehicle?->total_trip > 0 ? $detail?->vehicle?->decrement('total_trip',$detail->quantity) : '';
        }

        Helpers::increment_order_count($trip->provider);
        return response()->json(['message' => translate('Trip_successfully_canceled')], 200);

    }

    public function makePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
            'trip_id' => 'required',
            'payment_method' => 'required|in:cash_payment,wallet,partial_payment,digital_payment',
            'payment_gateway' => 'required_if:payment_method,digital_payment,partial_payment',
            'callback_url' => 'required_if:payment_method,digital_payment',
            'payment_platform' => 'required_if:payment_method,digital_payment',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $this->helpers->error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $trip = $this->trips->where([
            'user_id' => $user_id,
            'is_guest' => $is_guest,
            'id' => $request->trip_id,
            ])->first();

            if (!$trip) {
                return response()->json(['message' => translate('trip_data_not_found')], 404);
            }

            if ($is_guest && in_array($request->payment_method, ['wallet', 'partial_payment'])) {
                return response()->json(['message' => translate('This_payment_method_is_not_available_for_guest_users')], 403);
            }

            if($trip->payment_status == 'paid'){
                return response()->json(['message' => translate('This_trip_is_already_paid')], 403);
            }
            $user = $request->user ? $request->user : $this->getGuestUserDetails($trip, $user_id);

        switch ($request->payment_method) {
            case 'cash_payment':
                $this->processCashPayment($trip);
                break;

            case 'digital_payment':
                return $this->processDigitalPayment($trip, $user, $request);

            case 'wallet':
                if ($user->wallet_balance < $trip->trip_amount) {
                    return response()->json(['message' => translate('insufficient_balance')], 403);
                }
                $this->processWalletPayment($trip);
                break;

            case 'partial_payment':
                if ($user->wallet_balance > $trip->trip_amount) {
                    return response()->json(['message' => translate('trip_amount_must_be_greater_than_wallet_amount')], 403);
                }
                if ($user->wallet_balance <= 0) {
                    return response()->json(['message' => translate('insufficient_balance_for_partial_amount')], 403);
                }
                return $this->processPartialPayment($trip, $user, $request);

            default:
                return response()->json(['message' => translate('something_went_wrong')], 403);
        }

        if ($trip->trip_status == 'completed' && $trip->payment_status == 'paid' && !$trip->trip_transaction) {
            if ($this->create_transaction($trip, 'vendor') === false) {
                return response()->json(['message' => translate('Failed_to_create_Transaction')], 403);
            };
        }
        return response()->json(['message' => translate('payment_successful')], 200);
    }




    private function getGuestUserDetails($trip, $user_id)
    {
        $address = $trip['user_info'];
        return collect([
            'id' => $user_id,
            'f_name' => $address['contact_person_name'] ?? '',
            'l_name' => '',
            'phone' => $address['contact_person_number'] ?? '',
            'email' => $address['contact_person_email'] ?? '',
        ]);
    }

    private function processCashPayment($trip)
    {
        $trip->update([
            'payment_status' => 'paid',
            'payment_method' => 'cash_payment',
        ]);
        return true;
    }


    private function processDigitalPayment($trip, $user, $request)
    {
        return response()->json($this->digitalPayment($trip,$user,$request->payment_gateway,$request->callback_url,$request->payment_platform),200);
    }

    private function processWalletPayment($trip)
    {
        CustomerLogic::create_wallet_transaction($trip->user_id, $trip->trip_amount, 'trip_booking', $trip->id);
        $trip->update([
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
        ]);
        return true;
    }


    private function processPartialPayment($trip, $user, $request)
    {
        $paid_amount = min($user->wallet_balance, $trip->trip_amount);
        $unpaid_amount = $trip->trip_amount - $paid_amount;

        $trip->update([
            'partially_paid_amount' => $paid_amount,
            'payment_method' => 'partial_payment',
            'payment_status' => 'paid',
        ]);

        CustomerLogic::create_wallet_transaction($trip->user_id, $paid_amount, 'partial_payment', $trip->id);
        $this->create_order_payment(trip_id: $trip->id, amount: $paid_amount, payment_status: 'paid', payment_method: 'wallet');
        $this->create_order_payment(trip_id: $trip->id, amount: $unpaid_amount, payment_status: 'unpaid', payment_method: $request->payment_gateway);

        if ($request->payment_gateway !== 'cash_payment') {
            return $this->processDigitalPayment($trip, $user, $request);
        }
        return true;
    }


    private function create_order_payment($trip_id,$amount,$payment_status,$payment_method){
        $payment = new PartialPayment();
        $payment->trip_id = $trip_id;
        $payment->amount = $amount;
        $payment->payment_status = $payment_status;
        $payment->payment_method = $payment_method;
        $payment->save();
        return true;
    }

    private function digitalPayment($trip,$user,$payment_gateway,$url,$payment_platform='web'){

        $payer = new Payer(
            (string) data_get($user,'f_name',''),
            (string) data_get($user,'email',''),
             (string)data_get($user,'phone',''),
            ''
        );

        $store_logo= BusinessSetting::where(['key' => 'logo'])->first();
        $additional_data = [
            'business_name' => BusinessSetting::where(['key'=>'business_name'])->first()?->value,
            'business_logo' => $this->helpers->get_full_url('business',$store_logo?->value,$store_logo?->storage[0]?->value ?? 'public')
        ];

        $payment_info = new PaymentInfo(
            success_hook: 'trip_payment_success',
            failure_hook: 'trip_payment_fail',
            currency_code: Helpers::currency_code(),
            payment_method: $payment_gateway,
            payment_platform: $payment_platform,
            payer_id: $trip->user_id,
            receiver_id:  1,
            additional_data: $additional_data,
            payment_amount: $trip->trip_amount- $trip->partially_paid_amount ,
            external_redirect_link: $url,
            attribute: 'trip_booking',
            attribute_id: $trip->id,
        );
        $receiver_info = new Receiver('Admin','example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }



}
