<?php

namespace Modules\Rental\Http\Controllers\Api\Public;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Routing\Controller;
use Modules\Rental\Entities\VehicleCategory;

class VehicleCategoryController extends Controller
{

    public function __construct(private VehicleCategory $vehicleCategory, private Helpers $helpers)
    {
        $this->vehicleCategory = $vehicleCategory;
        $this->helpers = $helpers;
    }

    public function vehicleCategoryList(Request $request)
    {
        $limit = $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;
        $vehicleCategory = $this->vehicleCategory->where('status', 1)
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->select(['id','image' ,'name'])
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);

        $data = $this->helpers->preparePaginatedResponse(pagination: $vehicleCategory, limit: $limit, offset: $offset, key: 'vehicles', extraData: []);

        return response()->json($data, 200);
    }
}
