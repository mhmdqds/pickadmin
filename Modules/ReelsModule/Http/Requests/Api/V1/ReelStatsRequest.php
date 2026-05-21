<?php

namespace Modules\ReelsModule\Http\Requests\Api\V1;

use App\CentralLogics\Helpers;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ReelStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reel_id' => 'required|integer|exists:reels,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $moduleId = (int) data_get(config('module.current_module_data'), 'id', $this->header('moduleId'));

            if ($moduleId <= 0) {
                $validator->errors()->add('moduleId', translate('messages.module_id_required'));
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json(['errors' => Helpers::error_processor($validator)], 403);

        throw new ValidationException($validator, $response);
    }
}
