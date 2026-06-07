<?php

namespace Modules\Rental\Http\Controllers\Api\User;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Rental\Entities\RentalWishlish;

class RentalWishlistController extends Controller
{
    public function addToWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required_without:provider_id',
            'provider_id' => 'required_without:vehicle_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        if ($request->vehicle_id && $request->provider_id) {
            $errors = [];
            array_push($errors, ['code' => 'data', 'message' => translate('messages.can_not_add_both_vehicle_and_Provider_at_same_time')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $wishlist = RentalWishlish::where('user_id', $request->user()->id)->where('vehicle_id', $request->vehicle_id)->where('provider_id', $request->provider_id)->first();
        if (empty($wishlist)) {
            $wishlist = new RentalWishlish;
            $wishlist->user_id = $request->user()->id;
            $wishlist->vehicle_id = $request->vehicle_id;
            $wishlist->provider_id = $request->provider_id;
            $wishlist->save();
            return response()->json(['message' => translate('messages.added_successfully')], 200);
        }

        return response()->json(['message' => translate('messages.already_in_wishlist')], 403);
    }

    public function removeFromWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required_without:provider_id',
            'provider_id' => 'required_without:vehicle_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $wishlist = RentalWishlish::when($request->vehicle_id, function($query)use($request){
            return $query->where('vehicle_id', $request->vehicle_id);
        })
        ->when($request->provider_id, function($query)use($request){
            return $query->where('provider_id', $request->provider_id);
        })
        ->where('user_id', $request->user()->id)->first();

        if ($wishlist) {
            $wishlist->delete();
            return response()->json(['message' => translate('messages.successfully_removed')], 200);

        }
        return response()->json(['message' => translate('messages.not_found')], 404);
    }

    public function wishlist(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => 'Zone id is required!']);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $limit = $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;
        $zone_id= $request->header('zoneId');
        $zone_id=json_decode($zone_id, true);
        $longitude= $request->header('longitude');
        $latitude= $request->header('latitude');

        $wishlists = RentalWishlish::where('user_id', $request->user()->id)->with(['vehicle'=>function($q)use($zone_id){
            return $q->active()->withWhereHas('provider', function($query)use($zone_id){
                $query->active()->when(config('module.current_module_data'), function($query){
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereHas('module',function($query){
                    $query->where('status',1);
                })->where(function ($query) use ($zone_id) {
                    $query->whereJsonContains('pickup_zone_id', (string) $zone_id[0]);
                    for ($i = 1; $i < count($zone_id); $i++) {
                        $query->orWhereJsonContains('pickup_zone_id', (string) $zone_id[$i]);
                    }
                    return $query;
                });
            });
        }, 'provider'=>function($q)use($zone_id,$longitude,$latitude){
            return $q->when(config('module.current_module_data'), function($query){
                $query->whereHas('zone.modules', function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                })->module(config('module.current_module_data')['id']);
            })->withOpen($longitude??0,$latitude??0)->active()->whereHas('module',function($query){
                $query->where('status',1);
            })->where(function ($query) use ($zone_id) {
                $query->whereJsonContains('pickup_zone_id', (string) $zone_id[0]);
                for ($i = 1; $i < count($zone_id); $i++) {
                    $query->orWhereJsonContains('pickup_zone_id', (string) $zone_id[$i]);
                }
                return $query;
            });


        }])
        ->paginate($limit, ['*'], 'page', $offset);

        $providers = [];
        $vehicles = [];
        foreach ($wishlists as $wishlist) {
            if ($wishlist->provider) {
                $providers[] = Helpers::store_data_formatting($wishlist->provider);
            }
            if($wishlist->vehicle){
                $vehicles[]=$wishlist->vehicle;
            }
        }

        return response()->json(['providers' => $providers, 'vehicles' =>$vehicles], 200);
    }
}
