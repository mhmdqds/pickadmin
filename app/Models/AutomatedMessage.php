<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AutomatedMessage extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'integer',
        'status' => 'boolean',
    ];

    public function getMessageAttribute($value){
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'message') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeCustomer($query) {
        return $query->where('question_for', 'customer');
    }

    public function scopeRider($query) {
        return $query->withoutGlobalScope('customer_only')->where('question_for', 'rider');
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    protected static function booted()
    {
        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });

        static::addGlobalScope('customer_only', function (Builder $builder) {
            $builder->customer();
        });
    }
}
