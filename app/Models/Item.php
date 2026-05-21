<?php

namespace App\Models;

use App\Scopes\ZoneScope;
use App\Scopes\StoreScope;
use Illuminate\Support\Str;
use App\Traits\HasProductVideoPreview;
use App\Traits\ReportFilter;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\TaxModule\Entities\Taxable;

class Item extends Model
{
    use HasFactory, ReportFilter, HasProductVideoPreview;
    protected $guarded = ['id'];
    protected $with = ['translations','storage'];
    protected $casts = [
        'tax' => 'float',
        'price' => 'float',
        'status' => 'integer',
        'discount' => 'float',
        'avg_rating' => 'float',
        'set_menu' => 'integer',
        'category_id' => 'integer',
        'store_id' => 'integer',
        'reviews_count' => 'integer',
        'recommended' => 'integer',
        'maximum_cart_quantity' => 'integer',
        'organic' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'veg' => 'integer',
        'images' => 'array',
        'module_id' => 'integer',
        'is_approved' => 'integer',
        'stock' => 'integer',
        "min_price" => 'float',
        "max_price" => 'float',
        'order_count' => 'integer',
        'rating_count' => 'integer',
        'unit_id' => 'integer',
        'is_halal' => 'integer',
    ];

    protected $appends = ['unit_type', 'image_full_url', 'images_full_url', 'video_full_url', 'video_size', 'video_preview_type', 'video_embed_url', 'video_preview_url', 'video_thumbnail_url', 'video_preview_modal_type', 'video_preview_modal_url', 'has_video_preview', 'has_video_source'];

    public function scopeRecommended($query)
    {
        return $query->where('recommended', 1);
    }

    public function carts()
    {
        return $this->morphMany(Cart::class, 'item');
    }

    public function temp_product()
    {
        return $this->hasOne(TempProduct::class, 'item_id')->with('translations');
    }

    public function scopeDiscounted($query)
    {
        // return $query->where('discount','>',0);

        $nowDate = now()->format('Y-m-d');
        $nowTime = now()->format('H:i');

        return $query->where(function ($query) use ($nowDate, $nowTime) {
            $query->where('discount', '>', 0)
                ->orWhereHas('store.discount', function ($q) use ($nowDate, $nowTime) {
                    $q->whereDate('start_date', '<=', $nowDate)
                        ->whereDate('end_date', '>=', $nowDate)
                        ->whereTime('start_time', '<=', $nowTime)
                        ->whereTime('end_time', '>=', $nowTime);
                })
                ->orWhereHas('flashSaleItems.flashSale', function ($q) use ($nowDate, $nowTime) {
                    $q->where('is_publish', 1)
                        ->whereDate('start_date', '<=', $nowDate)
                        ->whereDate('end_date', '>=', $nowDate);
                });
        });
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    public function scopeModule($query, $module_id)
    {
        return $query->where('module_id', $module_id);
    }


    public function scopeActive($query , $zone_ids = null ,$module_id = null)
    {
        return $query
        ->where('status', 1)->where('is_approved', 1)
            ->whereHas('store', function ($query) use ($zone_ids) {
                $query->where('status', 1)
                    ->where(function ($query) {
                        $query->where('store_business_model', 'commission')
                            ->orWhereHas('store_sub', function ($query) {
                                $query->where(function ($query) {
                                    $query->where('max_order', 'unlimited')->orWhere('max_order', '>', 0);
                                });
                            });
                    })->when($zone_ids && is_array($zone_ids) , function ($query) use ($zone_ids) {
                        $query->whereIn('zone_id', $zone_ids);
                    }) ;
            })
            ->whereHas('module', function ($query) use ($module_id){
                $query->where('status', 1)->when($module_id && is_numeric($module_id), function ($query) use ($module_id) {
                    $query->where('id', $module_id);
                });
            })
            ->whereHas('category', function ($q) {
                $q->where(function ($q) {
                    $q->where([
                            ['parent_id', '=', 0],
                            ['status', '=', 1],
                        ])
                    ->orWhere(function ($q) {
                        $q->where('parent_id', '!=', 0)
                            ->whereHas('parent', fn ($p) => $p->where('status', 1));
                    });
                });
            });
    }
    public function scopePopular($query)
    {
        return $query->orderBy('order_count', 'desc');
    }
    public function scopeApproved($query)
    {
        return $query->where('is_approved', 1);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function whislists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    // public function scopeHasRunningFlashSale($query)
    // {
    //     return $query->whereHas('flashSaleItems', function ($query) {
    //         $query->whereHas('flashSale', function ($query) {
    //             $query->Running();
    //         });
    //     });
    // }

        public function rating()
    {
        return $this->hasMany(Review::class, 'item_id')
            ->select(
                'item_id',
                DB::raw('AVG(rating) as average'),
                DB::raw('COUNT(*) as rating_count'),
                DB::raw('COUNT(CASE WHEN comment IS NOT NULL THEN 1 END) as review_count')
            )
            ->groupBy('item_id');
    }

    public function flashSaleItems()
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    public function getUnitTypeAttribute()
    {
        return $this->unit ? $this->unit->unit : null;
    }

    public function getNameAttribute($value)
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'name') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function getDescriptionAttribute($value)
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'description') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }
    public function getImageFullUrlAttribute()
    {
        $value = $this->image;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'image') {
                    return Helpers::get_full_url('product', $value, $storage['value'],'default');
                }
            }
        }

        return Helpers::get_full_url('product', $value, 'public');
    }
    public function getImagesFullUrlAttribute()
    {
        $images = [];
        $value = is_array($this->images)
            ? $this->images
            : ($this->images && is_string($this->images) && $this->isValidJson($this->images)
                ? json_decode($this->images, true)
                : []);
        if ($value) {
            foreach ($value as $item) {
                $item = is_array($item) ? $item : (is_object($item) && get_class($item) == 'stdClass' ? json_decode(json_encode($item), true) : ['img' => $item, 'storage' => 'public']);
                $images[] = Helpers::get_full_url('product', $item['img'], $item['storage'],'default');
            }
        }

        return $images;
    }

    private function isValidJson($string)
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }


    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function pharmacy_item_details()
    {
        return $this->hasOne(PharmacyItemDetails::class, 'item_id');
    }
    public function ecommerce_item_details()
    {
        return $this->hasOne(EcommerceItemDetails::class, 'item_id');
    }

    public function orders()
    {
        return $this->hasMany(OrderDetail::class);
    }

    protected static function booted()
    {
        if (auth('vendor')->check() || auth('vendor_employee')->check()) {
            static::addGlobalScope(new StoreScope);
        }

        static::addGlobalScope(new ZoneScope);
        static::addGlobalScope('storage', function ($builder) {
            $builder->with('storage');
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }


    public function scopeType($query, $type)
    {
        if ($type == 'veg') {
            return $query->where('veg', true);
        } else if ($type == 'non_veg') {
            return $query->where('veg', false);
        }
        return $query;
    }

    public function scopeAvailable($query, $time)
    {
        $query->where(function ($q) use ($time) {
            $q->where('available_time_starts', '<=', $time)->where('available_time_ends', '>=', $time);
        });
    }
    public function scopeUnAvailable($query, $time)
    {
        $query->whereNot(function ($q) use ($time) {
            $q->where('available_time_starts', '<=', $time)->where('available_time_ends', '>=', $time);
        });
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    public function allergies()
    {
        return $this->belongsToMany(Allergy::class);
    }
    public function generic()
    {
        return $this->belongsToMany(GenericName::class, 'item_generic_names');
    }
    public function nutritions()
    {
        return $this->belongsToMany(Nutrition::class);
    }
    public function storage()
    {
        return $this->morphMany(Storage::class, 'data');
    }
    protected static function boot()
    {
        parent::boot();
        static::created(function ($item) {
            $item->slug = $item->generateSlug($item->name);
            $item->save();
        });
        static::saved(function ($model) {
            if ($model->isDirty('image')) {
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
            if ($model->isDirty('images')) {
                $value = Helpers::getDisk();

                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'images',
                ], [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if ($model->isDirty('video')) {
                $value = Helpers::getDisk();

                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'video',
                ], [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
    private function generateSlug($name)
    {
        $slug = Str::slug($name);
        if ($max_slug = static::where('slug', 'like', "{$slug}%")->latest('id')->value('slug')) {

            if ($max_slug == $slug) return "{$slug}-2";

            $max_slug = explode('-', $max_slug);
            $count = array_pop($max_slug);
            if (isset($count) && is_numeric($count)) {
                $max_slug[] = ++$count;
                return implode('-', $max_slug);
            }
        }
        return $slug;
    }

    public function taxVats()
    {
        return $this->morphMany(Taxable::class, 'taxable');
    }

    public function seoData(){
        return $this->hasOne(ItemSeoData::class,'item_id');
    }


    public function users()
    {
        return $this->morphToMany(User::class ,'visitor_log');
    }
}
