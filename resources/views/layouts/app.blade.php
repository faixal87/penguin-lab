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
                        @if (auth()->user()->profilePhotoUrl())
                            <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ auth()->user()->name }}" class="rounded-circle shellfix-avatar">
                        @endif
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
                        @if (auth()->user()->isStudent())
                            @php
                                $canAccessTerminal = (bool) auth()->user()->terminal_enabled
                                    || auth()->user()->enrolledClasses()->where('terminal_enabled', true)->exists();
                            @endphp
                            <a class="nav-link {{ request()->routeIs('scenarios*') ? 'active' : '' }}" href="{{ route('scenarios') }}"><span>&#9000;</span><em>Scenarios</em></a>
                            <a class="nav-link {{ request()->routeIs('results') ? 'active' : '' }}" href="{{ route('results') }}"><span>&#9671;</span><em>Results</em></a>
                            @if ($canAccessTerminal)
                                <a class="nav-link {{ request()->routeIs('terminal.index') ? 'active' : '' }}" href="{{ route('terminal.index') }}"><span>&gt;_</span><em>Terminal</em></a>
                            @endif
                        @endif

                        @if (auth()->user()->isAdmin() || auth()->user()->isLecturer())
                            <a class="nav-link {{ request()->routeIs('leaderboard') ? 'active' : '' }}" href="{{ route('leaderboard') }}"><span>&#9650;</span><em>Leaderboard</em></a>
                            <a class="nav-link {{ request()->routeIs('scoreboard') ? 'active' : '' }}" href="{{ route('scoreboard') }}"><span>&#9636;</span><em>Scoreboard</em></a>
                            <a class="nav-link {{ request()->routeIs('question-sets*') ? 'active' : '' }}" href="{{ route('question-sets.index') }}"><span>?</span><em>Question Sets</em></a>
                            <a class="nav-link {{ request()->routeIs('feedback.summary') ? 'active' : '' }}" href="{{ route('feedback.summary') }}"><span>&#10022;</span><em>Feedback Summary</em></a>
                            <a class="nav-link {{ request()->routeIs('feedback.raw') ? 'active' : '' }}" href="{{ route('feedback.raw') }}"><span>&#8801;</span><em>Raw Feedback</em></a>
                            @if (auth()->user()->isLecturer())
                                <a class="nav-link {{ request()->routeIs('lecturer.terminal.settings') ? 'active' : '' }}" href="{{ route('lecturer.terminal.settings') }}"><span>&gt;_</span><em>Terminal Settings</em></a>
                            @endif
                        @endif

                        @if (auth()->user()->isAdmin())
                            <div class="sidebar-section">Admin</div>
                            <a class="nav-link {{ request()->routeIs('admin.users.index') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span>&#9678;</span><em>Manage Users</em></a>
                            <a class="nav-link {{ request()->routeIs('admin.users.create-lecturer') ? 'active' : '' }}" href="{{ route('admin.users.create-lecturer') }}"><span>+</span><em>Add Lecturer</em></a>
                            <a class="nav-link {{ request()->routeIs('admin.users.create-student') ? 'active' : '' }}" href="{{ route('admin.users.create-student') }}"><span>+</span><em>Add Student</em></a>
                            <a class="nav-link {{ request()->routeIs('admin.users.edit') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span>&#9998;</span><em>Edit User</em></a>
                            <a class="nav-link {{ request()->routeIs('admin.classes*') ? 'active' : '' }}" href="{{ route('admin.classes.index') }}"><span>&#9638;</span><em>Manage Classes</em></a>
                            <a class="nav-link {{ request()->routeIs('admin.feedback*') ? 'active' : '' }}" href="{{ route('admin.feedback.index') }}"><span>&#9676;</span><em>Feedback Questions</em></a>
                            <a class="nav-link {{ request()->routeIs('admin.terminal.settings') ? 'active' : '' }}" href="{{ route('admin.terminal.settings') }}"><span>&gt;_</span><em>Terminal Settings</em></a>
                        @endif

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
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/shellfix-ui.js') }}"></script>
    @stack('scripts')
</body>
</html>
