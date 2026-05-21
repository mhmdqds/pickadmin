<?php

namespace Modules\ReelsModule\Http\Controllers;

use App\CentralLogics\Helpers;
use Illuminate\Http\Request;

class ReelsBusinessSettingsController
{
    public function validateStoreSettings(Request $request): void
    {
        $request->validate([
            'reels_max_upload_size_mb' => 'nullable|numeric|min:1',
            'reels_max_duration' => 'nullable|numeric|min:1',
            'reels_max_duration_unit' => 'nullable|in:min,hour',
            'reels_upload_limit' => 'nullable|numeric|min:1',
            'reels_upload_limit_type' => 'nullable|in:week,month',
        ]);

        if (($request->input('vendor_can_upload_reels') ?? 0) == 1) {
            $request->validate([
                'reels_max_upload_size_mb' => 'required|numeric|min:1',
                'reels_max_duration' => 'required|numeric|min:1',
                'reels_max_duration_unit' => 'required|in:min,hour',
            ]);

            if (($request->input('reels_upload_limit_unlimited') ?? 0) != 1) {
                $request->validate([
                    'reels_upload_limit' => 'required|numeric|min:1',
                    'reels_upload_limit_type' => 'required|in:week,month',
                ]);
            }
        }
    }

    public function getStoreSettingsPayload(Request $request): array
    {
        $isEnabled = ($request->input('vendor_can_upload_reels') ?? 0) == 1;
        $isUnlimited = ($request->input('reels_upload_limit_unlimited') ?? 0) == 1;

        return [
            'vendor_can_upload_reels' => $isEnabled ? 1 : 0,
            'reels_max_upload_size_mb' => $isEnabled ? $request->input('reels_max_upload_size_mb') : null,
            'reels_max_duration' => $isEnabled ? $request->input('reels_max_duration') : null,
            'reels_max_duration_unit' => $isEnabled ? $request->input('reels_max_duration_unit', 'min') : null,
            'reels_upload_limit_unlimited' => $isEnabled ? ($isUnlimited ? 1 : 0) : 0,
            'reels_upload_limit' => $isEnabled && !$isUnlimited ? $request->input('reels_upload_limit') : null,
            'reels_upload_limit_type' => $isEnabled && !$isUnlimited ? $request->input('reels_upload_limit_type', 'week') : null,
        ];
    }

    public function persistStoreSettings(Request $request): void
    {
        foreach ($this->getStoreSettingsPayload($request) as $key => $value) {
            Helpers::businessUpdateOrInsert(['key' => $key], [
                'value' => $value,
            ]);
        }
    }
}
