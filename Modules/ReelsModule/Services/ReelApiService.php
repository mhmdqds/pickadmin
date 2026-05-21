<?php

namespace Modules\ReelsModule\Services;

use App\CentralLogics\Helpers;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\ReelsModule\Entities\Reel;
use Modules\ReelsModule\Entities\ReelEngagement;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReelApiService
{
    public function getReels(?int $moduleId, ?int $storeId, int $limit, int $page, ?string $zoneId = null): LengthAwarePaginator
    {
        $zoneIds = $this->resolveZoneIds($zoneId);
        $allZoneService = (bool) data_get(config('module.current_module_data'), 'all_zone_service', false);

        return Reel::query()
            ->active()
            ->with(['store.storeConfig:id,store_id,verified_seller', 'storage'])
            ->select([
                'id',
                'description',
                'thumbnail',
                'video',
                'store_id',
                'module_id',
                'status',
                'is_always_visible',
                'start_date',
                'end_date',
                'total_views',
                'total_likes',
                'total_store_visits',
            ])
            ->when($moduleId !== null, fn (Builder $query) => $query->where('module_id', $moduleId))
            ->when($storeId, fn (Builder $query) => $query->where('store_id', $storeId))
            ->when(!$allZoneService && !empty($zoneIds), function (Builder $query) use ($zoneIds) {
                $query->whereHas('store', function (Builder $storeQuery) use ($zoneIds) {
                    $storeQuery->when(!empty($zoneIds), function ($q) use ($zoneIds) {
                        $q->whereIn('zone_id', $zoneIds);
                    });

                    $storeQuery->whereHas('zone.modules', function ($q) {
                        $q->when(config('module.current_module_data'), function ($q) {
                            $q->where('modules.id', config('module.current_module_data')['id']);
                        });
                    });
                });
            })
            ->latest('id')
            ->paginate($limit, ['*'], 'page', $page);
    }

    private function resolveZoneIds(?string $zoneId): array
    {
        if (empty($zoneId)) {
            return [];
        }

        $decoded = json_decode($zoneId, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded, fn ($value) => is_numeric($value)));
        }

        return is_numeric($zoneId) ? [(int) $zoneId] : [];
    }

    public function getReelDetails(int $id, ?int $moduleId): ?Reel
    {
        return Reel::query()
            ->active()
            ->with(['store.storeConfig:id,store_id,verified_seller', 'storage'])
            ->when($moduleId !== null, fn (Builder $query) => $query->where('module_id', $moduleId))
            ->find($id);
    }

    public function trackView(Reel $reel, ?int $userId, ?string $guestId): void
    {
        $this->trackUniqueEngagement($reel, $userId, $guestId, 'view', 'total_views');
    }

    public function trackVisit(Reel $reel, ?int $userId, ?string $guestId): void
    {
        $this->trackUniqueEngagement($reel, $userId, $guestId, 'visit', 'total_store_visits');
    }

    public function toggleLike(Reel $reel, int $userId): array
    {
        return DB::transaction(function () use ($reel, $userId) {
            $engagementQuery = ReelEngagement::query()
                ->where('reel_id', $reel->id)
                ->where('user_id', $userId)
                ->where('type', 'like');

            $existingEngagement = $engagementQuery->lockForUpdate()->first();
            $lockedReel = Reel::query()->lockForUpdate()->findOrFail($reel->id);

            if ($existingEngagement) {
                $existingEngagement->delete();
                if ($lockedReel->total_likes > 0) {
                    $lockedReel->decrement('total_likes');
                }

                return [
                    'liked' => false,
                    'total_likes' => max(0, (int) $lockedReel->fresh()->total_likes),
                    'message' => 'Unliked successfully',
                ];
            }

            ReelEngagement::create([
                'reel_id' => $lockedReel->id,
                'user_id' => $userId,
                'type' => 'like',
            ]);

            $lockedReel->increment('total_likes');

            return [
                'liked' => true,
                'total_likes' => (int) $lockedReel->fresh()->total_likes,
                'message' => 'Liked successfully',
            ];
        });
    }

    public function streamVideo(Reel $reel, Request $request): Response|StreamedResponse|RedirectResponse
    {
        $disk         = $this->resolveVideoDisk($reel);
        $relativePath = 'reels/' . $reel->video;

        // Resolve local path in one step; fall back to redirect for remote disks (S3, etc.)
        $filePath = method_exists(Storage::disk($disk), 'path')
            ? Storage::disk($disk)->path($relativePath)
            : null;

        if (!$filePath || !is_file($filePath)) {
            abort_unless(Storage::disk($disk)->exists($relativePath), 404);
            return response()->redirectTo($reel->video_full_url);
        }

        $fileSize     = filesize($filePath);
        $mtime        = filemtime($filePath);
        $etag         = '"' . md5($mtime . $fileSize) . '"';
        $lastModified = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

        // Conditional request — return 304 if client already has current version
        if ($request->header('If-None-Match') === $etag ||
            ($request->header('If-Modified-Since') && strtotime($request->header('If-Modified-Since')) >= $mtime)
        ) {
            return response('', 304, [
                'ETag'          => $etag,
                'Last-Modified' => $lastModified,
            ]);
        }

        // Derive MIME type from extension to avoid reading magic bytes on every request
        static $mimeMap = [
            'mp4'  => 'video/mp4',
            'webm' => 'video/webm',
            'mov'  => 'video/quicktime',
            'mkv'  => 'video/x-matroska',
            '3gp'  => 'video/3gpp',
            'gif'  => 'image/gif',
        ];
        $ext      = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = $mimeMap[$ext] ?? (mime_content_type($filePath) ?: 'video/mp4');

        $start  = 0;
        $end    = $fileSize - 1;
        $status = 200;
        $headers = [
            'Content-Type'  => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=86400',
            'ETag'          => $etag,
            'Last-Modified' => $lastModified,
        ];

        $rangeHeader = $request->header('Range');
        if ($rangeHeader && preg_match('/bytes=(\d*)-(\d*)/i', $rangeHeader, $matches)) {
            $start = $matches[1] !== '' ? (int) $matches[1] : 0;
            $end   = $matches[2] !== '' ? (int) $matches[2] : $end;
            $end   = min($end, $fileSize - 1);

            if ($start > $end || $start >= $fileSize) {
                return response('', 416, [
                    'Content-Range' => "bytes */{$fileSize}",
                ]);
            }

            $status                  = 206;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
        }

        $headers['Content-Length'] = (string) ($end - $start + 1);

        return response()->stream(function () use ($filePath, $start, $end) {
            // Discard any buffered output from middleware/session before streaming
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $chunkSize = 1024 * 1024;
            $handle    = fopen($filePath, 'rb');

            if ($handle === false) {
                return;
            }

            try {
                fseek($handle, $start);
                $bytesRemaining = $end - $start + 1;

                while (!feof($handle) && $bytesRemaining > 0) {
                    if (connection_aborted()) {
                        break;
                    }

                    $readLength = min($chunkSize, $bytesRemaining);
                    $buffer     = fread($handle, $readLength);

                    if ($buffer === false) {
                        break;
                    }

                    echo $buffer;
                    flush();

                    $bytesRemaining -= strlen($buffer);
                }
            } finally {
                fclose($handle);
            }
        }, $status, $headers);
    }

    private function trackUniqueEngagement(Reel $reel, ?int $userId, ?string $guestId, string $type, string $counterColumn): void
    {
        DB::transaction(function () use ($reel, $userId, $guestId, $type, $counterColumn) {
            $engagementQuery = ReelEngagement::query()
                ->where('reel_id', $reel->id)
                ->where('type', $type)
                ->when($userId, fn ($query) => $query->where('user_id', $userId))
                ->when(!$userId, fn ($query) => $query->where('guest_id', $guestId));

            $alreadyTracked = $engagementQuery->lockForUpdate()->exists();

            if ($alreadyTracked) {
                return;
            }

            ReelEngagement::create([
                'reel_id' => $reel->id,
                'user_id' => $userId,
                'guest_id' => $userId ? null : $guestId,
                'type' => $type,
            ]);

            Reel::query()->where('id', $reel->id)->increment($counterColumn);
        });
    }

    private function resolveVideoDisk(Reel $reel): string
    {
        foreach ($reel->storage as $storage) {
            if ($storage->key === 'video' && $storage->value) {
                return $storage->value;
            }
        }

        return Helpers::getDisk();
    }
}
