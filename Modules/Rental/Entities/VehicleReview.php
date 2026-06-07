<?php

namespace Modules\Rental\Entities;

use App\Models\User;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleReview extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'provider_id' => 'integer',
        'module_id' => 'integer',
        'user_id' => 'integer',
        'trip_id' => 'integer',
        'vehicle_id' => 'integer',
        'vehicle_identity' => 'integer',
        'rating' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function scopeModule($query, $module_id)
    {
        return $query->where('module_id', $module_id);
    }

    /**
     * @return string
     */
    public function getReviewDateAttribute(): string
    {
        return Carbon::parse($this->created_at)->format('d F Y');
    }

    /**
     * @return string
     */
    public function getReviewTimeAttribute(): string
    {
        return Carbon::parse($this->created_at)->format('h:i A');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class,'vehicle_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider()
    {
        return $this->belongsTo(Store::class, 'provider_id');
    }

    /**
     * @return BelongsTo
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trips::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status',1);
    }

    protected static function boot()
    {
        parent::boot();
        static::saved(function ($review) {
            if($review->review_id == null){
                $review->review_id = $review->generateReviewId($review->trip_id);
                $review->save();
            }
        });
    }
    private function generateReviewId($id)
    {
        $review_id = Str::slug($id);
        if ($max_review_id = static::where('review_id', 'like',"{$review_id}%")->latest('id')->value('review_id')) {

            if($max_review_id == $review_id) return "{$review_id}-2";

            $max_review_id = explode('-',$max_review_id);
            $count = array_pop($max_review_id);
            if (isset($count) && is_numeric($count)) {
                $max_review_id[]= ++$count;
                return implode('-', $max_review_id);
            }
        }
        return $review_id;
    }

}
