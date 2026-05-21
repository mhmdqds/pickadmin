<?php

namespace Modules\ReelsModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReelEngagement extends Model
{
    public const TYPE_VIEW = 'view';
    public const TYPE_LIKE = 'like';
    public const TYPE_VISIT = 'visit';

    protected $guarded = ['id'];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }
}
