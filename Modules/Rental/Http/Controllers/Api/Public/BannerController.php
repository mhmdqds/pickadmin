<?php

namespace Modules\Rental\Http\Controllers\Api\Public;

use App\Models\Store;
use App\Models\Banner;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;



class BannerController extends Controller
{

    public function list(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $banners = $this->get_banners($zone_id, $request->featured);
        try {
            return response()->json(['banners' => $banners], 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function getStoreBanners(Request $request, $store_id)
    {

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $moduleData = config('module.current_module_data');
        $moduleId = isset($moduleData['id']) ? $moduleData['id'] : 'default';
        $cacheKey = 'banners_' . md5(implode('_', [
            $zone_id,
            $moduleId,
            $store_id
        ]));
        $banners = Cache::rememberForever($cacheKey, function () use ($zone_id,$store_id) {
            return  Banner::active()->Where('data',$store_id)->wherehas('module', function ($query) {
                $query->where('module_type', 'rental');
            })
               ->whereIn('zone_id', json_decode($zone_id, true))
                ->whereHas('module', function ($query) {
                    $query->active();
                })
                ->orderBy('featured','desc')
                ->where('created_by', 'store')
                ->get();
        });


        $data = [];
        foreach ($banners as $banner) {
            $data[] = [
                'id' => $banner->id,
                'title' => $banner->title,
                'type' =>'default',
                'image' => $banner->image,
                'link' => $banner->default_link,
                'provider_id' => null,
                'image_full_url' => $banner->image_full_url
            ];
    }
    return $data;

        return response()->json($banners, 200);
    }


    private function get_banners($zone_id, $featured = false)
    {
        $moduleData = config('module.current_module_data');
        $moduleId = isset($moduleData['id']) ? $moduleData['id'] : 'default';
        $cacheKey = 'banners_' . md5($zone_id . '_' . ($featured ? 'featured' : 'non_featured') . '_' . $moduleId);

        $banners = Cache::rememberForever($cacheKey, function () use ($zone_id, $featured) {
            return  Banner::active()->wherehas('module', function ($query) {
                $query->where('module_type', 'rental');
            })
                ->when($featured, function ($query) {
                    $query->featured();
                })->whereIn('zone_id', json_decode($zone_id, true))
                ->whereHas('module', function ($query) {
                    $query->active();
                })
                ->where('created_by', 'admin')
                ->get();
        });

        return $this->fromatBannerData($banners);
    }



    private function fromatBannerData($banners){
        $data = [];
        foreach ($banners as $banner) {
            if ($banner->type == 'store_wise') {
                $store = Store::active()
                    ->when(config('module.current_module_data'), function ($query) {
                        $query->whereHas('zone.modules', function ($query) {
                            $query->where('modules.id', config('module.current_module_data')['id']);
                        });
                    })
                    ->find($banner->data);
                if ($store) {
                    $data[] = [
                        'id' => $banner->id,
                        'title' => $banner->title,
                        'type' => $banner->type,
                        'image' => $banner->image,
                        'link' => null,
                        'provider_id' => $store ? Helpers::store_data_formatting($store, false)?->id : null,
                        'image_full_url' => $banner->image_full_url
                    ];
                }
            }
            if ($banner->type == 'default') {
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => $banner->default_link,
                    'provider_id' => null,
                    'image_full_url' => $banner->image_full_url
                ];
            }
            if ($banner->type == null) {
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => null,
                    'provider_id' => null,
                    'image_full_url' => $banner->image_full_url
                ];
            }
        }
        return $data;
    }
}
