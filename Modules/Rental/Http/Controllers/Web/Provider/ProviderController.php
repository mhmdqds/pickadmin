<?php

namespace Modules\Rental\Http\Controllers\Web\Provider;

use App\CentralLogics\Helpers;
use App\Models\EmployeeRole;
use App\Models\Translation;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Rental\Entities\VehicleBrand;
use Modules\Rental\Entities\VehicleCategory;
use Modules\Rental\Entities\VehicleReview;
use Modules\Rental\Exports\VehicleBrandExport;
use Modules\Rental\Exports\VehicleCategoryExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProviderController extends Controller
{
    private VehicleCategory $category;
    private VehicleBrand $brand;
    private VehicleReview $vehicleReview;
    private EmployeeRole $employeeRole;
    private Helpers $helpers;

    public function __construct(VehicleCategory $category, VehicleBrand $brand, VehicleReview $vehicleReview, EmployeeRole $employeeRole, Helpers $helpers)
    {
        $this->category = $category;
        $this->brand = $brand;
        $this->vehicleReview = $vehicleReview;
        $this->employeeRole = $employeeRole;
        $this->helpers = $helpers;
    }

    /**
     * @param Request $request
     * @return View|Application|Factory
     */
    public function categoryList(Request $request): View|Application|Factory
    {
        $categories = $this->category
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                $query->where(function ($subQuery) use ($keys) {
                    foreach ($keys as $key) {
                        $subQuery->where('name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->ofStatus(1)
            ->latest()
            ->paginate(config('default_pagination'));

        return view('rental::provider.category.list', compact('categories'));
    }

    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function categoryExport(Request $request): BinaryFileResponse
    {
        $categories = $this->category
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                $query->where(function ($subQuery) use ($keys) {
                    foreach ($keys as $key) {
                        $subQuery->where('name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->ofStatus(1)
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
     * @return View|Application|Factory
     */
    public function brandList(Request $request): View|Application|Factory
    {
        $brands = $this->brand
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                $query->where(function ($subQuery) use ($keys) {
                    foreach ($keys as $key) {
                        $subQuery->where('name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->ofStatus(1)
            ->latest()
            ->paginate(config('default_pagination'));

        return view('rental::provider.brand.list', compact('brands'));
    }

    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function brandExport(Request $request): BinaryFileResponse
    {
        $brands = $this->brand
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                $query->where(function ($subQuery) use ($keys) {
                    foreach ($keys as $key) {
                        $subQuery->where('name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->ofStatus(1)
            ->latest()->get();

        $data = [
            'data' => $brands,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new VehicleBrandExport($data), 'Brands.csv');
        }
        return Excel::download(new VehicleBrandExport($data), 'Brands.xlsx');
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    public function reviews(Request $request): Renderable
    {
        $providerId = $this->helpers->get_store_id();
        $reviews = $this->vehicleReview
            ->where('provider_id', $providerId)
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->where(function ($query) use ($key) {
                        $query->orWhere('comment', 'LIKE', '%' . $key . '%')
                            ->orWhere('reply', 'LIKE', '%' . $key . '%')
                            ->orWhereHas('customer', function ($customerQuery) use ($key) {
                                $customerQuery->where('f_name', 'LIKE', '%' . $key . '%')
                                    ->orWhere('l_name', 'LIKE', '%' . $key . '%')
                                    ->orWhere('phone', 'LIKE', '%' . $key . '%');
                            })
                            ->orWhereHas('vehicle', function ($vehicleQuery) use ($key) {
                                $vehicleQuery->where('name', 'LIKE', '%' . $key . '%');
                            });
                    });
                }
            })
            ->latest()->paginate(config('default_pagination'));
        return view('rental::provider.review.list', compact('reviews'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function reviewReply(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'reply' => 'required|max:65000',
        ]);

        $review = $this->vehicleReview->findOrFail($id);
        $review->reply = $request->reply;
        $review->replied_at = now();
        $review->save();

        Toastr::success(translate('messages.review_reply_updated'));
        return back();
    }

    /**
     * @param Request $request
     * @return Factory|View|Application
     */
    public function role(Request $request):Factory|View|Application
    {
        $key = explode(' ', $request['search']);
        $roles = $this->employeeRole->where('store_id',Helpers::get_store_id())->orderBy('name')
            ->when( isset($key) , function($query) use($key){
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            }
            )
            ->paginate(config('default_pagination'));

        return view('rental::provider.employee.list', compact('roles'));
    }

    /**
     * @param $id
     * @return View|Application|Factory
     */
    public function update($id): View|Application|Factory
    {
        $role = $this->employeeRole
            ->withoutGlobalScope('translate')
            ->where('store_id',Helpers::get_store_id())
            ->where(['id'=>$id])->first(['id','name','modules']);

        return view('rental::provider.employee.edit',compact('role'));
    }
}
