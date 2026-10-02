<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Platform\Models\PlatformBroadcast;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PlatformHealthController
{
    public function index(Request $request, DatabaseManager $database, CacheRepository $cache, Filesystem $filesystem): View
    {
        $checks = [];
        $checks['database'] = $this->timed(fn () => (bool) $database->connection()->getPdo());
        $checks['cache'] = $this->timed(function () use ($cache): bool {
            $key = '__bookresa_platform_health__';
            $cache->put($key, 'ok', 30);
            $ok = $cache->get($key) === 'ok';
            $cache->forget($key);
            return $ok;
        });
        $checks['storage'] = $filesystem->isWritable(storage_path());
        $checks['public_storage'] = is_dir(public_path('storage')) || is_link(public_path('storage'));

        $opcache = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
        $checks['opcache'] = is_array($opcache) && ($opcache['opcache_enabled'] ?? false);

        $failedJobs = (int) DB::table('failed_jobs')->count();
        $queuedJobs = (int) DB::table('jobs')->count();
        $sessions = (int) DB::table('sessions')->count();
        $openTickets = (int) SupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count();
        $pendingBroadcasts = (int) PlatformBroadcast::query()->where('status', 'pending')->count();

        $backupPath = (string) config('bookresa.operations.backup_path');

        $backups = [];
        if (is_dir($backupPath) && is_readable($backupPath)) {
            foreach (glob(rtrim($backupPath, DIRECTORY_SEPARATOR).'/*.sql.gz') ?: [] as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => filesize($file) ?: 0,
                    'modified_at' => date('c', filemtime($file) ?: time()),
                ];
            }
            usort($backups, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));
        }

        return view('admin.health.index', [
            'checks' => $checks,
            'failedJobs' => $failedJobs,
            'queuedJobs' => $queuedJobs,
            'sessions' => $sessions,
            'openTickets' => $openTickets,
            'pendingBroadcasts' => $pendingBroadcasts,
            'backups' => array_slice($backups, 0, 10),
            'phpVersion' => PHP_VERSION,
            'cacheDriver' => config('cache.default'),
            'queueDriver' => config('queue.default'),
            'sessionDriver' => config('session.driver'),
            'timezone' => config('app.timezone'),
            'appEnvironment' => config('app.env'),
        ]);
    }

    private function timed(callable $callback): bool
    {
        try {
            return (bool) $callback();
        } catch (\Throwable) {
            return false;
        }
    }
}
