<?php

namespace Modules\ReelsModule\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReelStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'total_views' => (int) $this->total_views,
            'total_likes' => (int) $this->total_likes,
            'total_store_visits' => (int) $this->total_store_visits,
        ];

        $user = $request->user('api');
        $data['is_liked'] = $user && \Modules\ReelsModule\Entities\ReelEngagement::query()
            ->where('reel_id', $this->id)
            ->where('user_id', $user->id)
            ->where('type', 'like')
            ->exists() ? 1 : 0;

        return $data;
    }
}
