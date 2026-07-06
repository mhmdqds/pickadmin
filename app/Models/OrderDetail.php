<?php

namespace App\Models;

use App\Traits\ReportFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderDetail extends Model
{
    use HasFactory , ReportFilter;

    protected $fillable = [
        'order_id',
        'item_id',
        'item_campaign_id',
        'item_details',
        'quantity',
        'price',
        'tax_amount',
        'tax_status',
        'discount_on_item',
        'discount_type',
        'discount_on_product_by',
        'discount_percentage',
        'variant',
        'variation',
        'add_ons',
        'total_add_on_price',
        'addon_discount',
        'note',
        'category_id',
    ];

    protected $casts = [
        'price' => 'float',
        'discount_on_item' => 'float',
        'total_add_on_price' => 'float',
        'tax_amount' => 'float',
        'item_id'=> 'integer',
        'order_id'=> 'integer',
        'quantity'=>'integer',
        'item_campaign_id'=>'integer',
        'category_id' => 'integer',
        'note' => 'string'
    ];

    protected $primaryKey   = 'id';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function vendor()
    {
        return $this->order->store();
    }
    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }
    public function campaign()
    {
        return $this->belongsTo(ItemCampaign::class, 'item_campaign_id');
    }

    protected static function boot(){
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->Has('order');
        });
    }
}