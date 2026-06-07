<?php

namespace Modules\Rental\Http\Controllers\Api\Provider;

use App\CentralLogics\Helpers;
use App\Traits\FileManagerTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Rental\Entities\VehicleDriver;

class DriverController extends Controller
{
    use FileManagerTrait;
    private VehicleDriver $driver;
    private Helpers $helpers;

    public function __construct(VehicleDriver $driver, Helpers $helpers)
    {
        $this->driver = $driver;
        $this->helpers = $helpers;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $limit = $request['limit'];
        $offset = $request['offset'];
        $providerId = $request->vendor->stores[0]->id;

        $drivers = $this->driver->where('provider_id', $providerId)
            ->withCount([
                'trips',
                'completedTrips as total_trip_completed',
                'canceledTrips as total_trip_canceled',
                'ongoingTrips as total_trip_ongoing',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('first_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('email', 'LIKE', '%' . $key . '%')
                            ->orWhere('phone', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->latest()->paginate($limit, ['*'], 'page', $offset);

        $data = $this->helpers->preparePaginatedResponse(pagination:$drivers, limit:$limit, offset:$offset, key:'drivers', extraData:[]);

        return response()->json($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:vehicle_drivers,email',
            'phone' => 'required|string|unique:vehicle_drivers,phone',
            'identity_type' => 'required|string',
            'identity_number' => 'required|string|max:50',
            'image' => 'required|image|mimes:webp,jpeg,png,jpg,gif|max:2048',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        if ($request->has('image')) {
            $imageName = $this->upload('driver/', 'png', $request->file('image'));
        } else {
            $imageName = 'def.png';
        }

        $identityImageNames = [];
        if (!empty($request->file('identity_image'))) {
            foreach ($request->identity_image as $img) {
                $identityImage = $this->upload('driver/', 'png', $img);
                $identityImageNames[] = ['img' => $identityImage, 'storage' => $this->helpers->getDisk()];
            }
            $identityImage = json_encode($identityImageNames);
        } else {
            $identityImage = json_encode([]);
        }

        try {
            $driver = $this->driver;
            $driver->provider_id = $request->vendor->stores[0]->id;
            $driver->first_name = $request->first_name;
            $driver->last_name = $request->last_name;
            $driver->email = $request->email;
            $driver->phone = $request->phone;
            $driver->identity_type = $request->identity_type;
            $driver->identity_number = $request->identity_number;
            $driver->image = $imageName;
            $driver->identity_image = $identityImage;
            $driver->save();

            return response()->json(['message' => translate('messages.driver_created_successfully.')], 200);

        } catch (Exception $exception) {
            return response()->json(['message' => translate('messages.some_thing_wrong.')], 400);

        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:vehicle_drivers,email,' . $id,
            'phone' => 'required|string|max:20|unique:vehicle_drivers,phone,' . $id,
            'identity_type' => 'required|string',
            'identity_number' => 'required|string|max:50',
            'image' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif|max:2048',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $driver = $this->driver->findOrFail($id);

        if ($request->has('image')) {
            $imageName = $this->updateAndUpload('driver/', $driver->image, 'png', $request->file('image'));
        } else {
            $imageName = $driver['image'];
        }

        if ($request->has('identity_image')){
            foreach (json_decode($driver['identity_image'], true) as $img) {

                Helpers::check_and_delete('driver/' , $img);

            }
            $imgKeeper = [];
            foreach ($request->identity_image as $img) {
                $identityImage = $this->upload('driver/', 'png', $img);
                $imgKeeper[] = ['img' => $identityImage, 'storage' => Helpers::getDisk()];
            }
            $identityImage = json_encode($imgKeeper);
        } else {
            $identityImage = $driver['identity_image'];
        }

        try {
            $driver->provider_id = $request->vendor->stores[0]->id;
            $driver->first_name = $request->first_name;
            $driver->last_name = $request->last_name;
            $driver->email = $request->email;
            $driver->phone = $request->phone;
            $driver->identity_type = $request->identity_type;
            $driver->identity_number = $request->identity_number;
            $driver->image = $imageName;
            $driver->identity_image = $identityImage;
            $driver->save();

            return response()->json(['message' => translate('messages.driver_updated_successfully.')], 200);

        } catch (Exception $exception) {
            return response()->json(['message' => translate('messages.some_thing_wrong.')], 400);

        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return JsonResponse
     */
    public function details($id): JsonResponse
    {
        $driver = $this->driver
            ->withCount([
                'trips',
                'completedTrips as total_trip_completed',
                'canceledTrips as total_trip_canceled',
                'ongoingTrips as total_trip_ongoing',
            ])
            ->with('trips.trip')
            ->findOrFail($id);

        if (isset($driver)) {

            return response()->json($driver, 200);
        }

        return response()->json(['message' => translate('messages.driver_not_found.')], 400);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function status(Request $request, $id): JsonResponse
    {
        $driver = $this->driver->find($id);

        if ($driver) {
            $driver->update(['status' => !$driver->status]);
            return response()->json(['message' => translate('messages.driver_status_updated.')], 200);
        }

        return response()->json(['message' => translate('messages.driver_not_found.')], 400);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $driver = $this->driver->find($id);

        if ($driver) {
            if ($driver->image) {
                $this->helpers->check_and_delete('driver/', $driver->image);
            }

            $driver->translations()->delete();
            $driver->delete();

            return response()->json(['message' => translate('messages.driver_deleted_successfully.')], 200);
        }

        return response()->json(['message' => translate('messages.failed_to_delete_driver.')], 400);
    }
}
