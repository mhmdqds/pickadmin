<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `addon_settings` table is the runtime store for every addon / plugin
 * configuration that this PickAdmin install has ever touched (payment
 * gateways, SMS providers, analytics, ...).  It is referenced from
 *
 *   - `app\helpers.php::config_settings($key, $settings_type)`
 *   - `app\CentralLogics\SMS_module::get_settings($name)`
 *   - `app\Traits\SmsGateway::get_settings($name)`
 *
 * If the table is missing, every SMS provider returns NULL and the OTP
 * login flow silently no-ops.  Several production deploys have shipped
 * without this table — this migration makes the schema reproducible.
 */
class CreateAddonSettingsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('addon_settings')) {
            return;
        }

        Schema::create('addon_settings', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('key_name', 191)->nullable();
            $table->longText('live_values')->nullable();
            $table->longText('test_values')->nullable();
            $table->string('settings_type', 255)->nullable();
            $table->string('mode', 20)->default('live');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->longText('additional_data')->nullable();

            $table->index(['key_name', 'settings_type'], 'idx_addon_settings_key_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_settings');
    }
}