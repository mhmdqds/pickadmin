<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\ZoneScope;

class ProvideDMEarning extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function rider(){
        return $this->belongsTo(DeliveryMan::class,'delivery_man_id')->withoutGlobalScope('delivery_only')->where('is_ride', 1);
    }


    protected static function booted()
    {
        static::addGlobalScope(new ZoneScope);
    }
}
