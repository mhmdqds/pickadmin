<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\BusinessSetting;
use App\Models\Item;
use App\Models\PriorityList;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\CommonCondition;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommonConditionController extends Controller
{
    public function get_conditions(Request $request,$search=null)
    {
        try {
            Helpers::setZoneIds($request);
            $common_condition_default_status = BusinessSetting::where('key', 'common_condition_default_status')->first()?->value ?? 1;
            $common_condition_sort_by_general = PriorityList::where('name', 'common_condition_sort_by_general')->where('type','general')->first()?->value ?? '';
            $key = explode(' ', $search);
            $zone_id = $request->header('zoneId');
            $type = $request->query('type', 'all');

            $conditionData = $this->getCommonConditionFilteredItemBaseQuery($zone_id, $type)
                ->join('pharmacy_item_details', 'pharmacy_item_details.item_id', '=', 'items.id')
                ->whereNotNull('pharmacy_item_details.common_condition_id')
                ->selectRaw('pharmacy_item_details.common_condition_id, COUNT(DISTINCT items.id) as items_count, COALESCE(SUM(items.order_count), 0) as order_count')
                ->groupBy('pharmacy_item_details.common_condition_id')
                ->get()
                ->keyBy('common_condition_id');

            $conditions = CommonCondition::Active()
            ->when($search, function($query)use($key){
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%". $value."%");
                    }
                });
            })
            ->when($conditionData->isNotEmpty(), function ($query) use ($conditionData) {
                $query->whereIn('id', $conditionData->keys());
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($common_condition_default_status  != 1 &&  $common_condition_sort_by_general == 'latest', function ($query) {
                $query->latest();
            })
            ->when($common_condition_default_status  != 1 &&  $common_condition_sort_by_general == 'oldest', function ($query) {
                $query->oldest();
            })
            ->when($common_condition_default_status  != 1 &&  $common_condition_sort_by_general == 'a_to_z', function ($query) {
                $query->orderby('name');
            })
            ->when($common_condition_default_status  != 1 &&  $common_condition_sort_by_general == 'z_to_a', function ($query) {
                $query->orderby('name','desc');
            })
            ->get()
            ->map(function ($condition) use ($conditionData) {
                $condition['items_count'] = (int) ($conditionData[$condition->id]->items_count ?? 0);
                return $condition;
            });


            if($common_condition_default_status  != 1 &&  $common_condition_sort_by_general == 'order_count'){
                $conditions = $conditions->sortByDesc('order_count')->values()->all();
            }

            return response()->json($conditions, 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function get_products($id, Request $request)
    {
        Helpers::setZoneIds($request);
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $zone_id= $request->header('zoneId');

        $type = $request->query('type', 'all');
        $limit = $request['limit'];
        $offset = $request['offset'];

        $paginator = Item::
        whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true));
        })
        ->whereHas('store', function($query)use($zone_id){
            $query->whereIn('zone_id', json_decode($zone_id, true))->whereHas('zone.modules',function($query){
                $query->when(config('module.current_module_data'), function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            });
        })
        ->whereHas('pharmacy_item_details',function($q)use($id){
            return $q->whereHas('common_condition',function($q)use($id){
                return $q->when(is_numeric($id),function ($qurey) use($id){
                    return $qurey->whereId($id);
                })
                ->when(!is_numeric($id),function ($qurey) use($id){
                    $qurey->where('slug', $id);
                });
            });
        })
        ->active()->type($type)->latest()->paginate($limit, ['*'], 'page', $offset);
        $data=[
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items()
        ];
        $data['products'] = Helpers::product_data_formatting($data['products'] , true, false, app()->getLocale());
        return response()->json($data, 200);
    }

    private function getCommonConditionFilteredItemBaseQuery($zone_id, $type)
    {
        return Item::query()
            ->whereHas('module.zones', function($query)use($zone_id){
                $query->whereIn('zones.id', json_decode($zone_id, true));
            })
            ->whereHas('store', function($query)use($zone_id){
                $query->whereIn('zone_id', json_decode($zone_id, true))->whereHas('zone.modules',function($query){
                    $query->when(config('module.current_module_data'), function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                });
            })
            ->whereHas('pharmacy_item_details', function ($q) {
                $q->whereNotNull('common_condition_id');
            })
            ->active()
            ->type($type);
    }

    public function getCommonConditionList(){
        $conditions = CommonCondition::Active()->get(['id','name']);
        return response()->json($conditions, 200);
    }
}
