<?php

namespace Modules\Rental\Http\Controllers\Web\Provider\Promotions;

use Exception;
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
use Modules\Rental\Exports\BannerExport;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BannerController extends Controller
{
    private Banner $banner;
    private Helpers $helpers;


    use FileManagerTrait;

    public function __construct(Banner $banner, Helpers $helpers)
    {
        $this->banner = $banner;
        $this->helpers = $helpers;
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
        return view('rental::provider.banner.list', compact('banners', 'language', 'defaultLang'));
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
        } catch (Exception) {
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
        return view('rental::provider.banner.edit', compact('banner', 'language', 'defaultLang'));
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
            $this->updateBanner($request, $banner);
            Helpers::add_or_update_translations(request: $request, key_data: 'title', name_field: 'title', model_name: 'Banner', data_id: $banner->id, data_value: $banner->title);
            Toastr::success(translate('messages.banner_updated_successfully'));
            return to_route('vendor.rental_banner.list');

        } catch (Exception) {
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
            ]
        );
    }

    /**
     * @param Request $request
     * @return Banner
     */
    private function createBanner(Request $request): Banner
    {
        $store = $this->helpers->get_store_data();
        $banner = $this->banner;
        $banner->title = $request->title[array_search('default', $request->lang)];
        $banner->zone_id = $store->zone_id ?? 0;
        $banner->data = $store->id;
        $banner->image = $this->upload('banner/', 'png', $request->file('image'));
        $banner->module_id = $store->module_id ?? null;
        $banner->type = 'store_wise';
        $banner->default_link = $request->default_link;
        $banner->created_by = 'store';
        $banner->save();

        return $banner;
    }

    private function updateBanner(Request $request, Banner $banner): void
    {
        if ($request->hasFile('image')) {
            $banner->image = $this->updateAndUpload('banner/', $banner->image ,'png', $request->file('image'));
        }
        $banner->title = $request->title[array_search('default', $request->lang)];
        $banner->default_link = $request->default_link;
        $banner->save();
    }

    private function getListData($request)
    {
        return $this->banner
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                $query->where(function ($subQuery) use ($keys) {
                    foreach ($keys as $key) {
                        $subQuery->where('title', 'LIKE', '%' . $key . '%');
                    }
                });
            })->where('data', $this->helpers->get_store_id())
            ->where('created_by', 'store')
            ->latest();
    }
}
