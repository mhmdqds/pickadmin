-- ============================================================
--  Add Message Central to addon_settings (sms_config)
--  Database: picklespies_pikadmin
--
--  Run this once on the production database. The Message Central
--  card will then appear on:
--    /admin/business-settings/third-party/sms-module
--
--  Status is 0 (inactive) so it does NOT interfere with the
--  currently active provider (Nexmo in your case, is_active=1).
-- ============================================================

START TRANSACTION;

INSERT INTO `addon_settings`
    (`id`, `key_name`, `live_values`, `test_values`, `settings_type`, `mode`, `is_active`, `created_at`, `updated_at`, `additional_data`)
VALUES
    (
        'f572937a-0b8f-4f05-ba92-f54b0a234927',
        'message_central',
        '{"gateway":"message_central","mode":"live","status":0,"customer_id":"","auth_token":"","country_code":"","otp_template":"Your verification code is #OTP#"}',
        '{"gateway":"message_central","mode":"live","status":0,"customer_id":"","auth_token":"","country_code":"","otp_template":"Your verification code is #OTP#"}',
        'sms_config',
        'live',
        0,
        NOW(),
        NOW(),
        NULL
    )
ON DUPLICATE KEY UPDATE
    `updated_at` = NOW(),
    `live_values` = JSON_SET(
        `live_values`,
        '$.sender_id', NULL,
        '$.otp_length', NULL,
        '$.message_type', NULL,
        '$.template_id', NULL,
        '$.entity_id', NULL
    );

COMMIT;

-- ============================================================
--  Verification query (read-only, optional)
-- ============================================================
-- SELECT id, key_name, settings_type, mode, is_active,
--        JSON_UNQUOTE(JSON_EXTRACT(live_values, '$.status')) AS status,
--        JSON_UNQUOTE(JSON_EXTRACT(live_values, '$.customer_id')) AS customer_id,
--        JSON_UNQUOTE(JSON_EXTRACT(live_values, '$.sender_id')) AS sender_id,
--        JSON_UNQUOTE(JSON_EXTRACT(live_values, '$.otp_template')) AS otp_template
-- FROM   addon_settings
-- WHERE  settings_type = 'sms_config'
-- ORDER BY key_name;