<?php

namespace Modules\ReelsModule\Http\Requests\Api\V1\Vendor;

use App\CentralLogics\Helpers;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Modules\ReelsModule\Entities\Reel;

class ReelStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxUploadSizeMb = max(1, (int) (Helpers::get_business_settings('reels_max_upload_size_mb') ?? 15));

        return [
            'description' => 'required|string|max:1000',
            'translations' => 'nullable|json',
            'thumbnail' => 'required|image|max:' . (MAX_FILE_SIZE * 1024) . '|mimes:'.IMAGE_FORMAT_FOR_VALIDATION,
            'video' => 'required|file|mimes:mp4,mov,3gp,gif,webm,mkv|max:' . ($maxUploadSizeMb * 1024),
            'is_always_visible' => 'nullable|in:1',
            'dates' => 'required_without:is_always_visible|nullable|string',
            'status' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        $maxUploadSizeMb = max(1, (int) (Helpers::get_business_settings('reels_max_upload_size_mb') ?? 15));

        return [
            'description.required' => translate('messages.default_description_is_required'),
            'translations.json' => translate('messages.json'),
            'thumbnail.required' => translate('messages.reel_thumbnail_is_required'),
            'thumbnail.image' => translate('messages.reel_thumbnail_must_be_an_image'),
            'thumbnail.mimes' => translate('messages.reel_thumbnail_format_is_invalid'),
            'thumbnail.max' => str_replace(':size', (string) MAX_FILE_SIZE, translate('messages.reel_thumbnail_size_must_not_exceed_2_mb')),
            'video.required' => translate('messages.reel_video_is_required'),
            'video.file' => translate('messages.reel_video_file_is_invalid'),
            'video.mimes' => translate('messages.reel_video_format_is_invalid'),
            'video.max' => str_replace(':size', (string) $maxUploadSizeMb, translate('messages.reel_video_size_must_not_exceed_mb')),
            'is_always_visible.in' => translate('messages.is_always_visible_must_be_1'),
            'dates.required_without' => translate('messages.please_select_reel_visibility_duration_or_choose_always_visible'),
            'dates.string' => translate('messages.dates_must_be_string'),
            'status.boolean' => translate('messages.status_must_be_boolean'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateUploadQuantity($validator);
            $this->validateVideoDuration($validator);

            if ($this->boolean('is_always_visible') || !$this->filled('dates')) {
                return;
            }

            try {
                [$startDate, $endDate] = array_map('trim', explode(' - ', $this->dates));
                $startDate = Carbon::createFromFormat('m/d/Y', $startDate)->startOfDay();
                $endDate = Carbon::createFromFormat('m/d/Y', $endDate)->endOfDay();
            } catch (\Throwable $th) {
                $validator->errors()->add('dates', translate('messages.please_select_a_valid_date_range'));
                return;
            }

            if ($startDate < Carbon::today()) {
                $validator->errors()->add('dates', translate('messages.Start date must be greater than or equal to today'));
            }

            if ($endDate < $startDate) {
                $validator->errors()->add('dates', translate('messages.End date must be greater than start date'));
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json(['errors' => Helpers::error_processor($validator)], 403);

        throw new ValidationException($validator, $response);
    }

    private function validateUploadQuantity($validator): void
    {
        if ((int) (Helpers::get_business_settings('reels_upload_limit_unlimited') ?? 1) === 1) {
            return;
        }

        $store = $this['vendor']?->stores[0] ?? null;
        $storeId = $store?->id;
        $limit = (int) (Helpers::get_business_settings('reels_upload_limit') ?? 0);
        $limitType = Helpers::get_business_settings('reels_upload_limit_type') ?? 'week';

        if (!$storeId || $limit < 1) {
            return;
        }

        [$startDate, $endDate] = $this->getUploadLimitWindow($limitType);

        $existingCount = Reel::where('store_id', $storeId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($existingCount >= $limit) {
            $validator->errors()->add(
                'video',
                str_replace(
                    [':limit', ':period'],
                    [(string) $limit, $limitType === 'month' ? translate('messages.month') : translate('messages.week')],
                    translate('messages.reel_upload_limit_exceeded')
                )
            );
        }
    }

    private function validateVideoDuration($validator): void
    {
        if (!$this->hasFile('video')) {
            return;
        }

        $maxDuration = max(1, (int) (Helpers::get_business_settings('reels_max_duration') ?? 30));
        $durationUnit = Helpers::get_business_settings('reels_max_duration_unit') ?? 'min';
        $durationSeconds = $this->extractVideoDurationInSeconds($this->file('video'));

        if ($durationSeconds === null) {
            return;
        }

        $maxDurationSeconds = $durationUnit === 'hour' ? $maxDuration * 3600 : $maxDuration * 60;

        if ($durationSeconds > $maxDurationSeconds) {
            $validator->errors()->add(
                'video',
                str_replace(
                    [':duration', ':unit'],
                    [(string) $maxDuration, $durationUnit === 'hour' ? translate('messages.Hour') : translate('messages.Minutes')],
                    translate('messages.reel_video_duration_must_not_exceed')
                )
            );
        }
    }

    private function getUploadLimitWindow(string $limitType): array
    {
        return $limitType === 'month'
            ? [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]
            : [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
    }

    private function extractVideoDurationInSeconds(?UploadedFile $file): ?float
    {
        if (!$file instanceof UploadedFile) {
            return null;
        }

        $filePath = $file->getRealPath();
        if (!$filePath) {
            return null;
        }

        if (function_exists('shell_exec')) {
            $ffprobePath = trim((string) @shell_exec('command -v ffprobe'));

            if ($ffprobePath !== '') {
                $command = $ffprobePath . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($filePath) . ' 2>/dev/null';
                $duration = trim((string) @shell_exec($command));

                if (is_numeric($duration)) {
                    return (float) $duration;
                }
            }
        }

        return null;
    }
}
