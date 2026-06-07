<?php

namespace Modules\Rental\Http\Controllers\Web\Admin\Promotions;

use Exception;
use App\Models\User;
use App\Models\CashBack;
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
use Modules\Rental\Exports\CashBackExport;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;



class CashBackController extends Controller
{
    private CashBack $cashback;
    private Helpers $helpers;
    private User $user;
    public function __construct(CashBack $cashback, User $user, Helpers $helpers)
    {
        $this->cashback = $cashback;
        $this->user = $user;
        $this->helpers = $helpers;
    }

    /**
     * @param Request $request
     * @return View|Factory|Application
     */
    public function list(Request $request): View|Factory|Application
    {
        $cashBacks = $this->getListData($request);
        $cashBacks =  $cashBacks->paginate(config('default_pagination'));
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $users = $this->user->ofStatus(1)->get(['id','f_name','l_name']);
        return view('rental::admin.cashback.list', compact('cashBacks', 'language', 'defaultLang','users'));
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
     public function store(Request $request): RedirectResponse
     {
         $request->validate([
             'title' => 'required',
             'cashback_type' => 'required|string',
             'same_user_limit' => 'required|integer',
             'cashback_amount' => 'required|numeric',
             'min_purchase' => 'nullable|numeric',
             'max_discount' => 'nullable|numeric',
             'start_date' => 'required|date|after_or_equal:today',
             'end_date' => 'required|date|after:start_date',
         ]);

         $title = $request->title[array_search('default', $request->lang)];
            if($request->cashback_type == 'percentage' && $request->cashback_amount > 100){
                Toastr::error(translate('cashback_amount_must_be_under_100%'));
                return back();
            }
         $cashbackData = [
             'title' => $title,
             'customer_id' => json_encode($request->customer_id),
             'cashback_type' => $request->cashback_type,
             'same_user_limit' => $request->same_user_limit,
             'cashback_amount' => $request->cashback_amount,
             'min_purchase' => $request->min_purchase ?? 0,
             'max_discount' => $request->max_discount ?? 0,
             'start_date' => $request->start_date,
             'end_date' => $request->end_date,
             'is_rental' => true,
         ];

         try {
             $cashback = $this->cashback->create($cashbackData);

             $this->helpers->add_or_update_translations(
                 request: $request,
                 key_data: 'title',
                 name_field: 'title',
                 model_name: 'CashBack',
                 data_id: $cashback->id,
                 data_value: $cashback->title
             );

             Toastr::success(translate('messages.Cashback created successfully'));
             return back();

         } catch (\Exception $e) {
             Toastr::error(translate('messages.Cashback not created successfully'));
             return back();
         }
     }

     /**
      * @param string $id
      * @return View|Factory|Application|RedirectResponse
      */
     public function edit(string $id): View|Factory|Application|RedirectResponse
     {
         $cashback = $this->cashback->findOrFail($id);
         $language = getWebConfig('language') ?? [];
         $defaultLang = str_replace('_', '-', app()->getLocale());
         $users = $this->user->ofStatus(1)->get(['id','f_name','l_name']);

         return view('rental::admin.cashback.edit', compact('cashback', 'language', 'defaultLang','users'));
     }

     /**
      * Update the specified resource in storage.
      * @param Request $request
      * @param string $id
      * @return RedirectResponse
      * @throws AuthorizationException
      */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'title' => 'required',
            'cashback_type' => 'required|string',
            'same_user_limit' => 'required|integer',
            'cashback_amount' => 'required|numeric',
            'min_purchase' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        if($request->cashback_type == 'percentage' && $request->cashback_amount > 100){
            Toastr::error(translate('cashback_amount_must_be_under_100%'));
            return back();
        }
        $cashback = $this->cashback->findOrFail($id);

        $title = $request->title[array_search('default', $request->lang)];

        $cashbackData = [
            'title' => $title,
            'customer_id' => json_encode($request->customer_id),
            'cashback_type' => $request->cashback_type,
            'same_user_limit' => $request->same_user_limit,
            'cashback_amount' => $request->cashback_amount,
            'min_purchase' => $request->min_purchase ?? 0,
            'max_discount' => $request->max_discount ?? 0,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_rental' => true,
        ];

        try {
            $cashback->update($cashbackData);

            $this->helpers->add_or_update_translations(
                request: $request,
                key_data: 'title',
                name_field: 'title',
                model_name: 'CashBack',
                data_id: $cashback->id,
                data_value: $cashback->title
            );

            Toastr::success(translate('messages.Cashback updated successfully'));
            return back();
        } catch (\Exception $e) {
            Toastr::error(translate('messages.Cashback not updated successfully'));
            return back();
        }
    }


    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function status(Request $request, $id): RedirectResponse
    {
        $cashback = $this->cashback->find($id);

        if (!$cashback) {
            Toastr::error(translate('messages.cashback_not_found'));
            return back();
        }

        $cashback->update(['status' => !$cashback->status]);

        Toastr::success(translate('messages.cashback_status_updated_successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $cashback = $this->cashback->find($id);

        if (!$cashback) {
            Toastr::error(translate('messages.failed_to_delete_cashback'));
            return back();
        }

        $cashback->translations()->delete();
        $cashback->delete();

        Toastr::success(translate('messages.cashback_deleted_successfully'));
        return back();
    }

    /**
     * @param $request
     * @return RedirectResponse
     */
    private function getListData($request)
    {
            $key = explode(' ', $request['search']);
            $cashBacks =  $this->cashback
            ->where('is_rental', true)
            ->when(isset($key), function($query)use($key){
                $query->where( function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('title', 'like', "%{$value}%");
                    }
                });
            })
            ->latest();
        return $cashBacks;
    }
}
