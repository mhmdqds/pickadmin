<?php

namespace Modules\Rental\Entities;

use App\CentralLogics\Helpers;
use App\Models\Storage;
use App\Models\Store;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VehicleDriver extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $fillable = [];
    protected $appends = ['image_full_url', 'identity_image_full_url'];

    /**
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

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
     * @return BelongsTo
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'provider_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function trips(): HasMany
    {
        return $this->hasMany(TripVehicleDetails::class, 'vehicle_driver_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function ongoingTrips(): HasMany
    {
        return $this->trips()->whereHas('trip', function ($query) {
            $query->where('trip_status', 'ongoing');
        });
    }

    /**
     * @return HasMany
     */
    public function completedTrips(): HasMany
    {
        return $this->trips()->whereHas('trip', function ($query) {
            $query->where('trip_status', 'completed');
        });
    }

    /**
     * @return HasMany
     */
    public function canceledTrips(): HasMany
    {
        return $this->trips()->whereHas('trip', function ($query) {
            $query->where('trip_status', 'canceled');
        });
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
    public function storage(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Storage::class, 'data');
    }

    /**
     * @return mixed|string|null
     */
    public function getImageFullUrlAttribute(): mixed
    {
        $value = $this->image;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'image') {
                    return Helpers::get_full_url('driver',$value,$storage['value']);
                }
            }
        }

        return Helpers::get_full_url('driver',$value,'public');
    }

    /**
     * @return array
     */
    public function getIdentityImageFullUrlAttribute(): array
    {
        $images = [];
        $value = is_array($this->identity_image)
            ? $this->identity_image
            : ($this->identity_image && is_string($this->identity_image) && $this->isValidJson($this->identity_image)
                ? json_decode($this->identity_image, true)
                : []);
        if ($value){
            foreach ($value as $item){
                $item = is_array($item)?$item:(is_object($item) && get_class($item) == 'stdClass' ? json_decode(json_encode($item), true):['img' => $item, 'storage' => 'public']);
                $images[] = Helpers::get_full_url('driver',$item['img'],$item['storage']);
            }
        }

        return $images;
    }

}
