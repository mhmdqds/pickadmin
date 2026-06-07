<?php

namespace Modules\Rental\Http\Controllers\Web\Admin;

use Exception;
use App\Models\Translation;
use Illuminate\Http\Request;
use App\Traits\FileManagerTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Modules\Rental\Entities\VehicleCategory;
use Illuminate\Contracts\Foundation\Application;
use Modules\Rental\Exports\VehicleCategoryExport;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\CentralLogics\Helpers;

class CategoryController extends Controller
{
    private VehicleCategory $category;
    private Translation $translation;

    use FileManagerTrait;

    public function __construct(VehicleCategory $category, Translation $translation)
    {
        $this->category = $category;
        $this->translation = $translation;
    }

    public function list(Request $request)
    {
        $categories = $this->category
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->latest()->paginate(config('default_pagination'));
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());

        return view('rental::admin.category.list', compact('categories', 'language', 'defaultLang'));
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

            $category = $this->createCategory($request);

            Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: VehicleCategory::class, data_id: $category->id, data_value: $category->name,model_class:true);

            DB::commit();
        } catch (Exception) {
            DB::rollBack();

            Toastr::error(translate('messages.failed_to_add_category'));
            return back();
        }

        Toastr::success(translate('messages.category_added_successfully'));
        return back();
    }

    /**
     * @param string $id
     * @return View|Factory|Application|RedirectResponse
     */
    public function edit(string $id): View|Factory|Application|RedirectResponse
    {
        $category = $this->category->withoutGlobalScope('translate')->with('translations')->findOrFail($id);

            $language = getWebConfig('language') ?? [];
            $defaultLang = str_replace('_', '-', app()->getLocale());

            return view('rental::admin.category.edit', compact('category', 'language', 'defaultLang'));


        Toastr::error(translate('messages.information not found'));
        return back();
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $this->validateRequest($request, $id);

        $category = $this->category->find($id);
        if (!$category) {
            Toastr::error(translate('messages.information_not_found'));
            return back();
        }

        try {
            DB::beginTransaction();

            $this->updateCategory($request, $category);
            Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: VehicleCategory::class, data_id: $category->id, data_value: $category->name,model_class:true);

            DB::commit();

            Toastr::success(translate('messages.category_updated_successfully'));
            return back();
        } catch (Exception) {
            DB::rollBack();

            Toastr::error(translate('messages.failed_to_update_category'));
            return back();
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function status(Request $request, $id): RedirectResponse
    {
        $category = $this->category->find($id);

        if (!$category) {
            Toastr::error(translate('messages.category_not_found'));
            return back();
        }

        $category->update(['status' => !$category->status]);

        Toastr::success(translate('messages.category_status_updated_successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $category = $this->category->find($id);

        if (!$category) {
            Toastr::error(translate('messages.failed_to_delete_category'));
            return back();
        }

        if ($category->image) {
            $this->upload('category/', $category->image);
        }

        $category->translations()->delete();
        $category->delete();

        Toastr::success(translate('messages.category_deleted_successfully'));
        return back();
    }


    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $categories = $this->category
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->latest()->get();

        $data = [
            'data' => $categories,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new VehicleCategoryExport($data), 'Categories.csv');
        }
        return Excel::download(new VehicleCategoryExport($data), 'Categories.xlsx');
    }

    /**
     * @param Request $request
     * @param $id
     * @return void
     */
    private function validateRequest(Request $request, $id = null): void
    {
        $request->validate(
            [
                'name' => 'required|unique:categories,name' . ($id ? ','.$id : ''),
                'name.0' => 'required',
                'image' => 'nullable|image|mimes:webp,jpeg,jpg,png,gif|max:10240',
            ],
            [
                'name.0.required' => translate('default_name_is_required'),
            ]
        );
    }

    /**
     * @param Request $request
     * @return VehicleCategory
     */
    private function createCategory(Request $request): VehicleCategory
    {
        $category = $this->category;
        $category->name = $request->name[array_search('default', $request->lang)];
        $category->image = $this->upload('category/', 'png', $request->file('image'));
        $category->save();

        return $category;
    }

    private function updateCategory(Request $request, VehicleCategory $category): void
    {
        $category->name = $request->name[array_search('default', $request->lang)];

        if ($request->hasFile('image')) {
            $category->image = $this->upload('category/', 'png', $request->file('image'), $category->image);
        }

        $category->save();
    }


    public function getCategories(Request $request)
    {
        $key = explode(' ', $request['q']);
        $cat = $this->category->when(isset($request->module_id), function ($query) use ($request) {
            $query->where('module_id', $request->module_id);
        })

            ->when(isset($key), function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
            })
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'text' => $category->name,
                ];
            });

        return response()->json($cat);
    }
}
