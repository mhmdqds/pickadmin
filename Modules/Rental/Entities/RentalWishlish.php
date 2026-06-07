<?php

namespace Modules\Rental\Entities;

use App\Models\Store;
use Modules\Rental\Entities\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentalWishlish extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'integer',
        'vehicle_id' => 'integer',
        'provider_id' => 'integer',

    ];


    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function provider()
    {
        return $this->belongsTo(Store::class,'provider_id');
    }


}
