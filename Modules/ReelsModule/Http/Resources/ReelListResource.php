<?php

namespace Modules\ReelsModule\Http\Resources;

use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReelListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reel_id' => $this->id,
            'description' => $this->description,
            'thumbnail_full_url' => $this->thumbnail_full_url,
            'store_id' => $this->store_id,
            'store_name' => $this->store?->name,
            'store_logo_full_url' => $this->store?->logo_full_url,
            'verified_seller' => Helpers::get_verified_seller_status($this->store, $this->store?->storeConfig),
            'stats' => (new ReelStatsResource($this->resource))->resolve(),
        ];
    }
}
