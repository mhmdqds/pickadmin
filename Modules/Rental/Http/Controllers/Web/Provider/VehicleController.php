<?php

namespace Modules\Rental\Http\Controllers\Web\Provider;

use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\Models\Store;
use App\Traits\FileManagerTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Rental\Entities\Vehicle;
use Modules\Rental\Entities\VehicleBrand;
use Modules\Rental\Entities\VehicleCategory;
use Modules\Rental\Entities\VehicleIdentity;
use Modules\Rental\Entities\VehicleReview;
use Modules\Rental\Exports\VehicleExport;
use Modules\Rental\Exports\VehicleReviewExport;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleController extends Controller
{
    use FileManagerTrait;
    private Vehicle $vehicle;
    private VehicleCategory $vehicleCategory;
    private VehicleBrand $vehicleBrand;
    private VehicleIdentity $vehicleIdentity;
    private VehicleReview $vehicleReview;
    private Helpers $helpers;
    private Store $store;

    public function __construct(Vehicle $vehicle, VehicleCategory $vehicleCategory, VehicleBrand $vehicleBrand, Helpers $helpers, Store $store, VehicleIdentity $vehicleIdentity, VehicleReview $vehicleReview)
    {
        $this->vehicle = $vehicle;
        $this->helpers = $helpers;
        $this->store = $store;
        $this->vehicleCategory = $vehicleCategory;
        $this->vehicleBrand = $vehicleBrand;
        $this->vehicleIdentity = $vehicleIdentity;
        $this->vehicleReview = $vehicleReview;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     */
    public function index(Request $request): Renderable
    {
        $providerId = $this->helpers->get_store_id();

        $vehicles = $this->vehicle
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->filled('brand_id'), function ($query) use ($request) {
                $query->where('brand_id', $request->input('brand_id'));
            })
            ->when($request->filled('vehicle_type'), function ($query) use ($request) {
                $query->where('type', $request->input('vehicle_type'));
            })
            ->ofProvider($providerId)
            ->latest()->paginate(config('default_pagination'));

        $categories = $this->vehicleCategory->ofStatus(1)->get();
        $brands = $this->vehicleBrand->ofStatus(1)->get();
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());

        return view('rental::provider.vehicle.list', compact('vehicles', 'language', 'defaultLang', 'categories', 'brands'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(): Renderable|RedirectResponse
    {

        if(!Helpers::get_store_data()->item_section)
        {
            Toastr::error(translate('messages.your_vehicle_upload_limit_is_over'));
            return back();
        }
        $categories = $this->vehicleCategory->ofStatus(1)->latest()->get();
        $brands = $this->vehicleBrand->ofStatus(1)->latest()->get();

        return view('rental::provider.vehicle.create', compact('categories', 'brands'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */


    private function checkVehicleLimit($store){
        if(!$store->item_section)
        {
            return ['message' => translate('your_vehicle_upload_limit_is_over')];
        }

        if ( $store->store_business_model == 'subscription' ) {
            $store_sub = $store?->store_sub;
            if (isset($store_sub)) {
                if ($store_sub->max_product != "unlimited" && $store_sub->max_product > 0 ) {
                    $total_item= $this->vehicle->where('provider_id', $store->id)->count()+1;
                    if ( $total_item >= $store_sub->max_product){
                        $store->item_section = 0;
                        $store->save();
                    }
                }
            } else{
                return ['message' => translate('you_are_not_subscribed_to_any_package')];

            }
        }elseif( $store->store_business_model == 'unsubscribed'){
            return ['message' => translate('you_are_not_subscribed_to_any_package')];
        }

        return null;
    }



    public function store(Request $request): RedirectResponse
    {

        $checkVehicleLimit= data_get($this->checkVehicleLimit(Helpers::get_store_data()) , 'message',null);
            if ($checkVehicleLimit) {
                Toastr::error($checkVehicleLimit);
                return back();
            };

        $request->validate([
            'name' => 'required|array',
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'category_id' => 'required|integer|exists:vehicle_categories,id',
            'model' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'engine_capacity' => 'nullable|numeric|min:0',
            'engine_power' => 'nullable|numeric|min:0',
            'seating_capacity' => 'required|integer|min:1',
            'fuel_type' => 'required|string|max:50',
            'transmission_type' => 'required|string|max:50',
            'hourly_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|max:50',
            'tag' => 'nullable|array',
            'tag.*' => 'string|max:50',
            'thumbnail' => 'required|image|mimes:webp,jpeg,png,jpg|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:webp,jpeg,png,jpg,gif|max:2048',
            'documents' => 'nullable|array',
            'documents.*' => 'max:2048',
        ]);

        if ($request->has('thumbnail')) {
            $thumbnailName = $this->upload('vehicle/', 'png', $request->file('thumbnail'));
        } else {
            $thumbnailName = 'def.png';
        }

        $imagesNames = [];
        if (!empty($request->file('images'))) {
            foreach ($request->images as $img) {
                $image = $this->upload('vehicle/', 'png', $img);
                $imagesNames[] = ['img' => $image, 'storage' => $this->helpers->getDisk()];
            }
            $image = json_encode($imagesNames);
        } else {
            $image = json_encode([]);
        }

        $vehicleDocuments = [];
        if (!empty($request->file('documents'))) {
            foreach ($request->documents as $img) {
                $extension = $img->getClientOriginalExtension();
                $documents = $this->upload('vehicle/', $extension, $img);
                $vehicleDocuments[] = ['img' => $documents, 'storage' => $this->helpers->getDisk()];
            }
            $documents = json_encode($vehicleDocuments);
        } else {
            $documents = json_encode([]);
        }

        $providerId = $this->helpers->get_store_id();
        $providerZoneId = $this->store->where('id', $providerId)->value('zone_id') ?? 0;
        $vehicles = $request->input('vehicle');
        $vinNumbers = $vehicles['vin_number'];
        $licensePlateNumbers = $vehicles['license_plate_number'];

        $vehicle = $this->vehicle;
        $vehicle->name = $request->name[array_search('default', $request->lang)];
        $vehicle->description = $request->description[array_search('default', $request->lang)];
        $vehicle->zone_id = $providerZoneId;
        $vehicle->provider_id = $providerId;
        $vehicle->brand_id = $request->brand_id;
        $vehicle->category_id = $request->category_id;
        $vehicle->model = $request->model;
        $vehicle->type = $request->type;
        $vehicle->engine_capacity = $request->engine_capacity;
        $vehicle->engine_power = $request->engine_power;
        $vehicle->seating_capacity = $request->seating_capacity;
        $vehicle->air_condition = $request->air_condition ? 1 : 0;
        $vehicle->multiple_vehicles = $request->multiple_vehicles ? 1 : 0;
        $vehicle->fuel_type = $request->fuel_type;
        $vehicle->transmission_type = $request->transmission_type;
        $vehicle->trip_hourly = $request->trip_hourly ? 1 : 0;
        $vehicle->trip_distance = $request->trip_distance ? 1 : 0;
        $vehicle->hourly_price = $request->hourly_price ?? 0.00;
        $vehicle->discount_price = $request->discount_price ?? 0.00;
        $vehicle->distance_price = $request->distance_price ?? 0;
        $vehicle->discount_type = $request->discount_type;
        $vehicle->tag = json_encode($request->tag);
        $vehicle->thumbnail = $thumbnailName;
        $vehicle->images = $image;
        $vehicle->documents = $documents;
        $vehicle->save();

        if (!empty($vinNumbers[0]) && !empty($licensePlateNumbers[0])){
            foreach ($vinNumbers as $index => $vin) {
                $licensePlate = $licensePlateNumbers[$index];

                if (!empty($vin) && !empty($licensePlate)) {
                    $this->vehicleIdentity->create([
                        'vehicle_id' => $vehicle->id,
                        'provider_id' => $vehicle->provider_id,
                        'vin_number' => $vin,
                        'license_plate_number' => $licensePlate,
                    ]);
                }
            }
        }

        $this->helpers->add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: Vehicle::class, data_id: $vehicle->id, data_value: $vehicle->name,model_class:true);
        $this->helpers->add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: Vehicle::class, data_id: $vehicle->id, data_value: $vehicle->description ,model_class:true);


        Toastr::success(translate('messages.vehicle_added_successfully'));
        return back();
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {

        $vehicle = $this->vehicle->withoutGlobalScope('translate')->with('translations')->findOrFail($id);
        $categories = $this->vehicleCategory->ofStatus(1)->latest()->get();
        $brands = $this->vehicleBrand->ofStatus(1)->latest()->get();

        return view('rental::provider.vehicle.edit', compact('vehicle', 'categories', 'brands'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|array',
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'category_id' => 'required|integer|exists:vehicle_categories,id',
            'model' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'engine_capacity' => 'nullable|numeric|min:0',
            'engine_power' => 'nullable|numeric|min:0',
            'seating_capacity' => 'required|integer|min:1',
            'fuel_type' => 'required|string|max:50',
            'transmission_type' => 'required|string|max:50',
            'hourly_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|max:50',
            'tag' => 'nullable|array',
            'tag.*' => 'string|max:50',
            'thumbnail' => 'nullable|image|mimes:webp,jpeg,png,jpg|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:webp,jpeg,png,jpg,gif|max:2048',
            'documents' => 'nullable|array',
            'documents.*' => 'max:2048',
        ]);

        $vehicle = $this->vehicle->findOrFail($id);
        if (!$vehicle) {
            Toastr::success(translate('messages.vehicle_not_found_successfully'));
            return back();
        }

        if ($request->has('thumbnail')) {
            $thumbnailName = $this->updateAndUpload('vehicle/', $vehicle->thumbnail, 'png', $request->file('thumbnail'));
        } else {
            $thumbnailName = $vehicle->thumbnail;
        }

        $imagesNames = !empty($vehicle->images) ? json_decode($vehicle->images, true) : [];

        if ($request->filled('removed_images')) {
            $removedImages = json_decode($request->input('removed_images'), true);

            foreach ($removedImages as $removedImage) {
                $imagesNames = array_filter($imagesNames, function ($image) use ($removedImage) {
                    return $image['img'] !== $removedImage;
                });

                $imagePath = 'vehicle/' . $removedImage;
                Storage::disk('public')->delete($imagePath);
            }
        }

        if (!empty($request->file('images'))) {
            foreach ($request->images as $img) {
                $image = $this->updateAndUpload('vehicle/', $vehicle->images, 'png', $img);
                $imagesNames[] = ['img' => $image, 'storage' => $this->helpers->getDisk()];
            }
        }

        $image = json_encode(array_values($imagesNames));


        $docNames = !empty($vehicle->documents) ? json_decode($vehicle->documents, true) : [];

        if ($request->filled('removed_documents')) {
            $removedDocs = json_decode($request->input('removed_documents'), true);

            foreach ($removedDocs as $removedDoc) {
                $docNames = array_filter($docNames, function ($image) use ($removedDoc) {
                    return $image['img'] !== $removedDoc;
                });

                $docPath = 'vehicle/' . $removedDoc;
                Storage::disk('public')->delete($docPath);
            }
        }

        if (!empty($request->file('documents'))) {
            foreach ($request->documents as $doc) {
                $extension = $doc->getClientOriginalExtension();
                $file= $this->updateAndUpload('vehicle/', $vehicle->images, $extension, $doc);
                $docNames[] = ['img' => $file, 'storage' => $this->helpers->getDisk()];
            }
        }

        $documents = json_encode(array_values($docNames));

        $providerId = $this->helpers->get_store_id();
        $providerZoneId = $this->store->where('id', $providerId)->value('zone_id') ?? 0;
        $vehicles = $request->input('vehicle');
        $vinNumbers = $vehicles['vin_number'];
        $licensePlateNumbers = $vehicles['license_plate_number'];

        $vehicle->name = $request->name[array_search('default', $request->lang)];
        $vehicle->description = $request->description[array_search('default', $request->lang)];
        $vehicle->zone_id = $providerZoneId;
        $vehicle->provider_id = $providerId;
        $vehicle->brand_id = $request->brand_id;
        $vehicle->category_id = $request->category_id;
        $vehicle->model = $request->model;
        $vehicle->type = $request->type;
        $vehicle->engine_capacity = $request->engine_capacity;
        $vehicle->engine_power = $request->engine_power;
        $vehicle->seating_capacity = $request->seating_capacity;
        $vehicle->air_condition = $request->air_condition ? 1 : 0;
        $vehicle->multiple_vehicles = $request->multiple_vehicles ? 1 : 0;
        $vehicle->fuel_type = $request->fuel_type;
        $vehicle->transmission_type = $request->transmission_type;
        $vehicle->trip_hourly = $request->trip_hourly ? 1 : 0;
        $vehicle->trip_distance = $request->trip_distance ? 1 : 0;
        $vehicle->hourly_price = $request->hourly_price ?? 0.00;
        $vehicle->discount_price = $request->discount_price ?? 0.00;
        $vehicle->distance_price = $request->distance_price ?? 0.00;
        $vehicle->discount_type = $request->discount_type;
        $vehicle->tag = json_encode($request->tag);
        $vehicle->thumbnail = $thumbnailName;
        $vehicle->images = $image;
        $vehicle->documents = $documents;
        $vehicle->update();

        $existingRecords = $this->vehicleIdentity->where('vehicle_id', $vehicle->id)->get();

        $validVinNumbers = [];

        foreach ($vinNumbers as $index => $vin) {
            $licensePlate = $licensePlateNumbers[$index];

            if (!empty($vin) && !empty($licensePlate)) {
                $validVinNumbers[] = $vin;

                $existingRecord = $this->vehicleIdentity->where('vehicle_id', $vehicle->id)
                    ->where('vin_number', $vin)
                    ->first();

                if ($existingRecord) {
                    $existingRecord->update([
                        'license_plate_number' => $licensePlate,
                    ]);
                } else {
                    $this->vehicleIdentity->create([
                        'vehicle_id' => $vehicle->id,
                        'provider_id' => $vehicle->provider_id,
                        'vin_number' => $vin,
                        'license_plate_number' => $licensePlate,
                    ]);
                }
            }
        }

        foreach ($existingRecords as $record) {
            if (!in_array($record->vin_number, $validVinNumbers)) {
                $record->delete();
            }
        }


        $this->helpers->add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: Vehicle::class, data_id: $vehicle->id, data_value: $vehicle->name,model_class:true);
        $this->helpers->add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: Vehicle::class, data_id: $vehicle->id, data_value: $vehicle->description ,model_class:true);

        Toastr::success(translate('messages.vehicle_updated_successfully'));
        return back();
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function details(int $id): Renderable
    {

        $data['vehicle'] = $this->vehicle->findOrFail($id);
        $data['vehicleReview'] = $this->vehicleReview->where('vehicle_id', $id)->latest()->paginate(config('default_pagination'));

        $data['totalRating'] = $data['vehicle']->reviews->sum('rating');
        $data['avgRating'] = number_format($data['vehicle']->reviews->avg('rating'), 1);
        $data['totalReviews'] = $data['vehicle']->reviews->whereNotNull('comment')->count();
        $data['excellentCount'] = $data['vehicle']->reviews->where('rating', 5)->count();
        $data['goodCount'] = $data['vehicle']->reviews->where('rating', 4)->count();
        $data['averageCount'] = $data['vehicle']->reviews->where('rating', 3)->count();
        $data['belowAverageCount'] = $data['vehicle']->reviews->where('rating', 2)->count();
        $data['poorCount'] = $data['vehicle']->reviews->where('rating', 1)->count();

        $data['language'] = getWebConfig('language') ?? [];
        $data['defaultLang'] = str_replace('_', '-', app()->getLocale());
        return view('rental::provider.vehicle.details', $data);
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function status(Request $request, $id): RedirectResponse
    {
        $vehicle = $this->vehicle->find($id);

        if (!$vehicle) {
            Toastr::error(translate('messages.vehicle_not_found'));
            return back();
        }

        $status= !$vehicle->status;
        if(Helpers::get_store_data()->product_uploaad_check !== null && !in_array(Helpers::get_store_data()->product_uploaad_check,['unlimited' ,'commission']) && Helpers::get_store_data()->product_uploaad_check >= 0 && $status == 1 ){
            Toastr::error(translate('messages.Your_current_package_doesnot_allow_to_activate_more_then_allocated_vehicles_in_your_package'));
            return back();
        }
        $vehicle->update(['status' => !$vehicle->status]);

        Toastr::success(translate('messages.vehicle_status_updated_successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function reviewStatus(Request $request, $id): RedirectResponse
    {
        $vehicleReview = $this->vehicleReview->find($id);

        if (!$vehicleReview) {
            Toastr::error(translate('messages.vehicle_not_found'));
            return back();
        }

        $vehicleReview->update(['status' => !$vehicleReview->status]);

        Toastr::success(translate('messages.vehicle_status_updated_successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function newTag(Request $request, $id): RedirectResponse
    {
        $vehicle = $this->vehicle->find($id);

        if (!$vehicle) {
            Toastr::error(translate('messages.vehicle_not_found'));
            return back();
        }

        $vehicle->update(['new_tag' => !$vehicle->new_tag]);

        Toastr::success(translate('messages.vehicle_new_tag_updated_successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $vehicle = $this->vehicle->find($id);

        if (!$vehicle) {
            Toastr::error(translate('messages.failed_to_delete_vehicle'));
            return back();
        }

        if ($vehicle->thumbnail) {
            $this->helpers->check_and_delete('vehicle/' , $vehicle->thumbnail);
        }

        if ($vehicle->images) {
            $value = is_array($vehicle->images)
                ? $vehicle->images
                : ($vehicle->images && is_string($vehicle->images)
                    ? json_decode($vehicle->images, true)
                    : []);
            if ($value){
                foreach ($value as $item){
                    $item = is_array($item)?$item:(is_object($item) && get_class($item) == 'stdClass' ? json_decode(json_encode($item), true):['img' => $item]);
                    $this->helpers->check_and_delete('vehicle/' , $item['img']);
                }
            }
        }

        if ($vehicle->documents) {
            $value = is_array($vehicle->documents)
                ? $vehicle->documents
                : ($vehicle->documents && is_string($vehicle->documents)
                    ? json_decode($vehicle->documents, true)
                    : []);
            if ($value){
                foreach ($value as $item){
                    $item = is_array($item)?$item:(is_object($item) && get_class($item) == 'stdClass' ? json_decode(json_encode($item), true):['img' => $item]);
                    $this->helpers->check_and_delete('vehicle/' , $item['img']);
                }
            }
        }

        $vehicle->vehicleIdentities()->delete();
        $vehicle->translations()->delete();
        $vehicle->delete();

        Toastr::success(translate('messages.vehicle_deleted_successfully'));
        if ($request->vehicle_list){
            return to_route('vendor.vehicle.list');
        }
        return back();
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $providerId = $this->helpers->get_store_id();
        $vehicles = $this->vehicle
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where(function($subQuery) use ($request) {
                    $keys = explode(' ', $request['search']);
                    foreach ($keys as $key) {
                        $subQuery->orWhere('name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->filled('brand_id'), function ($query) use ($request) {
                $query->where('brand_id', $request->input('brand_id'));
            })
            ->when($request->filled('vehicle_type'), function ($query) use ($request) {
                $query->where('type', $request->input('vehicle_type'));
            })
            ->ofProvider($providerId)
            ->latest()->get();

        $data = [
            'data' => $vehicles,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new VehicleExport($data), 'Vehicles.csv');
        }
        return Excel::download(new VehicleExport($data), 'Vehicles.xlsx');
    }



    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function reviewExport(Request $request): BinaryFileResponse
    {
        $providerId = $this->helpers->get_store_id();

        $vehicles = $this->vehicleReview->where('provider_id', $providerId)->where('vehicle_id', $request->vehicle_id)->latest()->get();

        if ($vehicles->isEmpty()){
            $vehicles = $this->vehicleReview
                ->where('provider_id', $providerId)
                ->when($request->has('search'), function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    foreach ($keys as $key) {
                        $query->where(function ($query) use ($key) {
                            $query->orWhere('comment', 'LIKE', '%' . $key . '%')
                                ->orWhere('reply', 'LIKE', '%' . $key . '%')
                                ->orWhereHas('customer', function ($customerQuery) use ($key) {
                                    $customerQuery->where('f_name', 'LIKE', '%' . $key . '%')
                                        ->orWhere('l_name', 'LIKE', '%' . $key . '%')
                                        ->orWhere('phone', 'LIKE', '%' . $key . '%');
                                })
                                ->orWhereHas('vehicle', function ($vehicleQuery) use ($key) {
                                    $vehicleQuery->where('name', 'LIKE', '%' . $key . '%');
                                });
                        });
                    }
                })
                ->latest()->get();
        }

        $data = [
            'data' => $vehicles,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new VehicleReviewExport($data), 'Vehicle-reviews.csv');
        }
        return Excel::download(new VehicleReviewExport($data), 'Vehicle-reviews.xlsx');
    }

    /**
     * @return View|Application|Factory
     */
    public function bulkImportIndex(): View|Application|Factory
    {
        return view('rental::provider.vehicle.bulk-import');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkImportData(Request $request): RedirectResponse
    {
        $request->validate([
            'products_file' => 'required|max:2048'
        ]);
        $module_id = Config::get('module.current_module_id');
        $moduleType = Config::get('module.current_module_type');
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error(translate('messages.you_have_uploaded_a_wrong_format_file'));
            return back();
        }
        if ($request->button == 'import') {
            $data = [];
            try{
                foreach ($collections as $collection) {
                    if ($collection['Id'] === "" || $collection['Name'] === "" || $collection['CategoryId'] === "" || $collection['ProviderId'] === "" || $collection['BrandId'] === "" || $collection['ZoneId'] === "") {
                        Toastr::error(translate('messages.please_fill_all_required_fields'));
                        return back();
                    }

                    $data[] = [
                        'name' => $collection['Name'],
                        'description' => $collection['Description'] ?? null,
                        'thumbnail' => $collection['Thumbnail'] ?? null,
                        'images' => $collection['Images'] ?? null,
                        'zone_id' => $collection['ZoneId'] ?? null,
                        'provider_id' => $this->helpers->get_store_id(),
                        'brand_id' => $collection['BrandId'] ?? null,
                        'category_id' => $collection['CategoryId'] ?? null,
                        'model' => $collection['Model'] ?? null,
                        'type' => $collection['Type'] ?? null,
                        'engine_capacity' => $collection['EngineCapacity'] ?? null,
                        'engine_power' => $collection['EnginePower'] ?? null,
                        'seating_capacity' => $collection['SeatingCapacity'] ?? null,
                        'air_condition' => $collection['AirCondition'] ?? 0,
                        'fuel_type' => $collection['FuelType'] ?? null,
                        'transmission_type' => $collection['TransmissionType'] ?? null,
                        'multiple_vehicles' => $collection['MultipleVehicles'] ?? 0,
                        'trip_hourly' => $collection['TripHourly'] ?? 0,
                        'trip_distance' => $collection['TripDistance'] ?? 0,
                        'hourly_price' => $collection['HourlyPrice'] ?? 0.00,
                        'distance_price' => $collection['DistancePrice'] ?? 0.00,
                        'discount_type' => $collection['DiscountType'] ?? null,
                        'discount_price' => $collection['DiscountPrice'] ?? 0.00,
                        'tag' => $collection['Tag'] ?? null,
                        'documents' => $collection['Documents'] ?? null,
                        'status' => $collection['Status'] ?? 1,
                        'new_tag' => $collection['NewTag'] ?? 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                }
            }catch(\Exception $e){
                info(["line___{$e->getLine()}",$e->getMessage()]);
                Toastr::error(translate('messages.failed_to_import_data'));
                return back();
            }
            try {
                DB::beginTransaction();
                $chunkSize = 100;
                $chunk_items = array_chunk($data, $chunkSize);
                foreach ($chunk_items as $key => $chunk_item) {
//                    DB::table('items')->insert($chunk_item);
                    foreach ($chunk_item as $item) {
                        $insertedId = DB::table('vehicles')->insertGetId($item);
                        Helpers::updateStorageTable(get_class(new Vehicle()), $insertedId, $item['thumbnail']);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                info(["line___{$e->getLine()}", $e->getMessage()]);
                Toastr::error(translate('messages.failed_to_import_data'));
                return back();
            }
            Toastr::success(translate('messages.product_imported_successfully', ['count' => count($data)]));
            return back();
        }
        $data = [];
        try {
            foreach ($collections as $collection) {
                if ($collection['Id'] === "" || $collection['Name'] === "" || $collection['CategoryId'] === "" || $collection['ProviderId'] === "" || $collection['BrandId'] === "" || $collection['ZoneId'] === "") {
                    Toastr::error(translate('messages.please_fill_all_required_fields'));
                    return back();
                }

                $data[] = [
                    'name' => $collection['Name'],
                    'description' => $collection['Description'] ?? null,
                    'thumbnail' => $collection['Thumbnail'] ?? null,
                    'images' => $collection['Images'] ?? null,
                    'zone_id' => $collection['ZoneId'] ?? null,
                    'provider_id' => $this->helpers->get_store_id(),
                    'brand_id' => $collection['BrandId'] ?? null,
                    'category_id' => $collection['CategoryId'] ?? null,
                    'model' => $collection['Model'] ?? null,
                    'type' => $collection['Type'] ?? null,
                    'engine_capacity' => $collection['EngineCapacity'] ?? null,
                    'engine_power' => $collection['EnginePower'] ?? null,
                    'seating_capacity' => $collection['SeatingCapacity'] ?? null,
                    'air_condition' => $collection['AirCondition'] ?? 0,
                    'fuel_type' => $collection['FuelType'] ?? null,
                    'transmission_type' => $collection['TransmissionType'] ?? null,
                    'multiple_vehicles' => $collection['MultipleVehicles'] ?? 0,
                    'trip_hourly' => $collection['TripHourly'] ?? 0,
                    'trip_distance' => $collection['TripDistance'] ?? 0,
                    'hourly_price' => $collection['HourlyPrice'] ?? 0.00,
                    'distance_price' => $collection['DistancePrice'] ?? 0.00,
                    'discount_type' => $collection['DiscountType'] ?? null,
                    'discount_price' => $collection['DiscountPrice'] ?? 0.00,
                    'tag' => $collection['Tag'] ?? null,
                    'documents' => $collection['Documents'] ?? null,
                    'status' => $collection['Status'] ?? 1,
                    'new_tag' => $collection['NewTag'] ?? 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            $id = $collections->pluck('Id')->toArray();
            if ($this->vehicle->whereIn('id', $id)->doesntExist()) {
                Toastr::error(translate('messages.Item_doesnt_exist_at_the_database'));
                return back();
            }
        }catch(\Exception $e){
            info(["line___{$e->getLine()}",$e->getMessage()]);
            Toastr::error(translate('messages.failed_to_import_data'));
            return back();
        }
        try {
            DB::beginTransaction();
            $chunkSize = 100;
            $chunk_items = array_chunk($data, $chunkSize);
            foreach ($chunk_items as $key => $chunk_item) {
                foreach ($chunk_item as $item) {
                    if (isset($item['id']) && DB::table('items')->where('id', $item['id'])->exists()) {
                        DB::table('items')->where('id', $item['id'])->update($item);
                        Helpers::updateStorageTable(get_class(new Item), $item['id'], $item['thumbnail']);
                    } else {
                        $insertedId = DB::table('items')->insertGetId($item);
                        Helpers::updateStorageTable(get_class(new Item), $insertedId, $item['thumbnail']);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            info(["line___{$e->getLine()}", $e->getMessage()]);
            Toastr::error(translate('messages.failed_to_import_data'));
            return back();
        }
        Toastr::success(translate('messages.Vehicle_imported_successfully', ['count' => count($data)]));
        return back();
    }

    /**
     * @return View|Application|Factory|\Illuminate\Contracts\Foundation\Application
     */
    public function bulkExportIndex(): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        return view('rental::provider.vehicle.bulk-export');
    }

    /**
     * @param Request $request
     * @return StreamedResponse|string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function bulkExportData(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse|string
    {
        $request->validate([
            'type' => 'required',
            'start_id' => 'required_if:type,id_wise',
            'end_id' => 'required_if:type,id_wise',
            'from_date' => 'required_if:type,date_wise',
            'to_date' => 'required_if:type,date_wise'
        ]);

        $providerId = $this->helpers->get_store_id();
        $moduleType = Config::get('module.current_module_type');
        $vehicles = $this->vehicle->where('provider_id', $providerId)->when($request['type'] == 'date_wise', function ($query) use ($request) {
            $query->whereBetween('created_at', [$request['from_date'] . ' 00:00:00', $request['to_date'] . ' 23:59:59']);
        })
            ->when($request['type'] == 'id_wise', function ($query) use ($request) {
                $query->whereBetween('id', [$request['start_id'], $request['end_id']]);
            })->get();

        return (new FastExcel(ProductLogic::format_export_vehicles(Helpers::Export_generator($vehicles), $moduleType)))->download('Vehicles.xlsx');
    }

}
