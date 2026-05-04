<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminFeedbackQuestionController;
use App\Http\Controllers\AdminQuestionBankController;
use App\Http\Controllers\AdminQuestionSetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseFeedbackControlController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FeedbackReportController;
use App\Http\Controllers\LecturerClassController;
use App\Http\Controllers\LecturerQuestionSetAssignmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionSetController;
use App\Http\Controllers\ScoreboardController;
use App\Http\Controllers\SemesterController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\TerminalController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-json', [NotificationController::class, 'unreadJson'])->name('notifications.unread-json');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/{notification}/dismiss', [NotificationController::class, 'dismiss'])->name('notifications.dismiss');

    Route::middleware(RoleMiddleware::class . ':student')->group(function () {
        Route::get('/scenarios/choose', [ScenarioController::class, 'chooseSet'])->name('scenarios.choose');
        Route::post('/scenarios/choose', [ScenarioController::class, 'selectSet'])->name('scenarios.choose.store');
        Route::get('/scenarios', [ScenarioController::class, 'index'])->name('scenarios');
        Route::get('/scenarios/set-question/{setQuestion}', [ScenarioController::class, 'showSetQuestion'])->name('scenarios.set-question.show');
        Route::post('/scenarios/set-question/{setQuestion}/hint/{level}', [ScenarioController::class, 'showSetQuestionHint'])->name('scenarios.set-question.hint');
        Route::post('/scenarios/set-question/{setQuestion}/check', [ScenarioController::class, 'checkSetQuestion'])->name('scenarios.set-question.check');
        Route::get('/scenarios/{scenario}', [ScenarioController::class, 'show'])->name('scenarios.show');
        Route::post('/scenarios/{scenario}/hint', [ScenarioController::class, 'showHint'])->name('scenarios.hint');
        Route::post('/scenarios/{scenario}/check', [ScenarioController::class, 'check'])->name('scenarios.check');
        Route::get('/results', [ExamController::class, 'results'])->name('results');
        Route::get('/terminal', [TerminalController::class, 'index'])->name('terminal.index');
        Route::post('/terminal/launch', [TerminalController::class, 'launch'])->name('terminal.launch');
        Route::post('/terminal/stop', [TerminalController::class, 'stop'])->name('terminal.stop');
        Route::get('/feedback', [FeedbackController::class, 'form'])->name('feedback.form');
        Route::post('/feedback', [FeedbackController::class, 'submit'])->name('feedback.submit');
    });

    Route::middleware(RoleMiddleware::class . ':lecturer')->group(function () {
        Route::post('/lecturer/classes', [LecturerClassController::class, 'store'])->name('lecturer.classes.store');
        Route::get('/lecturer/classes/{schoolClass}', [LecturerClassController::class, 'show'])->name('lecturer.classes.show');
        Route::get('/lecturer/classes/{schoolClass}/edit', [LecturerClassController::class, 'edit'])->name('lecturer.classes.edit');
        Route::put('/lecturer/classes/{schoolClass}', [LecturerClassController::class, 'update'])->name('lecturer.classes.update');
        Route::delete('/lecturer/classes/{schoolClass}', [LecturerClassController::class, 'destroy'])->name('lecturer.classes.destroy');
        Route::post('/lecturer/classes/{schoolClass}/students', [LecturerClassController::class, 'uploadStudents'])->name('lecturer.classes.students.upload');
        Route::delete('/lecturer/classes/{schoolClass}/students/{student}', [LecturerClassController::class, 'removeStudent'])->name('lecturer.classes.students.remove');
        Route::put('/lecturer/classes/{schoolClass}/students/{student}/status', [LecturerClassController::class, 'disableStudent'])->name('lecturer.classes.students.status');
        Route::put('/lecturer/classes/{schoolClass}/students/{student}/password', [LecturerClassController::class, 'resetStudentPassword'])->name('lecturer.classes.students.password');
        Route::match(['get', 'post'], '/lecturer/terminal/settings', [TerminalController::class, 'lecturerSettings'])->name('lecturer.terminal.settings');
        Route::post('/lecturer/terminal/users/{user}/run', [TerminalController::class, 'runCommand'])->name('lecturer.terminal.run');
        Route::resource('/lecturer/question-bank', AdminQuestionBankController::class)->names('lecturer.question-bank')->parameters(['question-bank' => 'question'])->except(['show']);
        Route::resource('/lecturer/question-sets', AdminQuestionSetController::class)->names('lecturer.question-sets')->parameters(['question-sets' => 'set'])->except(['show']);
        Route::post('/lecturer/question-sets/{set}/questions', [AdminQuestionSetController::class, 'addQuestion'])->name('lecturer.question-sets.questions.add');
        Route::post('/lecturer/question-sets/{set}/questions/custom', [AdminQuestionSetController::class, 'createQuestion'])->name('lecturer.question-sets.questions.custom');
        Route::put('/lecturer/question-set-questions/{setQuestion}', [AdminQuestionSetController::class, 'updateQuestion'])->name('lecturer.question-set-questions.update');
        Route::delete('/lecturer/question-set-questions/{setQuestion}', [AdminQuestionSetController::class, 'removeQuestion'])->name('lecturer.question-set-questions.destroy');
        Route::get('/lecturer/question-set-assignment', [LecturerQuestionSetAssignmentController::class, 'index'])->name('lecturer.question-set-assignment');
        Route::put('/lecturer/classes/{schoolClass}/question-set', [LecturerQuestionSetAssignmentController::class, 'update'])->name('lecturer.classes.question-set.update');
        Route::put('/lecturer/students/{student}/question-sets', [LecturerQuestionSetAssignmentController::class, 'updateStudent'])->name('lecturer.students.question-sets.update');
    });

    Route::middleware(RoleMiddleware::class . ':admin')->group(function () {
        Route::match(['get', 'post'], '/admin/terminal/settings', [TerminalController::class, 'adminSettings'])->name('admin.terminal.settings');
        Route::post('/admin/terminal/users/{user}/preview', [TerminalController::class, 'previewCommand'])->name('admin.terminal.preview');
        Route::post('/admin/terminal/users/{user}/run', [TerminalController::class, 'runCommand'])->name('admin.terminal.run');
        Route::post('/admin/terminal/users/{user}/sync-guacamole', [TerminalController::class, 'syncGuacamole'])->name('admin.terminal.sync-guacamole');
        Route::post('/admin/terminal/users/{user}/reset-guacamole-password', [TerminalController::class, 'resetGuacamolePassword'])->name('admin.terminal.reset-guacamole-password');
        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create/lecturer', [AdminUserController::class, 'createLecturer'])->name('admin.users.create-lecturer');
        Route::get('/admin/users/create/student', [AdminUserController::class, 'createStudent'])->name('admin.users.create-student');
        Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::delete('/admin/users/batch-delete', [AdminUserController::class, 'batchDestroy'])->name('admin.users.batch-destroy');
        Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
        Route::put('/admin/users/{user}/password', [AdminUserController::class, 'resetPassword'])->name('admin.users.password');
        Route::get('/admin/semesters', [SemesterController::class, 'index'])->name('admin.semesters.index');
        Route::post('/admin/semesters', [SemesterController::class, 'store'])->name('admin.semesters.store');
        Route::put('/admin/semesters/{semester}/current', [SemesterController::class, 'setCurrent'])->name('admin.semesters.current');
        Route::resource('/admin/question-bank', AdminQuestionBankController::class)->names('admin.question-bank')->parameters(['question-bank' => 'question'])->except(['show']);
        Route::resource('/admin/question-sets', AdminQuestionSetController::class)->names('admin.question-sets')->parameters(['question-sets' => 'set'])->except(['show']);
        Route::post('/admin/question-sets/{set}/questions', [AdminQuestionSetController::class, 'addQuestion'])->name('admin.question-sets.questions.add');
        Route::post('/admin/question-sets/{set}/questions/custom', [AdminQuestionSetController::class, 'createQuestion'])->name('admin.question-sets.questions.custom');
        Route::put('/admin/question-set-questions/{setQuestion}', [AdminQuestionSetController::class, 'updateQuestion'])->name('admin.question-set-questions.update');
        Route::delete('/admin/question-set-questions/{setQuestion}', [AdminQuestionSetController::class, 'removeQuestion'])->name('admin.question-set-questions.destroy');
        Route::get('/admin/question-set-assignment', [LecturerQuestionSetAssignmentController::class, 'index'])->name('admin.question-set-assignment');
        Route::put('/admin/classes/{schoolClass}/question-set', [LecturerQuestionSetAssignmentController::class, 'update'])->name('admin.classes.question-set.update');
        Route::put('/admin/students/{student}/question-sets', [LecturerQuestionSetAssignmentController::class, 'updateStudent'])->name('admin.students.question-sets.update');
        Route::get('/admin/classes', [LecturerClassController::class, 'index'])->name('admin.classes.index');
        Route::post('/admin/classes', [LecturerClassController::class, 'store'])->name('admin.classes.store');
        Route::get('/admin/classes/{schoolClass}', [LecturerClassController::class, 'show'])->name('admin.classes.show');
        Route::get('/admin/classes/{schoolClass}/edit', [LecturerClassController::class, 'edit'])->name('admin.classes.edit');
        Route::put('/admin/classes/{schoolClass}', [LecturerClassController::class, 'update'])->name('admin.classes.update');
        Route::delete('/admin/classes/{schoolClass}', [LecturerClassController::class, 'destroy'])->name('admin.classes.destroy');
        Route::post('/admin/classes/{schoolClass}/students', [LecturerClassController::class, 'uploadStudents'])->name('admin.classes.students.upload');
        Route::delete('/admin/classes/{schoolClass}/students/{student}', [LecturerClassController::class, 'removeStudent'])->name('admin.classes.students.remove');
        Route::put('/admin/classes/{schoolClass}/students/{student}/status', [LecturerClassController::class, 'disableStudent'])->name('admin.classes.students.status');
        Route::put('/admin/classes/{schoolClass}/students/{student}/password', [LecturerClassController::class, 'resetStudentPassword'])->name('admin.classes.students.password');
        Route::post('/admin/terminal/class-settings', [TerminalController::class, 'lecturerSettings'])->name('admin.terminal.class-settings');
        Route::get('/admin/feedback/questions', [AdminFeedbackQuestionController::class, 'index'])->name('admin.feedback.index');
        Route::get('/admin/feedback/questions/create', [AdminFeedbackQuestionController::class, 'create'])->name('admin.feedback.create');
        Route::post('/admin/feedback/questions', [AdminFeedbackQuestionController::class, 'store'])->name('admin.feedback.store');
        Route::get('/admin/feedback/questions/{feedbackQuestion}/edit', [AdminFeedbackQuestionController::class, 'edit'])->name('admin.feedback.edit');
        Route::put('/admin/feedback/questions/{feedbackQuestion}', [AdminFeedbackQuestionController::class, 'update'])->name('admin.feedback.update');
        Route::delete('/admin/feedback/questions/{feedbackQuestion}', [AdminFeedbackQuestionController::class, 'destroy'])->name('admin.feedback.destroy');
        Route::put('/admin/feedback/questions/{feedbackQuestion}/toggle', [AdminFeedbackQuestionController::class, 'toggle'])->name('admin.feedback.toggle');
    });

    Route::middleware(RoleMiddleware::class . ':admin,lecturer')->group(function () {
        Route::get('/leaderboard', [ExamController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/scoreboard', [ScoreboardController::class, 'index'])->name('scoreboard');
        Route::get('/notifications/manage', [NotificationController::class, 'manage'])->name('notifications.manage');
        Route::post('/notifications/manage', [NotificationController::class, 'store'])->name('notifications.store');
        Route::delete('/notifications/manage/batch-delete', [NotificationController::class, 'batchDestroy'])->name('notifications.batch-destroy');
        Route::put('/notifications/{notification}/toggle', [NotificationController::class, 'toggle'])->name('notifications.toggle');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/feedback/control', [CourseFeedbackControlController::class, 'index'])->name('feedback.control');
        Route::post('/feedback/control', [CourseFeedbackControlController::class, 'store'])->name('feedback.control.store');
        Route::put('/feedback/control/{control}/toggle', [CourseFeedbackControlController::class, 'toggle'])->name('feedback.control.toggle');
        Route::get('/feedback/summary', [FeedbackReportController::class, 'dashboard'])->name('feedback.summary');
        Route::get('/feedback/raw', [FeedbackReportController::class, 'raw'])->name('feedback.raw');
        Route::get('/feedback/raw/export', [FeedbackReportController::class, 'export'])->name('feedback.raw.export');
        Route::get('/question-sets', [QuestionSetController::class, 'index'])->name('question-sets.index');
    });
});
