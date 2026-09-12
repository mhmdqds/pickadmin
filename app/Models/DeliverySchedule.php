<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeliverySchedule
 *
 * Independent schedule table that mirrors `store_schedule`. Each row
 * describes a window during which the store accepts Delivery orders
 * on a specific weekday. The Daily time schedule (StoreSchedule)
 * remains authoritative for store opening hours and is intentionally
 * not modified here.
 *
 * Conventions mirror StoreSchedule:
 *   - `day` is an integer in the range 0..6 (PHP `date('w')`)
 *   - `opening_time` / `closing_time` are stored as `H:i:s` time strings
 *   - `is_active` allows disabling delivery for that specific day
 */
class DeliverySchedule extends Model
{
    use HasFactory;

    protected $table = 'delivery_schedule';

    protected $casts = [
        'day'       => 'integer',
        'store_id'  => 'integer',
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'store_id',
        'day',
        'opening_time',
        'closing_time',
        'is_active',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
