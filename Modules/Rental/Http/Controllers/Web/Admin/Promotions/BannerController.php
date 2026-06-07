<?php

namespace Modules\Rental\Http\Controllers\Web\Admin\Promotions;

use Exception;
use App\Models\Store;
use App\Models\Banner;
use Illuminate\Http\Request;
use App\Traits\FileManagerTrait;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use App\CentralLogics\Helpers;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Config;
use Modules\Rental\Exports\BannerExport;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;



class BannerController extends Controller
{

    private Banner $banner;


    use FileManagerTrait;

    public function __construct(Banner $banner)
    {
        $this->banner = $banner;
    }

    /**
     * @param Request $request
     * @return Application|View|Factory
     */
    public function list(Request $request): View|Factory|Application
    {
        $banners = $this->getListData($request);
        $banners =  $banners->paginate(config('default_pagination'));
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        return view('rental::admin.banner.list', compact('banners', 'language', 'defaultLang'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $this->validateRequest($request);
        try {
            DB::beginTransaction();
            $banner = $this->createBanner($request);
            Helpers::add_or_update_translations(request: $request, key_data: 'title', name_field: 'title', model_name: 'Banner', data_id: $banner->id, data_value: $banner->title);
            DB::commit();
        } catch (Exception $exception) {
            info([$exception->getFile(), $exception->getLine(), $exception->getMessage()]);
            DB::rollBack();
            Toastr::error(translate('messages.failed_to_add_banner'));
            return back();
        }
        Toastr::success(translate('messages.banner_added_successfully'));
        return back();
    }

    /**
     * @param Banner $banner
     * @return View|Factory|Application|RedirectResponse
     */
    public function edit(Banner $banner): View|Factory|Application|RedirectResponse
    {
        $banner->load('translations');
        $language = getWebConfig('language') ?? [];
        $defaultLang = str_replace('_', '-', app()->getLocale());
        return view('rental::admin.banner.edit', compact('banner', 'language', 'defaultLang'));
    }

    /**
     * Update the specified resource in storage.
     * @param Banner $banner
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Banner $banner, Request $request): RedirectResponse
    {
        $this->validateRequest($request, false, $banner->id);
        try {
            DB::beginTransaction();
            $this->updateBanner($request, $banner);
            Helpers::add_or_update_translations(request: $request, key_data: 'title', name_field: 'title', model_name: 'Banner', data_id: $banner->id, data_value: $banner->title);
            DB::commit();
            Toastr::success(translate('messages.banner_updated_successfully'));
            return to_route('admin.rental.banner.add-new');
        } catch (Exception  $exception) {
            info([$exception->getFile(), $exception->getLine(), $exception->getMessage()]);
            DB::rollBack();
            Toastr::error(translate('messages.failed_to_update_banner'));
            return back();
        }
    }

    /**
     * @param Banner $banner
     * @return RedirectResponse
     */
    public function status(Banner $banner): RedirectResponse
    {
        $banner->update(['status' => !$banner->status]);
        Toastr::success(translate('messages.banner_status_updated_successfully'));
        return back();
    }

    /**
     * @param Banner $banner
     * @return RedirectResponse
     */
    public function updateFeatured(Banner $banner): RedirectResponse
    {
        $banner->update(['featured' => !$banner->featured]);
        Toastr::success(translate('messages.banner_featured_updated_successfully'));
        return back();
    }

    /**
     * @param Banner $banner
     * @return RedirectResponse
     */
    public function destroy(Banner $banner): RedirectResponse
    {
        if ($banner->image) {
            Helpers::check_and_delete('banner/' , $banner->image);
        }
        $banner->translations()->delete();
        $banner->delete();

        Toastr::success(translate('messages.banner_deleted_successfully'));
        return back();
    }


    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $banners = $this->getListData($request);
        $banners =  $banners->get();

        $data = [
            'data' => $banners,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new BannerExport($data), 'Banners.csv');
        }
        return Excel::download(new BannerExport($data), 'Banners.xlsx');
    }

    /**
     * @param Request $request
     * @param bool $image
     * @param null $id
     * @return void
     */
    private function validateRequest(Request $request, $image = true, $id = null): void
    {
        $request->validate(
            [
                'title' => 'required|max:191',
                'image' => $image == true ? 'required' : 'nullable',
                'banner_type' => 'required',
                'store_id' => 'required_if:banner_type,store_wise',
                'title.0' => 'required',
            ],
            [
                'store_id.required_if' => translate('messages.Provider is required when banner type is Provider wise'),
                'title.0.required' => translate('default_data_is_required'),
            ]
        );
    }

    /**
     * @param Request $request
     * @return Banner
     */
    private function createBanner(Request $request): Banner
    {
        $banner = $this->banner;
        $banner->title = $request->title[array_search('default', $request->lang)];
        $banner->type = $request->banner_type;
        $banner->zone_id = $request->zone_id ?? $request->banner_type == 'store_wise' ? Store::whereKey($request->store_id)->first(['zone_id'])->zone_id : 1;
        $banner->image = $this->upload('banner/', 'png', $request->file('image'));
        $banner->data = ($request->banner_type == 'store_wise') ? $request->store_id : (($request->banner_type == 'item_wise') ? $request->item_id : '');
        $banner->module_id = Config::get('module.current_module_id');
        $banner->default_link = $request->default_link;
        $banner->created_by = 'admin';
        $banner->save();

        return $banner;
    }

    private function updateBanner(Request $request, Banner $banner): void
    {
        if ($request->hasFile('image')) {
            $banner->image = $this->updateAndUpload('banner/', $banner->image ,'png', $request->file('image'));
        }
        $banner->title = $request->title[array_search('default', $request->lang)];
        $banner->type = $request->banner_type;
        $banner->zone_id = $request->zone_id ?? $request->banner_type == 'store_wise' ? Store::whereKey($request->store_id)->first(['zone_id'])->zone_id : 1;
        $banner->data = ($request->banner_type == 'store_wise') ? $request->store_id : (($request->banner_type == 'item_wise') ? $request->item_id : '');
        $banner->module_id = Config::get('module.current_module_id');
        $banner->default_link = $request->default_link;
        $banner->save();
    }

    private function getListData($request)
    {
        $banners =  $this->banner
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('title', 'LIKE', '%' . $key . '%');
                }
            })->where('module_id', Config::get('module.current_module_id'))
            ->where('created_by', 'admin')
            ->latest();
        return $banners;
    }
}
