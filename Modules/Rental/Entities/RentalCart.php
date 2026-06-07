<?php

namespace Modules\Rental\Entities;

use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentalCart extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'provider_id' => 'integer',
        'user_id' => 'integer',
        'module_id' => 'integer',
        'vehicle_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'float',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class,'vehicle_id');
    }
    public function provider()
    {
        return $this->belongsTo(Store::class,'provider_id');
    }
}
