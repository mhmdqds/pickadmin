<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ReportFilter;

class DeliverymanLoyaltyPointHistory extends Model
{
    use ReportFilter;
    protected $casts = [
        'delivery_man_id' => 'integer',
        'point' => 'integer',
        'converted_amount' => 'float',
    ];

    protected $fillable = [
        'delivery_man_id',
        'transaction_id',
        'transaction_type',
        'point_conversion_type',
        'point',
        'converted_amount',
        'reference',
    ];

    public function deliveryman()
    {
        return $this->belongsTo(Deliveryman::class);
    }

}
