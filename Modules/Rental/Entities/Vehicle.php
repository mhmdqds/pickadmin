<?php

namespace Modules\Rental\Entities;

use App\Models\Store;
use App\Models\Storage;
use App\Scopes\ZoneScope;
use App\Models\Translation;
use Illuminate\Support\Str;
use App\Traits\ReportFilter;
use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory , ReportFilter;

    protected $guarded = ['id'];
    protected $appends = ['thumbnail_full_url', 'images_full_url', 'documents_full_url'];
    protected $casts = [
        'air_condition' => 'integer',
        'multiple_vehicles' => 'integer',
        'trip_hourly' => 'integer',
        'trip_distance' => 'integer',
        'status' => 'integer',
        'new_tag' => 'integer',
        'provider_id' => 'integer',
        'brand_id' => 'integer',
        'category_id' => 'integer',
        'hourly_price' => 'float',
        'avg_rating' => 'float',
        'distance_price' => 'float',
        'discount_price' => 'float',
        'total_trip' => 'integer',
        'total_reviews' => 'integer',
        'zone_id' => 'integer',
    ];

    /**
     * @param $query
     * @param $status
     * @return void
     */
    public function scopeOfStatus($query, $status): void
    {
        $query->where('status', '=', $status);
    }

    /**
     * @param $query
     * @param $providerId
     * @return void
     */
    public function scopeOfProvider($query, $providerId): void
    {
        $query->where('provider_id', '=', $providerId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1)
        ->whereHas('provider', function($query) {
            $query->where('status', 1)->where('active',1)
                    ->where(function($query) {
                        $query->where('store_business_model', 'commission')
                                ->orWhereHas('store_sub', function($query) {
                                    $query->where(function($query) {
                                        $query->where('max_order', 'unlimited')->orWhere('max_order', '>', 0);
                                    });
                                });
                    });
            });
    }

    /**
     * @return BelongsTo
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'provider_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'category_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function vehicleIdentities(): HasMany
    {
        return $this->hasMany(VehicleIdentity::class);
    }

    /**
     * @return HasMany
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trips::class);
    }

    public function tripDetails(): HasMany
    {
        return $this->hasMany(TripDetails::class, 'vehicle_id');
    }

    /**
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(VehicleReview::class);
    }


    /**
     * @return MorphMany
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    /**
     * @param $string
     * @return bool
     */
    private function isValidJson($string): bool
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * @return MorphMany
     */
    public function storage(): MorphMany
    {
        return $this->morphMany(Storage::class, 'data');
    }

    /**
     * @return mixed|string|null
     */
    public function getThumbnailFullUrlAttribute(): mixed
    {
        $value = $this->thumbnail;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'image') {
                    return Helpers::get_full_url('vehicle',$value,$storage['value']);
                }
            }
        }

        return Helpers::get_full_url('vehicle',$value,'public');
    }

    /**
     * @return array
     */
    public function getImagesFullUrlAttribute(): array
    {
        $images = [];
        $value = is_array($this->images)
            ? $this->images
            : ($this->images && is_string($this->images) && $this->isValidJson($this->images)
                ? json_decode($this->images, true)
                : []);
        if ($value){
            foreach ($value as $item){
                $item = is_array($item)?$item:(is_object($item) && get_class($item) == 'stdClass' ? json_decode(json_encode($item), true):['img' => $item, 'storage' => 'public']);
                $images[] = Helpers::get_full_url('vehicle',$item['img'],$item['storage']);
            }
        }

        return $images;
    }

    /**
     * @return array
     */
    public function getDocumentsFullUrlAttribute(): array
    {
        $images = [];
        $value = is_array($this->documents)
            ? $this->documents
            : ($this->documents && is_string($this->documents) && $this->isValidJson($this->documents)
                ? json_decode($this->documents, true)
                : []);
        if ($value){
            foreach ($value as $item){
                $item = is_array($item)?$item:(is_object($item) && get_class($item) == 'stdClass' ? json_decode(json_encode($item), true):['img' => $item, 'storage' => 'public']);
                $images[] = Helpers::get_full_url('vehicle',$item['img'],$item['storage']);
            }
        }

        return $images;
    }

    public function getNameAttribute($value){
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'name') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function getDescriptionAttribute($value){
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'description') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }


    protected static function booted()
    {
        static::addGlobalScope('storage', function ($builder) {
            $builder->with('storage');
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function($query){
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }

    private function generateSlug($name)
    {
        $slug = Str::slug($name);
        if ($max_slug = static::where('slug', 'like',"{$slug}%")->latest('id')->value('slug')) {

            if($max_slug == $slug) return "{$slug}-2";

            $max_slug = explode('-',$max_slug);
            $count = array_pop($max_slug);
            if (isset($count) && is_numeric($count)) {
                $max_slug[]= ++$count;
                return implode('-', $max_slug);
            }
        }
        return $slug;
    }
    protected static function boot()
    {
        parent::boot();
        static::created(function ($item) {
            $item->slug = $item->generateSlug($item->name);
            $item->save();
            Helpers::deleteCacheData('vehicle_');
        });
        static::deleted(function(){
            Helpers::deleteCacheData('vehicle_');
        });
        static::saved(function(){
            Helpers::deleteCacheData('vehicle_');
        });
        static::updated(function(){
            Helpers::deleteCacheData('vehicle_');
        });

    }
}

