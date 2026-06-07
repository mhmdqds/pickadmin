<?php

namespace Modules\Rental\Http\Controllers\Web\Admin;

use App\Models\Item;
use App\Models\Zone;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Store;
use App\Models\Module;
use App\Models\Vendor;
use App\Models\UserInfo;
use App\Models\StoreWallet;
use App\Models\TempProduct;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Carbon;
use App\Mail\StoreRegistration;
use App\Models\BusinessSetting;
use App\Models\WithdrawRequest;
use Illuminate\Validation\Rule;
use App\Traits\FileManagerTrait;
use App\CentralLogics\StoreLogic;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Entities\Trips;
use App\Models\DisbursementDetails;
use App\Models\SubscriptionPackage;
use Illuminate\Contracts\View\View;
use App\Mail\VendorSelfRegistration;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Rental\Entities\Vehicle;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Modules\Rental\Entities\VehicleDriver;
use Modules\Rental\Entities\VehicleReview;
use OpenSpout\Common\Exception\IOException;
use Illuminate\Contracts\Support\Renderable;
use Modules\Rental\Entities\TripTransaction;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\Rental\Emails\ProviderRegistration;
use Modules\Rental\Exports\VehicleReviewExport;
use Illuminate\Contracts\Foundation\Application;
use Modules\Rental\Emails\ProviderSelfRegistration;
use Modules\Rental\Emails\ProviderStatus;
use Symfony\Component\HttpFoundation\StreamedResponse;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProviderController extends Controller
{
    private BusinessSetting $businessSetting;
    private Zone $zone;
    private Vendor $vendor;
    private Store $store;
    private VehicleDriver $vehicleDriver;
    private Vehicle $vehicle;
    private Admin $admin;
    private StoreLogic $storeLogic;
    private SubscriptionPackage $subscriptionPackage;
    private Helpers $helpers;
    private Order $order;
    private StoreWallet $storeWallet;
    private TempProduct $tempProduct;
    private Item $item;
    private UserInfo $userInfo;
    private Conversation $conversation;
    private DisbursementDetails $disbursementDetails;
    private Trips $trips;
    private VehicleReview $vehicleReview;
    private TripTransaction $tripTransaction;
    private WithdrawRequest $withdrawRequest;

    use FileManagerTrait;

    /**
     * @param BusinessSetting $businessSetting
     * @param StoreWallet $storeWallet
     * @param Item $item
     * @param DisbursementDetails $disbursementDetails
     * @param Conversation $conversation
     * @param UserInfo $userInfo
     * @param TempProduct $tempProduct
     * @param Zone $zone
     * @param Order $order
     * @param Vendor $vendor
     * @param Store $store
     * @param Admin $admin
     * @param StoreLogic $storeLogic
     * @param SubscriptionPackage $subscriptionPackage
     * @param Helpers $helpers
     * @param VehicleDriver $vehicleDriver
     * @param Vehicle $vehicle
     * @param Trips $trips
     * @param VehicleReview $vehicleReview
     * @param TripTransaction $tripTransaction
     * @param WithdrawRequest $withdrawRequest
     */
    public function __construct(BusinessSetting $businessSetting, StoreWallet $storeWallet, Item $item, DisbursementDetails $disbursementDetails, Conversation $conversation, UserInfo $userInfo, TempProduct $tempProduct, Zone $zone, Order $order, Vendor $vendor, Store $store, Admin $admin, StoreLogic $storeLogic, SubscriptionPackage $subscriptionPackage, Helpers $helpers, VehicleDriver $vehicleDriver, Vehicle $vehicle, Trips $trips,  VehicleReview $vehicleReview,  TripTransaction $tripTransaction, WithdrawRequest $withdrawRequest)
    {
        $this->businessSetting = $businessSetting;
        $this->zone = $zone;
        $this->vendor = $vendor;
        $this->store = $store;
        $this->admin = $admin;
        $this->storeLogic = $storeLogic;
        $this->subscriptionPackage = $subscriptionPackage;
        $this->helpers = $helpers;
        $this->order = $order;
        $this->storeWallet = $storeWallet;
        $this->tempProduct = $tempProduct;
        $this->item = $item;
        $this->userInfo = $userInfo;
        $this->conversation = $conversation;
        $this->disbursementDetails = $disbursementDetails;
        $this->vehicleDriver = $vehicleDriver;
        $this->vehicle = $vehicle;
        $this->trips = $trips;
        $this->vehicleReview = $vehicleReview;
        $this->tripTransaction = $tripTransaction;
        $this->withdrawRequest = $withdrawRequest;
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    public function list(Request $request): Renderable
    {
        $key = explode(' ', $request['search']);
        $zone_id = $request->query('zone_id', 'all');
        $type = $request->query('type', 'all');
        $module_id = $request->query('module_id', 'all');

        $stores = $this->store->with('vendor','module')->whereHas('vendor', function($query){
            return $query->where('status', 1);
        })
            ->when(is_numeric($zone_id), function($query)use($zone_id){
                return $query->where('zone_id', $zone_id);
            })
            ->when(is_numeric($module_id), function($query)use($request){
                return $query->module($request->query('module_id'));
            })
            ->when(isset($key), function($query)use($key,$request){
                return $query->where(function($query)use($key){
                    $query->orWhereHas('vendor',function ($q) use ($key) {
                        $q->where(function($q)use($key){
                            foreach ($key as $value) {
                                $q->orWhere('f_name', 'like', "%{$value}%")
                                    ->orWhere('l_name', 'like', "%{$value}%")
                                    ->orWhere('email', 'like', "%{$value}%")
                                    ->orWhere('phone', 'like', "%{$value}%");
                            }
                        });
                    })->orWhere(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%")
                                ->orWhere('email', 'like', "%{$value}%")
                                ->orWhere('phone', 'like', "%{$value}%");
                        }
                    });
                })->orderByRaw("FIELD(name, ?) DESC", [$request->search]);
            })
            ->module(Config::get('module.current_module_id'))
            ->with('vendor','module')->type($type)
            ->latest()->paginate(config('default_pagination'));

        $transaction = $this->tripTransaction->where('module_id', Config::get('module.current_module_id'))->get();
        $totalTransaction = $transaction->count();
        $comissionEarned = $transaction->whereNull('status')->sum('admin_commission');
        $storeWithdraws = $this->withdrawRequest
            ->wherehas('store', function($query){
                $query->where('module_id', Config::get('module.current_module_id'));
            })
            ->where(['approved'=>1])
            ->sum('amount');

        $zone = is_numeric($zone_id) ? $this->zone->findOrFail($zone_id) : null;

        return view('rental::admin.provider.list', compact('stores', 'zone', 'type', 'totalTransaction', 'comissionEarned', 'storeWithdraws'));
    }

    /**
     * @param Request $request
     * @param $store_id
     * @param null $tab
     * @param string $sub_tab
     * @return Application|Factory|View|RedirectResponse
     */
    public function details(Request $request, $store_id, $tab=null, $sub_tab='cash'): Application|Factory|View|RedirectResponse
    {
        $filter= $request?->filter;
        $key = explode(' ', request()->search);

        $store = $this->store->findOrFail($store_id);
        $store->withoutGlobalScope('translate')->with('translations');
        $wallet = $store->vendor->wallet;
        $language = getWebConfig('language') ?? [];


        if(!$wallet)
        {
            $wallet = $this->storeWallet;
            $wallet->vendor_id = $store->vendor->id;
            $wallet->total_earning = 0.0;
            $wallet->total_withdrawn = 0.0;
            $wallet->pending_withdraw = 0.0;
            $wallet->created_at = now();
            $wallet->updated_at = now();
            $wallet->save();
        }

        if($tab == 'settings')
        {
            return view('rental::admin.provider.details.settings', compact('store'));
        }
        else if ($tab == 'driver'){
            if (!$this->helpers->module_permission_check('driver')){
                Toastr::error(translate('messages.Access denied'));
                return back();
            }

            $query = $this->vehicleDriver->where('provider_id', $store_id);
            $totalDrivers = $query->count();
            $activeDrivers = (clone $query)->ofStatus(1)->count();
            $inactiveDrivers = (clone $query)->ofStatus(0)->count();

            if (isset($key)) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('first_name', 'like', "%{$value}%")
                            ->orWhere('last_name', 'like', "%{$value}%");
                    }
                });
            }


            $drivers = $query->latest()->paginate(config('default_pagination'));
            return view('rental::admin.provider.details.driver-list', compact('store', 'drivers', 'totalDrivers', 'activeDrivers', 'inactiveDrivers'));
        }
        else if ($tab == 'vehicle'){
            if (!$this->helpers->module_permission_check('vehicle')){
                Toastr::error(translate('messages.Access denied'));
                return back();
            }

            $query = $this->vehicle->where('provider_id', $store_id);
            $totalVehicles = $query->count();
            $activeVehicles = (clone $query)->ofStatus(1)->count();
            $inactiveVehicles = (clone $query)->ofStatus(0)->count();
            $ongoingVehicles = $this->trips->where('provider_id', $store_id)->Ongoing()->count();

            if (isset($key)) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            }

            $vehicles = $query->latest()->paginate(config('default_pagination'));
            return view('rental::admin.provider.details.vehicle-list', compact('store', 'vehicles', 'totalVehicles', 'activeVehicles', 'inactiveVehicles', 'ongoingVehicles'));
        }
        else if($tab == 'order')
        {
            if (!$this->helpers->module_permission_check('trip')){
                Toastr::error(translate('messages.Access denied'));
                return back();
            }
            $trips = $this->trips->where('provider_id', $store->id)->latest()
                ->when(isset($key ), function ($q) use ($key){
                    $q->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('id', 'like', "%{$value}%");
                        }
                    });
                })->latest()->paginate(config('default_pagination'));
            return view('rental::admin.provider.details.trip', compact('store','trips'));
        }
        else if($tab == 'discount')
        {
            if (!$this->helpers->module_permission_check('promotion')){
                Toastr::error(translate('messages.Access denied'));
                return back();
            }
            return view('rental::admin.provider.details.discount', compact('store'));
        }
        else if($tab == 'transaction')
        {
            if (!$this->helpers->module_permission_check('rental_report')){
                Toastr::error(translate('messages.Access denied'));
                return back();
            }
            return view('rental::admin.provider.details.transaction', compact('store', 'sub_tab'));
        }

        else if($tab == 'reviews')
        {
            $tripReviews = $this->vehicleReview->where('provider_id', $store->id)->latest()->paginate(config('default_pagination'));
            $reviews = $store->vehicle_reviews()
                            ->selectRaw('
                                COUNT(*) as total_reviews,
                                ROUND(AVG(rating), 1) as avg_rating,
                                SUM(rating) as total_rating,
                                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as excellent_count,
                                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as good_count,
                                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as average_count,
                                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as below_average_count,
                                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as poor_count
                            ')
                            ->first();

            $avgRating = $reviews->avg_rating;
            $totalRating = $reviews->total_rating;
            $totalReviews = $reviews->total_reviews;
            $excellentCount = $reviews->excellent_count;
            $goodCount = $reviews->good_count;
            $averageCount = $reviews->average_count;
            $belowAverageCount = $reviews->below_average_count;
            $poorCount = $reviews->poor_count;

            return view('rental::admin.provider.details.review', compact('totalRating', 'store', 'sub_tab', 'tripReviews', 'avgRating', 'totalReviews', 'excellentCount', 'goodCount', 'averageCount', 'belowAverageCount', 'poorCount'));

        } else if ($tab == 'conversations') {
            $user = $this->userInfo->where(['vendor_id' => $store->vendor->id])->first();
            if ($user) {
                $conversations = $this->conversation->with(['sender', 'receiver', 'last_message'])->WhereUser($user->id)
                    ->paginate(8);
            } else {
                $conversations = [];
            }
            return view('rental::admin.provider.details.conversations', compact('store', 'sub_tab', 'conversations'));

        } else if ($tab == 'meta-data') {
            $store = $this->store->withoutGlobalScope('translate')->findOrFail($store_id);
            return view('rental::admin.provider.details.meta-data', compact('store', 'sub_tab'));

        } else if ($tab == 'disbursements') {
            $disbursements = $this->disbursementDetails->where('store_id', $store->id)
                ->when(isset($key), function ($q) use ($key){
                    $q->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('disbursement_id', 'like', "%{$value}%")
                                ->orWhere('status', 'like', "%{$value}%");
                        }
                    });
                })
                ->latest()->paginate(config('default_pagination'));
            return view('rental::admin.provider.details.disbursement', compact('store','disbursements'));

        } else if ($tab == 'business_plan') {

            $store= $this->store->where('id',$store->id)->with([
                'store_sub_update_application.package','vendor','store_sub_update_application.last_transcations'
            ])->withcount('vehicles')->first();

            $packages = $this->subscriptionPackage->where('module_type','rental')->where('status',1)->latest()->get();
            $admin_commission = $this->businessSetting->where('key', 'admin_commission')->first()?->value ;
            $business_name =  $this->businessSetting->where('key', 'business_name')->first()?->value ;

            try {
                $index=  $store->store_business_model == 'commission' ? 0 : 1+ array_search($store?->store_sub_update_application?->package_id??1 ,array_column($packages->toArray() ,'id') );
            } catch (\Throwable $th) {
                $index= 2;
            }
            return view('rental::admin.provider.details.subscription',compact('store','packages','business_name','admin_commission','index'));
        }

        return view('rental::admin.provider.details.overview', compact('store', 'wallet', 'language'));
    }


    /**
     * @return View|Factory|RedirectResponse|Application
     */
    public function create(): View|Factory|RedirectResponse|Application
    {
        if (!$this->isStoreRegistrationEnabled()) {
            Toastr::error(translate('messages.not_found'));
            return back();
        }

        $admin_commission = $this->helpers->get_business_data('admin_commission');
        $business_name = $this->helpers->get_business_data('business_name');
        $packages = $this->subscriptionPackage->where('module_type','rental')->ofStatus(1)->latest()->get();
        $zones = $this->zone->active(1)->latest()->get();

        return view('rental::admin.provider.create', compact('admin_commission','business_name', 'packages', 'zones'));
    }


    /**
     * @param Request $request
     * @return View|Factory|RedirectResponse|Application
     */
    public function store(Request $request): View|Factory|RedirectResponse|Application
    {
        if (!$this->isStoreRegistrationEnabled()) {
            Toastr::error(translate('messages.not_found'));
            return back();
        }

        $validator = $this->validateStoreRequest($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if ($request->zone_id && !$this->isValidZone($request)) {
            $validator->getMessageBag()->add('latitude', translate('messages.coordinates_out_of_zone'));
            return back()->withErrors($validator)->withInput();
        }

        if ($request->business_plan == 'subscription-base' && !$request->package_id) {
            $validator->getMessageBag()->add('package_id', translate('messages.You_must_select_a_package'));
            return back()->withErrors($validator)->withInput();
        }

        $vendor = $this->createVendor($request);
        $store = $this->createStore($request, $vendor);
        // $store?->module?->increment('stores_count');

        $this->helpers->add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Store', data_id: $store->id, data_value: $store->name);
        $this->helpers->add_or_update_translations(request: $request, key_data: 'address', name_field: 'address', model_name: 'Store', data_id: $store->id, data_value: $store->address);

        $this->sendRegistrationEmails($request, $vendor);

        if(config('module.'.$store->module->module_type)['always_open'])
        {
            $this->storeLogic->insert_schedule($store->id);
        }

        return $this->handleBusinessPlan($request, $store);
    }


    /**
     * @param Request $request
     * @return View|Factory|RedirectResponse|Application
     */
    public function newRequests(Request $request): View|Factory|RedirectResponse|Application
    {
        $zone_id = $request->query('zone_id', 'all');
        $search_by = $request->query('search_by');
        $key = explode(' ', $search_by);
        $type = $request->query('type', 'all');
        $requestType = $request->query('request_type', 'pending_provider');
        $module_id = $request->query('module_id', 'all');

        $stores = $this->store->with('vendor','module')
            ->whereHas('vendor', function ($query) use ($requestType) {
                if ($requestType === 'pending_provider') {
                    $query->where('status', null);
                } elseif ($requestType === 'denied_provider') {
                    $query->where('status', 0);
                }
            })
            ->when(is_numeric($zone_id), function($query)use($zone_id){
                return $query->where('zone_id', $zone_id);
            })
            ->when(is_numeric($module_id), function($query)use($request){
                return $query->module($request->query('module_id'));
            })
            ->when($search_by, function($query)use($key){
                return $query->where(function($query)use($key){
                    $query->orWhereHas('vendor',function ($q) use ($key) {
                        $q->where(function($q)use($key){
                            foreach ($key as $value) {
                                $q->orWhere('f_name', 'like', "%{$value}%")
                                    ->orWhere('l_name', 'like', "%{$value}%")
                                    ->orWhere('email', 'like', "%{$value}%")
                                    ->orWhere('phone', 'like', "%{$value}%");
                            }
                        });
                    })->orWhere(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%")
                                ->orWhere('email', 'like', "%{$value}%")
                                ->orWhere('phone', 'like', "%{$value}%");
                        }
                    });
                });
            })
            ->module(Config::get('module.current_module_id'))
            ->type($type)->latest()->paginate(config('default_pagination'));
        $zone = is_numeric($zone_id)?Zone::findOrFail($zone_id):null;
        return view('rental::admin.provider.new-request', compact('stores', 'zone','type', 'search_by'));
    }

    /**
     * @param Request $request
     * @param $store_id
     * @return Factory|\Illuminate\Foundation\Application|View|Application
     */
    public function newRequestsDetails(Request $request, $store_id): Factory|\Illuminate\Foundation\Application|View|Application
    {
        $store = $this->store->findOrFail($store_id);
        $store->pickupZones = Zone::whereIn('id', json_decode($store->pickup_zone_id))->pluck('name', 'id');

        return view('rental::admin.provider.new-request-details', compact('store'));
    }

    /**
     * @param $id
     * @return Factory|\Illuminate\Foundation\Application|View|RedirectResponse|Application
     */
    public function editBasicSetup($id): Factory|\Illuminate\Foundation\Application|View|RedirectResponse|Application
    {
        if(env('APP_MODE')=='demo' && $id == 2)
        {
            Toastr::warning(translate('messages.you_can_not_edit_this_provider_please_add_a_new_provider_to_edit'));
            return back();
        }

        $zones = $this->zone->active(1)->latest()->get();
        $store = $this->store->withoutGlobalScope('translate')->findOrFail($id);

        return view('rental::admin.provider.edit-basic-setup', compact('store', 'zones'));
    }

    /**
     * @param $id
     * @return Factory|\Illuminate\Foundation\Application|View|RedirectResponse|Application
     */
    public function editBusinessSetup($id): Factory|\Illuminate\Foundation\Application|View|RedirectResponse|Application
    {
        $admin_commission = $this->helpers->get_business_data('admin_commission');
        $business_name = $this->helpers->get_business_data('business_name');
        $packages = $this->subscriptionPackage->ofStatus(1)->where('module_type','rental')->latest()->get();
        $zones = $this->zone->active(1)->latest()->get();
        $store = $this->store->withoutGlobalScope('translate')->findOrFail($id);


        try {
            $index=  $store->store_business_model == 'commission' ? 0 : 1+ array_search($store?->store_sub_update_application?->package_id??1 ,array_column($packages->toArray() ,'id') );
            $index=  $index > 0 ?  $index :1;
        } catch (\Throwable $th) {
            $index= 1;
        }


        return view('rental::admin.provider.edit-business-setup', compact('store', 'zones', 'business_name', 'admin_commission', 'packages','index'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return Factory|\Illuminate\Foundation\Application|View|RedirectResponse|Application
     */
    public function updateBasicSetup(Request $request, $id): Factory|\Illuminate\Foundation\Application|View|RedirectResponse|Application
    {
        $store = $this->store->find($id);
        if (!$store) {
            Toastr::error(translate('messages.information_not_found'));
            return back();
        }

        if (!$this->isStoreRegistrationEnabled()) {
            Toastr::error(translate('messages.not_found'));
            return back();
        }

        $validator = $this->validateStoreRequest($request, $store->vendor_id);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if ($request->zone_id && !$this->isValidZone($request)) {
            $validator->getMessageBag()->add('latitude', translate('messages.coordinates_out_of_zone'));
            return back()->withErrors($validator)->withInput();
        }

        $this->updateVendor($request, $store->vendor);
        $this->updateStore($request, $store);

        $this->helpers->add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Store', data_id: $id, data_value: $store->name);
        $this->helpers->add_or_update_translations(request: $request, key_data: 'address', name_field: 'address', model_name: 'Store', data_id: $id, data_value: $store->address);

        Toastr::success(translate('messages.Provider_updated_successfully'));
        return redirect()->route('admin.rental.provider.edit-business-setup', $id);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function approveOrDeny(Request $request): RedirectResponse
    {
        $store = $this->store->findOrFail($request->id);
        $store->comment = $request->message;
        $store->vendor->status = $request->status;
        $store->vendor->save();

        if($request->status) $store->status = 1;

        $add_days = 1;

        if($store?->store_sub_update_application){
            if($store?->store_sub_update_application && $store?->store_sub_update_application->is_trial == 1){
                $add_days = $this->businessSetting->where(['key' => 'subscription_free_trial_days'])->first()?->value ?? 1;
            }elseif($store?->store_sub_update_application && $store?->store_sub_update_application->is_trial == 0){
                $add_days = $store?->store_sub_update_application->validity;
            }

            $store?->store_sub_update_application->update([
                'expiry_date'=> Carbon::now()->addDays($add_days)->format('Y-m-d'),
                'status'=>1
            ]);
            $store->store_business_model= 'subscription';
        }

        $store->save();

        try{
            if($request->status == 1){

                if(config('mail.status') && Helpers::get_mail_status('rental_approve_mail_status_provider') == '1' &&  Helpers::getRentalNotificationStatusData('provider','provider_registration_approval','mail_status') ){
                    Mail::to($store?->vendor?->email)->send(new ProviderSelfRegistration('approved', $store->vendor->f_name.' '.$store->vendor->l_name));
                }
            }else{
                if(config('mail.status') && Helpers::get_mail_status('rental_deny_mail_status_provider') == '1' &&  Helpers::getRentalNotificationStatusData('provider','provider_registration_deny','mail_status') ){
                    Mail::to($store?->vendor?->email)->send(new ProviderSelfRegistration('denied', $store->vendor->f_name.' '.$store->vendor->l_name));
                }
            }
        }
        catch(\Exception $ex){
            info($ex->getMessage());
        }
        Toastr::success(translate('messages.application_status_updated_successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportReview(Request $request): BinaryFileResponse
    {
        $vehicles = $this->vehicleReview->where('provider_id', $request->provider_id)->latest()->get();

        $data = [
            'data' => $vehicles,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new VehicleReviewExport($data), 'Providers-reviews.csv');
        }
        return Excel::download(new VehicleReviewExport($data), 'Providers-reviews.xlsx');
    }

    /**
     * @return View|\Illuminate\Foundation\Application|Factory|Application
     */
    public function bulkImportIndex(): View|\Illuminate\Foundation\Application|Factory|Application
    {
        return view('rental::admin.provider.bulk-import');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkImportData(Request $request): RedirectResponse
    {
        $request->validate([
            'products_file'=>'required|max:2048'
        ]);
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error(translate('messages.you_have_uploaded_a_wrong_format_file'));
            return back();
        }
        $duplicate_phones = $collections->duplicates('phone');
        $duplicate_emails = $collections->duplicates('email');


        if ($duplicate_emails->isNotEmpty()) {
            Toastr::error(translate('messages.duplicate_data_on_column', ['field' => translate('messages.email')]));
            return back();
        }

        if ($duplicate_phones->isNotEmpty()) {
            Toastr::error(translate('messages.duplicate_data_on_column', ['field' => translate('messages.phone')]));
            return back();
        }

        $email= $collections->pluck('email')->toArray();
        $phone= $collections->pluck('phone')->toArray();

        if($request->button == 'import'){

            if(Store::whereIn('email', $email)->orWhereIn('phone', $phone)->exists()
            ){
                Toastr::error(translate('messages.duplicate_email_or_phone_exists_at_the_database'));
                return back();
            }

            $vendors = [];
            $stores = [];
            $vendor = Vendor::orderBy('id', 'desc')->first('id');
            $vendor_id = $vendor?$vendor->id:0;
            $store = Store::orderBy('id', 'desc')->first('id');
            $store_id = $store?$store->id:0;
            $store_ids = [];
            foreach ($collections as $key => $collection) {
                if ($collection['OwnerFirstName'] === "" || $collection['ProviderName'] === "" || $collection['Phone'] === ""
                    || $collection['Email'] === "" || $collection['Latitude'] === "" || $collection['Longitude'] === ""
                    || $collection['ZoneId'] === "" ||  $collection['PickupTime'] === ""  || $collection['Tax'] === "" || $collection['Logo'] === ""  ) {
                    Toastr::error(translate('messages.please_fill_all_required_fields'));
                    return back();
                }
                if(isset($collection['PickupTime']) && explode("-", (string)$collection['PickupTime'])[0] >  explode("-", (string)$collection['PickupTime'])[1]){
                    Toastr::error('messages.max_pickup_time_must_be_greater_than_min_delivery_time');
                    return back();
                }
                if(isset($collection['Comission']) && ($collection['Comission'] < 0 ||  $collection['Comission'] > 100) ) {
                    Toastr::error('messages.Comission_must_be_in_0_to_100');
                    return back();
                }
                if(isset($collection['Tax']) && ($collection['Tax'] < 0 ||  $collection['Tax'] > 100 )) {
                    Toastr::error('messages.Tax_must_be_in_0_to_100');
                    return back();
                }
                if(isset($collection['Latitude']) && ($collection['Latitude'] < -90 ||  $collection['Latitude'] > 90 )) {
                    Toastr::error('messages.latitude_must_be_in_-90_to_90');
                    return back();
                }
                if(isset($collection['Longitude']) && ($collection['Longitude'] < -180 ||  $collection['Longitude'] > 180 )) {
                    Toastr::error('messages.longitude_must_be_in_-180_to_180');
                    return back();
                }



                $vendors[] = [
                    'id' => $vendor_id + $key + 1,
                    'f_name' => $collection['OwnerFirstName'],
                    'l_name' => $collection['OwnerLastName'],
                    'password' => bcrypt(12345678),
                    'phone' => $collection['Phone'],
                    'email' => $collection['Email'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $stores[] = [
                    'name' => $collection['ProviderName'],
                    'phone' => $collection['Phone'],
                    'email' => $collection['Email'],
                    'logo' => $collection['Logo'],
                    'cover_photo' => $collection['CoverPhoto'],
                    'latitude' => $collection['Latitude'],
                    'longitude' => $collection['Longitude'],
                    'address' => $collection['Address'],
                    'zone_id' => $collection['ZoneId'],
                    'module_id' => $collection['ModuleId'],
                    'comission' => $collection['Comission'],
                    'tax' => $collection['Tax'],
                    'delivery_time' => (isset($collection['PickupTime']) && preg_match('([0-9]+[\-][0-9]+\s[min|hours|days])', $collection['PickupTime'])) ? $collection['PickupTime'] : '30-40 min',
                    'schedule_order' => $collection['ScheduleTrip'] == 'yes' ? 1 : 0,
                    'status' => $collection['Status'] == 'active' ? 1 : 0,
                    'reviews_section' => $collection['ReviewsSection'] == 'active' ? 1 : 0,
                    'active' => $collection['StoreOpen'] == 'yes' ? 1 : 0,
                    'vendor_id' => $vendor_id + $key + 1,
                    'pickup_zone_id' => $collection['PickupZoneId'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if($module = Module::select('module_type')->where('id', $collection['ModuleId'])->first())
                {
                    if(config('module.'.$module->module_type))
                    {
                        $store_ids[] = $store_id+$key+1;
                    }
                }

            }

            $data = array_map(function($id){
                return array_map(function($item)use($id){
                    return     ['store_id'=>$id,'day'=>$item,'opening_time'=>'00:00:00','closing_time'=>'23:59:59'];
                },[0,1,2,3,4,5,6]);
            },$store_ids);

            try{
                DB::beginTransaction();

                $chunkSize = 100;
                $chunk_stores= array_chunk($stores,$chunkSize);
                $chunk_vendors= array_chunk($vendors,$chunkSize);

                foreach($chunk_stores as $key=> $chunk_store){
                    DB::table('vendors')->insert($chunk_vendors[$key]);
//                    DB::table('stores')->insert($chunk_store);
                    foreach ($chunk_store as $store) {
                        $insertedId = DB::table('stores')->insertGetId($store);
                        Helpers::updateStorageTable(get_class(new Store), $insertedId, $store['logo']);
                        Helpers::updateStorageTable(get_class(new Store), $insertedId, $store['cover_photo']);
                    }
                }
                DB::table('store_schedule')->insert(array_merge(...$data));
                DB::commit();
            }catch(\Exception $e)
            {
                DB::rollBack();
                info(["line___{$e->getLine()}",$e->getMessage()]);
                Toastr::error(translate('messages.failed_to_import_data'));
                return back();
            }

            Toastr::success(translate('messages.provider_imported_successfully',['count'=>count($stores)]));
            return back();
        }

        if(Store::whereIn('email', $email)->orWhereIn('phone', $phone)->doesntExist()
        ){
            Toastr::error(translate('messages.email_or_phone_doesnt_exist_at_the_database'));
            return back();
        }


        $vendors = [];
        $stores = [];
        $vendor = Vendor::orderBy('id', 'desc')->first('id');
        $vendor_id = $vendor?$vendor->id:0;
        $store = Store::orderBy('id', 'desc')->first('id');
        $store_id = $store?$store->id:0;
        $store_ids = [];
        foreach ($collections as $key => $collection) {
            if ($collection['id'] === "" || $collection['OwnerId'] === "" || $collection['OwnerFirstName'] === "" || $collection['ProviderName'] === "" || $collection['Phone'] === ""
                || $collection['Email'] === "" || $collection['Latitude'] === "" || $collection['Longitude'] === ""
                || $collection['ZoneId'] === "" ||  $collection['PickupTime'] === ""  || $collection['Tax'] === "" || $collection['Logo'] === ""  ) {
                Toastr::error(translate('messages.please_fill_all_required_fields'));
                return back();
            }
            if(isset($collection['PickupTime']) && explode("-", (string)$collection['PickupTime'])[0] >  explode("-", (string)$collection['PickupTime'])[1]){
                Toastr::error('messages.max_pickup_time_must_be_greater_than_min_pickup_time');
                return back();
            }
            if(isset($collection['Comission']) && ($collection['Comission'] < 0 ||  $collection['Comission'] > 100) ) {
                Toastr::error('messages.Comission_must_be_in_0_to_100');
                return back();
            }
            if(isset($collection['Tax']) && ($collection['Tax'] < 0 ||  $collection['Tax'] > 100 )) {
                Toastr::error('messages.Tax_must_be_in_0_to_100');
                return back();
            }
            if(isset($collection['Latitude']) && ($collection['Latitude'] < -90 ||  $collection['Latitude'] > 90 )) {
                Toastr::error('messages.latitude_must_be_in_-90_to_90');
                return back();
            }
            if(isset($collection['Longitude']) && ($collection['Longitude'] < -180 ||  $collection['Longitude'] > 180 )) {
                Toastr::error('messages.longitude_must_be_in_-180_to_180');
                return back();
            }

            $vendors[] = [
                'id' => $collection['OwnerId'],
                'f_name' => $collection['OwnerFirstName'],
                'l_name' => $collection['OwnerLastName'],
                'password' => bcrypt(12345678),
                'phone' => $collection['Phone'],
                'email' => $collection['Email'],
                'created_at' => now(),
                'updated_at' => now()
            ];

            $stores[] = [
                'id' => $collection['id'],
                'name' => $collection['ProviderName'],
                'phone' => $collection['Phone'],
                'email' => $collection['Email'],
                'logo' => $collection['Logo'],
                'cover_photo' => $collection['CoverPhoto'],
                'latitude' => $collection['Latitude'],
                'longitude' => $collection['Longitude'],
                'address' => $collection['Address'],
                'zone_id' => $collection['ZoneId'],
                'module_id' => $collection['ModuleId'],
                'comission' => $collection['Comission'],
                'tax' => $collection['Tax'],
                'delivery_time' => (isset($collection['PickupTime']) && preg_match('([0-9]+[\-][0-9]+\s[min|hours|days])', $collection['PickupTime'])) ? $collection['PickupTime'] : '30-40 min',
                'schedule_order' => $collection['ScheduleTrip'] == 'yes' ? 1 : 0,
                'status' => $collection['Status'] == 'active' ? 1 : 0,
                'reviews_section' => $collection['ReviewsSection'] == 'active' ? 1 : 0,
                'active' => $collection['storeOpen'] == 'yes' ? 1 : 0,
                'vendor_id' => $collection['id'],
                'pickup_zone_id' => $collection['PickupZoneId'],
                'updated_at' => now(),
            ];
        }

        try{
            $chunkSize = 100;
            $chunk_stores= array_chunk($stores,$chunkSize);
            $chunk_vendors= array_chunk($vendors,$chunkSize);


            DB::beginTransaction();

            foreach($chunk_stores as $key=> $chunk_store){
                DB::table('vendors')->upsert($chunk_vendors[$key],['id','email','phone'],['f_name','l_name','password']);
//                    DB::table('stores')->upsert($chunk_store,['id','email','phone','vendor_id'],['name','logo','cover_photo','latitude','longitude','address','zone_id','module_id','minimum_order','comission','tax','delivery_time','minimum_shipping_charge','per_km_shipping_charge','maximum_shipping_charge','schedule_order','status','self_delivery_system','veg','non_veg','free_delivery','take_away','delivery','reviews_section','pos_system','active','featured']);
                foreach ($chunk_store as $store) {
                    if (isset($store['id']) && DB::table('vehicles')->where('id', $store['id'])->exists()) {
                        DB::table('stores')->where('id', $store['id'])->update($store);
                        Helpers::updateStorageTable(get_class(new Store), $store['id'], $store['logo']);
                        Helpers::updateStorageTable(get_class(new Store), $store['id'], $store['cover_photo']);
                    } else {
                        $insertedId = DB::table('stores')->insertGetId($store);
                        Helpers::updateStorageTable(get_class(new Store), $insertedId, $store['logo']);
                        Helpers::updateStorageTable(get_class(new Store), $insertedId, $store['cover_photo']);
                    }
                }
            }
            DB::commit();
        }catch(\Exception $e)
        {
            DB::rollBack();
            info(["line___{$e->getLine()}",$e->getMessage()]);
            Toastr::error(translate('messages.failed_to_import_data'));
            return back();
        }

        Toastr::success(translate('messages.provider_imported_successfully',['count'=>count($stores)]));
        return back();
    }

    /**
     * @return View|\Illuminate\Foundation\Application|Factory|Application
     */
    public function bulkExportIndex(): View|\Illuminate\Foundation\Application|Factory|Application
    {
        return view('rental::admin.provider.bulk-export');
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
            'type'=>'required',
            'start_id'=>'required_if:type,id_wise',
            'end_id'=>'required_if:type,id_wise',
            'from_date'=>'required_if:type,date_wise',
            'to_date'=>'required_if:type,date_wise'
        ]);
        $vendors = Vendor::with('stores')
            ->when($request['type']=='date_wise', function($query)use($request){
                $query->whereBetween('created_at', [$request['from_date'].' 00:00:00', $request['to_date'].' 23:59:59']);
            })
            ->when($request['type']=='id_wise', function($query)use($request){
                $query->whereBetween('id', [$request['start_id'], $request['end_id']]);
            })->whereHas('stores', function ($q) use ($request) {
                return $q->where('module_id', Config::get('module.current_module_id'));
            })
            ->get();
        // Export consumes only a few MB, even with 10M+ rows.
        return  (new FastExcel(StoreLogic::format_export_stores(Helpers::Export_generator($vendors))))->download('Providers.xlsx');
        // return (new FastExcel(StoreLogic::format_export_stores($vendors)))->download('Stores.xlsx');
    }

    /**
     * @return bool
     */
    private function isStoreRegistrationEnabled(): bool
    {
        $status = $this->businessSetting->where('key', 'toggle_store_registration')->first();
        return isset($status) && $status->value !== '0';
    }

    /**
     * @param Request $request
     * @param null $id
     * @return \Illuminate\Validation\Validator
     */
    private function validateStoreRequest(Request $request, $id = null): \Illuminate\Validation\Validator
    {
        $rules = [
            'f_name' => 'required',
            'name' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'email' => 'required|unique:vendors,email,' . ($id ? $id : 'NULL') . ',id',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:vendors,phone' . ($id ? ','.$id : ''),
            'minimum_delivery_time' => 'required',
            'maximum_delivery_time' => 'required',
            'password' => [
                $id ? 'nullable' : 'required',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols()
                    ->uncompromised(),
                function ($attribute, $value, $fail) {
                    if (strpos($value, ' ') !== false) {
                        $fail('The :attribute cannot contain white spaces.');
                    }
                },
            ],
            'zone_id' => 'required',
            'logo' => [
                $id ? 'nullable' : 'required',
                'image',
                'mimes:webp,jpg,jpeg,png',
                'max:2048',
            ],
            'tax' => 'required',
            'delivery_time_type' => 'required',
            'business_plan' => $id ? 'nullable' : 'required',
            'package_id' => $id ? 'nullable' : 'required_if:business_plan,subscription-based',
        ];

        $messages = [
            'password.min_length' => translate('The password must be at least :min characters long'),
            'password.mixed' => translate('The password must contain both uppercase and lowercase letters'),
            'password.letters' => translate('The password must contain letters'),
            'password.numbers' => translate('The password must contain numbers'),
            'password.symbols' => translate('The password must contain symbols'),
            'password.uncompromised' => translate('The password is compromised. Please choose a different one'),
            'password.custom' => translate('The password cannot contain white spaces.'),
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isValidZone(Request $request): bool
    {
        $zone = $this->zone->query()
            ->whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))
            ->where('id', $request->zone_id)
            ->first();
        return (bool)$zone;
    }


    /**
     * @param Request $request
     * @return mixed
     */
    private function createVendor(Request $request): mixed
    {
        return $this->vendor->create([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'status' => 1,
        ]);
    }

    /**
     * @param Request $request
     * @param Vendor $vendor
     * @return mixed
     */
    private function updateVendor(Request $request, Vendor $vendor): mixed
    {
        return $vendor->update([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
        ]);
    }


    /**
     * @param Request $request
     * @param Vendor $vendor
     * @return mixed
     */
    private function createStore(Request $request, Vendor $vendor): mixed
    {
        return $this->store->create([
            'name' => $request->name[array_search('default', $request->lang)],
            'phone' => $request->phone,
            'email' => $request->email,
            'logo' => $this->upload('store/', 'png', $request->file('logo')),
            'cover_photo' => $this->upload('store/cover/', 'png', $request->file('cover_photo')),
            'address' => $request->address[array_search('default', $request->lang)],
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'vendor_id' => $vendor->id,
            'zone_id' => $request->zone_id,
            'module_id' => config('module')['current_module_id'],
            'pickup_zone_id' => json_encode($request->pickup_zones ?? []),
            'tax' => $request->tax,
            'delivery_time' => "{$request->minimum_delivery_time}-{$request->maximum_delivery_time} {$request->delivery_time_type}",
            'status' => 1,
            'store_business_model' => 'none',
        ]);
    }

    /**
     * @param Request $request
     * @param Store $store
     * @return mixed
     */
    private function updateStore(Request $request, Store $store): mixed
    {
        return $store->update([
            'name' => $request->name[array_search('default', $request->lang)],
            'phone' => $request->phone,
            'email' => $request->email,
            'logo' => $this->updateAndUpload('store/', $store->logo,'png', $request->file('logo')),
            'cover_photo' => $this->updateAndUpload('store/cover/', $store->cover_photo,'png', $request->file('cover_photo')),
            'address' => $request->address[array_search('default', $request->lang)],
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'zone_id' => $request->zone_id,
            'module_id' => config('module')['current_module_id'],
            'pickup_zone_id' => json_encode($request->pickup_zones ?? []),
            'tax' => $request->tax,
            'delivery_time' => "{$request->minimum_delivery_time}-{$request->maximum_delivery_time} {$request->delivery_time_type}",
            'status' => 1,
            'store_business_model' => 'none',
        ]);
    }

    /**
     * @param Request $request
     * @param Vendor $vendor
     * @return void
     */
    private function sendRegistrationEmails(Request $request, Vendor $vendor): void
    {
        try{
            $admin = $this->admin->where('role_id', 1)->first();

            if(config('mail.status') && Helpers::get_mail_status('rental_registration_mail_status_provider') == '1' &&  Helpers::getRentalNotificationStatusData('provider','provider_registration','mail_status') ){
                Mail::to($request['email'])->send(new ProviderSelfRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
            }

            if( config('mail.status') && Helpers::get_mail_status('rental_provider_registration_mail_status_admin') == '1' &&  Helpers::getRentalNotificationStatusData('admin','provider_self_registration','mail_status') ){
                Mail::to($admin['email'])->send(new ProviderRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
            }

        }catch(\Exception $ex){
            info($ex->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param Store $store
     * @return RedirectResponse
     */
    private function handleBusinessPlan(Request $request, Store $store): RedirectResponse
    {
        if ($this->helpers->subscription_check()){
            if ($request->business_plan == 'subscription-base' && $request->package_id != null ) {

                return $this->handleSubscriptionPlan($request, $store);

            } elseif ($request->business_plan == 'commission-base') {

                $store->update(['store_business_model' => 'commission']);

                Toastr::success(translate('messages.Your_provider_registration_is_successful'));
                return back();

            } else {
                $admin_commission = $this->helpers->get_business_data('admin_commission');
                $business_name = $this->helpers->get_business_data('business_name');
                $packages = $this->subscriptionPackage->ofStatus(1)->where('module_type','rental')->latest()->get();

                Toastr::error(translate('messages.please_follow_the_steps_properly.'));
                return back();
            }
        }else{
            $store->update(['store_business_model' => 'commission']);

            Toastr::success(translate('messages.your_provider_registration_is_successful'));
            return back();
        }
    }

    /**
     * @param Request $request
     * @param Store $store
     * @return RedirectResponse
     */
    private function handleSubscriptionPlan(Request $request, Store $store): RedirectResponse
    {
        Helpers::subscription_plan_chosen(store_id:$store->id,package_id:$request->package_id,payment_method:'manual_payment_by_admin',discount:0,reference:'manual_payment_by_admin',type: 'new_join');
        $store->update(['package_id' => $request->package_id]);
        Toastr::success(translate('messages.your_provider_registration_is_successful'));
        return back();
    }

    public function status($store_id)
    {
        $store = $this->store->with('vendor')->findOrFail($store_id);
        $store->status = !$store->status;
        $store->save();
        $vendor = $store->vendor;
        try
        {
            if($store->status == 0)
            {   $vendor->auth_token = null;
                if(isset($vendor->firebase_token) && Helpers::getRentalNotificationStatusData('provider','provider_account_block','push_notification_status',$store?->id))
                {
                    $data = [
                        'title' => translate('messages.suspended'),
                        'description' => translate('messages.your_account_has_been_suspended'),
                        'order_id' => '',
                        'image' => '',
                        'type'=> 'block'
                    ];
                    Helpers::send_push_notif_to_device($vendor->firebase_token, $data);
                    DB::table('user_notifications')->insert([
                        'data'=> json_encode($data),
                        'vendor_id'=>$vendor->id,
                        'created_at'=>now(),
                        'updated_at'=>now()
                    ]);
                }

                if ( config('mail.status') && Helpers::get_mail_status('rental_suspend_mail_status_provider') == '1' &&  Helpers::getRentalNotificationStatusData('provider','provider_account_block','mail_status',$store?->id)) {
                    Mail::to($vendor?->email)->send(new ProviderStatus('suspended', $vendor?->f_name.' '.$vendor?->l_name));
                }
            } else{

                if ( Helpers::getRentalNotificationStatusData('provider','provider_account_unblock','push_notification_status',$store?->id) &&  isset($vendor->firebase_token)) {
                    $data = [
                        'title' => translate('Account_Activation'),
                        'description' => translate('messages.your_account_has_been_activated'),
                        'order_id' => '',
                        'image' => '',
                        'type' => 'unblock'
                    ];
                    Helpers::send_push_notif_to_device($vendor->firebase_token, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'vendor_id' => $vendor->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                if ( config('mail.status') && Helpers::get_mail_status('rental_unsuspend_mail_status_provider') == '1' &&  Helpers::getNotificationStatusData('provider','provider_account_unblock','mail_status',$store?->id)) {
                    Mail::to( $vendor?->email)->send(new ProviderStatus('unsuspended', $vendor?->f_name.' '.$vendor?->l_name));
                }
            }

        }
        catch (\Exception $e) {

            // dd($e);
            Toastr::warning(translate('messages.push_notification_faild'));
        }

        Toastr::success(translate('messages.store_status_updated'));
        return back();
    }


    /**
     * @param Store $store
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSettings(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'tax'=>'required',
            'minimum_pickup_time' => 'required|min:1|max:2',
            'maximum_pickup_time' => 'required|min:1|max:2|gt:minimum_pickup_time',
        ]);

        $store = $this->store->findOrFail($id);
        $store->tax = $request->tax;
        $store->delivery_time = $request->minimum_pickup_time .'-'. $request->maximum_pickup_time.' '.$request->pickup_time_type;
        $store->save();

        Toastr::success(translate('messages.vendor_settings_updated'));
        return back();
    }



}
