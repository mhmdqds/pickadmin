<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Scopes\ZoneScope;
use Modules\RideShare\Entities\ReviewModule\RideReview;
use Modules\RideShare\Entities\TripManagement\RideRequest;
use Modules\RideShare\Entities\UserManagement\RiderDetail;
use Modules\RideShare\Entities\UserManagement\RiderTimeLog;
use Modules\RideShare\Entities\UserManagement\TimeTrack;
use Modules\RideShare\Entities\UserManagement\UserLastLocation;
use Modules\RideShare\Entities\UserManagement\UserLevel;
use Modules\RideShare\Entities\UserManagement\UserLevelHistory;
use Modules\RideShare\Entities\VehicleManagement\RiderVehicle;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\DemoMaskable;

class DeliveryMan extends Authenticatable
{
    use Notifiable,DemoMaskable;

    protected $casts = [
        'zone_id' => 'integer',
        'status'=>'boolean',
        'active'=>'integer',
        'available'=>'integer',
        'earning'=>'float',
        'store_id'=>'integer',
        'current_orders'=>'integer',
        'vehicle_id'=>'integer',
        'ref_by'=>'integer',
        'loyalty_point'=>'float',
    ];

    protected $guarded = [];

    protected $hidden = [
        'password',
        'auth_token',
    ];

    protected $appends = ['image_full_url','identity_image_full_url'];

    public function getFullNameAttribute()
    {
        return $this->f_name . ' ' . $this->l_name;
    }

    public function getRefCodeAttribute($value)
    {
        if ($value) {
            return $value;
        }

        $code = Helpers::generate_referer_code('deliveryman');

        $this->newQuery()
            ->where('id', $this->id)
            ->update(['ref_code' => $code]);

        $this->attributes['ref_code'] = $code;

        return $code;
    }

    public function referalHistory()
    {
        return $this->hasMany(DeliverymanReferralHistory::class);
    }
    public function total_canceled_orders()
    {
        return $this->hasMany(Order::class)->where('order_status','canceled');
    }
    public function total_ongoing_orders()
    {
        return $this->hasMany(Order::class)->whereIn('order_status',['handover','picked_up']);
    }

    public function userinfo()
    {
        return $this->hasOne(UserInfo::class,'deliveryman_id', 'id');
    }

    public function vehicle()
    {
        return $this->belongsTo(DMVehicle::class, 'vehicle_id')->withoutGlobalScope('delivery_only');
    }

    public function rider_vehicle()
    {
        return $this->hasOne(RiderVehicle::class, 'rider_id');
    }

    public function vehicleCategory()
    {
        return $this->hasOne(RiderVehicle::class, 'rider_id')->with('category');
    }

    public function riderDetails()
    {
        return $this->hasOne(RiderDetail::class, 'user_id', 'id');
    }
    public function driverTrips()
    {
        return $this->hasMany(RideRequest::class, 'driver_id');
    }

    public function todays_rides()
    {
        return $this->hasMany(RideRequest::class, 'driver_id')
            ->whereHas('tripStatus', function ($query) {
                $query->whereDate('accepted', now());
            });
    }

    public function this_week_rides()
    {
        return $this->hasMany(RideRequest::class, 'driver_id')
            ->whereHas('tripStatus', function ($query) {
                $query->whereBetween('accepted', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);
            });
    }

    public function this_month_rides()
    {
        return $this->hasMany(RideRequest::class, 'driver_id')
            ->whereHas('tripStatus', function ($query) {
                $query->whereBetween('accepted', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ]);
            });
    }
    public function driverCompletedTrips()
    {
        return $this->hasMany(RideRequest::class, 'driver_id')->where('current_status', 'completed');
    }
    public function driverCancelledTrips()
    {
        return $this->hasMany(RideRequest::class, 'driver_id')->where('current_status', 'cancelled');
    }

    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'user_level_id');
    }

    public function levelHistory()
    {
        return $this->hasMany(UserLevelHistory::class, 'user_id');
    }

    public function latestLevelHistory()
    {
        return $this->hasOne(UserLevelHistory::class, 'user_id')->latestOfMany();
    }

    public function givenReviews()
    {
        return $this->hasMany(RideReview::class, 'given_by')->where('review_for', CUSTOMER);
    }

    public function receivedReviews()
    {
        return $this->hasMany(RideReview::class, 'received_by')->where('review_for', DRIVER);
    }

    public function rideRating()
    {
        return $this->hasMany(RideReview::class, 'received_by')->where('review_for', DRIVER)
        ->select(DB::raw('avg(rating) average, count(received_by) rating_count, received_by'))
            ->groupBy('received_by');
    }

    public function getCombinedRatingAttribute()
    {
        $ride = DB::table('ride_reviews')
            ->where('review_for', 'driver')
            ->where('received_by', $this->id)
            ->select('rating');

        $dm = DB::table('d_m_reviews')
            ->where('delivery_man_id', $this->id)
            ->select('rating');

        $union = $ride->unionAll($dm);

        return DB::query()
            ->fromSub($union, 'all_reviews')
            ->selectRaw('AVG(rating) as average, COUNT(*) as total')
            ->first();
    }

    public function lastLocations()
    {
        return $this->hasOne(UserLastLocation::class, 'user_id')->where('type', 'rider');
    }

    public function getDriverLastTrip()
    {
        return $this->driverTrips()
            ->whereIn('current_status', DV_DELETE_TRIP_CURRENT_STATUS)->get();
    }

    public function getDriverOngoingTrip() {
        return $this->driverTrips()
            ->where('current_status', ONGOING)->with('coordinate')->first();
    }

    public function getDriverAcceptedTrip()
    {
        return $this->driverTrips()
            ->where('current_status', ACCEPTED)->with('coordinate')->first();
    }

    public function driverDetails()
    {
        return $this->hasOne(RiderDetail::class, 'user_id');
    }

    public function latestTrack()
    {
        return $this->hasOne(TimeTrack::class, 'user_id')->latestOfMany();
    }

    public function timeTrack()
    {
        return $this->hasMany(TimeTrack::class, 'user_id');
    }

    public function timeLog()
    {
        return $this->hasMany(RiderTimeLog::class, 'rider_id');
    }

    public function wallet()
    {
        return $this->hasOne(DeliveryManWallet::class, 'delivery_man_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function order_transaction()
    {
        return $this->hasMany(OrderTransaction::class);
    }

    public function todays_earning()
    {
        return $this->hasMany(OrderTransaction::class)->whereDate('created_at',now());
    }

    public function this_week_earning()
    {
        return $this->hasMany(OrderTransaction::class)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function this_month_earning()
    {
        return $this->hasMany(OrderTransaction::class)->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'));
    }

    public function todaysorders()
    {
        return $this->hasMany(Order::class)->whereDate('accepted',now());
    }

    public function total_delivered_orders()
    {
        return $this->hasMany(Order::class)->where('order_status','delivered');
    }

    public function this_week_orders()
    {
        return $this->hasMany(Order::class)->whereBetween('accepted', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function delivery_history()
    {
        return $this->hasMany(DeliveryHistory::class, 'delivery_man_id');
    }

    public function last_location()
    {
        return $this->hasOne(DeliveryHistory::class, 'delivery_man_id')->latestOfMany();
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function reviews()
    {
        return $this->hasMany(DMReview::class);
    }

    public function disbursement_method()
    {
        return $this->hasOne(DisbursementWithdrawalMethod::class)->where('is_default',1);
    }

    public function rating()
    {
        return $this->hasMany(DMReview::class)
            ->select(DB::raw('avg(rating) average, count(delivery_man_id) rating_count, delivery_man_id'))
            ->groupBy('delivery_man_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1)->where('application_status','approved');
    }
    public function scopeInActive($query)
    {
        return $query->where('active', 0)->where('application_status','approved');
    }

    public function scopeEarning($query)
    {
        return $query->where('earning', 1);
    }

    public function scopeAvailable($query)
    {
        return $query->where('current_orders', '<' ,config('dm_maximum_orders')??1);
    }

    public function scopeUnavailable($query)
    {
        return $query->where('current_orders', '>' ,config('dm_maximum_orders')??1);
    }

    public function scopeZonewise($query)
    {
        return $query->where('type','zone_wise');
    }

    public function getImageFullUrlAttribute(){
        $value = $this->image;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'image') {
                    return Helpers::get_full_url('delivery-man',$value,$storage['value']);
                }
            }
        }

        return Helpers::get_full_url('delivery-man',$value,'public');
    }
    public function getIdentityImageFullUrlAttribute(){
        $images = [];
        $value = is_array($this->identity_image)
            ? $this->identity_image
            : ($this->identity_image && is_string($this->identity_image) && $this->isValidJson($this->identity_image)
                ? json_decode($this->identity_image, true)
                : []);
        if ($value){
            foreach ($value as $item){
                $item = is_array($item)?$item:(is_object($item) && get_class($item) == 'stdClass' ? json_decode(json_encode($item), true):['img' => $item, 'storage' => 'public']);
                $images[] = Helpers::get_full_url('delivery-man',$item['img'],$item['storage']);
            }
        }

        return $images;
    }

    private function isValidJson($string)
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    public function storage()
    {
        return $this->morphMany(Storage::class, 'data');
    }

    public function scopeRider($query)
    {
        return $query->withoutGlobalScope('delivery_only')->where('is_ride', 1);
    }

    protected static function booted()
    {
        static::addGlobalScope('storage', function ($builder) {
            $builder->with('storage');
        });
        static::addGlobalScope(new ZoneScope);

        if(!request()->is('api/*') && !request()->is('deliveryman-earning-report-invoice/*') && addon_published_status('RideShare')){
            static::addGlobalScope('delivery_only', function (Builder $builder) {
                $builder->where('is_delivery', 1);
            });
        }
    }

    protected static function boot()
    {
        parent::boot();
        static::saved(function ($model) {
            if($model->isDirty('image')){
                $value = Helpers::getDisk();

                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'image',
                ], [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

    }
}
