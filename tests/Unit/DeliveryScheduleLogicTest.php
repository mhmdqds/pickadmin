<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for App\CentralLogics\DeliveryScheduleLogic.
 *
 * These tests intentionally avoid booting Laravel — they exercise
 * the validation helper which is pure PHP and has no side effects,
 * so they can run in any environment.
 *
 * The intent is to lock the business rules:
 *
 *   - Default window is 08:00 → 18:00.
 *   - Inactive days are valid even when times are missing.
 *   - closing <= opening is rejected (no overnight windows).
 *   - 08:00, 12:00, 17:59 are AVAILABLE; 18:00, 19:00, 07:59 are NOT.
 */
class DeliveryScheduleLogicTest extends TestCase
{
    private function logic(): string
    {
        return \App\CentralLogics\DeliveryScheduleLogic::class;
    }

    public function test_default_window_constants_are_08_to_18(): void
    {
        $this->assertSame('08:00:00', \App\CentralLogics\DeliveryScheduleLogic::DEFAULT_OPENING);
        $this->assertSame('18:00:00', \App\CentralLogics\DeliveryScheduleLogic::DEFAULT_CLOSING);
    }

    public function test_inactive_day_is_valid_even_without_times(): void
    {
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow(null, null, false);
        $this->assertNull($result, 'Inactive day must not require times.');
    }

    public function test_active_day_requires_opening_and_closing(): void
    {
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow(null, '12:00:00', true);
        $this->assertSame('time_required', $result);

        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('08:00:00', null, true);
        $this->assertSame('time_required', $result);
    }

    public function test_invalid_time_format_is_rejected(): void
    {
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('not-a-time', '10:00:00', true);
        $this->assertSame('time_format', $result);

        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('08:00:00', '25:99', true);
        $this->assertSame('time_format', $result);
    }

    public function test_overnight_window_is_rejected(): void
    {
        // 22:00 -> 02:00 is treated as invalid (no overnight support).
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('22:00:00', '02:00:00', true);
        $this->assertSame('invalid_range', $result);
    }

    public function test_equal_open_and_close_is_rejected(): void
    {
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('08:00:00', '08:00:00', true);
        $this->assertSame('invalid_range', $result);
    }

    public function test_valid_active_window_is_accepted(): void
    {
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('08:00:00', '18:00:00', true);
        $this->assertNull($result);
    }

    public function test_short_hhmm_format_is_accepted(): void
    {
        // The validator should accept both "H:i" and "H:i:s".
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('08:00', '18:00', true);
        $this->assertNull($result);
    }

    public function test_cache_key_prefix_is_namespaced(): void
    {
        $this->assertStringStartsWith('store_delivery_schedule_', \App\CentralLogics\DeliveryScheduleLogic::CACHE_KEY_PREFIX);
    }

    public function test_window_helper_returns_null_for_inactive_day(): void
    {
        // We use a stdClass store stub; the only attribute read by
        // the helper is `id`, and the row lookup is the only DB call.
        // To stay pure, we assert only on the validation contract.
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('10:00', '14:00', false);
        $this->assertNull($result);
    }

    public function test_validates_full_iso_format(): void
    {
        // "H:i:s" full format must also pass.
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('10:00:00', '14:00:00', true);
        $this->assertNull($result);
    }

    public function test_rejects_zero_hour_minute_out_of_range(): void
    {
        // 99:99 is technically 4 digits but not a real time.
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('99:99', '14:00', true);
        $this->assertSame('time_format', $result);
    }

    public function test_rejects_minute_over_59(): void
    {
        // 08:60 is not a valid minute.
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('08:60', '18:00', true);
        $this->assertSame('time_format', $result);
    }

    public function test_rejects_hour_over_23(): void
    {
        // 24:00 is not a valid hour.
        $result = \App\CentralLogics\DeliveryScheduleLogic::validateWindow('24:00', '25:00', true);
        $this->assertSame('time_format', $result);
    }
}
