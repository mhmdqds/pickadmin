<?php

namespace Modules\ReelsModule\Http\Requests\Api\V1;

use App\CentralLogics\Helpers;
use App\Models\Store;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\ReelsModule\Support\ReelModuleConfig;

class ReelListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => 'nullable|integer|exists:stores,id',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'offset' => 'nullable|integer|min:1',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isMultiModule = ReelModuleConfig::isMultiModule();
            $moduleId = (int) data_get(config('module.current_module_data'), 'id', $this->header('moduleId'));

            if ($isMultiModule && $moduleId <= 0) {
                $validator->errors()->add('moduleId', translate('messages.module_id_required'));
                return;
            }

            if (!$this->filled('store_id')) {
                return;
            }

            $storeBelongsToModule = Store::query()
                ->where('id', (int) $this->input('store_id'))
                ->when($isMultiModule, fn ($query) => $query->where('module_id', $moduleId))
                ->exists();

            if (!$storeBelongsToModule) {
                $validator->errors()->add('store_id', 'The selected store does not belong to the provided module.');
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json(['errors' => Helpers::error_processor($validator)], 403);

        throw new ValidationException($validator, $response);
    }
}
