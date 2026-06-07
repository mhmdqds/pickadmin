<?php

namespace Modules\Rental\Http\Controllers\Api\Provider;

use Exception;
use App\Models\Store;
use App\Models\Banner;
use App\Models\Translation;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Traits\FileManagerTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Support\Renderable;

class BannerController extends Controller
{
    use FileManagerTrait;

    private Banner $banner;
    private Store $store;
    private Config $config;
    private Helpers $helpers;

    public function __construct(Banner $banner, Config $config, Helpers $helpers, Store $store)
    {
        $this->banner = $banner;
        $this->store = $store;
        $this->config = $config;
        $this->helpers = $helpers;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $this->helpers->error_processor($validator)], 403);
        }

        $limit = $request['limit'];
        $offset = $request['offset'];
        $providerId = $request->vendor->store->id;
        $moduleId = $request->vendor->stores[0]->module_id;
        $banners =  $this->banner->with('translations')->where('data', $providerId)->where('created_by', 'store')
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('title', 'LIKE', '%' . $key . '%');
                }
            })
            ->where('module_id', $moduleId)
            ->latest()->paginate($limit, ['*'], 'page', $offset);

        $data = $this->helpers->preparePaginatedResponse(pagination:$banners, limit:$limit, offset:$offset, key:'banners', extraData:[]);

        return response()->json($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {

       $validity= $this->validateRequest($request);
        if($validity !== true){
            return response()->json(['error' => $validity['errors']], 403);
        }

        try {
            DB::beginTransaction();
            $this->createBanner($request);
            DB::commit();

            return response()->json(['message' => translate('messages.banner_created_successfully.')], 200);

        } catch (Exception) {
            DB::rollBack();
            return response()->json(['message' => translate('messages.failed_to_create_banner.')], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
       $validity= $this->validateRequest($request, false, $id);
        if($validity !== true){
            return response()->json(['error' => $validity['errors']], 403);
        }

        try {
            DB::beginTransaction();
            $this->updateBanner($request, $id);
            DB::commit();
            return response()->json(['message' => translate('messages.banner_updated_successfully.')], 200);

        } catch (Exception) {
            DB::rollBack();
            return response()->json(['message' => translate('messages.failed_to_update_banner.')], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $banner = $this->banner->find($id);

        if ($banner) {
            if ($banner->image) {
                $this->helpers->check_and_delete('banner/', $banner->image);
            }

            $banner->translations()->delete();
            $banner->delete();

            return response()->json(['message' => translate('messages.banner_deleted_successfully.')], 200);
        }

        return response()->json(['message' => translate('messages.failed_to_delete_banner.')], 400);
    }


    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function status(Request $request, $id): JsonResponse
    {
        $banner = $this->banner->find($id);

        if ($banner) {
            $banner->update(['status' => !$banner->status]);
            return response()->json(['message' => translate('messages.banner_status_updated.')], 200);
        }

        return response()->json(['message' => translate('messages.banner_not_found.')], 400);
    }


    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function featured(Request $request, $id): JsonResponse
    {
        $banner = $this->banner->find($id);

        if ($banner) {
            $banner->update(['featured' => !$banner->featured]);
            return response()->json(['message' => translate('messages.banner_featured_updated.')], 200);
        }

        return response()->json(['message' => translate('messages.banner_not_found.')], 400);
    }

    /**
     * @param Request $request
     * @param bool $image
     * @param null $id
     * @return void
     */
    private function validateRequest(Request $request, bool $image = true, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'image' => $image ? 'required' : 'nullable',
        ]);

        if ($validator->fails()) {
            return ['errors' => $this->helpers->error_processor($validator)];
        }

        return true;
    }



    public function edit($id): JsonResponse
    {
        $banner = $this->banner->withoutGlobalScope('translate')->with('translations')->find($id);

        if ($banner) {
            $banner->load('translations');
            return response()->json($banner, 200);
        }

        return response()->json(['message' => translate('messages.coupon_not_found.')], 400);
    }


    /**
     * @param Request $request
     * @return Banner
     */
    private function createBanner(Request $request): Banner
    {
        $storeId = $request->vendor->stores[0]->id ?? 0;
        $zoneId = $request->vendor->stores[0]->zone_id ?? 0;
        $moduleId = $request->vendor->stores[0]->module_id ?? null;
        $data = json_decode($request->translations, true);

        $banner = $this->banner;
        $banner->title = $data[0]['value'];
        $banner->zone_id = $zoneId;
        $banner->data = $storeId;
        $banner->image = $this->upload('banner/', 'png', $request->file('image'));
        $banner->module_id = $moduleId;
        $banner->type = 'store_wise';
        $banner->default_link = $request->default_link;
        $banner->created_by = 'store';
        $banner->save();

        foreach ($data as $key=>$item) {
            Translation::updateOrInsert(
                ['translationable_type' => 'App\Models\Banner',
                    'translationable_id' => $banner->id,
                    'locale' => $item['locale'],
                    'key' => $item['key']],
                ['value' => $item['value']]
            );
        }

        return $banner;
    }

    private function updateBanner(Request $request, $id): Banner
    {
        $storeId = $request->vendor->stores[0]->id ?? 0;
        $zoneId = $request->vendor->stores[0]->zone_id ?? 0;
        $moduleId = $request->vendor->stores[0]->module_id ?? null;

        $data = json_decode($request->translations, true);

        $banner = $this->banner->findOrFail($id);

        if ($request->hasFile('image')) {
            $banner->image = $this->updateAndUpload('banner/', $banner->image ,'png', $request->file('image'));
        }

        $banner->title = $data[0]['value'];
        $banner->type = 'store_wise';
        $banner->zone_id = $zoneId;
        $banner->data = $storeId;
        $banner->module_id = $moduleId;
        $banner->default_link = $request->default_link;
        $banner->save();

        foreach ($data as $key=>$item) {
            Translation::updateOrInsert(
                ['translationable_type' => 'App\Models\Banner',
                    'translationable_id' => $banner->id,
                    'locale' => $item['locale'],
                    'key' => $item['key']],
                ['value' => $item['value']]
            );
        }

        return $banner;
    }
}
