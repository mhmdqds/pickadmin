<?php

namespace Modules\Rental\Http\Controllers\Web\Admin\Promotions;

use Exception;
use App\Models\User;
use App\Models\Zone;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Config;
use Modules\Rental\Exports\CouponExport;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;



class CouponController extends Controller
{

    public function __construct(private Coupon $coupon, private Zone $zone,private User $user)
    {
        $this->coupon = $coupon;
        $this->zone = $zone;
        $this->user = $user;
    }

    public function list(Request $request)
    {
        $coupons = $this->getListData($request);
        $coupons =  $coupons->paginate(config('default_pagination'));
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $zones = $this->zone->where('status' , 1)->get(['id','name']);
        $users = $this->user->where('status' , 1)->get(['id','f_name','l_name']);
        return view('rental::admin.coupon.list', compact('coupons', 'language', 'defaultLang','zones','users'));
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $this->validateRequest($request);
        try {
            DB::beginTransaction();
            $coupon = $this->createcoupon($request);
            Helpers::add_or_update_translations(request: $request, key_data: 'title', name_field: 'title', model_name: 'Coupon', data_id: $coupon->id, data_value: $coupon->title);
            DB::commit();
        } catch (Exception  $exception) {
            info([$exception->getFile(), $exception->getLine(), $exception->getMessage()]);
            DB::rollBack();
            Toastr::error(translate('messages.failed_to_add_coupon'));
            return back();
        }
        Toastr::success(translate('messages.coupon_added_successfully'));
        return back();
    }

    /**
     * @param string $id
     * @return View|Factory|Application|RedirectResponse
     */
    public function edit(Coupon $coupon): View|Factory|Application|RedirectResponse
    {
        $coupon->load('translations');
        $language = getWebConfig('language') ?? [];
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $zones = $this->zone->where('status' , 1)->get(['id','name']);
        $users = $this->user->where('status' , 1)->get(['id','f_name','l_name']);
        return view('rental::admin.coupon.edit', compact('coupon', 'language', 'defaultLang','zones','users'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Coupon $coupon, Request $request): RedirectResponse
    {
        $this->validateRequest($request, false, $coupon->id);
        try {
            DB::beginTransaction();
            $this->updatecoupon($request, $coupon);
            Helpers::add_or_update_translations(request: $request, key_data: 'title', name_field: 'title', model_name: 'Coupon', data_id: $coupon->id, data_value: $coupon->title);
            DB::commit();
            Toastr::success(translate('messages.coupon_updated_successfully'));
            return to_route('admin.rental.coupon.add-new');
        } catch (Exception  $exception) {
            info([$exception->getFile(), $exception->getLine(), $exception->getMessage()]);
            DB::rollBack();
            Toastr::error(translate('messages.failed_to_update_coupon'));
            return back();
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function status(Coupon $coupon): RedirectResponse
    {
        $coupon->update(['status' => !$coupon->status]);
        Toastr::success(translate('messages.coupon_status_updated_successfully'));
        return back();
    }


    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon?->translations()?->delete();
        $coupon?->delete();
        Toastr::success(translate('messages.coupon_deleted_successfully'));
        return back();
    }


    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $coupons = $this->getListData($request);
        $coupons =  $coupons->get();

        $data = [
            'data' => $coupons,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new CouponExport($data), 'Coupons.csv');
        }
        return Excel::download(new CouponExport($data), 'Coupons.xlsx');
    }

    /**
     * @param Request $request
     * @param $id
     * @return void
     */
    private function validateRequest(Request $request, $image = true, $id = null): void
    {
        $request->validate([
                'code' => 'required|max:100|unique:coupons,code' . ($id ? ','.$id : ''),
                'title.0' => 'required|max:191',
                'start_date' => 'required',
                'expire_date' => 'required',
                'discount' => 'required',
                'coupon_type' => 'required|in:zone_wise,store_wise,first_order,default',
                'zone_ids' => 'required_if:coupon_type,zone_wise',
                'store_ids' => 'required_if:coupon_type,store_wise',
                'title.0' => 'required',
            ],
            [
                'title.0.required'=>translate('default_title_is_required'),
            ]);
    }

    /**
     * @param Request $request
     * @return Coupon
     */
    private function createcoupon(Request $request): Coupon
    {
        $coupon = $this->coupon;
        return  $this->updatecoupon($request, $coupon);
    }
    private function updatecoupon(Request $request, Coupon $coupon): Coupon
    {
        $data  = '';
        $customerId  = $request->customer_ids ?? ['all'];
        if($request->coupon_type == 'zone_wise')
        {
            $data = $request->zone_ids;
        }
        else if($request->coupon_type == 'store_wise')
        {
            $data = $request->store_ids;
        }

        $coupon->title = $request->title[array_search('default', $request->lang)];
        $coupon->code = $request->code;
        $coupon->limit = $request->coupon_type=='first_order'?1:$request->limit;
        $coupon->coupon_type = $request->coupon_type;
        $coupon->start_date = $request->start_date;
        $coupon->expire_date = $request->expire_date;
        $coupon->min_purchase = $request?->min_purchase ??  0;
        $coupon->max_discount = $request?->max_discount??  0;
        $coupon->discount = $request->discount ?? 0;
        $coupon->discount_type = $request->discount_type??'';
        $coupon->status =  1;
        $coupon->created_by =  'admin';
        $coupon->data =  json_encode($data);
        $coupon->customer_id =  json_encode($customerId);
        $coupon->module_id =  Config::get('module.current_module_id');
        $coupon->store_id =  is_array($data) && $request->coupon_type == 'store_wise' ? $data[0] : null ;
        $coupon->save();
        return $coupon;
    }

    private function getListData($request)
    {
            $key = explode(' ', $request['search']);
            $coupons =  $this->coupon->where('created_by','admin')
            ->where('module_id', Config::get('module.current_module_id'))
            ->when(isset($key), function($query)use($key){
                $query->where( function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('title', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%");
                    }
                });
            })
            ->latest();
        return $coupons;
    }
}
