<?php

namespace Modules\Rental\Http\Controllers\Web\Provider;

use App\CentralLogics\Helpers;
use App\Traits\FileManagerTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Rental\Entities\TripVehicleDetails;
use Modules\Rental\Entities\VehicleDriver;
use Modules\Rental\Exports\DriverTripExport;
use Modules\Rental\Exports\VehicleDriverExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverController extends Controller
{
    use FileManagerTrait;
    private VehicleDriver $driver;
    private Helpers $helpers;
    private TripVehicleDetails $tripVehicleDetails;

    public function __construct(VehicleDriver $driver, Helpers $helpers, TripVehicleDetails $tripVehicleDetails)
    {
        $this->driver = $driver;
        $this->helpers = $helpers;
        $this->tripVehicleDetails = $tripVehicleDetails;
    }

    /**
     * Show the form for creating a new resource.
     * @param Request $request
     * @return Renderable
     */
    public function list(Request $request): Renderable
    {
        $query = $this->driver
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('first_name', 'LIKE', '%' . $key . '%')->orWhere('last_name', 'LIKE', '%' . $key . '%');
                }
            })
            ->where('provider_id', $this->helpers->get_store_id())
            ->latest();

            $totalDrivers = $query->count();
            $activeDrivers = (clone $query)->ofStatus(1)->count();
            $inactiveDrivers = (clone $query)->ofStatus(0)->count();
            $drivers = $query->latest()->paginate(config('default_pagination'));
        return view('rental::provider.driver.list', compact('drivers','totalDrivers','activeDrivers','inactiveDrivers'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(): Renderable
    {
        return view('rental::provider.driver.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|unique:vehicle_drivers,email',
            'phone' => 'required|unique:vehicle_drivers,phone',
            'first_name' => 'required',
            'last_name' => 'required',
            'identity_type' => 'required',
            'identity_number' => 'required',
        ]);

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

        $driver = $this->driver;
        $driver->provider_id = $this->helpers->get_store_id();
        $driver->first_name = $request->first_name;
        $driver->last_name = $request->last_name;
        $driver->email = $request->email;
        $driver->phone = $request->phone;
        $driver->identity_type = $request->identity_type;
        $driver->identity_number = $request->identity_number;
        $driver->image = $imageName;
        $driver->identity_image = $identityImage;
        $driver->save();

        Toastr::success(translate('messages.driver_added_successfully'));
        return back();

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function details($id): Renderable
    {
        $driver = $this->driver->findOrFail($id);
        $driverTrips = $this->tripVehicleDetails
            ->where('vehicle_driver_id', $id)
            ->when(request('search'), function ($query) {
                return $query->whereHas('trip', function ($query) {
                    return $query->where('id', request('search'));
                });
            })
            ->latest()->paginate(config('default_pagination'));
        return view('rental::provider.driver.details', compact('driver', 'driverTrips'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $driver = $this->driver->findOrFail($id);
        return view('rental::provider.driver.edit', compact('driver'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $driver = $this->driver->findOrFail($id);

        $request->validate([
            'email' => 'required|unique:vehicle_drivers,email,'.$id,
            'phone' => 'required|unique:vehicle_drivers,phone,'.$id,
            'first_name' => 'required',
            'last_name' => 'required',
            'identity_type' => 'required',
            'identity_number' => 'required',
        ]);

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

        $driver->first_name = $request->first_name;
        $driver->last_name = $request->last_name;
        $driver->email = $request->email;
        $driver->phone = $request->phone;
        $driver->identity_type = $request->identity_type;
        $driver->identity_number = $request->identity_number;
        $driver->image = $imageName;
        $driver->identity_image = $identityImage;
        $driver->save();

        Toastr::success(translate('messages.driver_updated_successfully'));
        return back();

    }


    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function status(Request $request, $id): RedirectResponse
    {
        $driver = $this->driver->find($id);

        if (!$driver) {
            Toastr::error(translate('messages.driver_not_found'));
            return back();
        }

        $driver->update(['status' => !$driver->status]);

        Toastr::success(translate('messages.driver_status_updated_successfully'));
        return back();
    }


    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $driver = $this->driver->find($id);

        if (!$driver) {
            Toastr::error(translate('messages.failed_to_delete_driver'));
            return back();
        }

        if ($driver->image) {
            $this->upload('driver/', $driver->image);
        }

        $driver->translations()->delete();
        $driver->delete();

        Toastr::success(translate('messages.driver_deleted_successfully'));

        if ($request['driver_list']){
            return redirect()->route('vendor.driver.list');
        }
        return back();
    }

    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $drivers = $this->driver
            ->where('provider_id', $this->helpers->get_store_id())
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('first_name', 'LIKE', '%' . $key . '%');
                    $query->orWhere('last_name', 'LIKE', '%' . $key . '%');
                }
            })
            ->latest()->get();

        $data = [
            'data' => $drivers,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new VehicleDriverExport($data), 'Drivers.csv');
        }
        return Excel::download(new VehicleDriverExport($data), 'Drivers.xlsx');
    }

    public function tripExport(Request $request): BinaryFileResponse
    {
        $driverTrips = $this->tripVehicleDetails
            ->where('vehicle_driver_id', $request->id)
            ->when(request('search'), function ($query) {
                return $query->whereHas('trip', function ($query) {
                    return $query->where('id', request('search'));
                });
            })
            ->latest()->get();

        $data = [
            'data' => $driverTrips,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new DriverTripExport($data), 'DriverTrips.csv');
        }
        return Excel::download(new DriverTripExport($data), 'DriversTrips.xlsx');
    }
}
