<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SystemHealthService
{
    public function snapshot(): array
    {
        $checkedAt = now()->format('Y-m-d H:i:s');
        $docker = $this->docker();
        $services = [
            'laravel_app' => $this->service('Laravel App', 'ONLINE', 'Application is responding.', 'app', $checkedAt),
            'db' => $this->database($checkedAt),
            'storage' => $this->storage($checkedAt),
            'docker' => $docker['docker'],
            'containers' => $docker['containers'],
            'guacamole' => $this->guacamole($checkedAt),
            'queue' => $this->queue($checkedAt),
            'notifications' => $this->service('Notification Polling', 'ONLINE', 'AJAX polling endpoint is available.', 'bell', $checkedAt),
        ];

        $hasDown = collect($services)->contains(fn ($service) => $service['status'] === 'OFFLINE');
        $hasWarning = collect($services)->contains(fn ($service) => $service['status'] === 'WARNING');

        return [
            'checked_at' => $checkedAt,
            'overall_status' => $hasDown ? 'OFFLINE' : ($hasWarning ? 'WARNING' : 'ONLINE'),
            'db_status' => $services['db']['status'],
            'docker_status' => $services['docker']['status'],
            'guacamole_status' => $services['guacamole']['status'],
            'storage_status' => $services['storage']['status'],
            'container_count' => $services['containers']['count'],
            'queue_status' => $services['queue']['status'],
            'notification_status' => $services['notifications']['status'],
            'services' => $services,
            'alerts' => collect($services)
                ->filter(fn ($service) => $service['status'] !== 'ONLINE')
                ->map(fn ($service) => "{$service['name']}: {$service['message']}")
                ->values(),
        ];
    }

    public function mini(): array
    {
        $snapshot = $this->snapshot();

        return [
            'checked_at' => $snapshot['checked_at'],
            'overall_status' => $snapshot['overall_status'],
            'services' => collect($snapshot['services'])
                ->only(['db', 'docker', 'guacamole', 'containers'])
                ->all(),
            'alerts' => $snapshot['alerts'],
        ];
    }

    private function database(string $checkedAt): array
    {
        try {
            DB::select('select 1 as health_check');

            return $this->service('MySQL / Database', 'ONLINE', 'Database query succeeded.', 'database', $checkedAt);
        } catch (Throwable $e) {
            return $this->service('MySQL / Database', 'OFFLINE', $this->cleanMessage($e->getMessage()), 'database', $checkedAt);
        }
    }

    private function storage(string $checkedAt): array
    {
        $paths = [storage_path('app'), storage_path('framework'), storage_path('logs')];
        $unwritable = collect($paths)->filter(fn ($path) => ! is_writable($path));

        if ($unwritable->isEmpty()) {
            return $this->service('Storage', 'ONLINE', 'Storage directories are writable.', 'folder', $checkedAt);
        }

        return $this->service('Storage', 'OFFLINE', 'Unwritable: ' . $unwritable->join(', '), 'folder', $checkedAt);
    }

    private function docker(): array
    {
        $checkedAt = now()->format('Y-m-d H:i:s');

        if (! function_exists('shell_exec')) {
            return [
                'docker' => $this->service('Docker Engine', 'WARNING', 'shell_exec is disabled; Docker status cannot be checked.', 'docker', $checkedAt),
                'containers' => $this->containerService(0, 'WARNING', 'Container count unavailable.', $checkedAt),
            ];
        }

        $versionOutput = @shell_exec('docker version --format "{{.Server.Version}}" 2>&1');

        if ($versionOutput === null || trim($versionOutput) === '') {
            return [
                'docker' => $this->service('Docker Engine', 'WARNING', 'No Docker output returned.', 'docker', $checkedAt),
                'containers' => $this->containerService(0, 'WARNING', 'No running student containers detected.', $checkedAt),
            ];
        }

        $lower = strtolower($versionOutput);
        if (str_contains($lower, 'error') || str_contains($lower, 'not recognized') || str_contains($lower, 'cannot connect') || str_contains($lower, 'daemon')) {
            return [
                'docker' => $this->service('Docker Engine', 'OFFLINE', trim($versionOutput), 'docker', $checkedAt),
                'containers' => $this->containerService(0, 'OFFLINE', 'Docker is unavailable.', $checkedAt),
            ];
        }

        $output = @shell_exec('docker ps --format "{{.Names}}" 2>&1') ?? '';
        $lowerPs = strtolower($output);
        if (str_contains($lowerPs, 'error') || str_contains($lowerPs, 'not recognized') || str_contains($lowerPs, 'cannot connect') || str_contains($lowerPs, 'daemon')) {
            return [
                'docker' => $this->service('Docker Engine', 'ONLINE', 'Docker engine responded.', 'docker', $checkedAt),
                'containers' => $this->containerService(0, 'WARNING', trim($output), $checkedAt),
            ];
        }

        $names = collect(preg_split('/\r\n|\r|\n/', trim($output)))
            ->map(fn ($name) => trim($name))
            ->filter();
        $prefix = (string) config('services.terminal.container_prefix', 'penguinlab_');
        $studentCount = $names->filter(fn ($name) => str_starts_with($name, $prefix))->count();

        return [
            'docker' => $this->service('Docker Engine', 'ONLINE', 'Docker engine responded.', 'docker', $checkedAt),
            'containers' => $this->containerService($studentCount, $studentCount > 0 ? 'ONLINE' : 'WARNING', "{$studentCount} running student container(s).", $checkedAt),
        ];
    }

    private function guacamole(string $checkedAt): array
    {
        $url = (string) config('services.guacamole.base_url', '#');

        if ($url === '' || $url === '#') {
            return $this->service('Guacamole', 'WARNING', 'GUACAMOLE_BASE_URL is not configured.', 'terminal', $checkedAt);
        }

        try {
            $response = Http::timeout(3)->get($url);

            $isRedirect = in_array($response->status(), [301, 302, 303, 307, 308], true);

            if ($response->successful() || $isRedirect) {
                return $this->service('Guacamole', 'ONLINE', "HTTP {$response->status()} from {$url}.", 'terminal', $checkedAt);
            }

            return $this->service('Guacamole', 'WARNING', "HTTP {$response->status()} from {$url}.", 'terminal', $checkedAt);
        } catch (Throwable $e) {
            return $this->service('Guacamole', 'OFFLINE', $this->cleanMessage($e->getMessage()), 'terminal', $checkedAt);
        }
    }

    private function queue(string $checkedAt): array
    {
        $connection = config('queue.default', 'sync');

        if ($connection === 'sync') {
            return $this->service('Queue Worker', 'ONLINE', 'Queue is using sync mode.', 'queue', $checkedAt);
        }

        if (Schema::hasTable('jobs')) {
            $pending = DB::table('jobs')->count();
            return $this->service('Queue Worker', $pending > 100 ? 'WARNING' : 'ONLINE', "{$pending} pending queued job(s).", 'queue', $checkedAt);
        }

        return $this->service('Queue Worker', 'WARNING', 'No queue heartbeat or jobs table found.', 'queue', $checkedAt);
    }

    private function containerService(int $count, string $status, string $message, string $checkedAt): array
    {
        return $this->service('Student Containers', $status, $message, 'boxes', $checkedAt) + ['count' => $count];
    }

    private function service(string $name, string $status, string $message, string $icon, string $checkedAt): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'message' => $this->cleanMessage($message),
            'icon' => $icon,
            'last_checked' => $checkedAt,
        ];
    }

    private function cleanMessage(string $message): string
    {
        return Str::of($message)->replaceMatches('/\s+/', ' ')->limit(180)->toString();
    }
}
