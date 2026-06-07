<?php

namespace Modules\Rental\Http\Controllers\Api\Provider;

use App\Models\StoreConfig;
use App\Models\Translation;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Rental\Entities\Trips;
use Modules\Rental\Traits\RentalPushNotification;

class BusinessSettingsController extends Controller
{
    use RentalPushNotification;
    public function updateStoreSetup(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_number' => 'required',
            'schedule_trip' => 'required|boolean',
            'gst' => 'required_if:gst_status,1',
            'minimum_pickup_time' => 'required|numeric',
            'maximum_pickup_time' => 'required|numeric',
            'pickup_time_type'=>'required|in:min,hours,days'

        ],[
            'gst.required_if' => translate('messages.gst_can_not_be_empty'),
        ]);

        $store = $request['vendor']->stores[0];

        $data = json_decode($request->translations, true);

        if (count($data) < 1) {
            $validator->getMessageBag()->add('translations', translate('messages.Name and address in english is required'));
        }

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $store->schedule_order = $request->schedule_trip;
        $store->gst = json_encode(['status'=>$request->gst_status, 'code'=>$request->gst]);
        $store->delivery_time = $request->minimum_pickup_time .'-'. $request->maximum_pickup_time.' '.$request->pickup_time_type;
        $store->name = $data[0]['value'];
        $store->address = $data[1]['value'];
        $store->phone = $request->contact_number;

        $store->logo = $request->has('logo') ? Helpers::update('store/', $store->logo, 'png', $request->file('logo')) : $store->logo;
        $store->cover_photo = $request->has('cover_photo') ? Helpers::update('store/cover/', $store->cover_photo, 'png', $request->file('cover_photo')) : $store->cover_photo;
        $store->meta_title = $data[2]['value'];
        $store->meta_description = $data[3]['value'];
        $store->meta_image = $request->has('meta_image') ? Helpers::update('store/', $store->meta_image, 'png', $request->file('meta_image')) : $store->meta_image;
        $store->pickup_zone_id = $request['pickup_zone_id'] ?? json_encode([]);
        $store->save();

        $conf = StoreConfig::firstOrNew(
            ['store_id' =>  $store->id]
        );
        $conf->save();

        foreach ($data as $key=>$i) {

            Translation::updateOrInsert(
                ['translationable_type'  => 'App\Models\Store',
                    'translationable_id'    => $store->id,
                    'locale'                => $i['locale'],
                    'key'                   => $i['key']],
                ['value'                 => $i['value']]
            );
        }

        return response()->json(['message'=>translate('messages.Provider_settings_updated')], 200);
    }

    public function testNotification($id){
        $trip = Trips::find($id);
        if($trip){
            $this->sendTripNotificationAdminPanel($trip);
            return response()->json('SUCCESS');
        }
        return response()->json($id);
    }
}
