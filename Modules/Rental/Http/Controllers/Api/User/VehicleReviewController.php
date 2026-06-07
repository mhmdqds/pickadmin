<?php

namespace Modules\Rental\Http\Controllers\Api\User;


use App\Models\Store;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\CentralLogics\StoreLogic;
use Illuminate\Routing\Controller;
use Modules\Rental\Entities\Trips;
use App\CentralLogics\ProductLogic;
use Modules\Rental\Entities\Vehicle;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Rental\Entities\VehicleReview;

class VehicleReviewController extends Controller
{

    public function __construct(private VehicleReview $review, private Store $store,private Trips $trips, private Vehicle $vehicle, private Helpers $helpers)
    {
        $this->vehicle = $vehicle;
        $this->helpers = $helpers;
        $this->review = $review;
        $this->trips = $trips;
        $this->store = $store;
    }

    public function submitVehicleReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required',
            'trip_id' => 'required',
            'vehicle_identity_id' => 'required',
            'rating' => 'required|numeric|max:5',
        ]);

        $trip = $this->trips->find($request->trip_id);
        if (isset($trip) == false) {
            $validator->errors()->add('trip_id', translate('messages.trip_data_not_found'));
        }



        $multi_review = $this->review->where(['vehicle_id' => $request->vehicle_id, 'user_id' => $request->user()->id, 'trip_id'=>$request->trip_id, 'vehicle_identity_id'=>$request->vehicle_identity_id])->first();
        if (isset($multi_review)) {
            return response()->json([
                'errors' => [
                    ['code'=>'review','message'=> translate('messages.already_submitted')]
                ]
            ], 403);
        }

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image_array = [];
        if (!empty($request->file('attachment'))) {
            foreach ($request->file('attachment') as $image) {
                if ($image != null) {
                    if (!Storage::disk('public')->exists('review')) {
                        Storage::disk('public')->makeDirectory('review');
                    }
                    array_push($image_array, Storage::disk('public')->put('review', $image));
                }
            }
        }

        // $trip?->tripReference?->update([
        //     'is_reviewed' => 1
        // ]);
        $review = $this->review;
        $review->user_id = $request->user()->id;
        $review->provider_id = $trip->provider_id;
        $review->module_id = $trip->module_id;
        $review->trip_id = $request->trip_id;
        $review->vehicle_id = $request->vehicle_id;
        $review->vehicle_identity_id = $request->vehicle_identity_id;
        $review->comment = $request?->comment;
        $review->rating = $request->rating;
        $review->attachment = json_encode($image_array);
        $review->save();


        $store = $this->store->find($review->provider_id);

        $store_rating = StoreLogic::update_store_rating($store->rating, (int)$request->rating);
        $store->rating = $store_rating;
        $store->save();


        $vehicle = $this->vehicle->find($request->vehicle_id);

        if($vehicle){
            $vehicle->rating = ProductLogic::update_rating($vehicle->rating, (int)$request->rating);
            $vehicle->avg_rating = ProductLogic::get_avg_rating(json_decode($vehicle->rating, true));
            $vehicle->save();
            $vehicle->increment('total_reviews');
        }

        return response()->json(['message' => translate('messages.review_submited_successfully')], 200);
    }


}
