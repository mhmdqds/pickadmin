<?php

namespace Modules\Rental\Entities;

use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleIdentity extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return BelongsTo
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'provider_id', 'id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function vehicle_trip_details()
    {
        return $this->hasMany(TripVehicleDetails::class, 'vehicle_identity_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(VehicleDriver::class, 'vehicle_driver_id', 'id');
    }


    public function scopeDynamicVehicleQuantity($query,$pickup_time)
    {
        if($pickup_time){
            return $query->where(function ($query) use ($pickup_time) {
                    $query->whereDoesntHave('vehicle_trip_details')
                        ->orWhere(function ($query) use ($pickup_time) {
                            $query->whereNotExists(function ($subQuery) use ($pickup_time) {
                                $subQuery->from('trip_vehicle_details')
                                    ->whereColumn('trip_vehicle_details.vehicle_identity_id', 'vehicle_identities.id')
                                    ->wheredate('estimated_trip_end_time', $pickup_time)->where('estimated_trip_end_time', '>', $pickup_time)->where('is_completed',0);
                            });
                    });
            });
        }

        return $query;

    }


}
