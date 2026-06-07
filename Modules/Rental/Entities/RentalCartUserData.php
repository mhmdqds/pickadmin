<?php

namespace Modules\Rental\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentalCartUserData extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'user_id' => 'integer',
        'estimated_hours' => 'float',
        'distance' => 'float',
        'is_guest' => 'integer',
        'total_cart_price' => 'float',
    ];

    public function getPickupLocationAttribute($value)
    {
        if ($value) {
            return json_decode($value, true);
        }
        return $value;
    }
    public function getDestinationLocationAttribute($value)
    {
        if ($value) {
            return json_decode($value, true);
        }
        return $value;
    }
}
