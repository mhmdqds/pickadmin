<?php

namespace Modules\ReelsModule\Http\Resources;

use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReelDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $streamUrl = route('customer.reels.show', [
            'reel_id' => $this->id,
            'stream' => 1,
        ]);

        return [
            'id' => $this->id,
            'description' => $this->description,
            'video_url' => $streamUrl,
            'thumbnail_url' => $this->thumbnail_full_url,
            'store' => [
                'id' => $this->store?->id,
                'name' => $this->store?->name,
                'logo_full_url' => $this->store?->logo_full_url,
                'verified_seller' => Helpers::get_verified_seller_status($this->store, $this->store?->storeConfig),
            ],
            'total_views' => (int) $this->total_views,
            'total_likes' => (int) $this->total_likes,
            'total_store_visits' => (int) $this->total_store_visits,
        ];
    }
}
