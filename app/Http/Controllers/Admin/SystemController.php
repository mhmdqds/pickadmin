<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BusinessSetting;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Modules\Rental\Entities\Trips;

class SystemController extends Controller
{

    public function store_data()
    {
        // Check for cancelled orders first (higher priority)
        $cancelled_order = Order::where(['checked' => 0])
            ->where('order_status', 'canceled')
            ->latest()
            ->first(['id', 'module_id', 'order_type', 'zone_id']);

        if ($cancelled_order) {
            return response()->json([
                'success' => 1,
                'data' => [
                    'new_order' => 0,
                    'new_cancelled_order' => 1,
                    'cancelled_order_id' => $cancelled_order->id,
                    'type' => $cancelled_order->order_type ?? 'store_order',
                    'module_id' => $cancelled_order->module_id ?? 0,
                    'zone_id' => $cancelled_order->zone_id ?? 0,
                ]
            ]);
        }

        $new_order = 0;
        $type = 'store_order';
        $module_id = 0;
        $order_id = null;

        if(Order::StoreOrder()->where(['checked' => 0])->count() > 0 ){
            $new_order = 1;
            $type = 'store_order';
            $latestOrder = Order::StoreOrder()->where(['checked' => 0])->latest()->first(['id', 'module_id']);
            $module_id = $latestOrder->module_id ?? 0;
            $order_id = $latestOrder->id ?? null;
        }
        elseif(Order::ParcelOrder()->where(['checked' => 0])->count() > 0 ){
            $new_order = 1;
            $type = 'parcel';
            $latestOrder = Order::ParcelOrder()->where(['checked' => 0])->latest()->first(['id', 'module_id']);
            $module_id = $latestOrder->module_id ?? 0;
            $order_id = $latestOrder->id ?? null;
        }
        elseif(addon_published_status('Rental') && Trips::where(['checked' => 0])->count() > 0 ){
            $new_order = 1;
            $type = 'trip';
            $latestOrder = Trips::where(['checked' => 0])->latest()->first(['id', 'module_id']);
            $module_id = $latestOrder->module_id ?? 0;
            $order_id = $latestOrder->id ?? null;
        }

        return response()->json([
            'success' => 1,
            'data' => [
                'new_order' => $new_order,
                'type' => $type,
                'module_id' => $module_id,
                'order_id' => $order_id,
            ]
        ]);
    }

    public function markOrderChecked($id)
    {
        Order::where('id', $id)->update(['checked' => 1]);
        return response()->json(['success' => true]);
    }

    /**
     * Confirm an order from notification popup
     * Changes order status from pending to confirmed
     */
    public function confirmOrderFromNotification($id)
    {
        try {
            $order = Order::find($id);
            
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }
            
            // Only confirm if order is in pending status
            if ($order->order_status !== 'pending') {
                return response()->json([
                    'success' => true, 
                    'message' => 'Order already ' . $order->order_status,
                    'order_status' => $order->order_status
                ]);
            }
            
            // Update order status to confirmed
            $order->order_status = 'confirmed';
            $order->confirmed = now();
            $order->checked = 1;
            $order->save();
            
            // Send notification to customer
            $fcm_token = $order->is_guest == 0 ? $order?->customer?->cm_firebase_token : $order?->guest?->fcm_token;
            $value = Helpers::order_status_update_message('confirmed', $order->module?->module_type, $order->customer?->current_language_key ?? 'en');
            $value = Helpers::text_variable_data_format(
                value: $value,
                store_name: $order->store?->name,
                order_id: $order->id,
                user_name: "{$order?->customer?->f_name} {$order?->customer?->l_name}",
                delivery_man_name: "{$order->delivery_man?->f_name} {$order->delivery_man?->l_name}"
            );
            
            try {
                if ($value && Helpers::getNotificationStatusData('customer', 'customer_order_notification', 'push_notification_status') && $fcm_token) {
                    $data = [
                        'title' => translate('Order_Notification'),
                        'description' => $value,
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'order_status'
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'user_id' => $order?->customer?->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            } catch (\Exception $e) {
                info($e->getMessage());
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Order confirmed successfully',
                'order_status' => 'confirmed'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function settings()
    {
        return view('admin-views.settings');
    }

    public function settings_update(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:admins,email,' . auth('admin')->id(),
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:admins,phone,' . auth('admin')->id(),
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'l_name.required' => translate('messages.Last name is required!'),
        ]);

        $admin = Admin::find(auth('admin')->id());

        if ($request->has('image')) {
            $image_name = Helpers::update('admin/', $admin->image, 'png', $request->file('image'));
        } else {
            $image_name = $admin['image'];
        }


        $admin->f_name = $request->f_name;
        $admin->l_name = $request->l_name;

        if($admin->email != $request->email){
            $login_remember_token= Str::random(60);
            $admin->login_remember_token =  $login_remember_token;
            session(['login_remember_token' => $login_remember_token]);
        }
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->image = $image_name;
        $admin->save();
        Toastr::success(translate('messages.admin_updated_successfully'));
        return back();
    }

    public function settings_password_update(Request $request)
    {
        $request->validate([
            'password' => ['required','same:confirm_password', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'confirm_password' => 'required',
        ]);

        $admin = Admin::find(auth('admin')->id());
        $admin->password = bcrypt($request['password']);
        $login_remember_token= Str::random(60);
        $admin->login_remember_token =  $login_remember_token;
        $admin->save();
        session(['login_remember_token' => $login_remember_token]);
        Toastr::success(translate('messages.admin_password_updated_successfully'));
        return back();
    }

    public function maintenance_mode()
    {
        $maintenance_mode = BusinessSetting::where('key', 'maintenance_mode')->first();
        if (isset($maintenance_mode) == false) {
            Helpers::businessInsert([
                'key' => 'maintenance_mode',
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            Helpers::businessUpdateOrInsert(['key' => 'maintenance_mode'], [
                'value' => $maintenance_mode->value == 1 ? 0 : 1
            ]);
        }

        if (isset($maintenance_mode) && $maintenance_mode->value) {
            return response()->json(['message' => translate('Maintenance is off.')]);
        }
        return response()->json(['message' => translate('Maintenance is on.')]);
    }

    public function landing_page()
    {
        $landing_page = BusinessSetting::where('key', 'landing_page')->first();
        if (isset($landing_page) == false) {
            Helpers::businessInsert([
                'key' => 'landing_page',
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            Helpers::businessUpdateOrInsert(['key' => 'landing_page'], [
                   'value' => $landing_page->value == 1 ? 0 : 1
               ]);
        }

        if (isset($landing_page) && $landing_page->value) {
            return response()->json(['message' => translate('landing_page_is_off.')]);
        }
        return response()->json(['message' => translate('landing_page_is_on.')]);
    }
    public function system_currency(Request $request)
    {
        $currency_check=Helpers::checkCurrency($request['currency']);
        if( $currency_check !== true ){
        return response()->json(['data'=> translate($currency_check) ],200);
        }
        return response()->json([],200);
    }
}
