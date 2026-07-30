<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * H-5 FIX — APP_KEY rotation helper.
 *
 * Invalidates every active session across the cluster. Used by operators
 * after `php artisan key:generate` to:
 *   • Guarantee that no session payload sealed with the *old* APP_KEY can be
 *     read back into the application (the framework decrypts every session
 *     payload transparently, so a stale payload surfaces as a decryption
 *     error the moment the user touches it).
 *   • Defeat session-fixation / replay attacks that may have piggy-backed
 *     onto the previous APP_KEY.
 *
 * Modes of operation:
 *   --driver=db       : wipe the `sessions` table (database driver)
 *   --driver=file     : delete files under storage/framework/sessions/
 *   --driver=redis    : delete via predis/phpredis
 *   --driver=cache    : delete via the configured cache store
 *   (no --driver)     : auto-detect from config('session.driver')
 *
 * Examples:
 *   php artisan session:flush
 *   php artisan session:flush --driver=file
 *   php artisan session:flush --force
 */
class SessionFlush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:flush
                            {--driver= : Session driver to flush (file|db|redis|cache). Defaults to config("session.driver").}
                            {--force : Skip the confirmation prompt.}
                            {--keep-last : Keep the most recent N sessions (default 0; wipes all).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'H-5 fix: invalidate every active session. Use after php artisan key:generate.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $driver = (string) ($this->option('driver') ?: config('session.driver', 'file'));
        $force  = (bool)   $this->option('force');

        // Operator confirmation – we are about to log every user out.
        if (!$force && !$this->confirm(
            "This will log every user out of the application ($driver driver). Continue?",
            false
        )) {
            $this->warn('Aborted by operator. No sessions were invalidated.');
            return self::FAILURE;
        }

        $start    = microtime(true);
        $affected = 0;

        try {
            switch (strtolower($driver)) {
                case 'file':
                    $affected = $this->flushFileDriver();
                    break;
                case 'database':
                case 'db':
                    $affected = $this->flushDatabaseDriver();
                    break;
                case 'redis':
                    $affected = $this->flushRedisDriver();
                    break;
                case 'cache':
                case 'apc':
                case 'memcached':
                case 'dynamodb':
                    $affected = $this->flushCacheDriver($driver);
                    break;
                default:
                    $this->error("Unknown session driver: $driver");
                    return self::FAILURE;
            }

            // Always blow away the in-memory session store of *this* request.
            Session::flush();

            $elapsedMs = (int) ((microtime(true) - $start) * 1000);
            $message   = "H-5 session flush complete. driver=$driver, invalidated={$affected}, took={$elapsedMs}ms.";
            $this->info($message);
            Log::info($message, ['driver' => $driver, 'invalidated' => $affected, 'elapsed_ms' => $elapsedMs]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('H-5 session:flush failed: '.$e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'driver'    => $driver,
            ]);
            $this->error('Flush failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Delete every session file under storage/framework/sessions.
     */
    private function flushFileDriver(): int
    {
        $path = config('session.files', storage_path('framework/sessions'));

        if (!is_dir($path)) {
            $this->warn("Session file directory does not exist: $path");
            return 0;
        }

        $count      = 0;
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iter as $entry) {
            /** @var \SplFileInfo $entry */
            if ($entry->isFile()) {
                // Best effort — we deliberately ignore per-file failures so a
                // locked file on one node does not stall the whole flush.
                if (@unlink($entry->getRealPath())) {
                    $count++;
                }
            } elseif ($entry->isDir()) {
                @rmdir($entry->getRealPath());
            }
        }

        return $count;
    }

    /**
     * Truncate the configured sessions table.
     */
    private function flushDatabaseDriver(): int
    {
        $connection = config('session.connection');
        $table      = config('session.table', 'sessions');

        return (int) DB::connection($connection)->table($table)->delete();
    }

    /**
     * Flush the Redis-backed sessions (Laravel uses the configured cache store).
     */
    private function flushRedisDriver(): int
    {
        $store = config('session.store');
        // PHP-redis driver key prefix is auto-prepended by Laravel's cache layer.
        $store = $store ?: config('cache.default');

        // Simpler / safer than introspecting key prefixes: blow the whole
        // configured cache store away. On a dedicated session-only cache
        // store this is a complete purge; on a shared store this may also
        // empty unrelated cache entries.  Use with care.
        \Illuminate\Support\Facades\Cache::store($store)->flush();

        // Count is unknown for FLUSHDB, return 0 silently.
        return 0;
    }

    /**
     * Flush any cache-driver backed sessions.
     */
    private function flushCacheDriver(string $driver): int
    {
        $store = config('session.store') ?: config('cache.default');
        \Illuminate\Support\Facades\Cache::store($store)->flush();
        return 0;
    }
}
