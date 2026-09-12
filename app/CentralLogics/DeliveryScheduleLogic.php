<?php

namespace App\CentralLogics;

use App\Models\DeliverySchedule;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * DeliveryScheduleLogic
 *
 * Single source of truth for "is Delivery currently available for
 * this store?". The Daily time schedule (StoreSchedule) is NOT
 * touched here — this class is intentionally separate so that
 * Takeaway orders can still be accepted after Delivery hours end.
 *
 * Design decisions:
 *   - If a store has no rows in `delivery_schedule`, the default
 *     08:00:00 → 18:00:00 window is used (no DB rows need to be
 *     written for legacy stores).
 *   - A day with `is_active = 0` means delivery is closed regardless
 *     of times.
 *   - Times are always compared using the application's configured
 *     timezone (config('app.timezone')).
 *   - Overnight windows (closing < opening) are not supported today;
 *     they are treated as invalid and the day is considered closed.
 */
class DeliveryScheduleLogic
{
    public const DEFAULT_OPENING  = '08:00:00';
    public const DEFAULT_CLOSING  = '18:00:00';

    /** Cache key prefix — bumped when delivery schedule changes. */
    public const CACHE_KEY_PREFIX = 'store_delivery_schedule_';

    /**
     * Return all configured delivery windows for a store (raw rows).
     */
    public static function getSchedulesForStore(int $storeId)
    {
        return DeliverySchedule::where('store_id', $storeId)
            ->orderBy('day')
            ->orderBy('opening_time')
            ->get();
    }

    /**
     * Return delivery windows formatted for API consumption, always
     * emitting all 7 weekdays. When a store has no schedule, the
     * default 08:00 / 18:00 window is returned.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getApiFormattedSchedule(Store $store): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $store->id;

        return Cache::remember($cacheKey, 600, function () use ($store) {
            $rows = self::getSchedulesForStore($store->id);
            $byDay = [];
            foreach ($rows as $row) {
                $byDay[(int) $row->day] = [
                    'is_active'    => (bool) $row->is_active,
                    'opening_time' => substr((string) $row->opening_time, 0, 5),
                    'closing_time' => substr((string) $row->closing_time, 0, 5),
                ];
            }

            $labels = [
                0 => 'sunday', 1 => 'monday', 2 => 'tuesday',
                3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday',
            ];

            $out = [];
            foreach ($labels as $day => $name) {
                if (isset($byDay[$day])) {
                    $out[$name] = $byDay[$day];
                } else {
                    $out[$name] = [
                        'is_active'    => true,
                        'opening_time' => '08:00',
                        'closing_time' => '18:00',
                    ];
                }
            }
            return $out;
        }) ?: [];
    }

    /**
     * Resolve the window for the given weekday (0..6). Returns null
     * when the day is inactive, missing on purpose, or invalid.
     *
     * @return array{opening: string, closing: string}|null
     */
    public static function getWindowForDay(Store $store, int $day): ?array
    {
        $row = DeliverySchedule::where('store_id', $store->id)
            ->where('day', $day)
            ->first();

        if (!$row) {
            // No row exists — default window applies (legacy stores
            // do NOT need rows to be considered open).
            return [
                'opening' => self::DEFAULT_OPENING,
                'closing' => self::DEFAULT_CLOSING,
            ];
        }

        if (!$row->is_active) {
            return null;
        }

        $opening = (string) $row->opening_time;
        $closing = (string) $row->closing_time;

        // The project does not support overnight windows today.
        if (strcmp($closing, $opening) <= 0) {
            return null;
        }

        return [
            'opening' => $opening,
            'closing' => $closing,
        ];
    }

    /**
     * Decide whether Delivery is available right now.
     */
    public static function isDeliveryAvailableNow(Store $store, $at = null): bool
    {
        $now = $at ? Carbon::parse($at) : Carbon::now(config('app.timezone'));

        $day = (int) $now->format('w');
        $window = self::getWindowForDay($store, $day);
        if (!$window) {
            return false;
        }

        $current = $now->format('H:i:s');

        // Inclusive lower bound, exclusive upper bound:
        //   08:00 -> available
        //   17:59 -> available
        //   18:00 -> not available
        return ($current >= $window['opening']) && ($current < $window['closing']);
    }

    /**
     * Decide whether Delivery is available at a specific timestamp.
     */
    public static function isDeliveryAvailableAt(Store $store, $at): bool
    {
        if (!$at) {
            return self::isDeliveryAvailableNow($store);
        }
        return self::isDeliveryAvailableNow($store, Carbon::parse($at));
    }

    /**
     * Return the current window in API shape. If Delivery is closed
     * right now, `available` is `false` but `opens_at` / `closes_at`
     * still reflect today's configured window when present.
     *
     * @return array<string, mixed>
     */
    public static function getCurrentAvailability(Store $store): array
    {
        $now = Carbon::now(config('app.timezone'));
        $day = (int) $now->format('w');
        $window = self::getWindowForDay($store, $day);

        if ($window && self::isDeliveryAvailableNow($store, $now)) {
            return [
                'available' => true,
                'opens_at'  => substr($window['opening'], 0, 5),
                'closes_at' => substr($window['closing'], 0, 5),
                'day'       => $day,
            ];
        }

        return [
            'available' => false,
            'opens_at'  => $window ? substr($window['opening'], 0, 5) : null,
            'closes_at' => $window ? substr($window['closing'], 0, 5) : null,
            'day'       => $day,
        ];
    }

    /**
     * Invalidate cached schedule for a store.
     */
    public static function invalidateCache(int $storeId): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $storeId);
    }

    /**
     * Validate a single day's incoming payload. Returns null when
     * valid, or an error code when invalid.
     */
    public static function validateWindow(?string $opening, ?string $closing, bool $isActive): ?string
    {
        if (!$isActive) {
            return null;
        }
        if (!$opening || !$closing) {
            return 'time_required';
        }

        // Format + actual time range validation. We don't accept
        // "25:99" — the regex alone would let it through.
        foreach ([$opening, $closing] as $value) {
            if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/', $value)) {
                return 'time_format';
            }
        }

        if (strcmp($closing, $opening) <= 0) {
            return 'invalid_range';
        }
        return null;
    }
}
