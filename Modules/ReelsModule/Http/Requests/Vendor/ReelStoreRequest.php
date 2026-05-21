<?php

namespace Modules\ReelsModule\Http\Requests\Vendor;

use App\CentralLogics\Helpers;
use Modules\ReelsModule\Http\Requests\Admin\ReelStoreRequest as AdminReelStoreRequest;

class ReelStoreRequest extends AdminReelStoreRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'store_id' => Helpers::get_store_id(),
        ]);
    }
}
