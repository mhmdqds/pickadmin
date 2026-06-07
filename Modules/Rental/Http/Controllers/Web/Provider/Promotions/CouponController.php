<?php

namespace Modules\Rental\Http\Controllers\Web\Provider\Promotions;

use App\Models\Translation;
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
use Symfony\Component\HttpFoundation\BinaryFileResponse;



class CouponController extends Controller
{
    private Coupon $coupon;
    private Zone $zone;
    private User $user;
    private Helpers $helpers;

    public function __construct(Coupon $coupon, Zone $zone, User $user, Helpers $helpers)
    {
        $this->coupon = $coupon;
        $this->zone = $zone;
        $this->user = $user;
        $this->helpers = $helpers;
    }

    public function list(Request $request)
    {
        $coupons = $this->getListData($request);
        $coupons =  $coupons->paginate(config('default_pagination'));
        $zones = $this->zone->where('status' , 1)->get(['id','name']);
        $users = $this->user->where('status' , 1)->get(['id','f_name','l_name']);
        return view('rental::provider.coupon.list', compact('coupons','zones','users'));
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|unique:coupons|max:100',
            'title' => 'required|max:191',
            'start_date' => 'required',
            'expire_date' => 'required',
            'discount' => 'required',
            'coupon_type' => 'required|in:free_delivery,default',
            'title.0' => 'required',
        ],[
            'title.0.required'=>translate('default_title_is_required'),
        ]);
        $customer_id  = $request->customer_ids ?? ['all'];
        $data = "";
        $coupon = new Coupon();
        $coupon->title = $request->title[array_search('default', $request->lang)];
        $coupon->code = $request->code;
        $coupon->limit = $request->coupon_type=='first_order'?1:$request->limit;
        $coupon->coupon_type = $request->coupon_type;
        $coupon->start_date = $request->start_date;
        $coupon->expire_date = $request->expire_date;
        $coupon->min_purchase = $request->min_purchase != null ? $request->min_purchase : 0;
        $coupon->max_discount = $request->max_discount != null ? $request->max_discount : 0;
        $coupon->discount = $request->discount_type == 'amount' ? $request->discount : $request['discount'];
        $coupon->discount_type = $request->discount_type??'';
        $coupon->status = 1;
        $coupon->created_by = 'vendor';
        $coupon->data = json_encode($data);
        $coupon->store_id = Helpers::get_store_id();
        $coupon->module_id = Helpers::get_store_data()->module_id;
        $coupon->customer_id = json_encode($customer_id);
        $coupon->save();
        $data = [];
        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang as $index => $key) {
            if($default_lang == $key && !($request->title[$index])){
                if ($key != 'default') {
                    $data[] = array(
                        'translationable_type' => 'App\Models\Coupon',
                        'translationable_id' => $coupon->id,
                        'locale' => $key,
                        'key' => 'title',
                        'value' => $coupon->title,
                    );
                }
            }else{
                if ($request->title[$index] && $key != 'default') {
                    $data[] = array(
                        'translationable_type' => 'App\Models\Coupon',
                        'translationable_id' => $coupon->id,
                        'locale' => $key,
                        'key' => 'title',
                        'value' => $request->title[$index],
                    );
                }
            }
        }

        Translation::insert($data);

        Toastr::success(translate('messages.coupon_added_successfully'));
        return back();
    }

    /**
     * @param string $id
     * @return View|Factory|Application|RedirectResponse
     */
    public function edit($id): View|Factory|Application|RedirectResponse
    {
        $coupon = Coupon::withoutGlobalScope('translate')->where(['id' => $id])->where('created_by', 'vendor' )->first();
        return view('rental::provider.coupon.edit', compact('coupon'));
    }

    /**
     * Update the specified resource in storage.
     * @param Coupon $coupon
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'code' => 'required|max:100|unique:coupons,code,'.$id,
            'title' => 'required|max:191',
            'start_date' => 'required',
            'expire_date' => 'required',
            'discount' => 'required',
            'coupon_type' => 'required|in:free_delivery,default',
            'title.0' => 'required',
        ],[
            'title.0.required'=>translate('default_title_is_required'),
        ]);

        $customer_id  = $request->customer_ids ?? ['all'];

        $coupon = Coupon::find($id);
        $coupon->title = $request->title[array_search('default', $request->lang)];
        $coupon->code = $request->code;
        $coupon->limit = $request->coupon_type=='first_order'?1:$request->limit;
        $coupon->coupon_type = $request->coupon_type;
        $coupon->start_date = $request->start_date;
        $coupon->expire_date = $request->expire_date;
        $coupon->min_purchase = $request->min_purchase != null ? $request->min_purchase : 0;
        $coupon->max_discount = $request->max_discount != null ? $request->max_discount : 0;
        $coupon->discount = $request->discount_type == 'amount' ? $request->discount : $request['discount'];
        $coupon->discount_type = $request->discount_type??'';
        $coupon->customer_id = json_encode($customer_id);
        $coupon->save();
        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang as $index => $key) {
            if($default_lang == $key && !($request->title[$index])){
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\Coupon',
                            'translationable_id' => $coupon->id,
                            'locale' => $key,
                            'key' => 'title'
                        ],
                        ['value' => $coupon->title]
                    );
                }
            }else{

                if ($request->title[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\Coupon',
                            'translationable_id' => $coupon->id,
                            'locale' => $key,
                            'key' => 'title'
                        ],
                        ['value' => $request->title[$index]]
                    );
                }
            }
        }

        Toastr::success(translate('messages.coupon_updated_successfully'));
        return redirect()->route('vendor.rental_coupon.list');
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

    private function getListData($request)
    {
        $storeId = $this->helpers->get_store_id();
        $key = explode(' ', $request['search']);
        $coupons =  $this->coupon->where('created_by','vendor')
        ->where('store_id', $storeId)
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
