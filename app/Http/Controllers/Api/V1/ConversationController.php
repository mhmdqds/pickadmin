<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AutomatedMessage;
use App\Models\Conversation;
use App\Models\DeliveryMan;
use App\Models\UserInfo;
use App\Models\Message;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Rental\Entities\Trips;
use Modules\RideShare\Entities\TripManagement\RideRequest;

class ConversationController extends Controller
{
    /**
     * Store a new message inside a conversation.
     *
     * Enhanced behavior:
     * - When the customer opens the Support Chat from an order (first message)
     *   and the request includes:
     *       receiver_type = admin
     *       order_id      = <order id>
     *   then in addition to the existing Customer ↔ Admin conversation,
     *   the backend transparently creates (if not already exists) the
     *   Customer ↔ Vendor conversation, copies the same message into
     *   both conversations and dispatches push notifications to both
     *   the Admin (existing) and the Vendor (new).
     *
     * The same response shape used by Flutter is preserved so no mobile
     * changes are required.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function messages_store(Request $request)
    {
        if ($request->has('image')) {
            $image_name = [];
            foreach ($request->file('image') as $key => $img) {
                $name = Helpers::upload('conversation/', 'png', $img);
                $image_name[] = ['img' => $name, 'storage' => Helpers::getDisk()];
            }
        } else {
            $image_name = null;
        }

        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;
        $fcm_token_web = null;

        $sender = UserInfo::where('user_id', $request->user()->id)->first();
        if (!$sender) {
            $sender = $this->createCustomerUserInfo($request);
        }

        if ($request->conversation_id) {
            [$conversation, $receiver_id, $fcm_token, $fcm_token_web] =
                $this->loadConversationById($request->conversation_id, $sender);
        } else {
            if ($request->receiver_type == 'admin') {
                $receiver_id = 0;
                $fcm_token = null;
                $fcm_token_web = null;
            } else if ($request->receiver_type == 'vendor') {
                $receiver = $this->getOrCreateVendorUserInfoById($request->receiver_id);
                $vendor = Vendor::find($request->receiver_id);
                $receiver_id = $receiver->id;
                $fcm_token = $vendor->firebase_token ?? null;
                $fcm_token_web = isset($vendor->stores[0])
                    ? "store_panel_{$vendor->stores[0]->id}_message"
                    : null;
            } else if ($request->receiver_type == 'delivery_man') {
                $receiver = $this->getOrCreateDeliveryManUserInfo($request->receiver_id);
                $delivery_man = DeliveryMan::find($request->receiver_id);
                $receiver_id = $receiver->id;
                $fcm_token = $delivery_man->fcm_token ?? null;
                $fcm_token_web = null;
            }
        }

        $existingConversation = Conversation::WhereConversation($sender->id, $receiver_id)->first();

        /*
         * Detect "First Support Message" scenario.
         *
         * Triggered when:
         *  - This is a customer → admin message (admin receiver)
         *  - No conversation_id passed (new conversation branch)
         *  - order_id is supplied (Support Chat launched from order)
         *  - No prior Customer ↔ Admin conversation exists for this customer
         */
        $orderId = $request->order_id ?? null;
        $isFirstSupportMessage =
            $request->receiver_type === 'admin'
            && empty($request->conversation_id)
            && !empty($orderId)
            && $existingConversation === null;

        /*
         * Atomic block:
         *  - Persist primary conversation (Customer ↔ Admin)
         *  - Save primary message
         *  - When triggered, mirror the same message into a
         *    Customer ↔ Vendor conversation driven from the order.
         * Any DB failure rolls everything back.
         */
        try {
            $payload = DB::transaction(function () use (
                $request, $sender, $receiver_id, $existingConversation,
                $image_name, $orderId, $isFirstSupportMessage
            ) {
                // 1. Ensure primary conversation exists (Customer ↔ Admin)
                $conversation = $existingConversation;
                if (!$conversation) {
                    $conversation = $this->createConversation(
                        $sender->id,
                        'customer',
                        $receiver_id,
                        $request->receiver_type
                    );
                }

                // 2. Save the primary message in the Customer ↔ Admin conversation
                $message = $this->saveMessage(
                    $conversation->id,
                    $sender->id,
                    $request->message,
                    $image_name,
                    $orderId
                );

                // 3. Update primary conversation metadata
                $this->updateConversationMetadata($conversation, $message);

                // 4. If first support message → mirror to Customer ↔ Vendor conversation
                $vendorMirrorPayload = null;
                if ($isFirstSupportMessage) {
                    $vendorMirrorPayload = $this->mirrorMessageToVendorConversation(
                        $sender,
                        $orderId,
                        $request->message,
                        $image_name
                    );
                }

                return [
                    'conversation' => $conversation,
                    'message' => $message,
                    'fcm_token' => null,
                    'fcm_token_web' => null,
                    'vendor_payload' => $vendorMirrorPayload,
                ];
            });

            $conversation = $payload['conversation'];
            $message = $payload['message'];
            $vendorPayload = $payload['vendor_payload'];

            // 4. Send push notifications (fire-and-forget; failures must not roll back).
            $this->sendMessageNotification(
                $request->receiver_type,
                $receiver_id,
                $message,
                $conversation->id,
                $fcm_token ?? null,
                $fcm_token_web ?? null
            );

            // 5. Send vendor push notification when mirroring happened.
            if ($vendorPayload !== null) {
                $this->sendVendorPushNotification(
                    $vendorPayload['vendor'],
                    $vendorPayload['message'],
                    $vendorPayload['conversation']->id
                );
            }
        } catch (\Exception $e) {
            info($e->getMessage());
            // On failure, return a clean error response.
            return response()->json([
                'errors' => [[
                    'code' => 'message_save_failed',
                    'message' => $e->getMessage(),
                ]],
            ], 500);
        }

        $messages = Message::where(['conversation_id' => $conversation->id])->with('order')->latest()->paginate($limit, ['*'], 'page', $offset);
        $messages->getCollection()->transform(function ($message) {
            if ($message->order) {
                $message->order->delivery_address = gettype($message->order->delivery_address) == 'string' ? json_decode($message->order->delivery_address, true) : $message->order->delivery_address;
                $message->order->id = (int) $message->order->id;
                $message->order->order_amount = (float) $message->order->order_amount;
                $message->order->details_count = (int) $message->order->details_count;
            }

            return $message;
        });

        $conv = Conversation::with('sender', 'receiver', 'last_message')->find($conversation->id);

        if ($conv->sender_type == 'vendor' && $conv->sender) {
            $vd = Vendor::find($conv->sender->vendor_id);
            if ($vd?->store?->module_type == 'rental' && addon_published_status('Rental')) {
                $order = Trips::where('user_id', $request->user()->id)->where('provider_id', $vd->store->id)->whereIn('trip_status', ['pending', 'confirmed', 'ongoing', 'completed'])->where('payment_status', 'unpaid')->count();
            } else {
                $order = Order::where('user_id', $request->user()->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
            }
        } else if ($conv->receiver_type == 'vendor' && $conv->receiver) {
            $vd = Vendor::find($conv->receiver->vendor_id);

            if ($vd?->store?->module_type == 'rental' && addon_published_status('Rental')) {
                $order = Trips::where('user_id', $request->user()->id)->where('provider_id', $vd->store->id)->whereIn('trip_status', ['pending', 'confirmed', 'ongoing', 'completed'])->where('payment_status', 'unpaid')->count();
            } else {
                $order = Order::where('user_id', $request->user()->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
            }
        } else if ($conv->sender_type == 'delivery_man' && $conv->sender) {
            $user2 = DeliveryMan::find($conv->sender->deliveryman_id);
            $order = Order::where('user_id', $request->user()->id)->where('delivery_man_id', $user2->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
            if (($order <= 0) && addon_published_status('RideShare')) {
                $order = RideRequest::where('customer_id', $request->user()->id)->where('driver_id', $user2->id)->whereIn('current_status', ['pending', 'accepted', 'ongoing'])->count();
            }
        } else if ($conv->receiver_type == 'delivery_man' && $conv->receiver) {
            $user2 = DeliveryMan::find($conv->receiver->deliveryman_id);
            $order = Order::where('user_id', $request->user()->id)->where('delivery_man_id', $user2->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
            if (($order <= 0) && addon_published_status('RideShare')) {
                $order = RideRequest::where('customer_id', $request->user()->id)->where('driver_id', $user2->id)->whereIn('current_status', ['pending', 'accepted', 'ongoing'])->count();
            }
        } else {
            $order = 1;
        }

        $data = [
            'total_size' => intval($messages->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'status' => ($order > 0) ? true : false,
            'message' => 'successfully sent!',
            'messages' => $messages->items(),
            'conversation' => $conv,
        ];
        return response()->json($data, 200);
    }

    /**
     * Replicate a vendor's reply in the Customer ↔ Admin conversation
     * (reverse synchronization) and dispatch a push notification to the
     * customer.
     *
     * Called from the Vendor-side controller when the vendor responds to
     * a customer message.
     *
     * @param Conversation $vendorConversation  Customer ↔ Vendor conversation
     * @param Message      $vendorMessage       Newly saved vendor message
     * @return void
     */
    public function mirrorVendorReplyToAdminConversation(Conversation $vendorConversation, Message $vendorMessage): void
    {
        try {
            DB::transaction(function () use ($vendorConversation, $vendorMessage) {
                /*
                 * Locate the Customer ↔ Admin conversation. In that
                 * conversation the Customer is the sender and the receiver
                 * is id 0 (admin).
                 */
                $customerInfo = $vendorConversation->receiver_type === 'customer'
                    ? UserInfo::find($vendorConversation->receiver_id)
                    : UserInfo::find($vendorConversation->sender_id);

                if (!$customerInfo || !$customerInfo->user_id) {
                    return;
                }

                $adminConversation = Conversation::where(function ($q) use ($customerInfo) {
                    $q->where('sender_id', $customerInfo->id)->where('receiver_id', 0);
                })->orWhere(function ($q) use ($customerInfo) {
                    $q->where('sender_id', 0)->where('receiver_id', $customerInfo->id);
                })->first();

                if (!$adminConversation) {
                    return;
                }

                /*
                 * Copy the vendor reply into the Customer ↔ Admin
                 * conversation as a separate message record. The vendor's
                 * UserInfo is used as sender_id so the admin sees the
                 * vendor is the source of the new message.
                 */
                $vendorInfo = $vendorConversation->receiver_type === 'vendor'
                    ? UserInfo::find($vendorConversation->receiver_id)
                    : UserInfo::find($vendorConversation->sender_id);

                if (!$vendorInfo) {
                    return;
                }

                $mirrored = new Message();
                $mirrored->conversation_id = $adminConversation->id;
                $mirrored->sender_id = $vendorInfo->id;
                $mirrored->message = $vendorMessage->message;
                if (!empty($vendorMessage->file)) {
                    $mirrored->file = $vendorMessage->file;
                }
                if (!empty($vendorMessage->order_id)) {
                    $mirrored->order_id = $vendorMessage->order_id;
                }
                $mirrored->save();

                $this->updateConversationMetadata($adminConversation, $mirrored);

                // Notify admin about the mirrored vendor reply.
                $this->sendAdminPushNotification($mirrored, $adminConversation->id);

                // Notify customer about the vendor reply.
                if (isset($customerInfo->user) && !empty($customerInfo->user->cm_firebase_token)) {
                    $this->sendCustomerPushNotification(
                        $customerInfo->user->cm_firebase_token,
                        $vendorMessage,
                        $vendorConversation->id
                    );
                }
            });
        } catch (\Exception $e) {
            info($e->getMessage());
        }
    }

    /**
     * Create a fresh Conversation model and persist it.
     *
     * @param int    $senderId
     * @param string $senderType
     * @param int    $receiverId
     * @param string $receiverType
     * @return Conversation
     */
    private function createConversation(int $senderId, string $senderType, int $receiverId, string $receiverType): Conversation
    {
        $conversation = new Conversation();
        $conversation->sender_id = $senderId;
        $conversation->sender_type = $senderType;
        $conversation->receiver_id = $receiverId;
        $conversation->receiver_type = $receiverType;
        $conversation->unread_message_count = 0;
        $conversation->last_message_time = Carbon::now()->toDateTimeString();
        $conversation->save();

        return Conversation::find($conversation->id);
    }

    /**
     * Persist a new Message and return it.
     *
     * @param int         $conversationId
     * @param int         $senderId
     * @param string|null $body
     * @param array|null  $imageName
     * @param int|null    $orderId
     * @return Message
     */
    private function saveMessage(int $conversationId, int $senderId, ?string $body, $imageName, ?int $orderId): Message
    {
        $message = new Message();
        $message->conversation_id = $conversationId;
        $message->sender_id = $senderId;
        $message->message = $body;
        $message->order_id = $orderId;

        if ($imageName && count($imageName) > 0) {
            $message->file = json_encode($imageName, JSON_UNESCAPED_SLASHES);
        }

        $message->save();
        return $message;
    }

    /**
     * Update Conversation metadata (last_message, last_message_time, unread count).
     *
     * @param Conversation $conversation
     * @param Message      $message
     * @return void
     */
    private function updateConversationMetadata(Conversation $conversation, Message $message): void
    {
        $conversation->unread_message_count = $conversation->unread_message_count
            ? $conversation->unread_message_count + 1
            : 1;
        $conversation->last_message_id = $message->id;
        $conversation->last_message_time = Carbon::now()->toDateTimeString();
        $conversation->save();
    }

    /**
     * Look up an existing conversation by id, resolve its
     * receiver details and gather FCM push tokens.
     *
     * @param int      $conversationId
     * @param UserInfo $sender
     * @return array{0:Conversation,1:int,2:?string,3:?string}
     */
    private function loadConversationById(int $conversationId, UserInfo $sender): array
    {
        $conversation = Conversation::find($conversationId);
        $fcm_token = null;
        $fcm_token_web = null;

        if ($conversation->sender_id == $sender->id) {
            $receiver_id = $conversation->receiver_id;
            $receiver = UserInfo::find($receiver_id);
            if ($receiver?->vendor_id) {
                $vendor = Vendor::find($receiver->vendor_id);
                $fcm_token = $vendor->firebase_token ?? null;
                $fcm_token_web = isset($vendor->stores[0])
                    ? "store_panel_{$vendor->stores[0]->id}_message"
                    : null;
            } elseif ($receiver?->deliveryman_id) {
                $delivery_man = DeliveryMan::find($receiver->deliveryman_id);
                $fcm_token = $delivery_man->fcm_token ?? null;
            } elseif ($receiver?->admin_id) {
                $receiver_id = 0;
            }
        } else {
            $receiver_id = $conversation->sender_id;
            $receiver = UserInfo::find($receiver_id);
            if ($receiver?->vendor_id) {
                $vendor = Vendor::find($receiver->vendor_id);
                $fcm_token = $vendor->firebase_token ?? null;
                $fcm_token_web = isset($vendor->stores[0])
                    ? "store_panel_{$vendor->stores[0]->id}_message"
                    : null;
            } elseif ($receiver?->deliveryman_id) {
                $delivery_man = DeliveryMan::find($receiver->deliveryman_id);
                $fcm_token = $delivery_man->fcm_token ?? null;
            } elseif ($receiver?->admin_id) {
                $receiver_id = 0;
            }
        }

        return [$conversation, $receiver_id, $fcm_token, $fcm_token_web];
    }

    /**
     * Get (or create) a UserInfo record for a given Vendor id.
     *
     * @param int $vendorId
     * @return UserInfo
     */
    private function getOrCreateVendorUserInfoById(int $vendorId): UserInfo
    {
        $vendor = Vendor::find($vendorId);
        return $this->getOrCreateVendorUserInfo($vendor);
    }

    /**
     * Get (or create) a UserInfo record for the given Vendor model.
     *
     * @param Vendor $vendor
     * @return UserInfo
     */
    private function getOrCreateVendorUserInfo(Vendor $vendor): UserInfo
    {
        $receiver = UserInfo::where('vendor_id', $vendor->id)->first();
        if (!$receiver) {
            $store = $vendor->stores[0] ?? null;
            $receiver = new UserInfo();
            $receiver->vendor_id = $vendor->id;
            $receiver->f_name = $store?->name ?? $vendor->f_name ?? '';
            $receiver->l_name = '';
            $receiver->phone = $vendor->phone;
            $receiver->email = $vendor->email;
            $receiver->image = $store?->logo;
            $receiver->save();
        }
        return $receiver;
    }

    /**
     * Get (or create) a UserInfo record for a given DeliveryMan id.
     *
     * @param int $deliveryManId
     * @return UserInfo
     */
    private function getOrCreateDeliveryManUserInfo(int $deliveryManId): UserInfo
    {
        $delivery_man = DeliveryMan::find($deliveryManId);
        $receiver = UserInfo::where('deliveryman_id', $delivery_man->id)->first();
        if (!$receiver) {
            $receiver = new UserInfo();
            $receiver->deliveryman_id = $delivery_man->id;
            $receiver->f_name = $delivery_man->f_name;
            $receiver->l_name = $delivery_man->l_name;
            $receiver->phone = $delivery_man->phone;
            $receiver->email = $delivery_man->email;
            $receiver->image = $delivery_man->image;
            $receiver->save();
        }
        return $receiver;
    }

    /**
     * Get (or create) a UserInfo record for an Order's Vendor.
     * Vendor is derived strictly from the Order model so the
     * receiver_id coming from the Flutter client is never trusted.
     *
     * @param int $orderId
     * @return array{0:?UserInfo,1:?Vendor}
     */
    private function resolveVendorFromOrder(int $orderId): array
    {
        $order = Order::find($orderId);
        if (!$order || !$order->store) {
            return [null, null];
        }

        $vendor = $order->store->vendor;
        if (!$vendor) {
            return [null, null];
        }

        $userInfo = $this->getOrCreateVendorUserInfo($vendor);
        return [$userInfo, $vendor];
    }

    /**
     * Build a fresh Customer UserInfo record.
     *
     * @param Request $request
     * @return UserInfo
     */
    private function createCustomerUserInfo(Request $request): UserInfo
    {
        $sender = new UserInfo();
        $sender->user_id = $request->user()->id;
        $sender->f_name = $request->user()->f_name;
        $sender->l_name = $request->user()->l_name;
        $sender->phone = $request->user()->phone;
        $sender->email = $request->user()->email;
        $sender->image = $request->user()->image;
        $sender->save();
        return $sender;
    }

    /**
     * Mirror the same customer message into the
     * Customer ↔ Vendor conversation.
     *
     * @param UserInfo   $sender
     * @param int        $orderId
     * @param string|null $body
     * @param array|null $imageName
     * @return array{vendor:Vendor,conversation:Conversation,message:Message}|null
     */
    private function mirrorMessageToVendorConversation(UserInfo $sender, int $orderId, ?string $body, $imageName): ?array
    {
        [$vendorUserInfo, $vendor] = $this->resolveVendorFromOrder($orderId);

        if (!$vendorUserInfo || !$vendor) {
            return null;
        }

        // Reuse existing conversation if present.
        $conversation = Conversation::WhereConversation($sender->id, $vendorUserInfo->id)->first();

        if (!$conversation) {
            $conversation = $this->createConversation(
                $sender->id,
                'customer',
                $vendorUserInfo->id,
                'vendor'
            );
        }

        $message = $this->saveMessage(
            $conversation->id,
            $sender->id,
            $body,
            $imageName,
            $orderId
        );

        $this->updateConversationMetadata($conversation, $message);

        return [
            'vendor' => $vendor,
            'conversation' => $conversation,
            'message' => $message,
        ];
    }

    /**
     * Send a push notification for a stored message to the correct
     * channel based on receiver_type.
     *
     * @param string       $receiverType
     * @param int          $receiverId
     * @param Message      $message
     * @param int          $conversationId
     * @param string|null  $fcmToken
     * @param string|null  $fcmTokenWeb
     * @return void
     */
    private function sendMessageNotification(string $receiverType, int $receiverId, Message $message, int $conversationId, ?string $fcmToken, ?string $fcmTokenWeb): void
    {
        try {
            if ($receiverType == 'admin' || $receiverId == 0) {
                $data = [
                    'title' => translate('messages.message'),
                    'description' => $message->message ?? translate('attachment'),
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($message),
                    'type' => 'message',
                ];
                Helpers::send_push_notif_to_topic($data, 'admin_message', 'message');
            } else if ($receiverType == 'vendor' || $receiverType == 'delivery_man') {
                $data = [
                    'title' => translate('messages.message'),
                    'description' => $message->message ?? translate('attachment'),
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($message),
                    'type' => 'message',
                    'conversation_id' => $conversationId,
                    'sender_type' => 'user',
                ];
                if (!empty($fcmToken)) {
                    Helpers::send_push_notif_to_device($fcmToken, $data);
                }
                if (!empty($fcmTokenWeb)) {
                    Helpers::send_push_notif_to_topic($data, $fcmTokenWeb, 'message');
                }
            }
        } catch (\Exception $e) {
            info($e->getMessage());
        }
    }

    /**
     * Push notification to the Vendor (mobile device + store panel topic).
     *
     * @param Vendor  $vendor
     * @param Message $message
     * @param int     $conversationId
     * @return void
     */
    private function sendVendorPushNotification(Vendor $vendor, Message $message, int $conversationId): void
    {
        try {
            $data = [
                'title' => translate('messages.message'),
                'description' => $message->message ?? translate('attachment'),
                'order_id' => '',
                'image' => '',
                'message' => json_encode($message),
                'type' => 'message',
                'conversation_id' => $conversationId,
                'sender_type' => 'user',
            ];

            if (!empty($vendor->firebase_token)) {
                Helpers::send_push_notif_to_device($vendor->firebase_token, $data);
            }

            if (isset($vendor->stores[0])) {
                $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";
                Helpers::send_push_notif_to_topic($data, $fcm_token_web, 'message');
            }
        } catch (\Exception $e) {
            info($e->getMessage());
        }
    }

    /**
     * Push notification to the Admin topic for the mirrored vendor reply.
     *
     * @param Message $message
     * @param int     $conversationId
     * @return void
     */
    private function sendAdminPushNotification(Message $message, int $conversationId): void
    {
        try {
            $data = [
                'title' => translate('messages.message'),
                'description' => $message->message ?? translate('attachment'),
                'order_id' => '',
                'image' => '',
                'message' => json_encode($message),
                'type' => 'message',
                'conversation_id' => $conversationId,
                'sender_type' => 'vendor',
            ];
            Helpers::send_push_notif_to_topic($data, 'admin_message', 'message');
        } catch (\Exception $e) {
            info($e->getMessage());
        }
    }

    /**
     * Push notification to a Customer (used when mirroring vendor reply).
     *
     * @param string  $fcmToken
     * @param Message $message
     * @param int     $conversationId
     * @return void
     */
    private function sendCustomerPushNotification(string $fcmToken, Message $message, int $conversationId): void
    {
        try {
            $data = [
                'title' => translate('messages.message_from') . ' ' . ($message->sender?->f_name ?? ''),
                'description' => $message->message ?? translate('attachment'),
                'order_id' => '',
                'image' => '',
                'message' => json_encode($message),
                'type' => 'message',
                'conversation_id' => $conversationId,
                'sender_type' => 'vendor',
            ];
            Helpers::send_push_notif_to_device($fcmToken, $data);
        } catch (\Exception $e) {
            info($e->getMessage());
        }
    }

    public function chat_image(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request->has('image')) {
            $image_name = Helpers::upload('conversation/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $url = asset('storage/app/public/conversation') . '/' . $image_name;

        return response()->json(['image_url' => $url], 200);
    }


    public function conversations(Request $request)
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $sender = UserInfo::where('user_id', $request?->user()?->id)->first();
        if (!$sender) {
            $sender = $this->createCustomerUserInfo($request);
        }

        $conversations = Conversation::with('sender', 'receiver', 'last_message')
            ->where(function ($q) use ($sender) {
                $q->where(['sender_id' => $sender->id])->orWhere(['receiver_id' => $sender->id]);
            })
            ->when(isset($request->type), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('receiver_type', $request->type)->where('sender_type', 'customer')
                        ->orWhere(function ($q) use ($request) {
                            $q->where('sender_type', $request->type)->where('receiver_type', 'customer');
                        });
                });
            })
            ->orderBy('last_message_time', 'DESC')->paginate($limit, ['*'], 'page', $offset);

        $data = [
            'type' => $request->type ?? null,
            'total_size' => intval($conversations->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'conversations' => $conversations->items()
        ];
        return response()->json($data, 200);
    }

    public function search_conversations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $key = explode(' ', $request['name']);

        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $sender = UserInfo::where('user_id', $request->user()->id)->first();
        if (!$sender) {
            $sender = $this->createCustomerUserInfo($request);
        }

        $conversations = Conversation::with('sender', 'receiver', 'last_message')->WhereUser($sender->id)->where(function ($qu) use ($key) {
            $qu->whereHas('sender', function ($query) use ($key) {
                foreach ($key as $value) {
                    $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                }
            })
                ->orWhereHas('receiver', function ($query1) use ($key) {
                    foreach ($key as $value) {
                        $query1->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                    }
                });
        });

        $conversations = $conversations->orderBy('last_message_time', 'DESC')->paginate($limit, ['*'], 'page', $offset);

        $data = [
            'total_size' => intval($conversations->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'conversations' => $conversations->items()
        ];
        return response()->json($data, 200);
    }

    public function messages(Request $request)
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $user = UserInfo::where('user_id', $request->user()->id)->first();
        if (!$user) {
            $user = $this->createCustomerUserInfo($request);
        }

        $conversation = null;
        if ($request->conversation_id) {
            $conversation = Conversation::with(['sender', 'receiver', 'last_message'])->find($request->conversation_id);
        } else if ($request->has('admin_id')) {
            $conversation = Conversation::with(['sender', 'receiver', 'last_message'])->WhereConversation($user->id, 0)->first();
            $order = 0;
        } else if ($request->vendor_id) {
            $vendor = UserInfo::where('vendor_id', $request->vendor_id)->first();
            if (!$vendor) {
                $vd = Vendor::find($request->vendor_id);
                $vendor = new UserInfo();
                $vendor->vendor_id = $vd->id;
                $vendor->f_name = $vd->stores[0]->name;
                $vendor->l_name = '';
                $vendor->phone = $vd->phone;
                $vendor->email = $vd->email;
                $vendor->image = $vd->stores[0]->logo;
                $vendor->save();
            }
            $conversation = Conversation::with(['sender', 'receiver', 'last_message'])->WhereConversation($user->id, $vendor->id)->first();
        } else if ($request->delivery_man_id) {
            $dm = UserInfo::where('deliveryman_id', $request->delivery_man_id)->first();
            if (!$dm) {
                $user2 = DeliveryMan::find($request->delivery_man_id);
                $dm = new UserInfo();
                $dm->deliveryman_id = $user2->id;
                $dm->f_name = $user2->f_name;
                $dm->l_name = $user2->l_name;
                $dm->phone = $user2->phone;
                $dm->email = $user2->email;
                $dm->image = $user2->image;
                $dm->save();
            }
            $conversation = Conversation::with(['sender', 'receiver', 'last_message'])->WhereConversation($user->id, $dm->id)->first();
        }

        if (isset($conversation)) {
            if ($conversation->sender_type == 'vendor' && $conversation->sender) {
                $vd = Vendor::find($conversation->sender->vendor_id);
                if ($vd?->store?->module_type == 'rental' && addon_published_status('Rental')) {
                    $order = Trips::where('user_id', $request->user()->id)->where('provider_id', $vd->store->id)->whereIn('trip_status', ['pending', 'confirmed', 'ongoing', 'completed'])->where('payment_status', 'unpaid')->count();
                } else {
                    $order = Order::where('user_id', $request->user()->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
                }
            } else if ($conversation->receiver_type == 'vendor' && $conversation->receiver) {
                $vd = Vendor::find($conversation->receiver->vendor_id);
                if ($vd?->store?->module_type == 'rental' && addon_published_status('Rental')) {
                    $order = Trips::where('user_id', $request->user()->id)->where('provider_id', $vd->store->id)->whereIn('trip_status', ['pending', 'confirmed', 'ongoing', 'completed'])->where('payment_status', 'unpaid')->count();
                } else {
                    $order = Order::where('user_id', $request->user()->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
                }
            } else if ($conversation->sender_type == 'delivery_man' && $conversation->sender) {
                $user2 = DeliveryMan::find($conversation->sender->deliveryman_id);
                $order = Order::where('user_id', $user->user_id)->where('delivery_man_id', $user2->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
                if (($order <= 0) && addon_published_status('RideShare')) {
                    $order = RideRequest::where('driver_id', $user2->id)->where('customer_id', $user->user_id)->whereIn('current_status', ['pending', 'accepted', 'ongoing'])->count();
                }
            } else if ($conversation->receiver_type == 'delivery_man' && $conversation->receiver) {
                $user2 = DeliveryMan::find($conversation->receiver->deliveryman_id);
                $order = Order::where('user_id', $user->user_id)->where('delivery_man_id', $user2->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
                if (($order <= 0) && addon_published_status('RideShare')) {
                    $order = RideRequest::where('driver_id', $user2->id)->where('customer_id', $user->user_id)->whereIn('current_status', ['pending', 'accepted', 'ongoing'])->count();
                }
            } else {
                $order = 1;
            }

            $lastmessage = $conversation->last_message;
            if ($lastmessage && $lastmessage->sender_id != $user->id) {
                $conversation->unread_message_count = 0;
                $conversation->save();
            }
            Message::where(['conversation_id' => $conversation->id])->where('sender_id', '!=', $user->id)->update(['is_seen' => 1]);
            $messages = Message::where(['conversation_id' => $conversation->id])->with('order')->latest()->paginate($limit, ['*'], 'page', $offset);
            $messages->getCollection()->transform(function ($message) {
                if ($message->order) {
                    $message->order->delivery_address = gettype($message->order->delivery_address) == 'string' ? json_decode($message->order->delivery_address, true) : $message->order->delivery_address;
                    $message->order->id = (int) $message->order->id;
                    $message->order->order_amount = (float) $message->order->order_amount;
                    $message->order->details_count = (int) $message->order->details_count;
                }

                return $message;
            });
        } else {
            $messages = [];
            $order = 0;
        }


        $data = [
            'total_size' => $messages ? intval($messages->total()) : 0,
            'limit' => intval($limit),
            'offset' => intval($offset),
            'status' => ($order > 0) ? true : false,
            'messages' => $messages ? $messages->items() : [],
            'conversation' => $conversation
        ];
        return response()->json($data, 200);
    }

    public function dm_messages_store(Request $request)
    {

        if ($request->has('image')) {
            $image_name = [];
            foreach ($request->file('image') as $key => $img) {

                $name = Helpers::upload('conversation/', 'png', $img);
                array_push($image_name, ['img' => $name, 'storage' => Helpers::getDisk()]);
            }
        } else {
            $image_name = null;
        }

        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;
        $fcm_token_web = null;

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $sender = UserInfo::where('deliveryman_id', $dm->id)->first();
        if (!$sender) {
            $sender = new UserInfo();
            $sender->deliveryman_id = $dm->id;
            $sender->f_name = $dm->f_name;
            $sender->l_name = $dm->l_name;
            $sender->phone = $dm->phone;
            $sender->email = $dm->email;
            $sender->image = $dm->image;
            $sender->save();
        }

        if ($request->conversation_id) {
            $conversation = Conversation::find($request->conversation_id);

            if ($conversation->sender_id == $sender->id) {
                $receiver_id = $conversation->receiver_id;
                $receiver = UserInfo::find($receiver_id);
                if ($receiver->vendor_id) {
                    $vendor = Vendor::find($receiver->vendor_id);
                    $fcm_token = $vendor->firebase_token;
                    $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";
                } elseif ($receiver->user_id) {
                    $user = User::find($receiver->user_id);
                    $fcm_token = $user->cm_firebase_token;
                } elseif ($receiver->admin_id) {
                    $receiver_id = 0;
                }
            } else {
                $receiver_id = $conversation->sender_id;
                $receiver = UserInfo::find($receiver_id);
                if ($receiver->vendor_id) {
                    $vendor = Vendor::find($receiver->vendor_id);
                    $fcm_token = $vendor->firebase_token;
                    $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";
                } elseif ($receiver->user_id) {
                    $user = User::find($receiver->user_id);
                    $fcm_token = $user->cm_firebase_token;
                } elseif ($receiver->admin_id) {
                    $receiver_id = 0;
                }
            }
        } else {
            if ($request->receiver_type == 'admin') {
                $receiver_id = 0;
            } else if ($request->receiver_type == 'vendor') {
                $receiver = UserInfo::where('vendor_id', $request->receiver_id)->first();
                $vendor = Vendor::find($request->receiver_id);

                if (!$receiver) {
                    $receiver = new UserInfo();
                    $receiver->vendor_id = $vendor->id;
                    $receiver->f_name = $vendor->stores[0]->name;
                    $receiver->l_name = '';
                    $receiver->phone = $vendor->phone;
                    $receiver->email = $vendor->email;
                    $receiver->image = $vendor->stores[0]->logo;
                    $receiver->save();
                }
                $receiver_id = $receiver->id;
                $fcm_token = $vendor->firebase_token;
                $fcm_token_web = "store_panel_{$vendor->stores[0]->id}_message";
            } else if ($request->receiver_type == 'customer') {
                $receiver = UserInfo::where('user_id', $request->receiver_id)->first();
                $user = User::find($request->receiver_id);
                // dd($user);

                if (!$receiver) {
                    $receiver = new UserInfo();
                    $receiver->user_id = $user->id;
                    $receiver->f_name = $user->f_name;
                    $receiver->l_name = $user->l_name;
                    $receiver->phone = $user->phone;
                    $receiver->email = $user->email;
                    $receiver->image = $user->image;
                    $receiver->save();
                }
                $receiver_id = $receiver->id;
                $fcm_token = $user->cm_firebase_token;
            }
        }

        $conversation = Conversation::WhereConversation($sender->id, $receiver_id)->first();

        if (!$conversation) {
            $conversation = new Conversation;
            $conversation->sender_id = $sender->id;
            $conversation->sender_type = 'delivery_man';
            $conversation->receiver_id = $receiver_id;
            $conversation->receiver_type = $request->receiver_type;
            $conversation->unread_message_count = 0;
            $conversation->last_message_time = Carbon::now()->toDateTimeString();
            $conversation->save();
            $conversation = Conversation::find($conversation->id);
        }


        $message = new Message();
        $message->conversation_id = $conversation->id;
        $message->sender_id = $sender->id;
        $message->message = $request->message;
        if ($image_name && count($image_name) > 0) {
            $message->file = json_encode($image_name, JSON_UNESCAPED_SLASHES);
        }
        try {
            if ($message->save()) {
                $conversation->unread_message_count = $conversation->unread_message_count ? $conversation->unread_message_count + 1 : 1;
                $conversation->last_message_id = $message->id;
                $conversation->last_message_time = Carbon::now()->toDateTimeString();
                if ($conversation->save()) {
                    if ($request->receiver_type == 'admin' || $receiver_id == 0) {
                        $data = [
                            'title' => translate('messages.message'),
                            'description' => $message->message ?? translate('attachment'),
                            'order_id' => '',
                            'image' => '',
                            'message' => json_encode($message),
                            'type' => 'message'
                        ];
                        Helpers::send_push_notif_to_topic($data, 'admin_message', 'message');
                    } else {
                        $data = [
                            'title' => translate('messages.message_from') . " " . $sender->f_name,
                            'description' => $message->message ?? translate('attachment'),
                            'order_id' => '',
                            'image' => '',
                            'message' => json_encode($message),
                            'type' => 'message',
                            'conversation_id' => $conversation->id,
                            'sender_type' => 'delivery_man'
                        ];
                        Helpers::send_push_notif_to_device($fcm_token, $data);
                        if ($fcm_token_web) {
                            Helpers::send_push_notif_to_topic($data, $fcm_token_web, 'message');
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            info($e->getMessage());
        }

        $messages = Message::where(['conversation_id' => $conversation->id])->latest()->paginate($limit, ['*'], 'page', $offset);

        $conv = Conversation::with('sender', 'receiver', 'last_message')->find($conversation->id);

        if ($conv->sender_type == 'vendor' && $conversation->sender) {
            $vd = Vendor::find($conv->sender->vendor_id);
            $order = Order::where('delivery_man_id', $dm->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
        } else if ($conv->receiver_type == 'vendor' && $conversation->receiver) {
            $vd = Vendor::find($conv->receiver->vendor_id);
            $order = Order::where('delivery_man_id', $dm->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
        } else if ($conv->sender_type == 'customer' && $conversation->sender) {
            $user = User::find($conv->sender->user_id);
            $order = Order::where('delivery_man_id', $dm->id)->where('user_id', $user->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
            if (($order <= 0) && addon_published_status('RideShare')) {
                $order = RideRequest::where('driver_id', $dm->id)->where('customer_id', $user->id)->whereIn('current_status', ['pending', 'accepted', 'ongoing'])->count();
            }
        } else if ($conv->receiver_type == 'customer' && $conversation->receiver) {
            $user = User::find($conv->receiver->user_id);
            $order = Order::where('delivery_man_id', $dm->id)->where('user_id', $user->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
            if (($order <= 0) && addon_published_status('RideShare')) {
                $order = RideRequest::where('driver_id', $dm->id)->where('customer_id', $user->id)->whereIn('current_status', ['pending', 'accepted', 'ongoing'])->count();
            }
        } else {
            $order = 1;
        }


        $data = [
            'total_size' => intval($messages->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'status' => ($order > 0) ? true : false,
            'message' => 'successfully sent!',
            'messages' => $messages->items(),
            'conversation' => $conv,
        ];
        return response()->json($data, 200);
    }

    public function dm_conversations(Request $request)
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $delivery_man = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $sender = UserInfo::where('deliveryman_id', $delivery_man->id)->first();
        if (!$sender) {
            $sender = new UserInfo();
            $sender->deliveryman_id = $delivery_man->id;
            $sender->f_name = $delivery_man->f_name;
            $sender->l_name = $delivery_man->l_name;
            $sender->phone = $delivery_man->phone;
            $sender->email = $delivery_man->email;
            $sender->image = $delivery_man->image;
            $sender->save();
        }


        $conversations = Conversation::with('sender', 'receiver', 'last_message')
            ->where(function ($q) use ($sender) {
                $q->where(['sender_id' => $sender->id])->orWhere(['receiver_id' => $sender->id]);
            })
            ->when(isset($request->type), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('receiver_type', $request->type)->where('sender_type', 'delivery_man')
                        ->orWhere(function ($q) use ($request) {
                            $q->where('sender_type', $request->type)->where('receiver_type', 'delivery_man');
                        });
                });
            })
            ->orderBy('last_message_time', 'DESC')->paginate($limit, ['*'], 'page', $offset);


        $data = [
            'total_size' => intval($conversations->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'conversation' => $conversations->items()
        ];

        return response()->json($data, 200);
    }

    public function dm_search_conversations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $key = explode(' ', $request['name']);

        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $delivery_man = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $sender = UserInfo::where('deliveryman_id', $delivery_man->id)->first();
        if (!$sender) {
            $sender = new UserInfo();
            $sender->deliveryman_id = $delivery_man->id;
            $sender->f_name = $delivery_man->f_name;
            $sender->l_name = $delivery_man->l_name;
            $sender->phone = $delivery_man->phone;
            $sender->email = $delivery_man->email;
            $sender->image = $delivery_man->image;
            $sender->save();
        }

        $conversations = Conversation::with('sender', 'receiver', 'last_message')->WhereUser($sender->id)
            ->when(isset($request->type), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('receiver_type', $request->type)->where('sender_type', 'delivery_man')
                        ->orWhere(function ($q) use ($request) {
                            $q->where('sender_type', $request->type)->where('receiver_type', 'delivery_man');
                        });
                });
            })
            ->where(function ($qu) use ($key) {
                $qu->whereHas('sender', function ($query) use ($key) {
                    foreach ($key as $value) {
                        $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                    }
                })
                    ->orWhereHas('receiver', function ($query1) use ($key) {
                        foreach ($key as $value) {
                            $query1->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                        }
                    });
            });

        $conversations = $conversations->orderBy('last_message_time', 'DESC')->paginate($limit, ['*'], 'page', $offset);

        $data = [
            'total_size' => intval($conversations->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'conversation' => $conversations->items()
        ];
        return response()->json($data, 200);
    }


    public function dm_messages(Request $request)
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $delivery_man = UserInfo::where('deliveryman_id', $dm->id)->first();

        if (!$delivery_man) {
            $delivery_man = new UserInfo();
            $delivery_man->deliveryman_id = $dm->id;
            $delivery_man->f_name = $dm->f_name;
            $delivery_man->l_name = $dm->l_name;
            $delivery_man->phone = $dm->phone;
            $delivery_man->email = $dm->email;
            $delivery_man->image = $dm->image;
            $delivery_man->save();
        }

        if ($request->conversation_id) {
            $conversation = Conversation::with(['sender', 'receiver'])->find($request->conversation_id);
        } else if ($request->has('admin_id')) {
            $conversation = Conversation::with(['sender', 'receiver', 'last_message'])->WhereConversation($delivery_man->id, 0)->first();
            $order = 0;
        } else if ($request->vendor_id) {
            $vendor = UserInfo::where('vendor_id', $request->vendor_id)->first();
            if (!$vendor) {
                $user = Vendor::find($request->vendor_id);
                $vendor = new UserInfo();
                $vendor->vendor_id = $user->id;
                $vendor->f_name = $user->stores[0]->name;
                $vendor->l_name = '';
                $vendor->phone = $user->phone;
                $vendor->email = $user->email;
                $vendor->image = $user->image;
                $vendor->save();
            }
            $conversation = Conversation::with(['sender', 'receiver', 'last_message'])->WhereConversation($delivery_man->id, $vendor->id)->first();
        } else if ($request->user_id) {
            $user = UserInfo::where('user_id', $request->user_id)->first();
            if (!$user) {
                $customer = User::find($request->user_id);
                $user = new UserInfo();
                $user->user_id = $customer->id;
                $user->f_name = $customer->f_name;
                $user->l_name = $customer->l_name;
                $user->phone = $customer->phone;
                $user->email = $customer->email;
                $user->image = $customer->image;
                $user->save();
            }
            $conversation = Conversation::with(['sender', 'receiver', 'last_message'])->WhereConversation($delivery_man->id, $user->id)->first();
        }

        if ($conversation) {

            if ($conversation->sender_type == 'vendor' && $conversation->sender) {
                $vd = Vendor::find($conversation->sender->vendor_id);
                $order = Order::where('delivery_man_id', $dm->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
            } else if ($conversation->receiver_type == 'vendor' && $conversation->receiver) {
                $vd = Vendor::find($conversation->receiver->vendor_id);
                $order = Order::where('delivery_man_id', $dm->id)->where('store_id', $vd->stores[0]->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
            } else if ($conversation->sender_type == 'customer' && $conversation->sender) {
                $user = User::find($conversation->sender->user_id);
                $order = Order::where('delivery_man_id', $dm->id)->where('user_id', $user->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
                if (($order <= 0) && addon_published_status('RideShare')) {
                    $order = RideRequest::where('driver_id', $dm->id)->where('customer_id', $user->id)->whereIn('current_status', ['pending', 'accepted', 'ongoing'])->count();
                }
            } else if ($conversation->receiver_type == 'customer' && $conversation->receiver) {
                $user = User::find($conversation->receiver->user_id);
                $order = Order::where('delivery_man_id', $dm->id)->where('user_id', $user->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
                if (($order <= 0) && addon_published_status('RideShare')) {
                    $order = RideRequest::where('driver_id', $dm->id)->where('customer_id', $user->id)->whereIn('current_status', ['pending', 'accepted', 'ongoing'])->count();
                }
            } else {
                $order = 1;
            }


            $lastmessage = $conversation->last_message;
            if ($lastmessage && $lastmessage->sender_id != $delivery_man->id) {
                $conversation->unread_message_count = 0;
                $conversation->save();
            }

            Message::where(['conversation_id' => $conversation->id])->where('sender_id', '!=', $delivery_man->id)->update(['is_seen' => 1]);
            $messages = Message::where(['conversation_id' => $conversation->id])->latest()->paginate($limit, ['*'], 'page', $offset);
        } else {
            $messages = [];
            $order = 1;
        }

        $data = [
            'total_size' => $messages ? intval($messages->total()) : 0,
            'limit' => intval($limit),
            'offset' => intval($offset),
            'status' => ($order > 0) ? true : false,
            'messages' => $messages ? $messages->items() : [],
            'conversation' => $conversation
        ];
        return response()->json($data, 200);
    }


    public function dm_auto_messages_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $sender = UserInfo::where('deliveryman_id', $dm->id)->first();
        if (!$sender) {
            $sender = new UserInfo();
            $sender->deliveryman_id = $dm->id;
            $sender->f_name = $dm->f_name;
            $sender->l_name = $dm->l_name;
            $sender->phone = $dm->phone;
            $sender->email = $dm->email;
            $sender->image = $dm->image;
            $sender->save();
        }

        $admin = Admin::where('role_id', 1)->first();
        $receiver = UserInfo::where('admin_id', $admin->id)->first();
        if (!$receiver) {
            $receiver = new UserInfo();
            $receiver->admin_id = $admin->id;
            $receiver->f_name = $admin->f_name;
            $receiver->l_name = $admin->l_name;
            $receiver->phone = $admin->phone;
            $receiver->email = $admin->email;
            $receiver->image = $admin->image;
            $receiver->save();
        }

        $receiver_id = 0;

        $conversation = Conversation::WhereConversation($sender->id, $receiver_id)->first();

        if (!$conversation) {
            $conversation = new Conversation;
            $conversation->sender_id = $sender->id;
            $conversation->sender_type = 'delivery_man';
            $conversation->receiver_id = $receiver_id;
            $conversation->receiver_type = 'admin';
            $conversation->unread_message_count = 0;
            $conversation->last_message_time = Carbon::now()->toDateTimeString();
            $conversation->save();
            $conversation = Conversation::find($conversation->id);
        }

        $automated_message = AutomatedMessage::rider()->where('id', $request->question_id)->first();

        $message = new Message();
        $message->conversation_id = $conversation->id;
        $message->sender_id = $sender->id;
        $message->message = $automated_message->question;
        try {
            if ($message->save())
                $reply_time = Carbon::now()->addSeconds(2);
            $message = new Message();
            $message->conversation_id = $conversation->id;
            $message->sender_id = $receiver->id;
            $message->message = $automated_message->message;
            $message->created_at = $reply_time;
            $message->updated_at = $reply_time;
            $message->save();
            $conversation->unread_message_count = $conversation->unread_message_count ? $conversation->unread_message_count + 1 : 1;
            $conversation->last_message_id = $message->id;
            $conversation->last_message_time = $reply_time;
            $conversation->save();
            {
                $data = [
                    'title' => translate('messages.message'),
                    'description' => $message->message ?? translate('attachment'),
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($message),
                    'type' => 'message'
                ];
                Helpers::send_push_notif_to_topic($data, 'admin_message', 'message');

                $data = [
                    'title' => translate('messages.message_from_admin'),
                    'description' => $message->message ?? translate('attachment'),
                    'order_id' => '',
                    'image' => '',
                    'message' => json_encode($message),
                    'type' => 'message',
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'admin'
                ];
                Helpers::send_push_notif_to_device($dm->fcm_token, $data);
            }
        } catch (\Exception $e) {
            info($e->getMessage());
        }

        $messages = Message::where(['conversation_id' => $conversation->id])->latest()->paginate($limit, ['*'], 'page', $offset);

        $conv = Conversation::with('sender', 'receiver', 'last_message')->find($conversation->id);

        $data = [
            'total_size' => intval($messages->total()),
            'limit' => intval($limit),
            'offset' => intval($offset),
            'status' => true,
            'message' => 'successfully sent!',
            'messages' => $messages->items(),
            'conversation' => $conv,
        ];
        return response()->json($data, 200);
    }
}
