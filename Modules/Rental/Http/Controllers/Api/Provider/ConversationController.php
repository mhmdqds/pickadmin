<?php

namespace Modules\Rental\Http\Controllers\Api\Provider;

use App\CentralLogics\Helpers;
use App\Models\Conversation;
use App\Models\DeliveryMan;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Models\UserInfo;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Rental\Entities\VehicleDriver;

class ConversationController extends Controller
{
    private User $user;
    private Order $order;
    private Vendor $vendor;
    private UserInfo $userInfo;
    private Message $message;
    private Conversation $conversation;
    private VehicleDriver $vehicleDriver;
    private Helpers $helpers;

    public function __construct(Vendor $vendor, Helpers $helpers, UserInfo $userInfo, Conversation $conversation, VehicleDriver $vehicleDriver, User $user, Order $order, Message $message)
    {
        $this->user = $user;
        $this->order = $order;
        $this->vendor = $vendor;
        $this->userInfo = $userInfo;
        $this->message = $message;
        $this->conversation = $conversation;
        $this->vehicleDriver = $vehicleDriver;
        $this->helpers = $helpers;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function conversations(Request $request): JsonResponse
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

        $vendor = $this->vendor->find($request->vendor->id);
        $sender = $this->userInfo->where('vendor_id', $vendor->id)->first();

        if(!$sender){
            $sender = $this->userInfo;
            $sender->vendor_id = $vendor->id;
            $sender->f_name = $vendor->stores[0]->name;
            $sender->l_name = '';
            $sender->phone = $vendor->phone;
            $sender->email = $vendor->email;
            $sender->image = $vendor->stores[0]->logo;
            $sender->save();
        }

        $conversations = $this->conversation
            ->with(['sender', 'receiver','last_message'])
            ->where(['sender_id' => $sender->id])
            ->orWhere(['receiver_id' => $sender->id])
            ->orderBy('last_message_time', 'DESC')
            ->paginate($limit, ['*'], 'page', $offset);

        $data = $this->helpers->preparePaginatedResponse(pagination:$conversations, limit:$limit, offset:$offset, key:'conversation', extraData:[]);

        return response()->json($data, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'limit' => 'required',
            'offset' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $limit = $request['limit'];
        $offset = $request['offset'];
        $key = explode(' ', $request['name']);

        $vendor = $this->vendor->find($request->vendor->id);
        $sender = $this->userInfo->where('vendor_id', $vendor->id)->first();

        if(!$sender){
            $sender = $this->userInfo;
            $sender->vendor_id = $vendor->id;
            $sender->f_name = $vendor->stores[0]->name;
            $sender->l_name = '';
            $sender->phone = $vendor->phone;
            $sender->email = $vendor->email;
            $sender->image = $vendor->stores[0]->logo;
            $sender->save();
        }

        $conversations = $this->conversation
            ->with('sender','receiver','last_message')
            ->WhereUser($sender->id)->where(function($qu)use($key){
                $qu->whereHas('sender',function($query)use($key){
                    foreach ($key as $value) {
                        $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                    }
                })
            ->orWhereHas('receiver',function($query1)use($key){
                foreach ($key as $value) {
                    $query1->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                }
            });
        });

        $conversations = $conversations->orderBy('last_message_time', 'DESC')->paginate($limit, ['*'], 'page', $offset);
        $data = $this->helpers->preparePaginatedResponse(pagination:$conversations, limit:$limit, offset:$offset, key:'conversation', extraData:[]);

        return response()->json($data, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function messages(Request $request): JsonResponse
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
        $driverId = $request->driver_id;
        $userId = $request->user_id;
        $conversationId = $request->conversation_id;

        $vnd = $this->vendor->find($request->vendor->id);
        $vendor = $this->userInfo->where('vendor_id', $vnd->id)->first();

        if(!$vendor){
            $vendor = $this->userInfo;
            $vendor->vendor_id = $vnd->id;
            $vendor->f_name = $vnd->stores[0]->name;
            $vendor->l_name = '';
            $vendor->phone = $vnd->phone;
            $vendor->email = $vnd->email;
            $vendor->image = $vnd->stores[0]->logo;
            $vendor->save();
        }

        if($conversationId)
        {
            $conversation = $this->conversation->with(['sender','receiver'])->find($conversationId);
        }
        else if($driverId)
        {
            $dm = $this->userInfo->where('deliveryman_id', $driverId)->first();
            $user = $this->vehicleDriver->find($driverId);

            if(!$dm){
                $dm = $this->userInfo;
                $dm->deliveryman_id = $user->id;
                $dm->f_name = $user->f_name;
                $dm->l_name = $user->l_name;
                $dm->phone = $user->phone;
                $dm->email = $user->email;
                $dm->image = $user->image;
                $dm->save();
            }

            $conversation = $this->conversation
                ->with(['sender','receiver','last_message'])
                ->WhereConversation($vendor->id, $dm->id)->first();

        }
        else if($userId)
        {
            $user = $this->userInfo->where('user_id', $userId)->first();
            if(!$user){
                $customer = $this->user->find($userId);
                $user = new UserInfo();
                $user->user_id = $customer->id;
                $user->f_name = $customer->f_name;
                $user->l_name = $customer->l_name;
                $user->phone = $customer->phone;
                $user->email = $customer->email;
                $user->image = $customer->image;
                $user->save();
            }

            $conversation = $this->conversation
                ->with(['sender','receiver','last_message'])
                ->WhereConversation($vendor->id, $user->id)->first();
        }

        if($conversation){
            if($conversation->sender_type == 'customer' && $conversation->sender)
            {
                $user = $this->user->find($conversation->sender->user_id);
                $order = $this->order
                    ->where('store_id',$vnd->stores[0]->id)
                    ->where('user_id', $user->id)
                    ->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])
                    ->count();
            }
            else if($conversation->receiver_type == 'customer'  && $conversation->receiver)
            {
                $user = $this->user->find($conversation->receiver->user_id);
                $order = $this->order
                    ->where('store_id',$vnd->stores[0]->id)
                    ->where('user_id', $user->id)
                    ->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])
                    ->count();
            }
            else if($conversation->sender_type == 'delivery_man'&& $conversation->sender)
            {
                $user2 = $this->vehicleDriver->find($conversation->sender->deliveryman_id);
                $order = $this->order
                    ->where('store_id',$vnd->stores[0]->id)
                    ->where('delivery_man_id', $user2->id)
                    ->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])
                    ->count();
            }
            else if($conversation->receiver_type == 'delivery_man' && $conversation->receiver)
            {
                $user2 = $this->vehicleDriver->find($conversation->receiver->deliveryman_id);
                $order = $this->order
                    ->where('store_id',$vnd->stores[0]->id)
                    ->where('delivery_man_id', $user2->id)
                    ->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])
                    ->count();
            }
            else{
                $order = 0;
            }

            $lastmessage = $conversation->last_message;
            if($lastmessage && $lastmessage->sender_id != $vendor->id ) {
                $conversation->unread_message_count = 0;
                $conversation->save();
            }
            $this->message
                ->where(['conversation_id' => $conversation->id])
                ->where('sender_id','!=', $vendor->id)->update(['is_seen' => 1]);
            $messages = $this->message
                ->where(['conversation_id' => $conversation->id])
                ->latest()->paginate($limit, ['*'], 'page', $offset);
        }
        else
        {
            $messages = [];
            $messages = new LengthAwarePaginator(
                $messages,
                count($messages),
                $limit,
                $offset / $limit + 1
            );
            $order = 0;
        }

        $extraData =  [
            'status' => $order > 0,
            'conversation' => $conversation
        ];

        $data = $this->helpers->preparePaginatedResponse(pagination:$messages, limit:$limit, offset:$offset, key:'messages', extraData:$extraData);

        return response()->json($data, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function messagesStore(Request $request): JsonResponse
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
        $conversationId = $request->conversation_id;

        if ($request->has('image')) {
            $imageName = [];
            foreach($request->file('image') as $key=>$img)
            {

                $name = $this->helpers->upload('conversation/', 'png', $img);
                $imageName[] = ['img'=>$name, 'storage'=> $this->helpers->getDisk()];
            }
        } else {
            $imageName = null;
        }

        $vendor = $this->vendor->find($request->vendor->id);
        $sender = $this->userInfo->where('vendor_id', $vendor->id)->first();

        if(!$sender){
            $sender = $this->userInfo;
            $sender->vendor_id = $vendor->id;
            $sender->f_name = $vendor->stores[0]->name;
            $sender->l_name = '';
            $sender->phone = $vendor->phone;
            $sender->email = $vendor->email;
            $sender->image = $vendor->stores[0]->logo;
            $sender->save();
        }

        if($conversationId){
            $conversation = $this->conversation->find($conversationId);

            if($conversation?->sender_id == $sender->id)
            {
                $receiver_id = $conversation->receiver_id;
                $receiver = $this->userInfo->find($receiver_id);

                if($receiver->deliveryman_id)
                {
                    $delivery_man = $this->vehicleDriver->find($receiver->deliveryman_id);
                    $fcm_token = $delivery_man->fcm_token;
                }
                elseif($receiver->user_id)
                {
                    $user = $this->user->find($receiver->user_id);
                    $fcm_token = $user->cm_firebase_token;
                }
            }else
            {
                $receiver_id = $conversation->sender_id;
                $receiver = $this->userInfo->find($receiver_id);

                if($receiver->deliveryman_id)
                {
                    $delivery_man = $this->vehicleDriver->find($receiver->deliveryman_id);
                    $fcm_token=$delivery_man->fcm_token;
                }
                elseif($receiver->user_id)
                {
                    $user = $this->user->find($receiver->user_id);
                    $fcm_token=$user->cm_firebase_token;
                }
            }
        }else{

            if($request->receiver_type == 'customer'){
                $receiver = $this->userInfo->where('user_id',$request->receiver_id)->first();
                $user = $this->user->find($request->receiver_id);

                if(!$receiver){
                    $receiver = $this->userInfo;
                    $receiver->user_id = $user->id;
                    $receiver->f_name = $user->f_name;
                    $receiver->l_name = $user->l_name;
                    $receiver->phone = $user->phone;
                    $receiver->email = $user->email;
                    $receiver->image = $user->image;
                    $receiver->save();
                }

                $receiver_id = $receiver->id;
                $fcm_token=$user->cm_firebase_token;

            }else if($request->receiver_type == 'delivery_man')
            {
                $receiver = $this->userInfo->where('deliveryman_id',$request->receiver_id)->first();
                $delivery_man = $this->vehicleDriver->find($request->receiver_id);

                if(!$receiver){
                    $receiver = $this->userInfo;
                    $receiver->deliveryman_id = $delivery_man->id;
                    $receiver->f_name = $delivery_man->f_name;
                    $receiver->l_name = $delivery_man->l_name;
                    $receiver->phone = $delivery_man->phone;
                    $receiver->email = $delivery_man->email;
                    $receiver->image = $delivery_man->image;
                    $receiver->save();
                }

                $receiver_id = $receiver->id;
                $fcm_token = $delivery_man->fcm_token;
            }
        }

        $conversation = $this->conversation
            ->with('sender','receiver','last_message')
            ->WhereConversation($sender->id,$receiver_id)
            ->first();

        if(!$conversation){
            $conversation = $this->conversation;
            $conversation->sender_id = $sender->id;
            $conversation->sender_type = 'vendor';
            $conversation->receiver_id = $receiver->id;
            $conversation->receiver_type = $request->receiver_type;
            $conversation->unread_message_count = 0;
            $conversation->last_message_time = Carbon::now()->toDateTimeString();
            $conversation->save();
            $conversation = $this->conversation->find($conversation->id);
        }

        $message = $this->message;
        $message->conversation_id = $conversation->id;
        $message->sender_id = $sender->id;
        $message->message = $request->message;

        if($imageName && count($imageName) > 0)
        {
            $message->file = json_encode($imageName, JSON_UNESCAPED_SLASHES);
        }

        try {
            if($message->save())
            {
                $conversation->unread_message_count = $conversation->unread_message_count? $conversation->unread_message_count+1:1;
                $conversation->last_message_id=$message->id;
                $conversation->last_message_time = Carbon::now()->toDateTimeString();
                $conversation->save();

                $data = [
                    'title' =>translate('messages.message_from')." ".$sender->f_name,
                    'description' => $message->message ?? translate('attachment'),
                    'order_id' => '',
                    'image' => '',
                    'type'=> 'message',
                    'conversation_id'=> $conversation->id,
                    'sender_type'=> 'vendor'
                ];

                $this->helpers->send_push_notif_to_device($fcm_token, $data);

            }

        } catch (\Exception $e) {
            info($e->getMessage());
        }

        $messages = $this->message
            ->where(['conversation_id' => $conversation->id])
            ->latest()->paginate($limit, ['*'], 'page', $offset);

        $conv = $this->conversation->with('sender','receiver','last_message')->find($conversation->id);

        if($conv->sender_type == 'customer' && $conversation->sender)
        {
            $user = $this->user->find($conv->sender->user_id);

            $order = $this->order
                ->where('store_id',$vendor->stores[0]->id)
                ->where('user_id', $user->id)
                ->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])
                ->count();
        }
        else if($conv->receiver_type == 'customer' && $conversation->receiver)
        {
            $user = $this->user->find($conv->receiver->user_id);
            $order = $this->order
                ->where('store_id',$vendor->stores[0]->id)
                ->where('user_id', $user->id)
                ->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])
                ->count();
        }
        else if($conv->sender_type == 'delivery_man' && $conversation->sender)
        {
            $user2 = $this->vehicleDriver->find($conv->sender->deliveryman_id);
            $order = $this->order
                ->where('store_id',$vendor->stores[0]->id)
                ->where('delivery_man_id', $user2->id)
                ->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])
                ->count();
        }
        else if($conv->receiver_type == 'delivery_man' && $conversation->receiver)
        {
            $user2 = $this->vehicleDriver->find($conv->receiver->deliveryman_id);
            $order = $this->order
                ->where('store_id',$vendor->stores[0]->id)
                ->where('delivery_man_id', $user2->id)
                ->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])
                ->count();
        }
        else{
            $order = 0;
        }

        $extraData =  [
            'status' => $order > 0,
            'message' => 'successfully sent!',
            'conversation' => $conv,
        ];

        $data = $this->helpers->preparePaginatedResponse(pagination:$messages, limit:$limit, offset:$offset, key:'messages', extraData:$extraData);

        return response()->json($data, 200);
    }
}
