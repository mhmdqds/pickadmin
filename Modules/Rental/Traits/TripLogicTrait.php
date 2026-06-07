<?php

namespace Modules\Rental\Traits;

use App\Models\User;
use App\Models\Admin;
use App\Models\Store;
use App\Models\Coupon;
use App\Models\Vendor;
use App\Models\Expense;
use App\Models\AdminWallet;
use App\Models\StoreWallet;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\CentralLogics\OrderLogic;
use App\CentralLogics\CouponLogic;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Entities\Trips;
use App\CentralLogics\CustomerLogic;
use Illuminate\Support\Facades\Mail;
use Modules\Rental\Entities\PartialPayment;
use Modules\Rental\Entities\TripTransaction;


trait TripLogicTrait
{

    public static function create_transaction($trip, $received_by = false, $status = null)
    {
        $amount_admin = 0;
        $store_d_amount = 0;
        $admin_coupon_discount_subsidy = 0;
        $store_coupon_discount_subsidy = 0;
        $discount_on_trip = 0;
        $comission_on_store_amount = 0;
        $ref_bonus_amount = 0;


        $provider = $trip?->provider;
        $store_sub = $provider?->store_sub;
        DB::beginTransaction();
        // coupon discount by Admin
        if ($trip->coupon_discount_by == 'admin') {
            $admin_coupon_discount_subsidy = $trip->coupon_discount_amount;
            self::tripExpenseCreate(amount: $admin_coupon_discount_subsidy, type: 'coupon_discount', datetime: now(), created_by: $trip->coupon_discount_by, trip_id: $trip->id);
        }
        // 1st order discount by Admin
        if ($trip->ref_bonus_amount > 0) {
            $ref_bonus_amount = $trip->ref_bonus_amount;
            self::tripExpenseCreate(amount: $ref_bonus_amount, type: 'referral_discount', datetime: now(), created_by: 'admin', trip_id: $trip->id);
        }
        // coupon discount by store
        if ($trip->coupon_discount_by == 'vendor') {
            $store_coupon_discount_subsidy = $trip->coupon_discount_amount;
            self::tripExpenseCreate(amount: $store_coupon_discount_subsidy, type: 'coupon_discount', datetime: now(), created_by: $trip->coupon_discount_by, trip_id: $trip->id, store_id: $provider->id);
        }

        if ($trip?->cashback_history) {
            self::cashbackToWallet($trip);
        }

        $comission =   $provider?->comission ??  BusinessSetting::where('key', 'admin_commission')->first()->value;


        if ($trip->discount_on_trip > 0  && $trip->discount_on_trip_by == 'vendor') {
            if ($provider->store_business_model == 'subscription' && isset($store_sub)) {
                $store_d_amount =  $trip->discount_on_trip;
                self::tripExpenseCreate(amount: $store_d_amount, type: 'discount_on_trip', datetime: now(), created_by: 'vendor', trip_id: $trip->id, store_id: $trip->provider->id);
            } else {
                $amount_admin = $comission ? ($trip->discount_on_trip / 100) * $comission : 0;
                $store_d_amount =  $trip->discount_on_trip - $amount_admin;
                self::tripExpenseCreate(amount: $store_d_amount, type: 'discount_on_trip', datetime: now(), created_by: 'vendor', trip_id: $trip->id, store_id: $trip->provider->id);
                self::tripExpenseCreate(amount: $amount_admin, type: 'discount_on_trip', datetime: now(), created_by: 'admin', trip_id: $trip->id);
            }
        }

        if ($trip->discount_on_trip > 0  && $trip->discount_on_trip_by == 'admin') {
            $discount_on_trip = $trip->discount_on_trip;
            self::tripExpenseCreate(amount: $discount_on_trip, type: 'discount_on_trip', datetime: now(), created_by: 'admin', trip_id: $trip->id);
        }



        $trip_amount = $trip->trip_amount - $trip->additional_charge  -  $trip->tax_amount   + $trip->coupon_discount_amount + $discount_on_trip  + $ref_bonus_amount+$store_d_amount+$amount_admin;


        //final comission
        if ($provider->store_business_model == 'subscription' && isset($store_sub)) {
            $comission_on_store_amount = 0;
            $subscription_mode = 1;
            $commission_percentage = 0;
        } else {
            $comission_on_store_amount = ($comission ? ($trip_amount / 100) * $comission : 0);
            $subscription_mode = 0;
            $commission_percentage = $comission;
        }

        $comission_amount = $comission_on_store_amount;

        $store_amount = $trip_amount + $trip->tax_amount  - $comission_on_store_amount - $store_coupon_discount_subsidy - $store_d_amount;
        try {
            TripTransaction::insert([
                'vendor_id' => $provider->vendor->id,
                'provider_id' => $provider->id,
                'trip_id' => $trip->id,
                'trip_amount' => $trip->trip_amount,
                'store_amount' => $store_amount,
                'admin_commission' => $comission_amount,
                'admin_expense' => $admin_coupon_discount_subsidy + $discount_on_trip + $amount_admin + $ref_bonus_amount,
                'tax' => $trip->tax_amount,
                'received_by' => $received_by ? $received_by : 'admin',
                'zone_id' => $trip->zone_id,
                'module_id' => $trip->module_id,
                'store_expense' =>  $store_coupon_discount_subsidy + $store_d_amount,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
                'discount_amount_by_store' => $store_coupon_discount_subsidy + $store_d_amount,
                'additional_charge' => $trip->additional_charge,
                'ref_bonus_amount' => $trip->ref_bonus_amount,
                // for store business model
                'is_subscribed' => $subscription_mode,
                'commission_percentage' => $commission_percentage,
                'admin_net_income' =>$comission_amount + $trip->additional_charge -($admin_coupon_discount_subsidy + $ref_bonus_amount + $discount_on_trip + $amount_admin)
            ]);
            $adminWallet = AdminWallet::firstOrNew(
                ['admin_id' => Admin::where('role_id', 1)->first()->id]
            );

            $adminWallet->total_commission_earning = $adminWallet->total_commission_earning + $comission_amount + $trip->additional_charge - $admin_coupon_discount_subsidy - $discount_on_trip  - $ref_bonus_amount;

            $vendorWallet = StoreWallet::firstOrNew(
                ['vendor_id' => $provider->vendor->id]
            );
            $vendorWallet->total_earning = $vendorWallet->total_earning + $store_amount;



            $unpaid_payment = PartialPayment::where('payment_status', 'unpaid')->where('trip_id', $trip->id)->first()?->payment_method;
            $unpaid_pay_method = 'digital_payment';
            if ($unpaid_payment) {
                $unpaid_pay_method = $unpaid_payment;
            }

            if ($received_by == 'admin') {
                $adminWallet->digital_received = $adminWallet->digital_received + ($trip->trip_amount - $trip->partially_paid_amount);
            } else if ($received_by == 'vendor' &&  ($trip->payment_method == "cash_payment" || $unpaid_pay_method == 'cash_payment')) {
                $store_over_flow =  true;
                $vendorWallet->collected_cash = $vendorWallet->collected_cash + ($trip->trip_amount - $trip->partially_paid_amount);
            } else if ($received_by == false) {
                $adminWallet->manual_received = $adminWallet->manual_received + ($trip->trip_amount - $trip->partially_paid_amount);
            }


            $adminWallet->save();
            $vendorWallet->save();


            if (isset($store_over_flow)) {
                self::create_account_transaction_for_collect_cash(old_collected_cash: $vendorWallet->collected_cash, from_type: 'store', from_id: $provider->vendor->id, amount: $trip->trip_amount - $trip->partially_paid_amount, trip_id: $trip->id);
            }


            OrderLogic::update_unpaid_trip_payment(trip_id: $trip->id, payment_method: $trip->payment_method);

            DB::commit();

            if ($trip->is_guest  == 0) {
                $ref_status = BusinessSetting::where('key', 'ref_earning_status')->first()->value;
                if (isset($trip->customer->ref_by) && $trip->customer->order_count == 0  && $ref_status == 1) {
                    $ref_code_exchange_amt = BusinessSetting::where('key', 'ref_earning_exchange_rate')->first()->value;
                    $referar_user = User::where('id', $trip->customer->ref_by)->first();
                    $refer_wallet_transaction = CustomerLogic::create_wallet_transaction($referar_user->id, $ref_code_exchange_amt, 'referrer', $trip->customer->phone);

                    $notification_data = [
                        'title' => translate('messages.Congratulation'),
                        'description' => translate('You have received') . ' ' . Helpers::format_currency($ref_code_exchange_amt) . ' ' . translate('in your wallet as') . ' ' . $trip?->customer?->f_name . ' ' . $trip?->customer?->l_name . ' ' . translate('you referred completed thier first order'),
                        'trip_id' => 1,
                        'image' => '',
                        'type' => 'referral_code',
                    ];

                    if(Helpers::getNotificationStatusData('customer','customer_referral_bonus_earning','push_notification_status') && $referar_user?->cm_firebase_token){
                        Helpers::send_push_notif_to_device($referar_user?->cm_firebase_token, $notification_data);
                        DB::table('user_notifications')->insert([
                            'data' => json_encode($notification_data),
                            'user_id' => $referar_user?->id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }


                    try {
                        Helpers::add_fund_push_notification($referar_user->id);
                        if(config('mail.status') && Helpers::get_mail_status('add_fund_mail_status_user') == '1' && Helpers::getNotificationStatusData('customer','customer_add_fund_to_wallet','mail_status') ) {
                            Mail::to($referar_user->email)->send(new \App\Mail\AddFundToWallet($refer_wallet_transaction));
                        }
                    } catch (\Exception $ex) {
                        info($ex->getMessage());
                    }
                }

                $create_loyalty_point_transaction = CustomerLogic::create_loyalty_point_transaction($trip->user_id, $trip->id, $trip->trip_amount, 'trip_booking');
                if ($create_loyalty_point_transaction > 0) {
                    $notification_data = [
                        'title' => translate('messages.Congratulation'),
                        'description' => translate('You_have_received') . ' ' . $create_loyalty_point_transaction . ' ' . translate('points_as_loyalty_point'),
                        'trip_id' => $trip->id,
                        'image' => '',
                        'type' => 'loyalty_point',
                    ];

                    if(Helpers::getNotificationStatusData('customer','customer_loyalty_point_earning','push_notification_status') && $trip->customer?->cm_firebase_token){
                        Helpers::send_push_notif_to_device($trip->customer?->cm_firebase_token, $notification_data);
                        DB::table('user_notifications')->insert([
                            'data' => json_encode($notification_data),
                            'user_id' => $trip->user_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            info($e);
            return false;
        }
        return true;
    }

    public static function tripExpenseCreate($amount, $type, $datetime, $created_by, $trip_id = null, $store_id = null, $description = '', $delivery_man_id = null, $user_id = null)
    {
        $expense = new Expense();
        $expense->amount = $amount;
        $expense->type = $type;
        $expense->trip_id = $trip_id;
        $expense->created_by = $created_by;
        $expense->store_id = $store_id;
        $expense->delivery_man_id = $delivery_man_id;
        $expense->user_id = $user_id;
        $expense->description = $description;
        $expense->created_at = now();
        $expense->updated_at = now();
        return $expense->save();
    }

    public static function create_account_transaction_for_collect_cash($old_collected_cash, $from_type, $from_id, $amount, $trip_id)
    {
        $account_transaction = new AccountTransaction();
        $account_transaction->from_type = $from_type;
        $account_transaction->from_id = $from_id;
        $account_transaction->created_by = $from_type;
        $account_transaction->method = 'cash_collection';
        $account_transaction->ref = $trip_id;
        $account_transaction->amount = $amount ?? 0;
        $account_transaction->current_balance = $old_collected_cash ?? 0;
        $account_transaction->type = 'cash_in';
        $account_transaction->save();

        if ($from_type  ==  'store') {
            $vendor = Vendor::find($from_id);
            $Payable_Balance = $vendor?->wallet?->collected_cash   > 0 ? 1 : 0;
            $cash_in_hand_overflow = BusinessSetting::where('key', 'cash_in_hand_overflow_store')->first()?->value;
            $cash_in_hand_overflow_store_amount = BusinessSetting::where('key', 'cash_in_hand_overflow_store_amount')->first()?->value;

            if ($Payable_Balance == 1 &&  $cash_in_hand_overflow && $vendor?->wallet?->balance < 0 &&  $cash_in_hand_overflow_store_amount <= abs($vendor?->wallet?->collected_cash)) {
                $rest = Store::where('vendor_id', $vendor->id)->first();
                $rest->status = 0;
                $rest->save();
            }
        }
        return true;
    }

    public static function cashbackToWallet($trip)
    {

        $refer_wallet_transaction = CustomerLogic::create_wallet_transaction($trip?->cashback_history?->user_id, $trip?->cashback_history?->calculated_amount, 'CashBack', $trip->id);
        if ($refer_wallet_transaction != false) {
            self::tripExpenseCreate(amount: $trip?->cashback_history?->calculated_amount, type: 'CashBack', datetime: now(), created_by: 'admin', trip_id: $trip->id);
            $trip?->cashback_history?->cashBack?->increment('total_used');

            $notification_data = [
                'title' => translate('messages.Congratulation_you_have_received').' '.$trip?->cashback_history?->calculated_amount.' '.translate('cashback'),
                'description' => translate('The_cashback_amount_successfully_added_to_your_wallet') ,
                'trip_id' => $trip->id,
                'image' => '',
                'type' => 'cashback',
            ];

            if($trip->customer?->cm_firebase_token && Helpers::getNotificationStatusData('customer','customer_cashback','push_notification_status')){
                Helpers::send_push_notif_to_device($trip->customer?->cm_firebase_token, $notification_data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($notification_data),
                    'user_id' => $trip->customer?->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

        }

        return true;
    }

    public static function getUpdatedTrip($request, Trips $trip, array $data, $isUpdated=true): Trips|array
    {
        $totalPrice = 0;
        $quantity = 0;
        $discountOnTrip = 0;
        $providerTax = $request->vendor ? $request?->vendor?->stores[0]->tax : $trip?->provider?->tax;
        $provider = $request->vendor ? $request?->vendor?->stores[0] : $trip?->provider;

        foreach ($trip->trip_details as $tripDetail) {
            if (!$tripDetail->vehicle) {
                return ['errors' => translate('vehicle_is_deleted'), 'code' => 403];
            }
        }
        $details=[];
        foreach ($trip->trip_details as $key=> $tripDetail) {
            $tripDetailData = self::calculateTripDetailPricing(
                $tripDetail,
                $data['vehicleQuantities'],
                $data['modifiedPrices'],
                $data['estimatedHours'],
                $data['distance'],
                $trip->trip_type,
                $providerTax,
                $data['quantityUpdate']?? false,
                $data['updateDistance']?? false,
            );

            $totalPrice += $tripDetailData['calculatedPrice'];
            $discountOnTrip += $tripDetailData['discount'] * $tripDetailData['quantity'];
            $quantity += $tripDetailData['quantity'];


            $details[$key]['id'] = $tripDetail->id;
            $details[$key]['calculated_price'] = round($tripDetailData['calculatedPrice'], config('round_up_to_digit'));
            $details[$key]['quantity'] = round($tripDetailData['quantity'], config('round_up_to_digit'));
            $details[$key]['originalPrice'] =  round($tripDetailData['originalPrice'], config('round_up_to_digit'));

            if ($isUpdated) {
                self::updateTripDetail($tripDetail, $tripDetailData, $data);
            }

        }

        $providerDiscount = self::applyProviderDiscount(
            $provider,
            $trip,
            $totalPrice,
            $isUpdated,
            $discountOnTrip
        );

        $finalPricing = self::calculateFinalPricing(
            $trip,
            $totalPrice,
            $providerDiscount['isAdminDiscount'] == true  ? $providerDiscount['discount'] : $discountOnTrip,
            $providerTax,
        );

        if ($isUpdated) {
            self::updateCashback($trip, $finalPricing['tripAmount']);

            self::updateMainTrip(
                $trip,
                $data,
                $finalPricing,
                $quantity,
                $providerDiscount['isAdminDiscount'] ?? false,
                count($data['modifiedPrices']) > 0
            );
            return $trip;
        }else{
            $finalPricing['details']=$details;
            return $finalPricing;
        }

    }

    public static function calculateTripDetailPricing($tripDetail, $vehicleQuantities, $modifiedPrices, $estimatedHours, $distance, $rentalType, $taxPercentage,$quantityUpdate=false,$updateDistance=false): array
    {
        $quantity = $vehicleQuantities[$tripDetail->vehicle_id] ?? $tripDetail->quantity;
        $originalPrice = $rentalType === 'hourly'
            ? $tripDetail->vehicle->hourly_price * $estimatedHours
            : $tripDetail->vehicle->distance_price * $distance;

            if($updateDistance == 1){
                $price = $originalPrice* $quantity;
            } else{
                $price = $modifiedPrices[$tripDetail->vehicle_id] ?? $originalPrice* $quantity;
                $price = ($tripDetail->vehicle_id ==  $quantityUpdate) ? $originalPrice *$quantity : $price;
                    if($rentalType != 'hourly' &&  $tripDetail->distance != $distance){
                        if($modifiedPrices[$tripDetail->vehicle_id] ==  $originalPrice* $quantity){
                            $price = $originalPrice* $quantity;
                        } else{
                            $price =$price;
                        }
                    }
            }

        $discountData = self::getDiscount(
                    price: $originalPrice,
                    discount_type: $tripDetail->vehicle->discount_type,
                    discount: $tripDetail->vehicle->discount_price
                );

        $calculatedPrice = $price  == $originalPrice ? $originalPrice * $quantity : $price;



        $discountData['discount']=  ($originalPrice * $quantity) == $price ?  $discountData['discount'] : 0;

        return [
            'quantity' => $quantity,
            'price' => round($price, config('round_up_to_digit')),
            'originalPrice' => round($originalPrice, config('round_up_to_digit')),
            'calculatedPrice' => $calculatedPrice,
            'discount' => $discountData['discount'],
            'discountPercentage' => $tripDetail->vehicle->discount_type === 'amount' ? 0 : $tripDetail->vehicle->discount_price,
            'taxAmount' => round(
                Helpers::product_tax($price - $discountData['discount'], $taxPercentage, self::taxIncluded()),
                config('round_up_to_digit')
            )
        ];
    }


    public static function calculateFinalPricing($trip, $totalPrice, $discount, $taxPercentage): array
    {
        $price = $totalPrice - $discount;

        $couponDiscount = self::calculateCouponDiscount($trip, $price);
        $couponDiscount =Helpers::minDiscountCheck(productPrice: $price, discount: $couponDiscount)['discount_applied'];

        $price -= $couponDiscount;

        $refBonus = self::calculateReferralBonus($trip, $price);
        $refBonus = Helpers::minDiscountCheck(productPrice: $price, discount: $refBonus)['discount_applied'];

        $finalPrice = max(0,$price - $refBonus);

        $calculatedTax = Helpers::product_tax($finalPrice, $taxPercentage, self::taxIncluded());
        $taxAmount = self::taxIncluded() ? 0 : $calculatedTax;

        return [
            'subTotal' => $totalPrice,
            'tripAmount' => max(0, $finalPrice + $taxAmount + self::getAdditionalCharge()),
            'discount' => $discount,
            'couponDiscount' => $couponDiscount,
            'refBonus' => $refBonus,
            'taxAmount' => $calculatedTax,
            'additionalCharge' => self::getAdditionalCharge()
        ];
    }

    public static function calculateCouponDiscount($trip, $price): float
    {
        if (!$trip->coupon_code || $trip->coupon_discount_amount <= 0) {
            return 0;
        }

        $coupon = Coupon::where(['code' => $trip->coupon_code])->first();
        return isset($coupon) ? CouponLogic::get_discount($coupon, $price) : $trip->coupon_discount_amount;
    }

    public static function calculateReferralBonus($trip, $price): float
    {
        if ($trip->ref_bonus_amount <= 0) {
            return 0;
        }

        $discountData = self::getCusromerFirstOrderDiscount($price);
        return data_get($discountData, 'calculated_amount') ?? $trip->ref_bonus_amount;
    }

    public static function getAdditionalCharge(): float
    {
        if (BusinessSetting::where('key', 'additional_charge_status')->first()?->value != 1) {
            return 0;
        }
        return BusinessSetting::where('key', 'additional_charge')->first()?->value ?? 0;
    }

    public static function getDiscount($price, $discount_type, $discount)
    {
        if ($price > 0 &&  $discount > 0) {
            $discount =  $discount_type == 'percent' ? ($price * $discount) / 100 :  $discount;
        }
        return ['price' => $price, 'discount' => $discount ?? 0];
    }

    public  static function checkAdminDiscount($price, $discount, $max_discount, $min_purchase, $vehicle_wise_price = null)
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
    public  static function couponCheck($request)
    {

        $coupon = Coupon::active()->where(['code' => $request['coupon_code']])->first();
        if (isset($coupon)) {


            if ($request->is_guest) {
                $staus = CouponLogic::is_valid_for_guest($coupon, $request['provider_id']);
            } else {
                $staus = CouponLogic::is_valide($coupon, $request->user->id, $request['provider_id']);
            }

            $message = match ($staus) {
                407 => translate('messages.coupon_expire'),
                408 => translate('messages.You_are_not_eligible_for_this_coupon'),
                406 => translate('messages.coupon_usage_limit_over'),
                404 => translate('messages.coupon_not_found'),
                default => null,
            };
            if ($message != null) {
                return ['code' => 'coupon', 'message' => $message, 'status_code' => $staus];
            }
            if ($coupon->coupon_type == 'free_delivery') {
                return ['code' => 'coupon', 'message' => translate('messages.invalid_coupon'), 'status_code' => 403];
            }

            $coupon->increment('total_uses');
            $coupon_discount_by = $coupon->created_by;

            return ['coupon' => $coupon, 'coupon_discount_by' => $coupon_discount_by];
        } else {
            return ['code' => 'coupon', 'message' => translate('messages.coupon_not_found'), 'status_code' => 404];
        }
    }


    public static function getCusromerFirstOrderDiscount($price = null)
    {
        $settings =  array_column(BusinessSetting::whereIn('key', ['new_customer_discount_amount', 'new_customer_discount_amount_type'])->get()->toArray(), 'value', 'key');
        $calculated_amount = 0;
        if (data_get($settings, 'new_customer_discount_amount') > 0) {
            if (data_get($settings, 'new_customer_discount_amount_type') == 'percentage' && isset($price)) {
                $calculated_amount = ($price / 100) * data_get($settings, 'new_customer_discount_amount');
            } else {
                $calculated_amount = data_get($settings, 'new_customer_discount_amount');
            }
        }
        return round($calculated_amount, config('round_up_to_digit'));
    }
    public static function taxIncluded(): bool
    {
        return BusinessSetting::where('key', 'tax_included')->value('value') ?? 0;
    }
    public static function taxStatus(): string
    {
        return self::taxIncluded() ? 'included' : 'excluded';
    }


    public static function applyProviderDiscount($store, Trips $trip, float $totalPrice, $isUpdated, $discountOnTrip): array
    {
        $providerDiscount = Helpers::get_store_discount($store);
        if (!$providerDiscount) {
            return [
                'discount' => 0,
                'isAdminDiscount' => false
            ];
        }

        $adminDiscount = self::checkAdminDiscount(
            price: $totalPrice,
            discount: $providerDiscount['discount'],
            max_discount: $providerDiscount['max_discount'],
            min_purchase: $providerDiscount['min_purchase']
        );

        if ($adminDiscount <= 0) {
            return [
                'discount' => 0,
                'isAdminDiscount' => false
            ];
        }


        $discount = max($discountOnTrip,$adminDiscount);
        if($adminDiscount > 0 &&  $discount == $adminDiscount )  {
            if($isUpdated){
                self::updateAdminDiscountAmount($trip, $totalPrice, $providerDiscount);
            }
            return [
                'discount' => $adminDiscount,
                'isAdminDiscount' => true
            ];
        }

        return [
            'discount' => 0,
            'isAdminDiscount' => false
        ];
    }


    public static function updateCashback($trip, $tripAmount): void
    {
        if (!$trip?->cashback_history) {
            return;
        }

        $cashBack = Helpers::getCalculatedCashBackAmount(
            amount: $tripAmount,
            customer_id: $trip->user_id,
            type: 'rental'
        );

        if (data_get($cashBack, 'calculated_amount') > 0) {
            $trip->cashback_history->fill([
                'calculated_amount' => data_get($cashBack, 'calculated_amount'),
                'cashback_amount' => data_get($cashBack, 'cashback_amount'),
                'cash_back_id' => data_get($cashBack, 'id'),
                'cashback_type' => data_get($cashBack, 'cashback_type'),
                'min_purchase' => data_get($cashBack, 'min_purchase'),
                'max_discount' => data_get($cashBack, 'max_discount'),
            ])->save();
        }
    }

    public static function updateMainTrip($trip, $data, $pricing, $quantity, $isAdminDiscount, $hasModifiedPrices): void
    {
        $trip->fill([
            'edited' => 1,
            'trip_amount' => $pricing['tripAmount'],
            'discount_on_trip' => $pricing['discount'],
            'discount_on_trip_by' => $isAdminDiscount ? 'admin' :  'vendor',
            'coupon_discount_amount' => $pricing['couponDiscount'],
            'tax_amount' => $pricing['taxAmount'],
            'tax_status' => self::taxStatus(),
            'tax_percentage' => $data['taxPercentage'],
            'additional_charge' =>  self::getAdditionalCharge(),
            'distance' => $data['distance'],
            'estimated_hours' => $data['estimatedHours'],
            'scheduled' => $data['scheduled'],
            'schedule_at' => $data['scheduleAt'],
            'quantity' => $quantity,
            'estimated_trip_end_time' => $data['estimatedTripEndTime'],
            'destination_location' => $data['destinationLocation'],
            'pickup_location' => $data['pickupLocation'],
        ])->save();
    }

    public static function updateTripDetail($tripDetail, $pricingData, $tripData): void
    {
        $tripDetail->fill([
            'quantity' => $pricingData['quantity'],

            'price' => $pricingData['originalPrice'] *$pricingData['quantity'] ,
            // unit price * hour or distacne * quantity


            'original_price' => $pricingData['originalPrice'],
            // unit price * hour or distacne


            'calculated_price' => $pricingData['calculatedPrice'],
            //  unit price  * hour or distacne * quantity

            'discount_on_trip' => $pricingData['discount'],
            'discount_percentage' => $pricingData['discountPercentage'],
            'tax_amount' => $pricingData['taxAmount'],
            'tax_status' => self::taxStatus(),
            'vehicle_details' => json_encode($tripDetail->vehicle),
            'estimated_hours' => $tripData['estimatedHours'],
            'distance' => $tripData['distance'],
            'scheduled' => $tripData['scheduled'],
            'schedule_at' => $tripData['scheduleAt'],
            'estimated_trip_end_time' => $tripData['estimatedTripEndTime'],
            'is_edited' => 1,
        ])->save();
    }

    public static function updateAdminDiscountAmount($trip, $totalPrice, $providerDiscount)
    {

        if($providerDiscount['discount'] >  0){
            foreach ($trip->trip_details as $tripDetail) {
                $itemDiscount = self::checkAdminDiscount(
                    price: $totalPrice,
                    discount: $providerDiscount['discount'],
                    max_discount: $providerDiscount['max_discount'],
                    min_purchase: $providerDiscount['min_purchase'],
                    vehicle_wise_price: $tripDetail->price
                );

                $tripDetail->fill([
                    'discount_on_trip_by' => 'admin',
                    'discount_type' => 'percentage',
                    'discount_percentage' => $providerDiscount['discount'],
                    'discount_on_trip' => $itemDiscount,

                    'tax_amount' => round(
                        Helpers::product_tax(
                            $tripDetail->price - $itemDiscount,
                            $tripDetail->tax_percentage,
                            self::taxIncluded()
                        ),
                        config('round_up_to_digit')
                    ),
                    ])->save();
                }
            }
            return true;
    }

}
