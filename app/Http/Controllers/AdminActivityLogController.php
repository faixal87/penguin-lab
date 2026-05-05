<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('activity_log_search', ''));

        $logs = LoginLog::query()
            ->with('user')
            ->when($search !== '', function (Builder $query) use ($search) {
                $needle = strtolower($search);

                $query->where(function (Builder $inner) use ($needle) {
                    $inner->whereRaw('LOWER(activity) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(ip_address) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(browser) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(platform) LIKE ?', ["%{$needle}%"])
                        ->orWhereHas('user', function (Builder $userQuery) use ($needle) {
                            $userQuery->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"])
                                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$needle}%"]);
                        });
                });
            })
            ->latest('logged_in_at')
            ->limit(500)
            ->get();

        $groupedLogs = $logs->groupBy(fn (LoginLog $log) => $log->user?->name ?? 'Unknown User');

        return view('admin.activity-logs.index', [
            'groupedLogs' => $groupedLogs,
            'search' => $search,
            'totalLogs' => $logs->count(),
        ]);
    }
}
