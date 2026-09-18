<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time backfill: pull courier credentials that were saved into the
 * `settings` table (Settings → Courier UI) into the `courier_providers`
 * table, which is the source of truth for the Sales page's
 * "Send to courier" buttons and the Steadfast API service.
 *
 * Going forward the sync happens live in
 * SettingService::updateCourierSettings(), so this migration only fixes
 * data that was saved before the sync was wired up.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('courier_providers')
            || !\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            return;
        }

        $courierSettings = DB::table('settings')
            ->where('group', 'courier')
            ->pluck('value', 'key')
            ->all();

        $providerMap = [
            'steadfast' => [
                'is_active'  => 'steadfast_enabled',
                'api_key'    => 'steadfast_api_key',
                'api_secret' => 'steadfast_api_secret',
            ],
            'pathao' => [
                'is_active'  => 'pathao_enabled',
                'api_key'    => 'pathao_client_id',
                'api_secret' => 'pathao_client_secret',
                'store_id'   => 'pathao_merchant_id',
            ],
        ];

        foreach ($providerMap as $slug => $fields) {
            $updates = [];
            foreach ($fields as $providerCol => $settingKey) {
                if (!array_key_exists($settingKey, $courierSettings)) {
                    continue;
                }
                $value = $courierSettings[$settingKey];
                if ($providerCol === 'is_active') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } else {
                    $value = is_string($value) ? trim($value) : $value;
                    $value = ($value === '' ? null : $value);
                }
                $updates[$providerCol] = $value;
            }
            if ($updates !== []) {
                DB::table('courier_providers')->where('slug', $slug)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // No reversal — the courier_providers rows may have been edited by
        // hand since then, and the original null values aren't recoverable.
    }
};
