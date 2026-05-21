<?php

namespace Modules\ReelsModule\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Exceptions\InvalidUploadException;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
use Modules\ReelsModule\Http\Requests\Admin\ReelStoreRequest;
use Modules\ReelsModule\Http\Requests\Admin\ReelUpdateRequest;
use Modules\ReelsModule\Support\ReelModuleConfig;

class ReelController extends Controller
{

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardAccessibleModule()) {
            return $redirect;
        }

        $stores = $this->getStores();
        $filteredQuery = $this->getFilteredQuery($request);
        $analyticsQuery = clone $filteredQuery;

        $reels = $this->applySorting(clone $filteredQuery, $request)
            ->paginate(config('default_pagination'))
            ->appends($request->query());

        $analytics = $this->buildAnalytics($analyticsQuery, $request);
        $filterCount = $this->getFilterCount($request);
        $stores = $this->getStores();
        $overview = [
            'total_reels' => Reel::moduleWise()->count(),
            'total_views' => $this->getModuleEngagementCount(ReelEngagement::TYPE_VIEW),
            'total_likes' => $this->getModuleEngagementCount(ReelEngagement::TYPE_LIKE),
            'total_store_visits' => $this->getModuleEngagementCount(ReelEngagement::TYPE_VISIT),
        ];

        return view('reelsmodule::admin.reels.index', compact('reels', 'stores', 'overview', 'analytics', 'filterCount'));
    }

    public function create(): View|RedirectResponse
    {
        if ($redirect = $this->guardAccessibleModule()) {
            return $redirect;
        }

        $language = getWebConfig('language') ?? [];
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $stores = $this->getStores();
        $reel = new Reel([
            'is_always_visible' => false,
            'status' => true,
        ]);

        return view('reelsmodule::admin.reels.create', compact('language', 'defaultLang', 'stores', 'reel'));
    }

    public function store(ReelStoreRequest $request)
    {
        if ($redirect = $this->guardAccessibleModule()) {
            return response()->json(['message' => translate('messages.this_feature_is_not_available_for_the_selected_module')], 403);
        }

        if(getEnvMode() === 'demo') {
            return response()->json(['message' => translate('Uploads are disabled in demo mode')], 403);
        }

        $store = $this->resolveStore($request->store_id);
        if (!$store) {
            return response()->json(['errors' => [['message' => 'Please select a valid ' . Helpers::getStoreLabelByModuleType(config('module.current_module_type'), true)]]], 422);
        }

        $reel = new Reel();

        try {
            $this->fillAndPersistReel($reel, $request, $store);
        } catch (InvalidUploadException $exception) {
            return response()->json(['errors' => [['message' => $exception->getMessage()]]], 422);
        }

        Toastr::success(translate('messages.reel_created_successfully'));

        return response()->json([
            'message' => translate('messages.reel_created_successfully'),
            'redirect' => route('admin.reels.index'),
        ]);
    }

    public function edit(int $id): View|RedirectResponse
    {
        if ($redirect = $this->guardAccessibleModule()) {
            return $redirect;
        }

        $reel = $this->findAccessibleReel($id);
        if (!$reel) {
            Toastr::error(translate('messages.reel_not_found'));

            return redirect()->back();
        }

        $language = getWebConfig('language') ?? [];
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $stores = $this->getStores();

        return view('reelsmodule::admin.reels.edit', compact('language', 'defaultLang', 'stores', 'reel'));
    }

    public function update(ReelUpdateRequest $request, int $id)
    {
        if ($redirect = $this->guardAccessibleModule()) {
            return response()->json(['message' => translate('messages.this_feature_is_not_available_for_the_selected_module')], 403);
        }
        if(getEnvMode() === 'demo') {
            return response()->json(['message' => translate('Uploads are disabled in demo mode')], 403);
        }
        
        $reel = $this->findAccessibleReel($id);
        if (!$reel) {
            return response()->json(['errors' => [['message' => translate('messages.reel_not_found')]]], 404);
        }

        $store = $this->resolveStore($request->store_id);
        if (!$store) {
            return response()->json(['errors' => [['message' => 'Please select a valid ' . Helpers::getStoreLabelByModuleType(config('module.current_module_type'), true)]]], 422);
        }

        try {
            $this->fillAndPersistReel($reel, $request, $store);
        } catch (InvalidUploadException $exception) {
            return response()->json(['errors' => [['message' => $exception->getMessage()]]], 422);
        }

        Toastr::success(translate('messages.reel_updated_successfully'));

        return response()->json([
            'message' => translate('messages.reel_updated_successfully'),
            'redirect' => route('admin.reels.index'),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        if ($redirect = $this->guardAccessibleModule()) {
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

        return redirect()->back();
    }

    public function status(int $id, int $status): RedirectResponse
    {
        if ($redirect = $this->guardAccessibleModule()) {
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

    private function guardAccessibleModule(): ?RedirectResponse
    {
        if (!addon_published_status('ReelsModule')) {
            abort(404);
        }

        if (!ReelModuleConfig::isMultiModule()) {
            return null;
        }

        $currentModuleType = config('module.current_module_type');
        $currentModuleId = config('module.current_module_id');

        if (!ReelModuleConfig::isAllowedType($currentModuleType) || !is_numeric($currentModuleId)) {
            Toastr::error(translate('messages.this_feature_is_not_available_for_the_selected_module'));

            return redirect()->back();
        }

        return null;
    }

    private function getStores()
    {
        return Store::withoutGlobalScopes()
            ->where('status', 1)
            ->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }])
            ->when(ReelModuleConfig::isMultiModule(), fn ($query) => $query->where('module_id', config('module.current_module_id')))
            ->latest()
            ->get(['id', 'name', 'logo', 'module_id']);
    }

    private function resolveStore(int|string|null $storeId): ?Store
    {
        return Store::withoutGlobalScopes()
            ->where('id', $storeId)
            ->when(ReelModuleConfig::isMultiModule(), fn ($query) => $query->where('module_id', config('module.current_module_id')))
            ->first();
    }

    private function findAccessibleReel(int $id): ?Reel
    {
        return Reel::withoutGlobalScope('translate')
            ->with(['store', 'storage', 'translations'])
            ->moduleWise()
            ->find($id);
    }

    private function getFilteredQuery(Request $request)
    {
        $keywords = array_filter(explode(' ', (string) $request->get('search', '')));
        $storeIds = array_filter(array_map('intval', (array) $request->input('store_ids', [])));
        if (!$storeIds && $request->filled('store_id')) {
            $storeIds = [(int) $request->input('store_id')];
        }

        $reelStatuses = array_values(array_filter((array) $request->input('reel_status', [])));
        $today = Carbon::today()->toDateString();

        $query = Reel::with(['store', 'storage'])
            ->withCount([
                'engagements as total_views' => fn (Builder $builder) => $builder->where('type', ReelEngagement::TYPE_VIEW),
                'engagements as total_likes' => fn (Builder $builder) => $builder->where('type', ReelEngagement::TYPE_LIKE),
                'engagements as total_store_visits' => fn (Builder $builder) => $builder->where('type', ReelEngagement::TYPE_VISIT),
            ])
            ->moduleWise()
            ->when(!empty($storeIds), function ($builder) use ($storeIds) {
                $builder->whereIn('store_id', $storeIds);
            })
            ->when($request->filled('status_filter'), function ($builder) use ($request) {
                $builder->where('status', $request->status_filter === 'active' ? 1 : 0);
            })
            ->when(!empty($keywords), function ($builder) use ($keywords) {
                foreach ($keywords as $value) {
                    $builder->where(function ($subQuery) use ($value) {
                        $subQuery->where('id', 'like', "%{$value}%")
                            ->orWhere('description', 'like', "%{$value}%")
                            ->orWhereHas('store', function ($storeQuery) use ($value) {
                                $storeQuery->where('name', 'like', "%{$value}%");
                            })
                            ->orWhereHas('translations', function ($translationQuery) use ($value) {
                                $translationQuery->where('key', 'description')
                                    ->where('value', 'like', "%{$value}%");
                            });
                    });
                }
            });

        $this->applyReelStatusFilter($query, $reelStatuses, $today);
        $this->applyUploadDateFilter($query, $request);

        return $query;
    }

    private function applySorting($query, Request $request)
    {
        return match ($request->input('sort_by')) {
            'most_viewed' => $query->orderByDesc('total_views')->latest('id'),
            'most_liked' => $query->orderByDesc('total_likes')->latest('id'),
            'most_store_visit' => $query->orderByDesc('total_store_visits')->latest('id'),
            default => $query->latest(),
        };
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

    private function applyUploadDateFilter($query, Request $request): void
    {
        $filterDate = $request->input('filter_date', 'all_time');

        match ($filterDate) {
            'this_week' => $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
            'this_month' => $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]),
            'custom' => $this->applyCustomDateFilter($query, $request),
            default => null,
        };
    }

    private function applyCustomDateFilter($query, Request $request): void
    {
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
    }

    private function buildAnalytics($query, Request $request): array
    {
        $records = (clone $query)->get(['id']);
        $reelIds = $records->pluck('id')->filter()->values();

        if ($reelIds->isEmpty()) {
            $monthlyRange = CarbonPeriod::create(Carbon::now()->subMonths(11)->startOfMonth(), '1 month', Carbon::now()->startOfMonth());

            return [
                'view_trend_categories' => collect($monthlyRange)->map(fn ($month) => $month->format('M'))->values()->all(),
                'view_trend_values' => array_fill(0, 12, 0),
                'engagement_series' => [0, 0, 0],
                'weekly_change' => [
                    'views' => '0%',
                    'likes' => '0%',
                    'visits' => '0%',
                ],
            ];
        }

        $engagements = ReelEngagement::query()
            ->select(['type', 'created_at'])
            ->whereIn('reel_id', $reelIds)
            ->get();

        $currentStart = Carbon::now()->startOfWeek();
        $currentEnd = Carbon::now()->endOfWeek();
        $previousStart = Carbon::now()->subWeek()->startOfWeek();
        $previousEnd = Carbon::now()->subWeek()->endOfWeek();

        $currentPeriod = $engagements->filter(fn ($engagement) => Carbon::parse($engagement->created_at)->between($currentStart, $currentEnd));
        $previousPeriod = $engagements->filter(fn ($engagement) => Carbon::parse($engagement->created_at)->between($previousStart, $previousEnd));

        $monthlyRange = CarbonPeriod::create(Carbon::now()->subMonths(11)->startOfMonth(), '1 month', Carbon::now()->startOfMonth());
        $chartCategories = [];
        $chartValues = [];

        foreach ($monthlyRange as $month) {
            $chartCategories[] = $month->format('M');
            $chartValues[] = $engagements
                ->filter(fn ($engagement) => $engagement->type === ReelEngagement::TYPE_VIEW)
                ->filter(fn ($engagement) => Carbon::parse($engagement->created_at)->format('Y-m') === $month->format('Y-m'))
                ->count();
        }

        $totalViews = $engagements->where('type', ReelEngagement::TYPE_VIEW)->count();
        $totalLikes = $engagements->where('type', ReelEngagement::TYPE_LIKE)->count();
        $totalVisits = $engagements->where('type', ReelEngagement::TYPE_VISIT)->count();

        return [
            'view_trend_categories' => $chartCategories,
            'view_trend_values' => $chartValues,
            'engagement_series' => [
                $totalViews,
                $totalLikes,
                $totalVisits,
            ],
            'weekly_change' => [
                'views' => $this->calculatePercentChange(
                    (float) $previousPeriod->where('type', ReelEngagement::TYPE_VIEW)->count(),
                    (float) $currentPeriod->where('type', ReelEngagement::TYPE_VIEW)->count()
                ),
                'likes' => $this->calculatePercentChange(
                    (float) $previousPeriod->where('type', ReelEngagement::TYPE_LIKE)->count(),
                    (float) $currentPeriod->where('type', ReelEngagement::TYPE_LIKE)->count()
                ),
                'visits' => $this->calculatePercentChange(
                    (float) $previousPeriod->where('type', ReelEngagement::TYPE_VISIT)->count(),
                    (float) $currentPeriod->where('type', ReelEngagement::TYPE_VISIT)->count()
                ),
            ],
        ];
    }

    private function getModuleEngagementCount(string $type): int
    {
        return ReelEngagement::query()
            ->where('type', $type)
            ->whereHas('reel', function (Builder $builder) {
                $builder->moduleWise();
            })
            ->count();
    }

    private function calculatePercentChange(float $previous, float $current): string
    {
        if ($previous <= 0 && $current <= 0) {
            return '0%';
        }

        if ($previous <= 0) {
            return '+100%';
        }

        $change = (($current - $previous) / $previous) * 100;
        $prefix = $change > 0 ? '+' : '';

        return $prefix . number_format($change, 1) . '%';
    }

    private function getFilterCount(Request $request): int
    {
        $count = 0;

        if ($request->filled('status_filter')) {
            $count++;
        }

        if (!empty(array_filter((array) $request->input('store_ids', [])))) {
            $count++;
        }

        if (!empty(array_diff(array_filter((array) $request->input('reel_status', [])), ['all']))) {
            $count++;
        }

        if ($request->filled('sort_by') && $request->input('sort_by') !== 'all') {
            $count++;
        }

        if ($request->filled('filter_date') && $request->input('filter_date') !== 'all_time') {
            $count++;
        }

        if ($request->filled('search')) {
            $count++;
        }

        return $count;
    }

    private function fillAndPersistReel(Reel $reel, Request $request, Store $store): void
    {
        [$startDate, $endDate] = $this->parseDateRange($request);

        $reel->store_id = $store->id;
        $reel->module_id = ReelModuleConfig::isMultiModule()
            ? (int) config('module.current_module_id')
            : ReelModuleConfig::defaultModuleId();
        $reel->module_type = ReelModuleConfig::isMultiModule()
            ? (string) config('module.current_module_type')
            : ReelModuleConfig::defaultModuleType();
        $reel->description = $request->description[array_search('default', $request->lang)] ?? $request->description[0];
        $reel->is_always_visible = $request->boolean('is_always_visible');
        $reel->start_date = $reel->is_always_visible ? null : $startDate;
        $reel->end_date = $reel->is_always_visible ? null : $endDate;
        $reel->created_by_id = $reel->created_by_id ?? auth('admin')->id();
        $reel->created_by_type = $reel->created_by_type ?? 'App\\Models\\Admin';

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
}
