<?php

namespace Modules\ReelsModule\Http\Controllers\Api\V1\Vendor;

use App\CentralLogics\Helpers;
use App\Exceptions\InvalidUploadException;
use App\Http\Controllers\Controller;
use App\Models\Translation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Modules\ReelsModule\Entities\Reel;
use Modules\ReelsModule\Entities\ReelEngagement;
use Modules\ReelsModule\Http\Requests\Api\V1\Vendor\ReelStoreRequest;
use Modules\ReelsModule\Http\Requests\Api\V1\Vendor\ReelUpdateRequest;
use Modules\ReelsModule\Support\ReelModuleConfig;

class ReelController extends Controller
{

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:1',
            'status' => 'nullable',
            'sort_by' => 'nullable|string|in:latest,oldest,most_viewed,most_liked',
            'search' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 1);
        $status = $this->parseStatusFilter($request->input('status'));
        $sortBy = $request->input('sort_by', 'latest');

        if ($request->filled('status') && empty($status)) {
            return response()->json([
                'errors' => [
                    ['code' => 'invalid_status', 'message' => 'Invalid status filter']
                ]
            ], 403);
        }

        $storeId = $request['vendor']->stores[0]->id;
        $today = Carbon::today()->toDateString();

        $query = Reel::with(['store', 'engagements'])
            ->withCount([
                'engagements as total_views' => fn (Builder $builder) => $builder->where('type', ReelEngagement::TYPE_VIEW),
                'engagements as total_likes' => fn (Builder $builder) => $builder->where('type', ReelEngagement::TYPE_LIKE),
                'engagements as total_store_visits' => fn (Builder $builder) => $builder->where('type', ReelEngagement::TYPE_VISIT),
            ])
            ->where('store_id', $storeId)
            ->when($request->filled('search'), function ($q) use ($request) {
                $keywords = array_filter(explode(' ', $request->input('search')));
                foreach ($keywords as $keyword) {
                    $q->where(function ($sub) use ($keyword) {
                        $sub->where('id', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%")
                            ->orWhereHas('translations', function ($tq) use ($keyword) {
                                $tq->where('key', 'description')
                                    ->where('value', 'like', "%{$keyword}%");
                            });
                    });
                }
            });

        if (!empty($status) && !in_array('all', $status, true)) {
            $this->applyReelStatusFilter($query, $status, $today);
        }

        $this->applySortBy($query, $sortBy);

        $reels = $query->paginate($limit, ['*'], 'page', $offset);

        $data = [
            'total_size' => $reels->total(),
            'limit' => $limit,
            'offset' => $offset,
            'reels' => $reels->items(),
        ];

        return response()->json($data, 200);
    }

    public function store(ReelStoreRequest $request)
    {
        $store = $request['vendor']->stores[0];

        if (!addon_published_status('ReelsModule')) {
            return response()->json([
                'errors' => [
                    ['code' => 'addon_not_published', 'message' => translate('messages.this_feature_is_not_available_for_the_selected_module')]
                ]
            ], 403);
        }

        if(getEnvMode() === 'demo') {
            return response()->json([
                'errors' => [
                    ['code' => 'demo_mode', 'message' => translate('Uploads are disabled in demo mode')]
                ]
            ], 403);
        }

        if (!Helpers::get_business_settings('vendor_can_upload_reels')) {
            return response()->json([
                'errors' => [
                    ['code' => 'feature_disabled', 'message' => translate('messages.this_feature_is_not_available_for_the_selected_module')]
                ]
            ], 403);
        }

        if (ReelModuleConfig::isMultiModule() && !ReelModuleConfig::isAllowedType($store->module?->module_type)) {
            return response()->json([
                'errors' => [
                    ['code' => 'module_not_allowed', 'message' => translate('messages.this_feature_is_not_available_for_the_selected_module')]
                ]
            ], 403);
        }

        if ($request->filled('translations') && empty($this->prepareTranslations($request))) {
            return response()->json([
                'errors' => [
                    ['code' => 'translations_required', 'message' => translate('messages.Description in english is required')]
                ]
            ], 403);
        }

        try {
            $reel = new Reel();
            $this->fillAndPersistReel($reel, $request);
        } catch (InvalidUploadException $exception) {
            return response()->json(['errors' => [['message' => $exception->getMessage()]]], 422);
        }

        return response()->json(['message' => translate('messages.reel_created_successfully')], 200);
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reel_id' => 'required|integer|exists:reels,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $storeId = $request['vendor']->stores[0]->id;
        $reel = Reel::where('id', $request->reel_id)
            ->where('store_id', $storeId)
            ->with(['store', 'engagements'])
            ->first();

        if (!$reel) {
            return response()->json([
                'errors' => [
                    ['code' => 'not_found', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        return response()->json($reel, 200);
    }

    public function update(ReelUpdateRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'reel_id' => 'required|integer|exists:reels,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        if(getEnvMode() === 'demo') {
            return response()->json([
                'errors' => [
                    ['code' => 'demo_mode', 'message' => translate('Uploads are disabled in demo mode')]
                ]
            ], 403);
        }

        if ($request->filled('translations') && empty($this->prepareTranslations($request))) {
            return response()->json([
                'errors' => [
                    ['code' => 'translations_required', 'message' => translate('messages.Description in english is required')]
                ]
            ], 403);
        }

        $storeId = $request['vendor']->stores[0]->id;
        $reel = Reel::where('id', $request->reel_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$reel) {
            return response()->json([
                'errors' => [
                    ['code' => 'not_found', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        try {
            $this->fillAndPersistReel($reel, $request);
        } catch (InvalidUploadException $exception) {
            return response()->json(['errors' => [['message' => $exception->getMessage()]]], 422);
        }

        return response()->json(['message' => translate('messages.reel_updated_successfully')], 200);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reel_id' => 'required|integer|exists:reels,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $storeId = $request['vendor']->stores[0]->id;
        $reel = Reel::where('id', $request->reel_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$reel) {
            return response()->json([
                'errors' => [
                    ['code' => 'not_found', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        // Delete associated files
        if ($reel->thumbnail) {
            Helpers::check_and_delete(dir: 'reels/', old_image: $reel->thumbnail);
        }
        if ($reel->video) {
            Helpers::check_and_delete(dir: 'reels/', old_image: $reel->video);
        }

        $reel->translations()->delete();
        $reel->storage()->delete();
        $reel->delete();

        return response()->json(['message' => translate('messages.reel_deleted_successfully')], 200);
    }

    public function status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reel_id' => 'required|integer|exists:reels,id',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $storeId = $request['vendor']->stores[0]->id;
        $reel = Reel::where('id', $request->reel_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$reel) {
            return response()->json([
                'errors' => [
                    ['code' => 'not_found', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        $reel->status = $request->status;
        $reel->save();

        return response()->json(['message' => translate('messages.reel_status_updated_successfully')], 200);
    }

    private function fillAndPersistReel(Reel $reel, Request $request): void
    {


        [$startDate, $endDate] = $this->parseDateRange($request);
        $store = $request['vendor']->stores[0];
        $translations = $this->prepareTranslations($request);

        $reel->store_id = $store->id;
        if (ReelModuleConfig::isMultiModule()) {
            $reel->module_id = (int) $store->module_id;
            $reel->module_type = (string) ($store->module?->module_type ?? ReelModuleConfig::defaultModuleType());
        } else {
            $reel->module_id = ReelModuleConfig::defaultModuleId();
            $reel->module_type = ReelModuleConfig::defaultModuleType();
        }
        $reel->description = $translations[0]['value'] ?? $request->description;
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

        if (!empty($translations)) {
            $reel->translations()->delete();

            foreach ($translations as $key => $translation) {
                $translations[$key]['translationable_type'] = Reel::class;
                $translations[$key]['translationable_id'] = $reel->id;
            }

            Translation::insert($translations);
        }
    }

    private function prepareTranslations(Request $request): array
    {
        if (!$request->filled('translations')) {
            return [];
        }

        $translations = json_decode($request->translations, true);
        if (!is_array($translations) || count($translations) < 1) {
            return [];
        }

        return $translations;
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

    private function parseStatusFilter($status): array
    {
        if (is_null($status)) {
            return [];
        }

        $allowedStatuses = ['all', 'live', 'upcoming', 'expired', 'deactivated'];
        $statuses = [];

        if (is_array($status)) {
            $statuses = $status;
        } elseif (is_string($status)) {
            $statuses = explode(',', $status);
        } else {
            $statuses = [$status];
        }

        return array_values(array_filter(array_map('trim', $statuses), function ($value) use ($allowedStatuses) {
            return in_array(strtolower($value), $allowedStatuses, true);
        }));
    }

    private function applySortBy($query, string $sortBy): void
    {
        if ($sortBy === 'oldest') {
            $query->orderBy('created_at', 'asc');
            return;
        }

        if ($sortBy === 'most_viewed') {
            $query->orderByDesc('total_views')->orderByDesc('created_at');
            return;
        }

        if ($sortBy === 'most_liked') {
            $query->orderByDesc('total_likes')->orderByDesc('created_at');
            return;
        }

        $query->orderByDesc('created_at');
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

    private function uploadReelAsset(\Illuminate\Http\UploadedFile $file, string $dir, string $type): string
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

        if ($fileToStore instanceof \Illuminate\Http\UploadedFile) {
            Storage::disk($disk)->putFileAs($dir, $fileToStore, $fileName);
        } else {
            Storage::disk($disk)->put($dir . '/' . $fileName, $fileToStore);
        }

        return $fileName;
    }

    private function validateReelFile(\Illuminate\Http\UploadedFile $file, string $type): void
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
