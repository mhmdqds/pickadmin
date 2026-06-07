<?php

namespace Modules\Rental\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use App\Models\Zone;
use App\Models\Store;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Entities\Trips;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Rental\Entities\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Modules\Rental\Exports\TripExport;
use Modules\Rental\Entities\TripDetails;
use Modules\Rental\Traits\TripLogicTrait;
use Illuminate\Contracts\Support\Renderable;
use Modules\Rental\Entities\TripVehicleDetails;
use Modules\Rental\Traits\RentalPushNotification;
use MatanYadaev\EloquentSpatial\Objects\Point;

class TripController extends Controller
{

    use TripLogicTrait,RentalPushNotification;
    private Trips $trips;
    private Store $provider;
    private TripDetails $tripDetails;
    private TripVehicleDetails $tripVehicleDetails;

    public function __construct( Trips $trips, TripDetails $tripDetails, TripVehicleDetails $tripVehicleDetails, Store $provider)
    {
        $this->provider = $provider;
        $this->trips = $trips;
        $this->tripDetails = $tripDetails;
        $this->tripVehicleDetails = $tripVehicleDetails;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     */
    public function list(Request $request): Renderable
    {
        $key = explode(' ', $request['search']);
        $status = $request['status'];

        $this->trips->where(['checked' => 0])->update(['checked' => 1]);

        $trips = $this->trips->with(['customer', 'provider'])
            ->when($status == 'scheduled', function ($query) {
                return $query->scheduled();
            })
            ->when($status == 'pending', function ($query) {
                return $query->Pending();
            })
            ->when($status == 'confirmed', function ($query) {
                return $query->Confirmed();
            })
            ->when($status == 'ongoing', function ($query) {
                return $query->Ongoing();
            })
            ->when($status == 'completed', function ($query) {
                return $query->Completed();
            })
            ->when($status == 'canceled', function ($query) {
                return $query->Canceled();
            })
            ->when($status == 'payment_failed', function ($query) {
                return $query->PaymentFailed();
            })
            ->when(isset($request->provider_ids) && count($request->provider_ids) > 0, function ($query) use ($request) {
                return $query->whereHas('provider', function ($query) use ($request) {
                    return $query->whereIn('id', $request->provider_ids);
                });
            })
            ->when(isset($request->zone_ids) && count($request->zone_ids) > 0, function ($query) use ($request) {
                return $query->whereHas('provider', function ($query) use ($request) {
                    return $query->whereIn('zone_id', $request->zone_ids);
                });
            })
            ->when(isset($request->tripStatus) && count($request->tripStatus) > 0, function ($query) use ($request) {
                return $query->whereIn('trip_status', $request->tripStatus);
            })
//            ->when(isset($request->tripScheduled) && $request->tripScheduled == 1, function ($query) {
//                return $query->whereNotNull('scheduled');
//            })
            ->when(isset($request->from_date) && isset($request->to_date) && $request->from_date && $request->to_date, function ($query) use ($request) {
                return $query->whereBetween('created_at', [$request->from_date . " 00:00:00", $request->to_date . " 23:59:59"]);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhereHas('customer', function ($q) use ($value) {
                                $q->where('f_name', 'like', "%{$value}%")
                                    ->orWhere('l_name', 'like', "%{$value}%")
                                    ->orWhere('email', 'like', "%{$value}%");
                            });
                    }
                });
            })
            ->orderBy('schedule_at', 'desc')
            ->paginate(config('default_pagination'));

        $total = $trips->total();

        $provider_ids = $request->provider_ids ?? [];
        $zone_ids = $request->zone_ids ?? [];
        $from_date = $request->from_date ?? null;
        $to_date = $request->to_date ?? null;
        $tripStatus = $request->tripStatus ?? [];
//        $tripScheduled = $request->tripScheduled ?? 0;

        return view('rental::admin.trip.list', compact('trips', 'status', 'total', 'provider_ids', 'zone_ids', 'from_date', 'to_date', 'tripStatus'));
    }

    /**
     * Show the form for creating a new resource.
     * @param $id
     * @return Renderable
     */
    public function details($id): Renderable
    {
        $trip = $this->trips->with(['trip_details' => function($query) {
            $query->withCount('vehicleVariations');
        }])->findOrFail($id);
        $is_deleted = 0;

        $trip->trip_details->each(function ($details) use (&$is_deleted) {
            if (!$details->vehicle) {
                $is_deleted = 1;
            }
        });

        return view('rental::admin.trip.details', compact('trip','is_deleted'));
    }

    /**
     * @param $id
     * @param $status
     * @return RedirectResponse
     */

    public function status($id, $status): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $trip = $this->trips->with('trip_details')->findOrFail($id);

            if (!$trip) {
                Toastr::success(translate('messages.trip_not_found'));
                return back();
            }

            if ($trip->trip_status != 'pending' && $status == 'pending') {
                $trip->vehicle_identity()->delete();
            }

            $totalVehicle = count($trip->assignedVehicle);
            $is_deleted = 0;
            $trip->trip_details->each(function ($details) use (&$is_deleted) {
                if (!$details->vehicle) {
                    $is_deleted = 1;
                }
            });

            if (in_array($status, ['ongoing', 'completed']) && $totalVehicle <= 0 && $is_deleted !=1) {
                Toastr::error(translate('messages.at_first_assign_a_vehicle'));
                return back();
            }

            if ($status == 'canceled') {
                $trip->canceled_by = 'admin';
                // $trip->cancellation_reason = $request?->cancellation_reason;
                foreach ($trip->trip_details as $detail) {
                    $detail?->vehicle?->total_trip > 0 ? $detail?->vehicle?->decrement('total_trip', $detail->quantity) : '';
                }
                Helpers::increment_order_count($trip->provider);
            }


            $trip->trip_status = $status;
            $trip[$status] = now();
            $trip->save();

            if ($status == 'completed' && $trip->payment_status == 'paid' && !$trip->trip_transaction) {
                if ($this->create_transaction($trip, 'admin') === false) {
                    DB::rollBack();

                    Toastr::error(translate('messages.Failed_to_create_Transaction'));
                    return back();
                }
            }


            if($status == 'completed'){
                TripVehicleDetails::where('trip_id' , $trip->id)->update([
                    'is_completed' => 1
                ]);
            }


            $this->sendTripNotificationCustomer($trip);

            DB::commit();

            Toastr::success(translate('messages.trip_status_updated_successfully'));
            return back();
        } catch (\Exception $e) {
            DB::rollBack();

            Toastr::error(translate('messages.something worng'));
            return back();
        }
    }

    /**
     * @param $id
     * @param $status
     * @return RedirectResponse
     */
    public function paymentStatus($id, $status): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $trip = $this->trips->findOrFail($id);

            if (!$trip) {
                Toastr::success(translate('messages.trip_not_found'));
                return back();
            }

        if($trip->payment_status == 'paid' && $status == 'paid'){
            Toastr::success(translate('messages.This_trip_is_already_paid'));
            return back();
        }

            $trip->payment_method =  $trip->payment_method ?? 'cash_payment';
            $trip->transaction_reference =  $trip?->transaction_reference;
            $trip->payment_status = $status;
            $trip->save();

            if ($trip->trip_status == 'completed' && $trip->payment_status == 'paid' && !$trip->trip_transaction) {
                if ($this->create_transaction($trip, 'admin') === false) {
                    DB::rollBack();

                    Toastr::error(translate('messages.Failed_to_create_Transaction'));
                    return back();
                }
            }
            $this->sendTripPaymentNotificationCustomer($trip);
            DB::commit();

            Toastr::success(translate('messages.trip_payment_status_updated_successfully'));
            return back();
        } catch (\Exception $e) {
            DB::rollBack();

            Toastr::error(translate('messages.something worng'));
            return back();
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function assignVehicle(Request $request): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $request->validate([
            'trip_id' => 'required',
            'vehicle_identity_ids'=>'required',
            'vehicle_id'=>'required',
            'details_id'=>'required',
        ]);

        $trip = $this->trips->find($request->trip_id);

        if(!$trip){
            Toastr::success(translate('messages.trip_data_not_found'));
            return back();
        }

        if(Vehicle::where(['id'=> $request->vehicle_id])->doesntExist()){
            Toastr::success(translate('messages.vehicle_not_found'));
            return back();
        }

        $vehicle_identity_ids = $request->vehicle_identity_ids;

        foreach($vehicle_identity_ids as  $identity_id){
            $vehicle_data = TripVehicleDetails::where(['trip_id' => $request->trip_id ,'vehicle_id' => $request->vehicle_id ,'vehicle_identity_id' => $identity_id])->firstOrNew();
            $vehicle_data->trip_id = $request->trip_id;
            $vehicle_data->vehicle_id = $request->vehicle_id;
            $vehicle_data->trip_details_id = $request->details_id;
            $vehicle_data->vehicle_identity_id = $identity_id;
            $vehicle_data->estimated_trip_end_time = $trip->estimated_trip_end_time;
            $vehicle_data->save();
        }

        TripVehicleDetails::where(['trip_id'=> $request->trip_id ,'vehicle_id' => $request->vehicle_id])->whereNotIn('vehicle_identity_id', $vehicle_identity_ids)->delete();

        Toastr::success(translate('messages.trip_vehicle_assigned_successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function assignDriver(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'trip_id' => 'required',
            'driver_ids'=>'required',
        ]);

        $trip = $this->trips->find($request->trip_id);
        if(!$trip){
            Toastr::success(translate('messages.trip_data_not_found'));
            return back();
        }

        $driver_ids = $request->driver_ids;

        foreach($driver_ids as $key => $driver_id){
            $vehicle_data = TripVehicleDetails::where('id', $key)->first();

            if(!$vehicle_data){
                return response()->json(['errors' => translate('vehicle_information_not_found')], 404);
            }

            $vehicle_data->vehicle_driver_id = $driver_id;
            $vehicle_data->save();
        }

        Toastr::success(translate('messages.trip_driver_assigned_successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request): mixed
    {
        $key = explode(' ', $request['search']);
        $status = $request['status'];

        $this->trips->where(['checked' => 0])->update(['checked' => 1]);

        $trips = $this->trips->with(['customer', 'provider'])
            ->when($status == 'scheduled', function ($query) {
                return $query->scheduled();
            })
            ->when($status == 'pending', function ($query) {
                return $query->Pending();
            })
            ->when($status == 'confirmed', function ($query) {
                return $query->Confirmed();
            })
            ->when($status == 'ongoing', function ($query) {
                return $query->Ongoing();
            })
            ->when($status == 'completed', function ($query) {
                return $query->Completed();
            })
            ->when($status == 'canceled', function ($query) {
                return $query->Canceled();
            })
            ->when($status == 'payment_failed', function ($query) {
                return $query->PaymentFailed();
            })
            ->when(isset($request->vendor), function ($query) use ($request) {
                return $query->whereHas('provider', function ($query) use ($request) {
                    return $query->whereIn('id', $request->vendor);
                });
            })
            ->when(isset($request->from_date) && isset($request->to_date) && $request->from_date != null && $request->to_date != null, function ($query) use ($request) {
                return $query->whereBetween('created_at', [$request->from_date . " 00:00:00", $request->to_date . " 23:59:59"]);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhereHas('customer', function ($q) use ($value) {
                                $q->where('f_name', 'like', "%{$value}%")
                                    ->orWhere('l_name', 'like', "%{$value}%")
                                    ->orWhere('email', 'like', "%{$value}%");
                            });
                    }
                });
            })
            ->when(isset($request->provider_id), function ($query) use ($request) {
                return $query->where('provider_id', $request->provider_id);
            })
            ->orderBy('schedule_at', 'desc')->get();

        $providerId = $request->provider_id;
        $fileName = 'Trips';

        if ($providerId){
            $providerName = $this->provider->where('id', $providerId)->value('name');
            $fileName = $providerName .' trips';
        }

        $data = [
            'providerId' => $providerId,
            'fileName' => $fileName,
            'data' => $trips,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new TripExport($data), $fileName.'.csv');
        }
        return Excel::download(new TripExport($data), $fileName.'.xlsx');
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */

    public function update(Request $request)
    {
        $request->validate([
            'trip_id' => 'required',
        ]);

        $trip = $this->trips->find($request->trip_id);

        if (!$trip) {
            return response()->json(['success' => false,
            'message' => translate('messages.trip_data_not_found')], 400);
        }

        if (in_array($trip->trip_status, ['completed', 'canceled'])) {
            return response()->json(['success' => false,
            'message' => translate('messages.You_can_not_edit_this')], 400);
        }

        $pickup = [
            'lat' => $request->pickup_lat ??  $trip->pickup_location['lat']?? null,
            'lng' => $request->pickup_lng ?? $trip->pickup_location['lng']??null,
            'location_name' => $request->pickup_location ?? $trip->pickup_location['location_name']?? null,
        ];

        $zones = [];
        if (data_get($pickup, 'lat')  && data_get($pickup, 'lng')) {
            $zones = Zone::whereContains('coordinates', new Point(data_get($pickup, 'lat'), data_get($pickup, 'lng'), POINT_SRID))->pluck('id')->toArray();
        }



        if (!empty($trip?->provider?->pickup_zone_id)) {
            $pickup_zone_id = is_string($trip->provider->pickup_zone_id)
                ? json_decode($trip->provider->pickup_zone_id, true)
                : (array) $trip->provider->pickup_zone_id;
        } else {
            $pickup_zone_id = [];
        }

        if (count($zones) > 0 &&  count($pickup_zone_id) > 0 &&  empty(array_intersect($pickup_zone_id, $zones)) == true) {
            return response()->json(['success' => false,
            'message' => translate('messages.Pickup_location_is_out_of_zone')], 400);
        }


        $destination = [
            'lat' => $request->destination_lat ??  $trip->destination_location['lat'] ?? null,
            'lng' => $request->destination_lng ??  $trip->destination_location['lng'] ?? null,
            'location_name' => $request->destination_location ??  $trip->destination_location['location_name'] ?? null,
        ];

        $destinationLocation = $request->destination_location ? json_encode($destination) :json_encode($trip->destination_location);
        $pickupLocation = $request->pickup_location  ? json_encode($pickup)  : json_encode($trip->pickup_location);
        $scheduleAt = $request->schedule_at ? Carbon::parse($request->schedule_at) : Carbon::parse($trip->schedule_at);

        $estimatedHours = $request->estimated_hours ?? $trip->estimated_hours;

        $distance = $trip->distance;

        if($request->distance && is_string($request->distance)){
            $distance = preg_replace('/[^\d]/', '', $request->distance);
        }

        $scheduled = $request->scheduled ?? $trip->scheduled;

        $estimatedTripEndTime = $scheduleAt->copy()->addHours(ceil(
            $trip->rental_type === 'hourly' ? $estimatedHours : ($request->destination_time ?? $trip->destination_time)
        ));

        $vehicleQuantities = array_combine($request->vehicle_ids, $request->quantities);
        $modifiedPrices = array_combine($request->vehicle_ids, $request->prices);

        $data = [
            'destinationLocation' => $destinationLocation,
            'pickupLocation' => $pickupLocation,
            'scheduleAt' => $scheduleAt,
            'estimatedHours' => $estimatedHours,
            'distance' => $distance,
            'scheduled' => $scheduled,
            'estimatedTripEndTime' => $estimatedTripEndTime,
            'vehicleQuantities' => $vehicleQuantities,
            'modifiedPrices' => $modifiedPrices,
            'taxPercentage' => $trip?->provider?->tax,
            'quantityUpdate' => $request->quantityUpdate,
            'updateDistance' => $request->update_distance ?? null,
        ];

            $calculationData = $this->getUpdatedTrip($request, $trip, $data, $request->update == 1 ? true : false);
            if ($request->update == 1) {
                return response()->json(['status' => 'updated',  'message' =>translate('messages.Trip_successfully_updated'), ], 200);
            } else{
                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'details' =>$calculationData['details'],
                    'subTotal' => round($calculationData['subTotal'], config('round_up_to_digit')),
                    'grandTotal' => round($calculationData['tripAmount'], config('round_up_to_digit')),
                    'refBonus' => round($calculationData['refBonus'], config('round_up_to_digit')),
                    'discount' => round($calculationData['discount'], config('round_up_to_digit')),
                    'couponDiscount' => round($calculationData['couponDiscount'], config('round_up_to_digit')),
                    'taxAmount' => round($calculationData['taxAmount'], config('round_up_to_digit')),
                    'additionalCharge' => round($calculationData['additionalCharge'], config('round_up_to_digit')),
                ]);
            }
            return response()->json(['success' => false], 400);
    }

    /**
     * @param $id
     * @return View|Application|Factory
     */
    public function generateInvoice($id): View|Application|Factory
    {
        $trip = $this->trips->findOrFail($id);
        return view('rental::admin.trip.invoice', compact('trip'));
    }

    /**
     * @param $id
     * @return string
     */
    public function printInvoice($id): string
    {
        $trip = $this->trips->findOrFail($id);
        return view('rental::admin.trip.invoice-print', compact('trip'))->render();
    }

}
