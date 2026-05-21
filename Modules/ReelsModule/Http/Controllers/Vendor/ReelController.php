<?php

namespace Modules\ReelsModule\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Exceptions\InvalidUploadException;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Modules\ReelsModule\Entities\Reel;
use Modules\ReelsModule\Entities\ReelEngagement;
use Modules\ReelsModule\Http\Requests\Vendor\ReelStoreRequest;
use Modules\ReelsModule\Http\Requests\Vendor\ReelUpdateRequest;

class ReelController extends Controller
{
    private array $allowedModuleTypes = ['grocery', 'food', 'ecommerce', 'pharmacy','rental'];

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardAccessibleStore()) {
            return $redirect;
        }

        $store = Helpers::get_store_data();
        $query = $this->getFilteredQuery($request, $store->id);

        $reels = $query->latest('id')
            ->paginate(config('default_pagination'))
            ->appends($request->query());

        $overview = $this->getFilteredOverview($request, $store->id);

        return view('reelsmodule::vendor.reels.index', compact('reels', 'overview', 'store'));
    }

    public function create(): View|RedirectResponse
    {
        if ($redirect = $this->guardAccessibleStore()) {
            return $redirect;
        }

        $language = getWebConfig('language') ?? [];
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $reel = new Reel([
            'is_always_visible' => false,
            'status' => true,
        ]);
        $store = Helpers::get_store_data();

        return view('reelsmodule::vendor.reels.create', compact('language', 'defaultLang', 'reel', 'store'));
    }

    public function store(ReelStoreRequest $request)
    {
        if ($redirect = $this->guardAccessibleStore()) {
            return response()->json(['message' => translate('messages.this_feature_is_not_available_for_the_selected_module')], 403);
        }
        if(getEnvMode() === 'demo') {
            return response()->json(['message' => translate('Uploads are disabled in demo mode')], 403);
        }
        $reel = new Reel();

        try {
            $this->fillAndPersistReel($reel, $request);
        } catch (InvalidUploadException $exception) {
            return response()->json(['errors' => [['message' => $exception->getMessage()]]], 422);
        }

        Toastr::success(translate('messages.reel_created_successfully'));

        return response()->json([
            'message' => translate('messages.reel_created_successfully'),
            'redirect' => route('vendor.reels.index'),
        ]);
    }

    public function edit(int $id): View|RedirectResponse
    {
        if ($redirect = $this->guardAccessibleStore()) {
            return $redirect;
        }

        $reel = $this->findAccessibleReel($id);
        if (!$reel) {
            Toastr::error(translate('messages.reel_not_found'));

            return redirect()->back();
        }

        $language = getWebConfig('language') ?? [];
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $store = Helpers::get_store_data();

        return view('reelsmodule::vendor.reels.edit', compact('language', 'defaultLang', 'reel', 'store'));
    }

    public function update(ReelUpdateRequest $request, int $id)
    {
        if ($redirect = $this->guardAccessibleStore()) {
            return response()->json(['message' => translate('messages.this_feature_is_not_available_for_the_selected_module')], 403);
        }
        
        if(getEnvMode() === 'demo') {
            return response()->json(['message' => translate('Uploads are disabled in demo mode')], 403);
        }

        $reel = $this->findAccessibleReel($id);
        if (!$reel) {
            return response()->json(['errors' => [['message' => translate('messages.reel_not_found')]]], 404);
        }

        try {
            $this->fillAndPersistReel($reel, $request);
        } catch (InvalidUploadException $exception) {
            return response()->json(['errors' => [['message' => $exception->getMessage()]]], 422);
        }

        Toastr::success(translate('messages.reel_updated_successfully'));

        return response()->json([
            'message' => translate('messages.reel_updated_successfully'),
            'redirect' => route('vendor.reels.index'),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        if ($redirect = $this->guardAccessibleStore()) {
            return $redirect;
        }

        $reel = $this->findAccessibleReel($id);
        if (!$reel) {
            Toastr::error(translate('messages.reel_not_found'));

            return redirect()->back();
        }

        Helpers::check_and_delete(dir: 'reels/', old_image: $reel->thumbnail);
        Helpers::check_and_delete(dir: 'reels/', old_image: $reel->video);
        $reel->translations()->delete();
        $reel->storage()->delete();
        $reel->delete();

        Toastr::success(translate('messages.reel_deleted_successfully'));

        return redirect()->route('vendor.reels.index');
    }

    public function status(int $id, int $status): RedirectResponse
    {
        if ($redirect = $this->guardAccessibleStore()) {
            return $redirect;
        }

        $reel = $this->findAccessibleReel($id);
        if (!$reel) {
            Toastr::error(translate('messages.reel_not_found'));

            return redirect()->back();
        }

        $reel->status = $status;
        $reel->save();

        Toastr::success(translate('messages.reel_status_updated_successfully'));

        return back();
    }

    private function guardAccessibleStore(): ?RedirectResponse
    {
        if (!addon_published_status('ReelsModule')) {
            abort(404);
        }

        if (!Helpers::get_business_settings('vendor_can_upload_reels')) {
            Toastr::error(translate('messages.this_feature_is_not_available_for_the_selected_module'));
            return redirect()->back();
        }

        $store = Helpers::get_store_data();
        $moduleType = $store?->module?->module_type;

        if (!$store || !in_array($moduleType, $this->allowedModuleTypes, true)) {
            Toastr::error(translate('messages.this_feature_is_not_available_for_the_selected_module'));
            return redirect()->back();
        }

        return null;
    }

    private function getFilteredQuery(Request $request, int $storeId)
    {
        $keywords = array_filter(explode(' ', (string) $request->get('search', '')));
        $reelStatuses = array_values(array_filter((array) $request->input('reel_status', [])));
        $today = Carbon::today()->toDateString();

        $query = Reel::with(['store', 'storage'])
            ->withCount([
                'engagements as total_views' => fn (Builder $builder) => $builder->where('type', ReelEngagement::TYPE_VIEW),
                'engagements as total_likes' => fn (Builder $builder) => $builder->where('type', ReelEngagement::TYPE_LIKE),
                'engagements as total_store_visits' => fn (Builder $builder) => $builder->where('type', ReelEngagement::TYPE_VISIT),
            ])
            ->where('store_id', $storeId)
            ->when(!empty($keywords), function ($builder) use ($keywords) {
                foreach ($keywords as $value) {
                    $builder->where(function ($subQuery) use ($value) {
                        $subQuery->where('id', 'like', "%{$value}%")
                            ->orWhere('description', 'like', "%{$value}%")
                            ->orWhereHas('translations', function ($translationQuery) use ($value) {
                                $translationQuery->where('key', 'description')
                                    ->where('value', 'like', "%{$value}%");
                            });
                    });
                }
            })
            ->when($request->filled('filter') && $request->input('filter') !== 'all_time', function ($builder) use ($request) {
                $filter = $request->input('filter');
                if ($filter === 'this_week') {
                    $builder->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                } elseif ($filter === 'this_month') {
                    $builder->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                } elseif ($filter === 'this_year') {
                    $builder->whereYear('created_at', Carbon::now()->year);
                } elseif ($filter === 'previous_year') {
                    $builder->whereYear('created_at', Carbon::now()->year - 1);
                } elseif ($filter === 'custom') {
                    if ($request->filled('from')) {
                        $builder->whereDate('created_at', '>=', $request->from);
                    }
                    if ($request->filled('to')) {
                        $builder->whereDate('created_at', '<=', $request->to);
                    }
                }
            });

        $this->applyReelStatusFilter($query, $reelStatuses, $today);

        return $query;
    }

    private function applyReelStatusFilter($query, array $statuses, string $today): void
    {
        $statuses = array_values(array_diff($statuses, ['all']));
        if (empty($statuses)) {
            return;
        }

        $query->where(function ($builder) use ($statuses, $today) {
            foreach ($statuses as $status) {
                if ($status === 'deactivated') {
                    $builder->orWhere('status', 0);
                }

                if ($status === 'live') {
                    $builder->orWhere(function ($subQuery) use ($today) {
                        $subQuery->where('status', 1)
                            ->where(function ($liveQuery) use ($today) {
                                $liveQuery->where('is_always_visible', 1)
                                    ->orWhere(function ($dateQuery) use ($today) {
                                        $dateQuery->where('is_always_visible', 0)
                                            ->whereDate('start_date', '<=', $today)
                                            ->whereDate('end_date', '>=', $today);
                                    });
                            });
                    });
                }

                if ($status === 'upcoming') {
                    $builder->orWhere(function ($subQuery) use ($today) {
                        $subQuery->where('status', 1)
                            ->where('is_always_visible', 0)
                            ->whereDate('start_date', '>', $today);
                    });
                }

                if ($status === 'expired') {
                    $builder->orWhere(function ($subQuery) use ($today) {
                        $subQuery->where('status', 1)
                            ->where('is_always_visible', 0)
                            ->whereDate('end_date', '<', $today);
                    });
                }
            }
        });
    }

    private function getFilteredOverview(Request $request, int $storeId): array
    {
        $baseQuery = Reel::query()->where('store_id', $storeId);

        if ($request->filled('filter') && $request->input('filter') !== 'all_time') {
            $filter = $request->input('filter');
            if ($filter === 'this_week') {
                $baseQuery->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($filter === 'this_month') {
                $baseQuery->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            } elseif ($filter === 'this_year') {
                $baseQuery->whereYear('created_at', Carbon::now()->year);
            } elseif ($filter === 'previous_year') {
                $baseQuery->whereYear('created_at', Carbon::now()->year - 1);
            } elseif ($filter === 'custom') {
                if ($request->filled('from')) {
                    $baseQuery->whereDate('created_at', '>=', $request->from);
                }
                if ($request->filled('to')) {
                    $baseQuery->whereDate('created_at', '<=', $request->to);
                }
            }
        }

        $reelIds = $baseQuery->pluck('id');

        return [
            'total_reels' => $reelIds->count(),
            'total_views' => ReelEngagement::query()
                ->where('type', ReelEngagement::TYPE_VIEW)
                ->whereIn('reel_id', $reelIds)
                ->count(),
            'total_likes' => ReelEngagement::query()
                ->where('type', ReelEngagement::TYPE_LIKE)
                ->whereIn('reel_id', $reelIds)
                ->count(),
            'total_store_visits' => ReelEngagement::query()
                ->where('type', ReelEngagement::TYPE_VISIT)
                ->whereIn('reel_id', $reelIds)
                ->count(),
        ];
    }

    private function fillAndPersistReel(Reel $reel, Request $request): void
    {
        [$startDate, $endDate] = $this->parseDateRange($request);
        $store = Helpers::get_store_data();

        $reel->store_id = $store->id;
        $reel->module_id = (int) $store->module_id;
        $reel->module_type = (string) $store->module_type;
        $reel->description = $request->description[array_search('default', $request->lang)] ?? $request->description[0];
        $reel->is_always_visible = $request->boolean('is_always_visible');
        $reel->start_date = $reel->is_always_visible ? null : $startDate;
        $reel->end_date = $reel->is_always_visible ? null : $endDate;

        if (!$reel->exists) {
            if (auth('vendor_employee')->check()) {
                $reel->created_by_id = auth('vendor_employee')->id();
                $reel->created_by_type = 'App\\Models\\VendorEmployee';
            } else {
                $reel->created_by_id = auth('vendor')->id();
                $reel->created_by_type = 'App\\Models\\Vendor';
            }
        }

        if ($request->hasFile('thumbnail')) {
            if ($reel->thumbnail) {
                Helpers::check_and_delete(dir: 'reels/', old_image: $reel->thumbnail);
            }

            $reel->thumbnail = $this->uploadReelAsset(
                file: $request->file('thumbnail'),
                dir: 'reels/',
                type: 'thumbnail'
            );
        }

        if ($request->hasFile('video')) {
            if ($reel->video) {
                Helpers::check_and_delete(dir: 'reels/', old_image: $reel->video);
            }

            $reel->video = $this->uploadReelAsset(
                file: $request->file('video'),
                dir: 'reels/',
                type: 'video'
            );
        }

        $reel->save();

        Helpers::add_or_update_translations(
            request: $request,
            key_data: 'description',
            name_field: 'description',
            model_name: Reel::class,
            data_id: $reel->id,
            data_value: $reel->description,
            model_class: true
        );
    }

    private function parseDateRange(Request $request): array
    {
        if ($request->boolean('is_always_visible') || !$request->filled('dates')) {
            return [null, null];
        }

        [$startDate, $endDate] = array_map('trim', explode(' - ', $request->dates));

        return [
            Carbon::createFromFormat('m/d/Y', $startDate)->startOfDay(),
            Carbon::createFromFormat('m/d/Y', $endDate)->endOfDay(),
        ];
    }

    private function uploadReelAsset(UploadedFile $file, string $dir, string $type): string
    {
        $this->validateReelFile($file, $type);

        $format = strtolower($file->getClientOriginalExtension() ?: Helpers::extensionFromMimeType($file->getMimeType()));
        $validExtForWebp = ['jpg', 'jpeg', 'png'];

        if ($type === 'thumbnail' && in_array($format, $validExtForWebp, true)) {
            $manager = new ImageManager(Driver::class);
            $image = $manager->read($file);
            $image = $image->encode(new WebpEncoder(quality: 80));
            $format = 'webp';
            $fileToStore = $image->toString();
        } else {
            $fileToStore = $file;
        }

        $fileName = now()->toDateString() . '-' . uniqid() . '.' . $format;
        $disk = Helpers::getDisk();

        if (!Storage::disk($disk)->exists($dir)) {
            Storage::disk($disk)->makeDirectory($dir);
        }

        if ($fileToStore instanceof UploadedFile) {
            Storage::disk($disk)->putFileAs($dir, $fileToStore, $fileName);
        } else {
            Storage::disk($disk)->put($dir . '/' . $fileName, $fileToStore);
        }

        return $fileName;
    }

    private function validateReelFile(UploadedFile $file, string $type): void
    {
        $allowedExtensions = match ($type) {
            'thumbnail' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'mov', '3gp', 'gif', 'webm', 'mkv'],
            default => [],
        };

        $maxSizeMb = match ($type) {
            'thumbnail' => 2,
            'video' => max(1, (int) (Helpers::get_business_settings('reels_max_upload_size_mb') ?? 15)),
            default => 0,
        };

        $extension = strtolower($file->getClientOriginalExtension() ?: Helpers::extensionFromMimeType($file->getMimeType()));

        if (!$extension || !in_array($extension, $allowedExtensions, true)) {
            throw new InvalidUploadException(
                $type === 'video'
                    ? translate('messages.reel_video_format_is_invalid')
                    : translate('messages.reel_thumbnail_format_is_invalid')
            );
        }

        if ($file->getSize() > ($maxSizeMb * 1024 * 1024)) {
            throw new InvalidUploadException(
                $type === 'video'
                    ? str_replace(':size', (string) $maxSizeMb, translate('messages.reel_video_size_must_not_exceed_mb'))
                    : str_replace(':size', (string) MAX_FILE_SIZE, translate('messages.reel_thumbnail_size_must_not_exceed_2_mb'))
            );
        }
    }

    private function findAccessibleReel(int $id): ?Reel
    {
        return Reel::withoutGlobalScope('translate')
            ->with(['store', 'storage', 'translations'])
            ->where('store_id', Helpers::get_store_id())
            ->find($id);
    }

    private function getStoreEngagementCount(int $storeId, string $type): int
    {
        return ReelEngagement::query()
            ->where('type', $type)
            ->whereHas('reel', fn (Builder $builder) => $builder->where('store_id', $storeId))
            ->count();
    }
}
