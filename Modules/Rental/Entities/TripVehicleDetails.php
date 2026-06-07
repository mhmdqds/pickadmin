<?php

namespace Modules\Rental\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripVehicleDetails extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'trip_id' => 'integer',
        'vehicle_id' => 'integer',
        'trip_details_id' => 'integer',
        'vehicle_identity_id' => 'integer',
        'vehicle_driver_id' => 'integer',
        'avg_rating' => 'float',
    ];

    /**
     * @return BelongsTo
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trips::class);
    }

    /**
     * @return BelongsTo
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(VehicleDriver::class, 'vehicle_driver_id', 'id');
    }

    public function vehicles()
    {
        return $this->belongsTo(Vehicle::class,'vehicle_id');
    }

    public function vehicle_identity_data()
    {
        return $this->belongsTo(VehicleIdentity::class,'vehicle_identity_id');
    }
    public function driver_data()
    {
        return $this->belongsTo(VehicleDriver::class,'vehicle_driver_id');
    }

}
