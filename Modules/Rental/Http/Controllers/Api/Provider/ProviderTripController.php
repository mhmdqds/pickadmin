<?php

namespace Modules\Rental\Http\Controllers\Api\Provider;

use App\Models\Zone;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Rental\Entities\Trips;
use Modules\Rental\Entities\Vehicle;
use Illuminate\Support\Facades\Validator;
use Modules\Rental\Traits\TripLogicTrait;
use Modules\Rental\Entities\TripVehicleDetails;
use Modules\Rental\Traits\RentalPushNotification;
use MatanYadaev\EloquentSpatial\Objects\Point;

class ProviderTripController extends Controller
{
    use TripLogicTrait , RentalPushNotification;
    public function __construct(private Trips $trips, private Helpers $helpers)
    {
        $this->trips = $trips;
        $this->helpers = $helpers;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function tripList(Request $request, $trip_status): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_status' => 'nullable|in:pending,confirmed,ongoing,completed,canceled,payment_failed',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }
        $limit = $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;

        $providerId = $request->vendor->stores[0]->id;
        // $moduleId = $request->vendor->stores[0]->module_id;


        $trips = $this->trips->where('provider_id', $providerId)
            ->when(in_array($trip_status, ['pending', 'confirmed', 'ongoing', 'completed', 'canceled', 'payment_failed']), function ($query) use ($trip_status) {
                $query->where('trip_status', $trip_status);
            })
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);


        $data = $this->helpers->preparePaginatedResponse(pagination: $trips, limit: $limit, offset: $offset, key: 'trips', extraData: []);

        return response()->json($data, 200);
    }


    public function updateTripStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
            'cancellation_reason' => 'nullable|max:255',
            'trip_status' => 'required|in:confirmed,ongoing,completed,canceled',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $trip = $this->trips->where(['provider_id' => $request->vendor->stores[0]->id, 'id' => $request->trip_id])->with(['trip_details.vehicle', 'trip_transaction:id,trip_id'])->first();

        if (!$trip) {
            return response()->json(['errors' => translate('trip_data_not_found')], 404);
        }

        if (in_array($trip->trip_status, ['completed', 'canceled'])) {
            return response()->json(['errors' => translate('You_can_not_change_this_trip_status')], 403);
        }


        $totalVehicle = count($trip->assignedVehicle);
            $is_deleted = 0;
            $trip->trip_details->each(function ($details) use (&$is_deleted) {
                if (!$details->vehicle) {
                    $is_deleted = 1;
                }
            });

            if (in_array($request->trip_status, ['ongoing', 'completed']) && $totalVehicle <= 0 && $is_deleted !=1) {
                return response()->json(['message' => translate('at_first_assign_a_vehicle')], 403);
            }

        $trip->trip_status = $request->trip_status;
        if ($request->trip_status == 'canceled') {
            $trip->canceled_by = 'vendor';
            $trip->cancellation_reason = $request?->cancellation_reason;
            foreach ($trip->trip_details as $detail) {
                $detail?->vehicle?->total_trip > 0 ? $detail?->vehicle?->decrement('total_trip', $detail->quantity) : '';
            }
            Helpers::increment_order_count($trip->provider);

        } elseif ($trip->trip_status != 'pending' && $request->trip_status == 'pending') {
            $trip->vehicle_identity()->delete();
        } else if ($request->trip_status == 'completed' && $trip->payment_status == 'paid' && !$trip->trip_transaction) {
            if ($this->create_transaction($trip, 'vendor') === false) {
                return response()->json(['errors' => translate('Failed_to_create_Transaction')], 403);
            };
        }
        $trip[$request->trip_status] = now();
        $trip->save();

        if($request->trip_status == 'completed'){
            TripVehicleDetails::where('trip_id' , $trip->id)->update([
                'is_completed' => 1
            ]);
        }


        $this->sendTripNotificationCustomer($trip);
        return response()->json(['message' => translate('Trip_successfully_updated')], 200);
    }

    public function updateTripPaymentStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
            'payment_method' => 'nullable|max:100',
            'transaction_reference' => 'nullable|max:100',
            'payment_status' => 'required|in:paid,unpaid',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $trip = $this->trips->where(['provider_id' => $request->vendor->stores[0]->id, 'id' => $request->trip_id])->with(['trip_transaction:id,trip_id'])->first();

        if (!$trip) {
            return response()->json(['errors' => translate('trip_data_not_found')], 404);
        }

        $trip->payment_status = $request->payment_status;
        $trip->payment_method = $request?->payment_method ??  $trip->payment_method ?? 'cash_payment';
        $trip->transaction_reference = $request?->transaction_reference ??  $trip->transaction_reference;
        $trip->save();

        if ($trip->trip_status == 'completed' && $trip->payment_status == 'paid' && !$trip->trip_transaction) {
            if ($this->create_transaction($trip, 'vendor') === false) {
                return response()->json(['errors' => translate('Failed_to_create_Transaction')], 403);
            };
        }
        $this->sendTripPaymentNotificationCustomer($trip);

        return response()->json(['message' => translate('Trip_payment_status_updated')], 200);
    }


    public function assignDriver(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
            'driver_ids' => 'required',

        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $trip = $this->trips->where(['provider_id' => $request->vendor->stores[0]->id, 'id' => $request->trip_id])->first();

        if (!$trip) {
            return response()->json(['errors' => translate('trip_data_not_found')], 404);
        }

        $driver_ids = json_decode($request->driver_ids, true);

        $vehicle_datas = [];
        foreach ($driver_ids as $key => $driver_id) {
            $vehicle_data = TripVehicleDetails::where('id', $key)->first();
            if (!$vehicle_data) {
                return response()->json(['errors' => translate('vehicle_information_not_found')], 404);
            }
            $vehicle_data->vehicle_driver_id = $driver_id;
            $vehicle_data->save();
            $vehicle_datas[] = $vehicle_data;
        }

        return response()->json($vehicle_datas, 200);
    }


    public function assignVehicle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
            'vehicle_identity_ids' => 'required',
            'trip_details_id' => 'required',
            'vehicle_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $trip = $this->trips->where(['provider_id' => $request->vendor->stores[0]->id, 'id' => $request->trip_id])->first();

        if (!$trip) {
            return response()->json(['errors' => translate('trip_data_not_found')], 404);
        }

        if (Vehicle::where(['id' => $request->vehicle_id, 'provider_id' => $request->vendor->stores[0]->id])->doesntExist()) {
            return response()->json(['errors' => translate('vehicle_not_found')], 404);
        }

        $vehicle_identity_ids = json_decode($request->vehicle_identity_ids, true);
        $vehicle_datas = [];
        foreach ($vehicle_identity_ids as  $identity_id) {
            $vehicle_data = TripVehicleDetails::where(['trip_id' => $request->trip_id, 'vehicle_id' => $request->vehicle_id, 'vehicle_identity_id' => $identity_id])->firstOrNew();
            $vehicle_data->trip_id = $request->trip_id;
            $vehicle_data->vehicle_id = $request->vehicle_id;
            $vehicle_data->trip_details_id = $request->trip_details_id;
            $vehicle_data->vehicle_identity_id = $identity_id;
            $vehicle_data->estimated_trip_end_time = $trip->estimated_trip_end_time;
            $vehicle_data->save();
            $vehicle_datas[] = $vehicle_data;
        }

        TripVehicleDetails::where(['trip_id' => $request->trip_id, 'vehicle_id' => $request->vehicle_id])->whereNotIn('vehicle_identity_id', $vehicle_identity_ids)->delete();
        return response()->json($vehicle_datas, 200);
    }



    public function getTripDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $trip = $this->trips->where('provider_id', $request->vendor->stores[0]->id)->where('id', $request->trip_id)
            ->with([
                'customer:id,f_name,l_name,phone,email,image',
                'vehicle_identity.driver_data:id,first_name,last_name,email,phone,image',
                'vehicle_identity.vehicle_identity_data:id,vin_number,license_plate_number',
                'vehicle_identity.vehicles:id,name,thumbnail',
                'trip_details.vehicle' => function($query) {
                    $query->select('id', 'hourly_price', 'distance_price', 'discount_type', 'discount_price')
                                    ->withCount('vehicleIdentities as total_vehicle_count');
                }])->first();

        if (!$trip) {
            return response()->json(['errors' => translate('Trip_not_found')], 404);
        }

        if ($trip->customer) {
            $trip->customer->trips_count = $trip->customer->trips()->count();
        }

        return response()->json($trip, 200);
    }





    public function editTrip(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $trip = $this->trips->where('provider_id', $request->vendor->stores[0]->id)->where('id', $request->trip_id)->with(['trip_details'])->first();

        if (!$trip) {
            $errors = [];
            array_push($errors, ['code' => 'coordinates', 'message' => translate('messages.Trip_not_found')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        if (in_array($trip->trip_status, ['completed', 'canceled'])) {
            $errors = [];
            array_push($errors, ['code' => 'coordinates', 'message' => translate('messages.You_can_not_edit_this')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $destinationLocation = $request->destination_location ? json_encode($request->destination_location) :json_encode( $trip->destination_location);

        $pickupLocation = $request->pickup_location  ? json_encode($request->pickup_location)  : json_encode($trip->pickup_location);
        $pickup_zones=json_decode($pickupLocation, true);
        $zones = [];
        if (data_get($pickup_zones, 'lat')  && data_get($pickup_zones, 'lng')) {
            $zones = Zone::whereContains('coordinates', new Point(data_get($pickup_zones, 'lat'), data_get($pickup_zones, 'lng'), POINT_SRID))->pluck('id')->toArray();
        }
        if (!empty($trip?->provider?->pickup_zone_id)) {
            $pickup_zone_id = is_string($trip->provider->pickup_zone_id)
                ? json_decode($trip->provider->pickup_zone_id, true)
                : (array) $trip->provider->pickup_zone_id;
        } else {
            $pickup_zone_id = [];
        }

        if (count($zones) > 0 &&  count($pickup_zone_id) > 0 &&  empty(array_intersect($pickup_zone_id, $zones)) == true) {
            $errors = [];
            array_push($errors, ['code' => 'coordinates', 'message' => translate('messages.Pickup_location_is_out_of_zone')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $scheduleAt = $request->schedule_at ? \Carbon\Carbon::parse($request->schedule_at) : \Carbon\Carbon::parse($trip->schedule_at);

        $estimatedHours = $request->estimated_hours ?? $trip->estimated_hours;
        $distance = $request->distance ?? $trip->distance;
        $scheduled = $request->scheduled ?? $trip->scheduled;

        $estimatedTripEndTime = $scheduleAt->copy()->addHours(ceil(
            $trip->rental_type === 'hourly' ? $estimatedHours : ($request->destination_time ?? $trip->destination_time)
        ));

        $vehicleQuantities = json_decode($request->vehicle_quantities ?? '[]', true);
        $modifiedPrices = json_decode($request->modified_prices ?? '[]', true);

        $data=[
            'destinationLocation' => $destinationLocation,
            'pickupLocation' => $pickupLocation,
            'scheduleAt' => $scheduleAt,
            'estimatedHours' => $estimatedHours,
            'distance' => $distance,
            'scheduled' => $scheduled,
            'estimatedTripEndTime' => $estimatedTripEndTime,
            'vehicleQuantities' => $vehicleQuantities,
            'modifiedPrices' => $modifiedPrices,
            'taxPercentage' => $request->vendor->stores[0]->tax,
        ];
        return response()->json($this->getUpdatedTrip($request,$trip,$data), 200);
    }






}
