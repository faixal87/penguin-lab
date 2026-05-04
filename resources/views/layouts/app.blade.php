<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ShellFix')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/shellfix-cyberpunk.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="{{ request()->routeIs('login') ? 'login-screen' : 'shellfix-app' }}">
    @if (request()->routeIs('login'))
        @yield('content')
    @else
    <div class="mobile-scrim" data-sidebar-close></div>

    <nav class="navbar navbar-expand-lg navbar-dark shellfix-topbar">
        <div class="container-fluid">
            <button class="btn shellfix-icon-btn sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <a class="navbar-brand fw-semibold ms-2" href="{{ route('dashboard') }}">MY Penguin-LAB</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNavbar">
                <div class="ms-auto d-flex align-items-center gap-3">
                    @auth
                        <span class="navbar-text d-none d-lg-inline">Semester: {{ $layoutCurrentSemester?->name ?? 'Not set' }}</span>
                        <button class="btn btn-sm btn-neon-outline position-relative" type="button" data-bs-toggle="offcanvas" data-bs-target="#notificationCenter" aria-controls="notificationCenter" aria-label="Open notifications">
                            &#128276;
                            @if (($layoutUnreadNotificationCount ?? 0) > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger" data-notification-count>{{ $layoutUnreadNotificationCount }}</span>
                            @else
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger d-none" data-notification-count>0</span>
                            @endif
                        </button>
                        <button class="btn btn-sm btn-neon-outline" type="button" data-notification-mute aria-label="Toggle notification sound">Sound On</button>
                        <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ auth()->user()->name }}" class="rounded-circle shellfix-avatar">
                        <span class="navbar-text">{{ auth()->user()->name }} <span class="role-chip">{{ auth()->user()->role }}</span></span>
                        <form method="POST" action="{{ route('logout') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-neon-outline">Logout</button>
                        </form>
                    @else
                        <a class="btn btn-sm btn-neon-outline" href="{{ route('login') }}">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div class="shellfix-shell">
            <aside class="shellfix-sidebar p-3" id="shellfixSidebar">
                <div class="sidebar-brand">
                    <span class="sidebar-logo">&#128039;</span>
                    <span class="sidebar-title">Control Deck</span>
                </div>
                <nav class="nav flex-column gap-1">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>&#8962;</span><em>Dashboard</em></a>
                    @auth
                        @php
                            $user = auth()->user();
                            $canAccessTerminal = $user->isStudent() && ((bool) $user->terminal_enabled || $user->enrolledClasses()->where('terminal_enabled', true)->exists());
                        @endphp

                        @if ($user->isStudent())
                            <button class="nav-link text-start" data-bs-toggle="collapse" data-bs-target="#menuLearning" type="button"><span>&#9000;</span><em>Learning</em></button>
                            <div class="collapse show" id="menuLearning">
                                <a class="nav-link {{ request()->routeIs('scenarios*') ? 'active' : '' }}" href="{{ route('scenarios') }}"><span>&#9000;</span><em>Scenarios / My Sets</em></a>
                                @if ($canAccessTerminal)
                                    <a class="nav-link {{ request()->routeIs('terminal.index') ? 'active' : '' }}" href="{{ route('terminal.index') }}"><span>&gt;_</span><em>Terminal</em></a>
                                @endif
                                <a class="nav-link {{ request()->routeIs('results') ? 'active' : '' }}" href="{{ route('results') }}"><span>&#9671;</span><em>Results</em></a>
                            </div>

                            @if ($layoutShowFeedbackMenu ?? false)
                                <button class="nav-link text-start" data-bs-toggle="collapse" data-bs-target="#menuFeedback" type="button"><span>&#10022;</span><em>Feedback & Research</em></button>
                                <div class="collapse show" id="menuFeedback">
                                    <a class="nav-link {{ request()->routeIs('feedback.form') ? 'active' : '' }}" href="{{ route('feedback.form') }}"><span>&#10022;</span><em>Course Feedback</em></a>
                                </div>
                            @endif
                        @endif

                        @if ($user->isAdmin() || $user->isLecturer())
                            <button class="nav-link text-start" data-bs-toggle="collapse" data-bs-target="#menuClass" type="button"><span>&#9638;</span><em>Class Management</em></button>
                            <div class="collapse show" id="menuClass">
                                @if ($user->isAdmin())
                                    <a class="nav-link {{ request()->routeIs('admin.classes*') ? 'active' : '' }}" href="{{ route('admin.classes.index') }}"><span>&#9638;</span><em>Classes</em></a>
                                    <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users.index', ['role' => 'student']) }}"><span>&#9678;</span><em>Students</em></a>
                                @else
                                    <a class="nav-link {{ request()->routeIs('lecturer.classes*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>&#9638;</span><em>Classes</em></a>
                                @endif
                                <a class="nav-link {{ request()->routeIs('scoreboard') ? 'active' : '' }}" href="{{ route('scoreboard') }}"><span>&#9636;</span><em>Scoreboard</em></a>
                            </div>

                            <button class="nav-link text-start" data-bs-toggle="collapse" data-bs-target="#menuQuestions" type="button"><span>?</span><em>Question Management</em></button>
                            <div class="collapse show" id="menuQuestions">
                                <a class="nav-link {{ request()->routeIs('question-sets*') ? 'active' : '' }}" href="{{ route('question-sets.index') }}"><span>?</span><em>Question Set Viewer</em></a>
                                @if ($user->isAdmin())
                                    <a class="nav-link {{ request()->routeIs('admin.question-bank*') ? 'active' : '' }}" href="{{ route('admin.question-bank.index') }}"><span>&#9000;</span><em>Question Bank</em></a>
                                    <a class="nav-link {{ request()->routeIs('admin.question-sets*') ? 'active' : '' }}" href="{{ route('admin.question-sets.index') }}"><span>&#9636;</span><em>Question Sets</em></a>
                                    <a class="nav-link {{ request()->routeIs('admin.question-set-assignment') ? 'active' : '' }}" href="{{ route('admin.question-set-assignment') }}"><span>&#9638;</span><em>Assign Sets</em></a>
                                @else
                                    <a class="nav-link {{ request()->routeIs('lecturer.question-bank*') ? 'active' : '' }}" href="{{ route('lecturer.question-bank.index') }}"><span>&#9000;</span><em>Question Bank</em></a>
                                    <a class="nav-link {{ request()->routeIs('lecturer.question-sets*') ? 'active' : '' }}" href="{{ route('lecturer.question-sets.index') }}"><span>&#9636;</span><em>Question Sets</em></a>
                                    <a class="nav-link {{ request()->routeIs('lecturer.question-set-assignment') ? 'active' : '' }}" href="{{ route('lecturer.question-set-assignment') }}"><span>&#9638;</span><em>Assign Sets</em></a>
                                @endif
                            </div>

                            <button class="nav-link text-start" data-bs-toggle="collapse" data-bs-target="#menuResearch" type="button"><span>&#10022;</span><em>Feedback & Research</em></button>
                            <div class="collapse show" id="menuResearch">
                                <a class="nav-link {{ request()->routeIs('feedback.control') ? 'active' : '' }}" href="{{ route('feedback.control') }}"><span>&#9881;</span><em>Course Feedback</em></a>
                                <a class="nav-link {{ request()->routeIs('feedback.summary') ? 'active' : '' }}" href="{{ route('feedback.summary') }}"><span>&#10022;</span><em>Feedback Dashboard</em></a>
                                <a class="nav-link {{ request()->routeIs('feedback.raw') ? 'active' : '' }}" href="{{ route('feedback.raw') }}"><span>&#8801;</span><em>Raw Feedback Data</em></a>
                            </div>
                        @endif

                        @if ($user->isAdmin())
                            <button class="nav-link text-start" data-bs-toggle="collapse" data-bs-target="#menuSystem" type="button"><span>&#9881;</span><em>System Administration</em></button>
                            <div class="collapse show" id="menuSystem">
                                <a class="nav-link {{ request()->routeIs('admin.semesters*') ? 'active' : '' }}" href="{{ route('admin.semesters.index') }}"><span>&#9673;</span><em>Semester Settings</em></a>
                                <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span>&#9678;</span><em>Manage Users</em></a>
                                <a class="nav-link" href="{{ route('dashboard') }}#login-logs"><span>&#9673;</span><em>Login Security Logs</em></a>
                                <a class="nav-link {{ request()->routeIs('notifications.manage') ? 'active' : '' }}" href="{{ route('notifications.manage') }}"><span>&#128276;</span><em>Notifications</em></a>
                                <a class="nav-link {{ request()->routeIs('admin.terminal.settings') ? 'active' : '' }}" href="{{ route('admin.terminal.settings') }}"><span>&gt;_</span><em>Terminal Management</em></a>
                                <a class="nav-link {{ request()->routeIs('admin.feedback*') ? 'active' : '' }}" href="{{ route('admin.feedback.index') }}"><span>&#9676;</span><em>Feedback Questions</em></a>
                            </div>
                        @elseif ($user->isLecturer())
                            <a class="nav-link {{ request()->routeIs('notifications.manage') ? 'active' : '' }}" href="{{ route('notifications.manage') }}"><span>&#128276;</span><em>Notifications</em></a>
                            <a class="nav-link {{ request()->routeIs('lecturer.terminal.settings') ? 'active' : '' }}" href="{{ route('lecturer.terminal.settings') }}"><span>&gt;_</span><em>Terminal Settings</em></a>
                        @endif

                        <a class="nav-link {{ request()->routeIs('notifications.index') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><span>&#128276;</span><em>Notification Center</em></a>
                        <a class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}"><span>&#9787;</span><em>Profile</em></a>
                    @else
                        <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}"><span>&#8618;</span><em>Login</em></a>
                    @endauth
                </nav>
            </aside>

            <main class="shellfix-main p-4">
                <div class="page-header d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h1 class="h3 mb-1 neon-title">@yield('page-title')</h1>
                        <p class="text-secondary mb-0">@yield('page-description')</p>
                    </div>
                </div>

                @yield('content')
            </main>
    </div>

    @auth
        @if ($layoutNotificationPopup ?? null)
            <div class="notification-float card border-0 shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h2 class="h6 mb-1">{{ $layoutNotificationPopup->title }}</h2>
                            <p class="mb-2 text-secondary small">{{ $layoutNotificationPopup->message }}</p>
                            <div class="d-flex gap-2 flex-wrap">
                                @if ($layoutNotificationPopup->title === 'Course Feedback is now available' && auth()->user()->isStudent())
                                    <a href="{{ route('feedback.form') }}" class="btn btn-sm btn-primary">Open Feedback</a>
                                @endif
                                <form method="POST" action="{{ route('notifications.read', $layoutNotificationPopup) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Mark Read</button>
                                </form>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('notifications.dismiss', $layoutNotificationPopup) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Dismiss notification">&times;</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="notificationCenter" aria-labelledby="notificationCenterLabel">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h5" id="notificationCenterLabel">Notifications</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body" data-notification-list>
                @forelse (($layoutNotifications ?? collect()) as $notice)
                    @php($read = $notice->reads->first()?->read_at)
                    @php($dismissed = $notice->reads->first()?->dismissed_at)
                    <div class="card border-0 shadow-sm mb-3 {{ $read ? '' : 'notification-unread' }}" data-notification-existing-id="{{ $notice->id }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between gap-2">
                                <h3 class="h6 mb-1">{{ $notice->title }}</h3>
                                @if (! $read)
                                    <span class="badge text-bg-info">Unread</span>
                                @elseif ($dismissed)
                                    <span class="badge text-bg-secondary">Dismissed</span>
                                @endif
                            </div>
                            <p class="small text-secondary mb-3">{{ $notice->message }}</p>
                            <div class="d-flex gap-2 flex-wrap">
                                @if ($notice->title === 'Course Feedback is now available' && auth()->user()->isStudent())
                                    <a href="{{ route('feedback.form') }}" class="btn btn-sm btn-primary">Open Feedback</a>
                                @endif
                                <form method="POST" action="{{ route('notifications.read', $notice) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Mark Read</button>
                                </form>
                                <form method="POST" action="{{ route('notifications.dismiss', $notice) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Dismiss</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-secondary">No notifications yet.</p>
                @endforelse
                <a href="{{ route('notifications.index') }}" class="btn btn-neon-outline w-100 mt-2">Open Notification Center</a>
            </div>
        </div>
    @endauth
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @auth
        <script>
            (() => {
                const csrfToken = @json(csrf_token());
                const pollUrl = @json(route('notifications.unread-json'));
                const soundUrl = @json(asset('assets/sounds/notify.mp3'));
                const feedbackUrl = @json(route('feedback.form'));
                const seenIds = new Set(Array.from(document.querySelectorAll('[data-notification-existing-id]')).map((item) => Number(item.dataset.notificationExistingId)));
                const list = document.querySelector('[data-notification-list]');
                const countBadge = document.querySelector('[data-notification-count]');
                const muteButton = document.querySelector('[data-notification-mute]');
                const sound = new Audio(soundUrl);
                let muted = localStorage.getItem('shellfix_notifications_muted') === '1';
                let primed = false;
                let polling = false;
                let lastToastAt = 0;

                function updateMuteButton() {
                    if (!muteButton) {
                        return;
                    }

                    muteButton.textContent = muted ? 'Sound Off' : 'Sound On';
                    muteButton.classList.toggle('btn-outline-warning', muted);
                }

                function updateCount(count) {
                    if (!countBadge) {
                        return;
                    }

                    countBadge.textContent = String(count || 0);
                    countBadge.classList.toggle('d-none', (count || 0) <= 0);
                }

                function pollNotifications() {
                    if (polling) {
                        return;
                    }

                    polling = true;

                    fetch(pollUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then((response) => response.ok ? response.json() : null)
                        .then((payload) => {
                            if (!payload) {
                                return;
                            }

                            updateCount(payload.unread_count);

                            (payload.notifications || []).forEach((notification) => {
                                if (seenIds.has(notification.id)) {
                                    return;
                                }

                                seenIds.add(notification.id);

                                if (list) {
                                    list.insertAdjacentHTML('afterbegin', notificationCard(notification));
                                }

                                if (primed) {
                                    showToast(notification);
                                }
                            });

                            primed = true;
                        })
                        .catch(() => {})
                        .finally(() => {
                            polling = false;
                        });
                }

                function showToast(notification) {
                    const now = Date.now();

                    if (now - lastToastAt < 450) {
                        window.setTimeout(() => showToast(notification), 500);
                        return;
                    }

                    lastToastAt = now;
                    document.querySelector('.notification-float[data-poll-toast="1"]')?.remove();

                    const toast = document.createElement('div');
                    toast.className = 'notification-float notification-slide-in card border-0 shadow-lg';
                    toast.dataset.pollToast = '1';
                    toast.dataset.notificationId = notification.id;
                    toast.innerHTML = `
                        <div class="card-body">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <h2 class="h6 mb-1">${escapeHtml(notification.title)}</h2>
                                    <p class="mb-2 text-secondary small">${escapeHtml(notification.message)}</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        ${feedbackButton(notification)}
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-notification-dismiss="${notification.dismiss_url}" aria-label="Dismiss notification">&times;</button>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(toast);
                    window.setTimeout(() => toast.classList.add('is-visible'), 20);
                    playSound();
                }

                function notificationCard(notification) {
                    return `
                        <div class="card border-0 shadow-sm mb-3 notification-unread" data-notification-existing-id="${notification.id}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-2">
                                    <h3 class="h6 mb-1">${escapeHtml(notification.title)}</h3>
                                    <span class="badge text-bg-info">Unread</span>
                                </div>
                                <p class="small text-secondary mb-3">${escapeHtml(notification.message)}</p>
                                <div class="d-flex gap-2 flex-wrap">
                                    ${feedbackButton(notification)}
                                    <form method="POST" action="${notification.read_url}">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Mark Read</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-notification-dismiss="${notification.dismiss_url}">Dismiss</button>
                                </div>
                            </div>
                        </div>
                    `;
                }

                function feedbackButton(notification) {
                    if (notification.title !== 'Course Feedback is now available') {
                        return '';
                    }

                    return `<a href="${notification.feedback_url || feedbackUrl}" class="btn btn-sm btn-primary">Open Feedback</a>`;
                }

                function dismissNotification(url, button) {
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                        .then((response) => response.ok ? response.json() : null)
                        .then((payload) => {
                            if (payload && Object.prototype.hasOwnProperty.call(payload, 'unread_count')) {
                                updateCount(payload.unread_count);
                            }
                        })
                        .catch(() => {})
                        .finally(() => {
                            button.closest('[data-notification-existing-id]')?.remove();
                            button.closest('[data-poll-toast]')?.remove();
                        });
                }

                function playSound() {
                    if (muted) {
                        return;
                    }

                    sound.currentTime = 0;
                    sound.play().catch(playFallbackBeep);
                }

                function playFallbackBeep() {
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;

                        if (!AudioContext) {
                            return;
                        }

                        const context = new AudioContext();
                        const oscillator = context.createOscillator();
                        const gain = context.createGain();
                        oscillator.type = 'sine';
                        oscillator.frequency.value = 880;
                        gain.gain.value = 0.04;
                        oscillator.connect(gain);
                        gain.connect(context.destination);
                        oscillator.start();
                        oscillator.stop(context.currentTime + 0.12);
                    } catch (error) {
                    }
                }

                function escapeHtml(value) {
                    const div = document.createElement('div');
                    div.textContent = value || '';
                    return div.innerHTML;
                }

                muteButton?.addEventListener('click', () => {
                    muted = !muted;
                    localStorage.setItem('shellfix_notifications_muted', muted ? '1' : '0');
                    updateMuteButton();
                });

                document.addEventListener('click', (event) => {
                    const dismissButton = event.target.closest('[data-notification-dismiss]');

                    if (dismissButton) {
                        dismissNotification(dismissButton.dataset.notificationDismiss, dismissButton);
                    }
                });

                updateMuteButton();
                pollNotifications();
                window.setInterval(pollNotifications, 5000);
            })();
        </script>
    @endauth
    <script src="{{ asset('js/shellfix-ui.js') }}"></script>
    @stack('scripts')
</body>
</html>
