<?php

namespace Modules\Rental\Entities;

use App\Models\Zone;
use App\Models\Store;
use App\Models\Module;
use App\Models\Vendor;
use App\Traits\ReportFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripTransaction extends Model
{
    use HasFactory, ReportFilter;

    protected $guarded = ['id'];
    protected $casts = [
        'provider_id' => 'integer',
        'vendor_id' => 'integer',
        'trip_id' => 'integer',
        'zone_id' => 'integer',
        'module_id' => 'integer',
        'trip_amount' => 'float',
        'store_amount' => 'float',
        'admin_commission' => 'float',
        'tax' => 'float',
        'admin_expense' => 'float',
        'store_expense' => 'float',
        'discount_amount_by_store' => 'float',
        'additional_charge' => 'float',
        'ref_bonus_amount' => 'float',
        'commission_percentage' => 'float',
        'is_subscribed' => 'integer',
        'avg_rating' => 'float',
    ];


    public function vendor()
    {
        return $this->belongsTo(Vendor::class,'vendor_id');
    }
    public function provider()
    {
        return $this->belongsTo(Store::class,'provider_id');
    }
    public function trip()
    {
        return $this->belongsTo(Trips::class,'trip_id');
    }
    public function module()
    {
        return $this->belongsTo(Module::class,'module_id');
    }
    public function zone()
    {
        return $this->belongsTo(Zone::class,'zone_id');
    }


}
