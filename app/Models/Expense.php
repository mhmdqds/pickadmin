<?php

namespace App\Models;

use App\Traits\ReportFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Rental\Entities\Trips;
use Modules\RideShare\Entities\TripManagement\RideRequest;

class Expense extends Model
{
    use HasFactory, ReportFilter;
    protected $casts = [
        'id' => 'integer',
        'order_id' => 'integer',
        'store_id' => 'integer',
        'ride_id' => 'integer',
        'amount' => 'float',
        'created_at' => 'datetime',
    ];


    public function store()
    {
        return $this->belongsTo(Store::class,'store_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class,'delivery_man_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d H:i:s',strtotime($value));
    }

    public function trip()
    {
        if (! addon_published_status('Rental')) {
            return $this->missingAddonRelation('trip_id');
        }

        return $this->belongsTo(Trips::class, 'trip_id');
    }

    public function ride()
    {
        if (! addon_published_status('RideShare')) {
            return $this->missingAddonRelation('ride_id');
        }

        return $this->belongsTo(RideRequest::class, 'ride_id');
    }

    public function scopeWithoutAddon($query)
    {
        return $query
            ->whereNull('ride_id')
            ->whereNull('trip_id');
    }

    protected function missingAddonRelation(string $foreignKey)
    {
        return $this->belongsTo(self::class, $foreignKey)->whereRaw('1 = 0');
    }
}
