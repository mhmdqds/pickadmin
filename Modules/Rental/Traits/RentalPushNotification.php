<?php

namespace Modules\Rental\Traits;

use App\CentralLogics\Helpers;
use App\Models\NotificationMessage;
use App\Models\UserNotification;

trait RentalPushNotification
{
    public static function getTripStatusMessage($status, $module_type = 'rental', $lang = 'en')
    {
        $statusMap = [
            'confirmed' => 'confirm',
            'completed' => 'complete',
            'canceled' => 'cancel'
        ];

        $status = $statusMap[$status] ?? $status;
        $status = 'trip_' . $status . '_message';
        $data = NotificationMessage::select(['id', 'message', 'status'])->with(['translations' => function ($query) use ($lang) {
            $query->where('locale', $lang)->limit(1);
        }])->where('module_type', $module_type)->where('key', $status)->first();
        if ($data?->status == 1) {
            return count($data->translations) > 0 ? $data->translations[0]->value : $data['message'];
        }
        return false;
    }

    public static function sendTripNotificationToAll($trip)
    {
        self::sendTripNotificationAdminPanel($trip);
        self::sendTripNotificationproviderPanel($trip);
        self::sendTripNotificationproviderApp($trip);
        self::sendTripNotificationCustomer($trip);
        return true;
    }
    public static function sendTripNotificationAdminPanel($trip)
    {
        $data = self::makeNotifyData(
            title: translate('Trip_Notification'),
            description: translate('messages.You have a new trip booking'),
            trip: $trip,
            order_type: 'trip',
            type: 'new_order'
        );
        Helpers::send_push_notif_to_topic($data, 'admin_message', 'order_request', url('/') . '/admin/rental/trip');
        return true;
    }

    public static function sendTripNotificationproviderPanel($trip)
    {
        $push_notification_status = Helpers::getRentalNotificationStatusData('provider', 'provider_trip_notification', 'push_notification_status', $trip?->provider_id);

        if ($push_notification_status) {
            $data = self::makeNotifyData(
                title: translate('Trip_Notification'),
                description: translate('messages.You have a new trip booking'),
                trip: $trip,
                order_type: 'trip',
                type: 'new_order'
            );
            $web_push_link = url('/') . '/vendor-panel/trip/list/all';
            Helpers::send_push_notif_to_topic($data, "store_panel_{$trip->provider_id}_message", 'new_order', $web_push_link);
            if ($trip?->provider?->vendor?->firebase_token) {
                Helpers::send_push_notif_to_device($trip?->provider?->vendor?->firebase_token, $data);
                UserNotification::create([
                    'data' => json_encode($data),
                    'vendor_id' => $trip->provider?->vendor_id,
                    'order_type' => 'trip',
                ]);
            }
        }
        return true;
    }
    public static function sendTripNotificationproviderApp($trip)
    {
        $push_notification_status = Helpers::getRentalNotificationStatusData('provider', 'provider_trip_notification', 'push_notification_status', $trip?->provider_id);
        if ($push_notification_status) {

            $data = self::makeNotifyData(
                title: translate('Trip_Notification'),
                description: translate('messages.You have a new trip booking'),
                trip: $trip,
                order_type: 'trip',
                type: 'new_order'
            );

            if ($trip?->provider?->vendor?->firebase_token) {
                Helpers::send_push_notif_to_device($trip?->provider?->vendor?->firebase_token, $data);
                UserNotification::create([
                    'data' => json_encode($data),
                    'vendor_id' => $trip->provider?->vendor_id,
                    'order_type' => 'trip',
                ]);
            }
        }
        return true;
    }

    public static function sendTripNotificationCustomer($trip) {

        if($trip->is_guest){
            $customer_details =$trip['user_info'];
            $value = self::getTripStatusMessage($trip->trip_status,'rental','en');
            $value = Helpers::text_variable_data_format(value:$value,store_name:$trip->provider?->name,order_id:$trip->id,user_name:$customer_details['contact_person_name']);
            $user_fcm = $trip?->guest?->fcm_token;
        }else{
            $value = self::getTripStatusMessage($trip->trip_status,'rental',$trip?->customer?->current_language_key??'en');
            $value = Helpers::text_variable_data_format(value:$value,store_name:$trip->provider?->name,order_id:$trip->id,user_name:"{$trip->customer?->f_name} {$trip->customer?->l_name}");
            $user_fcm = $trip?->customer?->cm_firebase_token;
        }

        if (Helpers::getRentalNotificationStatusData('customer','customer_trip_notification','push_notification_status') &&  $value && $user_fcm) {
            $data = self::makeNotifyData(
                title: translate('Trip_Notification'),
                description: $value,
                trip: $trip,
                order_type: 'trip',
                type: 'trip_status'
            );
            Helpers::send_push_notif_to_device($user_fcm, $data);
            UserNotification::create([
                'data' => json_encode($data),
                'user_id' => $trip->user_id,
                'order_type' => 'trip',
            ]);
        }
        return true;
    }
    public static function sendTripPaymentNotificationCustomer($trip) {
        Helpers::sendTripPaymentNotificationCustomerMain($trip);
        return true;
    }

    public static function makeNotifyData($title, $description, $trip, $order_type, $type)
    {
        return [
            'title' => $title,
            'description' => $description,
            'order_id' => $trip->id,
            'module_id' => $trip->module_id,
            'order_type' => $order_type,
            'status' => $trip->trip_status,
            'image' => '',
            'type' => $type,
            'zone_id' => $trip->zone_id,
        ];
    }
}
