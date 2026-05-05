<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function log(User $user, string $activity, Request $request): void
    {
        $userAgent = $request->userAgent();

        LoginLog::create([
            'user_id' => $user->id,
            'activity' => $activity,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'browser' => $this->detectBrowser($userAgent),
            'platform' => $this->detectPlatform($userAgent),
        ]);
    }

    private function detectBrowser(?string $userAgent): string
    {
        $userAgent = $userAgent ?? '';

        return match (true) {
            str_contains($userAgent, 'Edg') => 'Microsoft Edge',
            str_contains($userAgent, 'Chrome') => 'Chrome',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Safari') => 'Safari',
            default => 'Unknown',
        };
    }

    private function detectPlatform(?string $userAgent): string
    {
        $userAgent = $userAgent ?? '';

        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            default => 'Unknown',
        };
    }
}
