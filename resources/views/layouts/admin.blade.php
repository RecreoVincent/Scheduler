<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Admin Portal') | Scheduler</title>

    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --navy: #172554;
            --text: #334155;
            --muted: #64748b;
            --background: #f1f5f9;
            --surface: #ffffff;
            --border: #dbeafe;
            --sidebar: #eff6ff;
            --danger: #dc2626;
            --success: #15803d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top right, #dbeafe, transparent 30%),
                var(--background);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 255px;
            height: 100vh;
            padding: 24px 18px;
            background: rgba(239, 246, 255, 0.96);
            border-right: 1px solid var(--border);
            overflow-y: auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 35px;
            padding: 0 10px;
            font-size: 22px;
            font-weight: 700;
            color: var(--navy);
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            color: white;
            background: var(--primary);
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(59, 130, 246, .25);
        }

        .menu-label {
            margin: 22px 12px 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #94a3b8;
            text-transform: uppercase;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 7px;
            padding: 12px 14px;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            border-radius: 11px;
            transition: .2s;
        }

        .menu-link:hover,
        .menu-link.active {
            color: var(--primary-dark);
            background: #ffffff;
            box-shadow: 0 7px 18px rgba(15, 23, 42, .06);
        }

        .menu-icon { width: 20px; flex: 0 0 20px; color: #64748b; font-size: 18px; line-height: 1; text-align: center; }
        .menu-link:hover .menu-icon, .menu-link.active .menu-icon { color: var(--primary-dark); }

        .main {
            width: calc(100% - 255px);
            margin-left: 255px;
        }

        .topbar {
            min-height: 76px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: rgba(255, 255, 255, .88);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }

        .topbar h1 {
            font-size: 22px;
            color: var(--navy);
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .profile-avatar {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            font-weight: 700;
            color: white;
            background: var(--primary);
            border-radius: 50%;
        }

        .profile-name {
            font-size: 14px;
            font-weight: 700;
        }

        .profile-role {
            font-size: 12px;
            color: var(--muted);
        }

        .content {
            padding: 30px 32px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-header h2 {
            margin-bottom: 5px;
            color: var(--navy);
        }

        .page-header p {
            font-size: 14px;
            color: var(--muted);
        }

        .card {
            padding: 24px;
            background: rgba(255, 255, 255, .92);
            border: 1px solid var(--border);
            border-radius: 17px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .05);
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            padding: 11px 17px;
            font-size: 14px;
            font-weight: 700;
            color: white;
            background: var(--primary);
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        .button:hover {
            background: var(--primary-dark);
        }

        .button-secondary {
            color: var(--primary-dark);
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .button-secondary:hover {
            background: #dbeafe;
        }

        .button-danger {
            color: var(--danger);
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .button-danger:hover {
            background: #fee2e2;
        }

        body.modal-open {
            overflow: hidden;
        }

        .notification-modal[hidden] {
            display: none;
        }

        .notification-modal {
            position: fixed;
            z-index: 2000;
            inset: 0;
            display: grid;
            place-items: center;
            padding: 20px;
            background: rgba(15, 23, 42, .58);
            backdrop-filter: blur(3px);
        }

        .notification-dialog {
            width: min(440px, 100%);
            padding: 30px;
            text-align: center;
            background: white;
            border-radius: 18px;
            box-shadow: 0 25px 65px rgba(15, 23, 42, .28);
        }

        .notification-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            margin: 0 auto 17px;
            font-size: 28px;
            font-weight: 700;
            border-radius: 50%;
        }

        .notification-icon.success {
            color: var(--success);
            background: #dcfce7;
        }

        .notification-icon.error {
            color: var(--danger);
            background: #fee2e2;
        }

        .notification-dialog h2 {
            margin-bottom: 9px;
            color: var(--navy);
        }

        .notification-dialog p,
        .notification-errors {
            color: var(--muted);
            line-height: 1.6;
        }

        .notification-errors {
            max-height: 180px;
            margin: 14px 0 0;
            padding: 12px 16px 12px 32px;
            overflow-y: auto;
            text-align: left;
            background: #fef2f2;
            border-radius: 10px;
        }

        .notification-close {
            min-width: 120px;
            margin-top: 22px;
        }

        .logout-button {
            width: 100%;
            text-align: left;
            color: #475569;
            background: transparent;
            border: none;
            cursor: pointer;
        }

        @media (max-width: 850px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .app {
                display: block;
            }

            .main {
                width: 100%;
                margin-left: 0;
            }

            .content,
            .topbar {
                padding-left: 18px;
                padding-right: 18px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    @include('layouts.partials.sidebar-toggle-styles')
    @stack('styles')
    @include('layouts.partials.portal-unified-theme')
</head>

<body>
<div class="app">

    <aside id="portalSidebar" class="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            <span class="brand-icon brand-icon--mcc"><img src="{{ asset('images/mcc-college-logo.png') }}" alt="MCC logo"></span>
            <span class="brand-copy"><strong>MCC | Scheduler</strong><small>Admin Portal</small></span>
        </a>
        <div class="portal-chip">Institution administration</div>

        <p class="menu-label">Overview</p>

        <a href="{{ route('admin.dashboard') }}"
           class="menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="menu-icon" aria-hidden="true">⌂</span>
            Dashboard
        </a>

        <p class="menu-label">Management</p>

        <a href="{{ route('admin.users.index') }}"
           class="menu-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <span class="menu-icon" aria-hidden="true">♙</span>
            User Accounts
        </a>

        <p class="menu-label">Account</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <input type="hidden" name="role" value="admin">

            <button type="submit" class="menu-link logout-button">
                <span class="menu-icon" aria-hidden="true">↪</span>
                Sign Out
            </button>
        </form>
    </aside>
    <button id="sidebarBackdrop" class="sidebar-backdrop" type="button" aria-label="Close navigation menu"></button>

    <main class="main">

        <header class="topbar">
            <div class="topbar-start">
                @include('layouts.partials.sidebar-toggle')
                <div><span class="topbar-label">Administrator workspace</span><h1>@yield('page-title', 'Admin Portal')</h1></div>
            </div>

            <div class="profile">
                <div class="profile-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>

                <div>
                    <div class="profile-name">
                        {{ auth()->user()->name }}
                    </div>

                    <div class="profile-role">
                        Administrator
                    </div>
                </div>
            </div>
        </header>

        <section class="content">
            @yield('content')
        </section>
    </main>

</div>

@php
    $notificationIsSuccess = session()->has('success');
    $notificationIsError = session()->has('error') || $errors->any();
@endphp

@if ($notificationIsSuccess || $notificationIsError)
    <div id="notificationModal" class="notification-modal" role="presentation">
        <section class="notification-dialog" role="dialog" aria-modal="true" aria-labelledby="notificationTitle">
            <div class="notification-icon {{ $notificationIsSuccess ? 'success' : 'error' }}" aria-hidden="true">
                {{ $notificationIsSuccess ? '✓' : '!' }}
            </div>

            <h2 id="notificationTitle">
                {{ $notificationIsSuccess ? 'Success' : 'Action unsuccessful' }}
            </h2>

            @if ($notificationIsSuccess)
                <p>{{ session('success') }}</p>
            @elseif (session('error'))
                <p>{{ session('error') }}</p>
            @endif

            @if ($errors->any())
                <ul class="notification-errors">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <button type="button" id="closeNotificationModal" class="button notification-close">OK</button>
        </section>
    </div>
@endif

@stack('scripts')
@include('layouts.partials.sidebar-toggle-script')
@include('layouts.partials.auto-filter-script')

@if ($notificationIsSuccess || $notificationIsError)
<script>
    (() => {
        const modal = document.getElementById('notificationModal');
        const closeButton = document.getElementById('closeNotificationModal');

        function closeNotification() {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
        }

        document.body.classList.add('modal-open');
        closeButton.focus();
        closeButton.addEventListener('click', closeNotification);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeNotification();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeNotification();
            }
        });
    })();
</script>
@endif
</body>
</html>
