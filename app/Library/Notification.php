<?php

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Http;

function sendNotificationToHttp(array|null $data)
{
    $config = null;

    $paymentmethod = BusinessSetting::where('key', 'push_notification_service_file_content')->first();

    if ($paymentmethod) {
        $config = json_decode($paymentmethod->value, true);
    }
    $key = (array)$config;
    if($key['project_id']){
        $url = 'https://fcm.googleapis.com/v1/projects/'.$key['project_id'].'/messages:send';
        $headers = [
            'Authorization' => 'Bearer ' . getAccessToken($key),
            'Content-Type' => 'application/json',
        ];
        try {
            Http::withHeaders($headers)->post($url, $data);
        }catch (\Exception $exception){
            return false;
        }
    }
    return false;
}

function getAccessToken($key)
{
    $jwtToken = [
        'iss' => $key['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => time() + 3600,
        'iat' => time(),
    ];
    $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $jwtPayload = base64_encode(json_encode($jwtToken));
    $unsignedJwt = $jwtHeader . '.' . $jwtPayload;
    openssl_sign($unsignedJwt, $signature, $key['private_key'], OPENSSL_ALGO_SHA256);
    $jwt = $unsignedJwt . '.' . base64_encode($signature);

    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]);
    return $response->json('access_token');
}

if (!function_exists('device_notification')) {
    function device_notification($fcm_token, $title, $description, $image, $booking_id, $type='status', $channel_id = null, $user_id = null, $data=null, $advertisement_id=null, $bookingType=null, $repeat_type=null)
    {
        $title = text_variable_data_format($title, $booking_id, $type, $data, $bookingType);
        $postData = [
            'message' => [
                "token" => $fcm_token,
                "data" => [
                    "title" => (string)$title,
                    "body" => (string)$description,
                    "booking_id" => (string)$booking_id,
                    "channel_id" => (string)$channel_id,
                    "user_id" => (string)$user_id,
                    "type" => (string)$type,
                    "image" => (string)$image,
                    "advertisement_id" => (string)$advertisement_id,
                    "booking_type" => (string)$bookingType,
                    "repeat_type" => (string)$repeat_type,
                ],
                "notification" => [
                    'title' => (string)$title,
                    'body' => (string)$description,
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "sound" => "notification.wav"
                        ]
                    ]
                ],
                "android" => [
                    "notification" => [
                        "channelId" => "demandium"
                    ]
                ],
            ]
        ];

        return sendNotificationToHttp($postData);
    }
}

if (!function_exists('topic_notification')) {
    function topic_notification($topic, $title, $description, $image, $booking_id, $type='status')
    {
        $image = asset('storage/app/public/push-notification') . '/' . $image;

        $postData = [
            'message' => [
                "topic" => $topic,
                "data" => [
                    "title" => (string)$title,
                    "body" => (string)$description,
                    "booking_id" => (string)$booking_id,
                    "type" => (string)$type,
                    "image" => (string)$image
                ],
                "notification" => [
                    "title" => (string)$title,
                    "body" => (string)$description,
                    "image" => (string)$image,
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "sound" => "notification.wav"
                        ]
                    ]
                ],
                "android" => [
                    "notification" => [
                        "channelId" => "demandium"
                    ]
                ],
            ]
        ];

        return sendNotificationToHttp($postData);
    }
}

//bidding notification
if (!function_exists('device_notification_for_bidding')) {
    function device_notification_for_bidding($fcm_token, $title, $description, $image, $type='bidding', $booking_id = null, $post_id = null, $provider_id = null, $data=null)
    {
        $title = text_variable_data_format($title, $booking_id, $type, $data);
        $image = asset('storage/app/public/push-notification') . '/' . $image;

        $postData = [
            'message' => [
                "token" => $fcm_token,
                "data" => [
                    "title" => (string)$title,
                    "body" => (string)$description,
                    "booking_id" => (string)$booking_id,
                    "post_id" => (string)$post_id,
                    "provider_id" => (string)$provider_id,
                    "type" => (string)$type,
                    "image" => (string)$image
                ],
                "notification" => [
                    "title" => (string)$title,
                    "body" => (string)$description,
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "sound" => "notification.wav"
                        ]
                    ]
                ],
                "android" => [
                    "notification" => [
                        "channelId" => "demandium"
                    ]
                ],
            ]
        ];

        return sendNotificationToHttp($postData);
    }
}

//chatting notification

if (!function_exists('device_notification_for_chatting')) {
    function device_notification_for_chatting($fcm_token, $title, $description, $image, $channel_id, $user_name, $user_image, $user_phone, $user_type, $type = 'status')
    {
        $image = asset('storage/app/public/push-notification') . '/' . $image;

        $postData = [
            'message' => [
                "token" => $fcm_token,
                "data" => [
                    "title" => (string)$title,
                    "body" => (string)$description,
                    "image" => (string)$image,
                    "type" => (string)$type,
                    "channel_id" => (string)$channel_id,
                    "user_name" => (string)$user_name,
                    "user_image"=> (string)$user_image,
                    "user_phone"=> (string)$user_phone,
                    "user_type"=> (string)$user_type,
                ],
                "notification" => [
                    "title" => (string)$title,
                    "body" => (string)$description,
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "sound" => "notification.wav"
                        ]
                    ]
                ],
                "android" => [
                    "notification" => [
                        "channelId" => "demandium"
                    ]
                ],
            ]
        ];

        return sendNotificationToHttp($postData);
    }
}

if (!function_exists('sendDeviceNotification')) {
    function sendDeviceNotification($fcm_token, $title, $description, $status, $image = null, $ride_request_id = null, $type = null, $action = null, $user_id = null, $user_name = null, array $notificationData = []): bool|string
    {
        $imageUrl = $image ? asset("storage/app/public/push-notification/$image") : null;

        $postData = [
            'message' => [
                'token' => $fcm_token,
                'data' => [
                    'title' => (string)$title,
                    'body' => (string)$description,
                    'status' => (string)$status,
                    "ride_request_id" => (string)$ride_request_id,
                    "type" => (string)$type,
                    "user_name" => (string)$user_name,
                    "title_loc_key" => (string)$ride_request_id,
                    "body_loc_key" => (string)$type,
                    "image" => (string)$imageUrl,
                    "action" => (string)$action,
                    "reward_type" => (string)($notificationData['reward_type'] ?? null),
                    "reward_amount" => (string)($notificationData['reward_amount'] ?? 0),
                    "next_level" => (string)($notificationData['next_level'] ?? null),
                    "sound" => "notification.wav",
                    "android_channel_id" => "hexaride"
                ],
                'notification' => [
                    'title' => (string)$title,
                    'body' => (string)$description,
                    "image" => (string)$imageUrl,
                ],
                "android" => [
                    'priority' => 'high',
                    "notification" => [
                        "channel_id" => "hexaride",
                        "sound" => "notification.wav",
                        "icon" => "notification_icon",
                    ]
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "sound" => "notification.wav"
                        ]
                    ],
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                ],
            ]
        ];
        return sendNotificationToHttp($postData);
    }
}

if (!function_exists('sendTopicNotification')) {
    function sendTopicNotification($topic, $title, $description, $image = null, $ride_request_id = null, $type = null, $sentBy = null, $tripReferenceId = null, $route = null): bool|string
    {

        $image = asset('storage/app/public/push-notification') . '/' . $image;
        $postData = [
            'message' => [
                'topic' => $topic,
                'data' => [
                    'title' => (string)$title,
                    'body' => (string)$description,
                    "ride_request_id" => (string)$ride_request_id,
                    "type" => (string)$type,
                    "title_loc_key" => (string)$ride_request_id,
                    "body_loc_key" => (string)$type,
                    "image" => (string)$image,
                    "sound" => "notification.wav",
                    "android_channel_id" => "hexaride",
                    "sent_by" => (string)$sentBy,
                    "trip_reference_id" => (string)$tripReferenceId,
                    "route" => (string)$route,
                ],
                'notification' => [
                    'title' => (string)$title,
                    'body' => (string)$description,
                    "image" => (string)$image,
                ],
                "android" => [
                    'priority' => 'high',
                    "notification" => [
                        "channelId" => "hexaride"
                    ]
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "sound" => "notification.wav"
                        ]
                    ],
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                ],
            ]
        ];
        return sendNotificationToHttp($postData);
    }
}