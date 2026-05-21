<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use App\Models\OrderCancelReason;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;

class OrderCancelReasonController extends Controller
{

    public function edit($id)
    {
        $reason = OrderCancelReason::withoutGlobalScope('translate')->with('translations')->find($id);
        return response()->json(['view' => view('admin-views.business-settings.settings.partials._order-cancel-reason-edit', compact('reason'))->render()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'reason'=>'required|max:255',
            'user_type' =>'required|max:50',
            'reason.0' => 'required',
        ],[
            'reason.0.required'=>translate('default_reason_is_required'),
        ]);
        $cancelReason = new OrderCancelReason();
        $cancelReason->reason = $request->reason[array_search('default', $request->lang)];
        $cancelReason->user_type=$request->user_type;
        $cancelReason->created_at = now();
        $cancelReason->updated_at = now();
        $cancelReason->save();

        Helpers::add_or_update_translations(request: $request, key_data: 'reason', name_field: 'reason', model_name: 'OrderCancelReason', data_id: $cancelReason->id, data_value: $cancelReason->reason);

        Toastr::success(translate('messages.order_cancellation_reason_added_successfully'));
         return redirect()->back()->withFragment('order_cancellation_section');
    }
    public function destroy($cancelReason)
    {
        $cancelReason = OrderCancelReason::findOrFail($cancelReason);
        $cancelReason?->translations()?->delete();
        $cancelReason?->delete();
        Toastr::success(translate('messages.order_cancellation_reason_deleted_successfully'));
         return redirect()->back()->withFragment('order_cancellation_section');
    }

    public function status(Request $request)
    {
        $cancelReason = OrderCancelReason::findOrFail($request->id);
        $cancelReason->status = $request->status;
        $cancelReason->save();
        Toastr::success(translate('messages.status_updated'));
         return redirect()->back()->withFragment('order_cancellation_section');
    }
    public function update(Request $request)
    {
        $request->validate([
            'reason' => 'required|max:255',
            'user_type' =>'required|max:50',
            'reason.0' => 'required',
        ],[
            'reason.0.required'=>translate('default_reason_is_required'),
        ]);
        $cancelReason = OrderCancelReason::findOrFail($request->reason_id);
        $cancelReason->reason = $request->reason[array_search('default', $request->lang)];
        $cancelReason->user_type=$request->user_type;
        $cancelReason?->save();
        Helpers::add_or_update_translations(request: $request, key_data: 'reason', name_field: 'reason', model_name: 'OrderCancelReason', data_id: $cancelReason->id, data_value: $cancelReason->reason);

        Toastr::success(translate('order_cancellation_reason_updated_successfully'));
        return redirect()->back()->withFragment('order_cancellation_section');
    }
}
