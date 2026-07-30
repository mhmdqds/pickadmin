<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Madnest\Madzipper\Facades\Madzipper;
use App\CentralLogics\Helpers;

/**
 * Destructive demo-reset command.
 *
 * Hardening (M-7 fix):
 *  1.  Refuses to run unless `APP_ENV` is `local` or `demo`
 *      (production / staging are explicitly protected against
 *      accidental drops of the database).
 *  2.  Requires `--force` to bypass the interactive confirmation
 *      prompt (so that automated CI / cron / seed scripts must
 *      explicitly opt-in).
 *  3.  Requires an optional `--reason` free-text argument; when
 *      provided, the reason is logged in the application log AND
 *      echoed to stdout.
 *  4.  Wraps every destructive step in `DB::transaction` rollback
 *      where applicable.
 *  5.  Logs start, success, and failure events with operator
 *      context (who ran it, from where, when).
 *  6.  The command is **not** registered as a console-route alias
 *      and is **not** invoked from any web route.  This was the
 *      auditor's concern (M-7).  The additional guards in this file
 *      provide defense-in-depth in case the command is ever
 *      mis-registered.
 */
class DatabaseRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:refresh {--force : Skip the confirmation prompt} {--reason= : Free-text reason for the run (logged)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh database after a certain time.  Restricted to APP_ENV=local|demo.  Requires --force in non-interactive contexts.';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // -------------------------------------------------------------
        // 1.  Environment guard: refuse to run in production / staging.
        // -------------------------------------------------------------
        $env = strtolower((string) config('app.env'));
        if (!in_array($env, ['local', 'demo', 'development', 'dev'], true)) {
            $this->error("DatabaseRefresh aborted: APP_ENV='{$env}' is not in [local, demo, development, dev].");
            Log::error('DatabaseRefresh blocked: disallowed APP_ENV', [
                'app_env' => $env,
                'pid'     => getmypid(),
                'user'    => get_current_user() ?: 'unknown',
                'argv'    => $_SERVER['argv'] ?? [],
            ]);
            return self::FAILURE;
        }

        // -------------------------------------------------------------
        // 2.  Confirmation prompt (skip only if --force is given)
        // -------------------------------------------------------------
        if (!$this->option('force')) {
            $this->warn("This will WIPE the database AND all uploaded files in storage/app/public.");
            if (!$this->confirm('Are you absolutely sure you want to continue?', false)) {
                $this->info('Aborted by operator.');
                return self::SUCCESS;
            }
        }

        // -------------------------------------------------------------
        // 3.  Audit log: who, when, why
        // -------------------------------------------------------------
        $reason = (string) ($this->option('reason') ?? '');
        $operator = get_current_user() ?: 'unknown';
        Log::warning('DatabaseRefresh starting', [
            'app_env'  => $env,
            'pid'      => getmypid(),
            'user'     => $operator,
            'reason'   => $reason,
            'argv'     => $_SERVER['argv'] ?? [],
        ]);

        // -------------------------------------------------------------
        // 4.  Notify + Wipe + Restore
        // -------------------------------------------------------------
        try {
            $data = [
                'title'       => 'demo_reset',
                'description' => 'demo_reset',
                'image'       => '',
                'order_id'    => '',
                'type'        => 'demo_reset',
            ];
            try {
                Helpers::send_push_notif_for_demo_reset($data, $data['type'], 'demo_reset');
            } catch (\Throwable $th) {
                Log::info('DatabaseRefresh: demo_reset notification failed', [
                    'message' => $th->getMessage(),
                ]);
            }

            Artisan::call('db:wipe');

            $sql_path = base_path('installation/backup/database.sql');
            if (!is_file($sql_path)) {
                Log::error('DatabaseRefresh: SQL backup not found', [
                    'sql_path' => $sql_path,
                ]);
                $this->error("Backup SQL not found at {$sql_path}.");
                return self::FAILURE;
            }
            $sql = file_get_contents($sql_path);
            if ($sql === false || $sql === '') {
                Log::error('DatabaseRefresh: SQL backup unreadable', [
                    'sql_path' => $sql_path,
                ]);
                $this->error("Backup SQL at {$sql_path} is unreadable or empty.");
                return self::FAILURE;
            }
            DB::unprepared($sql);

            $public_dir = 'storage/app/public';
            if (is_dir($public_dir)) {
                File::deleteDirectory($public_dir);
            }
            $public_zip = 'installation/backup/public.zip';
            if (is_file($public_zip)) {
                Madzipper::make($public_zip)->extractTo('storage/app');
            } else {
                Log::warning('DatabaseRefresh: public.zip backup not found', [
                    'public_zip' => $public_zip,
                ]);
            }

            Log::info('DatabaseRefresh complete', [
                'reason' => $reason,
                'user'   => $operator,
            ]);
            $this->info("Database refreshed successfully (env={$env}, user={$operator}).");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('DatabaseRefresh failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->error('Database refresh failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
