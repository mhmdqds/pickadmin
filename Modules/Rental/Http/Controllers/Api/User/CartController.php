<?php

namespace Modules\Rental\Http\Controllers\Api\User;


use App\Models\Zone;
use App\Models\Store;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Routing\Controller;
use Modules\Rental\Entities\Vehicle;
use Modules\Rental\Entities\RentalCart;
use Illuminate\Support\Facades\Validator;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\Rental\Entities\RentalCartUserData;

class CartController extends Controller
{
    public function __construct(
        private RentalCart $cart,
        private RentalCartUserData $user_data,
        private Helpers $helpers,
        private Vehicle $vehicle
    ) {}

    public function getCartList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $user_data =   $this->user_data->where('user_id', $user_id)->where('is_guest', $is_guest)->first();

        if ($user_data) {
            $updated_cart_data = $this->updateCartPrice($request, $user_id, $is_guest, $user_data);
            $data = [
                'carts' => $updated_cart_data['carts'],
                'user_data' =>  $updated_cart_data['user_data'],
            ];
        } else {
            $data = [
                'carts' => [],
                'user_data' => $user_data ?? [],
            ];
        }
        return response()->json($data, 200);
    }

    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
            'vehicle_id' => 'required',
            'rental_type' => 'required|in:hourly,distance_wise',
            'estimated_hours' => 'required_if:rental_type,hourly',
            'distance' => 'required_if:rental_type,distance_wise',
            'destination_time' => 'required_if:rental_type,distance_wise',
        ], [
            'destination_time.required_if' => translate('destination_address_is_required_when_rental_type_is_distance_wise')
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $user_data =   $this->user_data->where('user_id', $user_id)->where('is_guest', $is_guest)->first();
        $pickup_time = $request->pickup_time
            ? \Carbon\Carbon::parse($request->pickup_time)
            : ($user_data?->pickup_time ? \Carbon\Carbon::parse($user_data->pickup_time) : now());


        $vehicle = $this->vehicle->where('id', $request->vehicle_id)->active()
            ->withCount([
                'vehicleIdentities as total_vehicle_count' => function ($query) use ($pickup_time) {
                    $query->DynamicVehicleQuantity($pickup_time);
                },
            ])
            ->first();


        $validation_check =  $this->addToCartValidations($vehicle, $request, $user_id, $is_guest, $pickup_time, $user_data);

        if (data_get($validation_check, 'status_code') === 403) {
            return response()->json([
                'errors' => [
                    ['code' => data_get($validation_check, 'code'), 'message' => data_get($validation_check, 'message')]
                ]
            ], data_get($validation_check, 'status_code'));
        }


        $price = $this->getDiscount(price: $request->rental_type == 'hourly' ? $vehicle->hourly_price *  $request->estimated_hours : $vehicle->distance_price *  $request->distance, discount_type: $vehicle->discount_type, discount: $vehicle->discount_price);

        $carts = $this->cart;
        $carts->user_id = $user_id;
        $carts->is_guest = $is_guest;
        $carts->vehicle_id = $request->vehicle_id;
        $carts->provider_id = $vehicle->provider_id;
        $carts->quantity = $request->quantity ?? 1;
        $carts->module_id = $request->header('moduleId');
        $carts->price = $price * $carts->quantity;
        $carts->save();

        $updated_cart_data = $this->updateCartPrice($request, $user_id, $is_guest);

        $data = [
            'carts' => $updated_cart_data['carts'],
            'user_data' =>  $updated_cart_data['user_data'],
        ];
        return response()->json($data, 200);
    }


    private function addToCartValidations($vehicle, $request, $user_id, $is_guest, $pickup_time, $user_data, $provider_check = true)
    {
        try {
            $provider_id = $this->cart->where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->first()?->provider_id;

            $response = match (true) {
                !$vehicle => [
                    'code' => 'cart_item',
                    'message' =>  'vehicle_not_found',
                    'status' => 403
                ],
                $this->cart->where('vehicle_id', $request->vehicle_id)->where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->exists() => [
                    'code' => 'cart_item',
                    'message' =>  'vehicle_already_exists',
                    'status' => 403
                ],

                $vehicle->total_vehicle_count <= 0 => [
                    'code' => 'cart_item',
                    'message' =>  'This_Vehicle_is_not_available_on_this_pickup_time',
                    'status' => 403
                ],
                $vehicle->total_vehicle_count < $request->quantity => [
                    'code' => 'cart_item',
                    'message' =>  translate('messages.max_vehicle_available_quantity_is') . ' ' . $vehicle->total_vehicle_count,
                    'status' => 403
                ],

                default => null
            };


            if ($response) {
                return ['code' => $response['code'], 'message' => translate($response['message']), 'status_code' => $response['status']];
            }


            $store = Store::selectRaw('*, IF(((select count(*) from `store_schedule` where `stores`.`id` = `store_schedule`.`store_id` and `store_schedule`.`day` = ' . $pickup_time->format('w') . ' and `store_schedule`.`opening_time` < "' . $pickup_time->format('H:i:s') . '" and `store_schedule`.`closing_time` >"' . $pickup_time->format('H:i:s') . '") > 0), true, false) as open')->where('id', $vehicle->provider_id)->active()->first();


            $pickup_location = $request->pickup_location ? $request->pickup_location : $user_data?->pickup_location;

            if (data_get($pickup_location, 'lat')  && data_get($pickup_location, 'lng')) {
                $zones = Zone::whereContains('coordinates', new Point(data_get($pickup_location, 'lat'), data_get($pickup_location, 'lng'), POINT_SRID))->pluck('id')->toArray();
            }
            $pickup_location_id = json_decode($store->pickup_zone_id, true) ?? [];


            $response = match (true) {
                !$store => [
                    'code' => 'cart_item',
                    'message' =>  'provider_not_found',
                    'status' => 403
                ],
                count($pickup_location_id) == 0 => [
                    'code' => 'cart_item',
                    'message' =>  'Provider_pickup_zone_not_found',
                    'status' => 403
                ],
                count($zones ?? []) > 0 && count($pickup_location_id) > 0 && empty(array_intersect($pickup_location_id, $zones)) == true => [
                    'code' => 'cart_item',
                    'message' =>  'This vehicle is not available for this pickup location. Please choose a different vehicle or location',
                    'status' => 403
                ],
                $store->open == false => [
                    'code' => 'cart_item',
                    'message' =>  'provider_is_closed_at_trip_time',
                    'status' => 403
                ],
                $request->rental_type ==  'hourly' && $vehicle->trip_hourly != 1 => [
                    'code' => 'cart_item',
                    'message' =>  $vehicle->name . ' ' . 'Does_not_Hourly_rental type.You cannot add a vehicle with a different rental type',
                    'status' => 403
                ],
                $request->rental_type ==  'distance_wise' && $vehicle->trip_distance != 1 => [
                    'code' => 'cart_item',
                    'message' =>  $vehicle->name . ' ' . 'Does_not_Distance-wise rental type.You cannot add a vehicle with a different rental type',
                    'status' => 403
                ],
                $provider_check && $provider_id && $user_data?->rental_type && $user_data?->rental_type != $request->rental_type => [
                    'code' => 'cart_item',
                    'message' =>  $vehicle->name . ' ' . translate('does_not_support') . ' ' . translate($user_data?->rental_type) . ' ' . translate('messages.You cannot add a vehicle with a different rental type'),
                    'status' => 403
                ],
                $provider_check && $provider_id  && $provider_id != $vehicle->provider_id => [
                    'code' => 'cart_item',
                    'message' =>  'You_can_not_add_different_provider_vehicles',
                    'status' => 403
                ],
                default => null
            };

            if ($response) {
                return ['code' => $response['code'], 'message' => translate($response['message']), 'status_code' => $response['status']];
            }
        } catch (\Exception $exception) {
            return ['code' => 'fatal_error', 'message' =>$exception->getMessage(), 'status_code' => 500];
        }

        return null;
    }

    public function updateCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $cart = $this->cart->where('id', $request->cart_id)->with('vehicle')->first();
        if (!$cart) {
            return response()->json(['errors' => translate('cart_not_found')], 404);
        }


        $user_data =   $this->user_data->where('user_id', $user_id)->where('is_guest', $is_guest)->first();
        $pickup_time = $request->pickup_time
            ? \Carbon\Carbon::parse($request->pickup_time)
            : ($user_data?->pickup_time ? \Carbon\Carbon::parse($user_data->pickup_time) : now());

        $vehicle = $this->vehicle->where('id', $cart->vehicle_id)->active()
            ->withCount([
                'vehicleIdentities as total_vehicle_count' => function ($query) use ($pickup_time) {
                    $query->DynamicVehicleQuantity($pickup_time);
                },
            ])
            ->first();
        if (!$vehicle) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item', 'message' => translate('messages.vehicle_not_found')]
                ]
            ], 403);
        }
        if ($vehicle->total_vehicle_count <= 0) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item', 'message' => translate('messages.This_Vehicle_is_not_available_on_this_pickup_time')]
                ]
            ], 403);
        }

        if ($vehicle->total_vehicle_count < $request->quantity) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item', 'message' => translate('messages.max_vehicle_available_quantity_is') . ' ' . $vehicle->total_vehicle_count]
                ]
            ], 403);
        }
        $price = $this->getDiscount(price: $user_data->rental_type == 'hourly' ? $cart->vehicle->hourly_price *  $user_data->estimated_hours : $cart->vehicle->distance_price *  $user_data->distance, discount_type: $cart->vehicle->discount_type, discount: $cart->vehicle->discount_price);
        $cart->user_id = $user_id;
        $cart->is_guest = $is_guest;
        $cart->quantity = $request->quantity ?? 1;
        $cart->price = $price * $cart->quantity;
        $cart->save();

        $updated_cart_data = $this->updateCartPrice($request, $user_id, $is_guest, $user_data);

        $data = [
            'carts' => $updated_cart_data['carts'],
            'user_data' =>  $updated_cart_data['user_data'],
        ];
        return response()->json($data, 200);
    }


    public function removeVehicle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $this->cart->where('id', $request->cart_id)->where('user_id', $user_id)->where('is_guest', $is_guest)->delete();

        $user_data =   $this->user_data->where('user_id', $user_id)->where('is_guest', $is_guest)->first() ?? [];
        $updated_cart_data = $this->updateCartPrice($request, $user_id, $is_guest, $user_data);

        $data = [
            'carts' => $updated_cart_data['carts'],
            'user_data' =>  $updated_cart_data['user_data'],
        ];
        return response()->json($data, 200);
    }
    public function removeMultipleVehicles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
            'cart_ids' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $this->cart->whereIn('id', json_decode($request->cart_ids, true))->where('user_id', $user_id)->where('is_guest', $is_guest)->delete();

        $user_data =   $this->user_data->where('user_id', $user_id)->where('is_guest', $is_guest)->first() ?? [];
        $updated_cart_data = $this->updateCartPrice($request, $user_id, $is_guest, $user_data);

        $data = [
            'carts' => $updated_cart_data['carts'],
            'user_data' =>  $updated_cart_data['user_data'],
        ];
        return response()->json($data, 200);
    }

    public function removeCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;



        if($request->vehicle_id && $request->pickup_time){

            $user_data =   $this->user_data->where('user_id', $user_id)->where('is_guest', $is_guest)->first();
            $pickup_time = $request->pickup_time
                ? \Carbon\Carbon::parse($request->pickup_time)
                : ($user_data?->pickup_time ? \Carbon\Carbon::parse($user_data->pickup_time) : now());


            $vehicle = $this->vehicle->where('id', $request->vehicle_id)->active()
            ->withCount([
                'vehicleIdentities as total_vehicle_count' => function ($query) use ($pickup_time) {
                    $query->DynamicVehicleQuantity($pickup_time);
                },
            ])
            ->first();

            $validation_check =  $this->addToCartValidations($vehicle, $request, $user_id, $is_guest, $pickup_time, $user_data,false);

            if (data_get($validation_check, 'status_code') === 403) {
                return response()->json([
                    'errors' => [
                        ['code' => data_get($validation_check, 'code'), 'message' => data_get($validation_check, 'message')]
                    ]
                ], data_get($validation_check, 'status_code'));
            }

        }



        $this->user_data->where('user_id', $user_id)->where('is_guest', $is_guest)->delete();
        $this->cart->where('user_id', $user_id)->where('is_guest', $is_guest)->delete();

        $data = [
            'carts' => [],
            'user_data' => [],
            'status'=> 'success'
        ];
        return response()->json($data, 200);
    }



    public function updateUserData(RentalCartUserData $user_data, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
            'rental_type' => 'required|in:hourly,distance_wise',
            'estimated_hours' => 'required_if:rental_type,hourly',
            'distance' => 'required_if:rental_type,distance_wise',
            'destination_time' => 'required_if:rental_type,distance_wise',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $pickup_time = $request->pickup_time
            ? \Carbon\Carbon::parse($request->pickup_time)
            : ($user_data?->pickup_time ? \Carbon\Carbon::parse($user_data->pickup_time) : now());

        $carts = $this->cart->where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->with(['vehicle'])->get();
        $unsupported_vehicle_ids = [];
        foreach ($carts as $cart) {
            $store = Store::selectRaw('*, IF(((select count(*) from `store_schedule` where `stores`.`id` = `store_schedule`.`store_id` and `store_schedule`.`day` = ' . $pickup_time->format('w') . ' and `store_schedule`.`opening_time` < "' . $pickup_time->format('H:i:s') . '" and `store_schedule`.`closing_time` >"' . $pickup_time->format('H:i:s') . '") > 0), true, false) as open')->where('id', $cart->provider_id)->first();

            if ($store->open == false) {
                return response()->json([
                    'errors' => [
                        ['code' => 'cart_item', 'message' => translate('messages.provider_is_closed_at_trip_time')]
                    ]
                ], 403);
            }


            if ($user_data->rental_type != $request->rental_type) {
                if ($request->rental_type ==  'hourly' && $cart?->vehicle->trip_hourly != 1) {
                    $unsupported_vehicle_ids[] = $cart?->id;
                }

                if ($request->rental_type ==  'distance_wise' && $cart?->vehicle->trip_distance != 1) {
                    $unsupported_vehicle_ids[] = $cart?->id;
                }

                if (count($unsupported_vehicle_ids) > 0) {
                    return response()->json($unsupported_vehicle_ids, 403);
                }
            }
        }

        $updated_cart_data = $this->updateCartPrice($request, $user_id, $is_guest);

        $data = [
            'carts' => $updated_cart_data['carts'],
            'user_data' =>  $updated_cart_data['user_data'],
        ];

        return response()->json($data, 200);
    }

    private function setUserData($request, $user_id, $is_guest, $total_cart_price)
    {
        $user_data = $this->user_data->where('user_id', $user_id)->where('is_guest', $is_guest)->firstOrNew();
        $user_data->user_id = $user_id;
        $user_data->pickup_location = $request->pickup_location ? json_encode($request->pickup_location) : json_encode($user_data->pickup_location);
        $user_data->destination_location = $request->destination_location ? json_encode($request->destination_location) : json_encode($user_data->destination_location);
        $user_data->pickup_time = $request->pickup_time ? \Carbon\Carbon::parse($request->pickup_time) : $user_data?->pickup_time ?? now();
        $user_data->rental_type = $request->rental_type ?? $user_data?->rental_type ?? 'hourly';
        $user_data->estimated_hours = $request->estimated_hours ??  $user_data?->estimated_hours ?? 0;
        $user_data->distance = $request->distance ??  $user_data?->distance ?? 0;
        $user_data->destination_time = $request->destination_time ??  $user_data?->destination_time ?? 0;
        $user_data->is_guest = $is_guest;
        $user_data->total_cart_price = $total_cart_price;
        $user_data->save();

        return $user_data;
    }

    private function getDiscount($price, $discount_type, $discount = 0)
    {
        if ($price > 0 &&  $discount > 0) {
            $discount =  $discount_type == 'percent' ? ($price * $discount) / 100 :  $discount;
        }
        return $price - $discount;
    }


    private function updateCartPrice($request, $user_id, $is_guest, $user_data = null)
    {
        $user_data =   $this->setUserData($request, $user_id, $is_guest, 0);
        $zones = [];
        if (data_get($user_data, 'pickup_location.lat')  && data_get($user_data, 'pickup_location.lng')) {
            $zones = Zone::whereContains('coordinates', new Point(data_get($user_data, 'pickup_location.lat'), data_get($user_data, 'pickup_location.lng'), POINT_SRID))->pluck('id')->toArray();
        }
        $zone_ids = $request->header('zoneId');
        $zone_ids =  json_decode($zone_ids, true) ?? [];

        if (count($zones) > 0 &&  count($zone_ids) > 0 &&  empty(array_intersect($zone_ids, $zones)) == true) {
            $this->cart->where('user_id', $user_id)->where('is_guest', $is_guest)->delete();
        }
        $carts = $this->cart->where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))
            ->with(['vehicle' => function ($query) {
                $query->withCount('vehicleIdentities as total_vehicle_count');
            }, 'provider:id,name,address,tax', 'provider.discount'])
            ->get();

        $total_cart_price = 0;
        foreach ($carts as $cart) {
            if ($cart->vehicle &&  $cart->vehicle->status == 1) {
                $price = $this->getDiscount(price: ($request->rental_type ?? $user_data?->rental_type) == 'hourly' ? $cart->vehicle->hourly_price *   ($request->estimated_hours ?? $user_data?->estimated_hours) : $cart->vehicle?->distance_price *  ($request->distance ?? $user_data?->distance), discount_type: $cart->vehicle->discount_type, discount: $cart->vehicle->discount_price);
                $cart->user_id = $user_id;
                $cart->is_guest = $is_guest;
                $cart->price = $price * $cart->quantity;
                $cart->save();
                $total_cart_price += $cart->price;
            } else {
                $cart->delete();
            }

            if (!$cart->vehicle()->when(count($zones) > 0, function ($query) use ($zones) {
                $query->whereHas('provider', function ($query) use ($zones) {
                    $query->active()->where(function ($query) use ($zones) {
                        $query->whereJsonContains('pickup_zone_id', (string) $zones[0]);
                        for ($i = 1; $i < count($zones); $i++) {
                            $query->orWhereJsonContains('pickup_zone_id', (string) $zones[$i]);
                        }
                        return $query;
                    });
                });
            })->exists()) {
                $cart->delete();
            }
        };

        $pickup_time = $request->pickup_time
            ? \Carbon\Carbon::parse($request->pickup_time)
            : ($user_data?->pickup_time ? \Carbon\Carbon::parse($user_data->pickup_time) : now());

            $carts=  $this->cart->where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))
            ->with(['vehicle' => function ($query) use ($pickup_time) {
                $query->withCount([
                    'vehicleIdentities as total_vehicle_count' => function ($query) use ($pickup_time) {
                        $query->DynamicVehicleQuantity($pickup_time);
                    },
                ]);
            }, 'provider:id,name,address,tax,pickup_zone_id', 'provider.discount' => function($query){
            return $query->validate();
        }])
            ->get();
            $carts->each(function ($cart) {
                if (!empty($cart->provider->pickup_zone_id)) {
                    $cart->provider->pickup_zone_id = is_string($cart->provider->pickup_zone_id)
                        ? json_decode($cart->provider->pickup_zone_id, true)
                        : (array) $cart->provider->pickup_zone_id;
                } else {
                    $cart->provider->pickup_zone_id = [];
                }
            });

        return [
            'carts' =>$carts,
            'user_data' => $this->setUserData($request, $user_id, $is_guest, $total_cart_price)
        ];
    }
}
