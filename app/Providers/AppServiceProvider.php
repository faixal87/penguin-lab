<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Services\CourseFeedbackService;
use App\Services\BirthdayNotificationService;
use App\Services\NotificationService;
use App\Models\Semester;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $view->with([
                'layoutNotifications' => collect(),
                'layoutNotificationPopup' => null,
                'layoutUnreadNotificationCount' => 0,
                'layoutShowFeedbackMenu' => false,
                'layoutBroadcastClassIds' => collect(),
                'layoutBroadcastConfig' => [],
                'layoutCurrentSemester' => null,
            ]);

            if (! $user) {
                return;
            }

            if (Schema::hasTable('semesters')) {
                $view->with('layoutCurrentSemester', Semester::current());
            }

            if (Schema::hasTable('notifications') && Schema::hasTable('notification_reads')) {
                if (Schema::hasColumn('users', 'date_of_birth')) {
                    app(BirthdayNotificationService::class)->ensureFor($user);
                }

                $notificationService = app(NotificationService::class);

                $view->with([
                    'layoutNotifications' => $notificationService->visibleFor($user)->take(8),
                    'layoutNotificationPopup' => $notificationService->popupFor($user),
                    'layoutUnreadNotificationCount' => $notificationService->unreadCount($user),
                ]);
            }

            if (Schema::hasTable('course_feedback_controls') && Schema::hasTable('feedback_answers')) {
                $view->with('layoutShowFeedbackMenu', app(CourseFeedbackService::class)->shouldShowMenu($user));
            }

            $classIds = $user->isStudent()
                ? $user->enrolledClasses()->pluck('classes.id')
                : ($user->isLecturer() ? $user->teachingClasses()->pluck('id') : collect());

            $view->with([
                'layoutBroadcastClassIds' => $classIds->values(),
                'layoutBroadcastConfig' => [
                    'key' => config('broadcasting.connections.reverb.key'),
                    'host' => config('broadcasting.connections.reverb.options.host', '127.0.0.1'),
                    'port' => (int) config('broadcasting.connections.reverb.options.port', 8080),
                    'scheme' => config('broadcasting.connections.reverb.options.scheme', 'http'),
                ],
            ]);
        });
    }
}
