<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Modules\RideShare\Entities\FareManagement\RideFare;
use Modules\RideShare\Entities\VehicleManagement\RiderVehicle;

class DMVehicle extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'integer',
        'status' => 'integer',
        'extra_charges' => 'float',
        'starting_coverage_area' => 'float',
        'maximum_coverage_area' => 'float',
    ];

    protected $appends = ['image_full_url'];

    public function getImageFullUrlAttribute(){
        $value = $this->image;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'image') {
                    return Helpers::get_full_url('vehicle/category',$value,$storage['value'],'category');
                }
            }
        }

        return Helpers::get_full_url('vehicle/category',$value,'public');
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    public function delivery_man()
    {
        return $this->hasOne(DeliveryMan::class,'vehicle_id');
    }

    public function vehicles()
    {
        return $this->hasMany(RiderVehicle::class, 'category_id');
    }

    public function tripFares()
    {
        return $this->hasMany(RideFare::class, 'vehicle_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getTypeAttribute($value){
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'type') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function scopeRide($query)
    {
        return $query->withoutGlobalScope('delivery_only')->where('is_ride', 1);
    }

    public function storage()
    {
        return $this->morphMany(Storage::class, 'data');
    }

    protected static function booted()
    {
        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });

        if(addon_published_status('RideShare')){
            static::addGlobalScope('delivery_only', function (Builder $builder) {
                $builder->where('is_delivery', 1);
            });
        }
    }
}
