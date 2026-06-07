<?php

namespace Modules\Rental\Http\Controllers\Web\Admin\Promotions;

use Exception;
use App\Models\Zone;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Traits\FileManagerTrait;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Modules\Rental\Exports\PushNotificationExport;
use Illuminate\Contracts\Foundation\Application;

use Symfony\Component\HttpFoundation\BinaryFileResponse;



class NotificationController extends Controller
{
    use FileManagerTrait;

    public function __construct(private Notification $notification, private Zone $zone)
    {
        $this->notification = $notification;
        $this->zone = $zone;
    }

    public function list(Request $request)
    {
        $notifications = $this->getListData($request);
        $notifications =  $notifications->paginate(config('default_pagination'));
        $zones = $this->zone->where('status' , 1)->get(['id','name']);
        return view('rental::admin.push-notification.list', compact('notifications','zones'));
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
            $notification = $this->createnotification($request);
            Helpers::send_push_notif_to_topic($notification, $this->getTopic($request), 'general');
            DB::commit();
        } catch (Exception) {
            DB::rollBack();
            Toastr::error(translate('messages.failed_to_sent_notification'));
            return back();
        }
        Toastr::success(translate('messages.notification_sent_successfully'));
        return back();
    }

    // /**
    //  * @param string $id
    //  * @return View|Factory|Application|RedirectResponse
    //  */
    public function edit(Notification $notification): View|Factory|Application|RedirectResponse
    {

        $zones = $this->zone->where('status' , 1)->get(['id','name']);
        return view('rental::admin.push-notification.edit', compact('notification', 'zones'));
    }

    // /**
    //  * Update the specified resource in storage.
    //  * @param Request $request
    //  * @param string $id
    //  * @return RedirectResponse
    //  * @throws AuthorizationException
    //  */
    public function update(Notification $notification, Request $request): RedirectResponse
    {
        $this->validateRequest($request, false, $notification->id);
        try {
            DB::beginTransaction();
            $this->updatenotification($request, $notification);
            Helpers::send_push_notif_to_topic($notification, $this->getTopic($request), 'general');
            DB::commit();
            Toastr::success(translate('messages.notification_updated_successfully'));
            return to_route('admin.rental.notification.list');
        } catch (Exception) {
            DB::rollBack();
            Toastr::error(translate('messages.failed_to_update_notification'));
            return back();
        }
    }

    // /**
    //  * @param Request $request
    //  * @param $id
    //  * @return RedirectResponse
    //  */
    public function status(Notification $notification): RedirectResponse
    {
        $notification->update(['status' => !$notification->status]);
        Toastr::success(translate('messages.notification_status_updated_successfully'));
        return back();
    }


    public function destroy(Notification $notification): RedirectResponse
    {
        if ($notification->image) {
            Helpers::check_and_delete('notification/' , $notification->image);
        }
        $notification?->delete();
        Toastr::success(translate('messages.notification_deleted_successfully'));
        return back();
    }


    // /**
    //  * @param Request $request
    //  * @return BinaryFileResponse
    //  */
    public function export(Request $request): BinaryFileResponse
    {
        $notifications = $this->getListData($request);
        $notifications =  $notifications->get();

        $data = [
            'data' => $notifications,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new PushNotificationExport($data), 'Notifications.csv');
        }
        return Excel::download(new PushNotificationExport($data), 'Notifications.xlsx');
    }

    // /**
    //  * @param Request $request
    //  * @param $id
    //  * @return void
    //  */
    private function validateRequest(Request $request): void
    {
        $request->validate([
                'notification_title' => 'required|max:191',
                'description' => 'required|max:1000',
                'tergat' => 'required',
                'zone'=>'required',
                'image' => 'nullable|max:2048',
            ]);
    }

    // /**
    //  * @param Request $request
    //  * @return Notification
    //  */
    private function createnotification(Request $request): Notification
    {
        $notification = $this->notification;
        return  $this->updatenotification($request, $notification, true);
    }
    private function updatenotification(Request $request, Notification $notification , $new_image = false): Notification
    {

        if ($request->hasFile('image')) {
            $notification->image = $new_image == true ? $this->upload('notification/', 'png', $request->file('image')) :$this->updateAndUpload('notification/', $notification->image ,'png', $request->file('image'));
        }

        $notification->title = $request->notification_title;
        $notification->description = $request->description;
        $notification->tergat= $request->tergat == 'store' ? 'provider': $request->tergat ;
        $notification->status = 1;
        $notification->zone_id = $request->zone=='all'?null:$request->zone;
        $notification->save();
        if($notification?->image){
            $notification->image = $notification->toArray()['image_full_url'];
        }
        return $notification;
    }

    private function getListData($request)
    {
        $key = explode(' ', $request['search']);
        $notifications = Notification::with('zone')
        ->when(isset($key), function ($q) use ($key){
            $q->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('title', 'like', "%{$value}%");
                }
            });
        })->latest();
        return $notifications;
    }
    public function getTopic(Object $request): string
    {
        $topicAllZone =[
            'customer'=>'all_zone_customer',
            'store'=>'all_zone_store',
        ];

        $topicZoneWise=[
            'customer'=>'zone_'.$request->zone.'_customer',
            'store'=>'zone_'.$request->zone.'_store',
        ];

        return $request->zone == 'all'?$topicAllZone[$request->tergat]:$topicZoneWise[$request->tergat];
    }
}
