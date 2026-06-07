<?php

namespace Modules\Rental\Http\Controllers\Api\Public;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Routing\Controller;
use Modules\Rental\Entities\VehicleBrand;


class VehicleBrandController extends Controller
{

    public function __construct(private VehicleBrand $brand, private Helpers $helpers)
    {
        $this->brand = $brand;
        $this->helpers = $helpers;
    }

    public function vehicleBrandList(Request $request)
    {
        $limit = $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;
        $brand = $this->brand->where('status', 1)
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->select(['id','image' ,'name'])
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);

        $data = $this->helpers->preparePaginatedResponse(pagination: $brand, limit: $limit, offset: $offset, key: 'brands', extraData: []);

        return response()->json($data, 200);
    }
}
