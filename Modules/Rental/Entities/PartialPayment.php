<?php

namespace Modules\Rental\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PartialPayment extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'trip_id' => 'integer',
        'amount' => 'float',
    ];

    public function trip()
    {
        return $this->belongsTo(Trips::class,'trip_id');
    }

}
