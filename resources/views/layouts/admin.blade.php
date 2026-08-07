<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.favicon')
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
    <style>
        body.admin-institution-portal,
        body.admin-institution-portal .app,
        body.admin-institution-portal .main,
        body.admin-institution-portal .content {
            background-color:#fff;
        }

        body.admin-institution-portal .main {
            position:relative;
            isolation:isolate;
        }

        body.admin-institution-portal .main::before {
            content:'';
            position:fixed;
            z-index:0;
            top:82px;
            right:0;
            bottom:0;
            left:0;
            background:#fff;
            opacity:1;
            pointer-events:none;
            animation:none;
            transform:none;
            will-change:auto;
        }

        @keyframes admin-background-drift {
            0% {
                background-position:42% 45%;
                transform:scale(1.08) translate3d(-1.5%, -1%, 0);
            }
            50% {
                background-position:58% 52%;
                transform:scale(1.12) translate3d(1.5%, 1%, 0);
            }
            100% {
                background-position:48% 58%;
                transform:scale(1.08) translate3d(1%, -1.5%, 0);
            }
        }

        body.admin-institution-portal .main::after {
            content:'';
            position:fixed;
            z-index:1;
            top:82px;
            right:0;
            bottom:0;
            left:220px;
            background-image:var(--admin-institution-logo);
            background-repeat:no-repeat;
            background-position:50% 50%;
            background-size:min(70vmin,760px) min(70vmin,760px);
            opacity:.3;
            pointer-events:none;
        }

        body.admin-institution-portal .topbar,
        body.admin-institution-portal .content {
            position:relative;
            z-index:2;
        }

        body.admin-institution-portal .content {
            background-color:transparent;
        }

        body.admin-institution-portal .content .card,
        body.admin-institution-portal .content .welcome,
        body.admin-institution-portal .content .welcome-card,
        body.admin-institution-portal .content .stat-card {
            color:#24152f;
            background-color:rgba(255,255,255,.46) !important;
            background-image:none !important;
            border-color:rgba(69,6,147,.25) !important;
        }

        body.admin-institution-portal .content .card :is(h1,h2,h3,h4,p,span,strong,label,td,th),
        body.admin-institution-portal .content .welcome :is(h1,h2,h3,h4,p,span,strong,label),
        body.admin-institution-portal .content .welcome-card :is(h1,h2,h3,h4,p,span,strong,label),
        body.admin-institution-portal .content .stat-card :is(h1,h2,h3,h4,p,span,strong,label) {
            color:#24152f !important;
        }

        body.admin-institution-portal .content .card .badge,
        body.admin-institution-portal .content .card .role-badge {
            color:#450693 !important;
        }

        body.admin-institution-portal .content .card .filters {
            background-color:rgba(255,255,255,.4);
            background-image:none;
            border-color:rgba(69,6,147,.2);
        }

        body.admin-institution-portal .content .card .filters .input {
            color:#271d2e;
        }

        body.admin-institution-portal .content .card .filters .input::placeholder {
            color:#55495e;
            opacity:1;
        }

        body.admin-institution-portal .content .card .table-wrap,
        body.admin-institution-portal .content .card .table-wrapper {
            background:rgba(255,255,255,.52);
        }

        body.admin-institution-portal .content .card table {
            background:transparent;
        }

        body.admin-institution-portal .content .card th {
            color:#302039;
            background:rgba(248,243,251,.68);
        }

        body.admin-institution-portal .content .card td,
        body.admin-institution-portal .content .card p,
        body.admin-institution-portal .content .card span {
            color:#302638;
        }

        body.admin-institution-portal .content .card .portal-page-button.is-active {
            color:#fff !important;
        }

        body.admin-institution-portal .content .card tbody tr:hover {
            background:rgba(255,255,255,.46);
        }

        .profile-menu { position:relative; }
        .profile-menu summary { list-style:none; cursor:pointer; user-select:none; }
        .profile-menu summary::-webkit-details-marker { display:none; }
        .profile-menu summary::after { content:'⌄'; margin-left:4px; color:var(--primary); font-size:15px; font-weight:900; }
        .profile-menu[open] summary::after { content:'⌃'; }
        .profile-dropdown { position:absolute; z-index:1300; top:calc(100% + 10px); right:0; width:190px; padding:8px; background:rgba(255,255,255,.97); border:1px solid rgba(69,6,147,.2); border-radius:12px; box-shadow:0 18px 45px rgba(31,5,57,.22); }
        .profile-dropdown form { margin:0; }
        .profile-dropdown-action { width:100%; min-height:40px; display:flex; align-items:center; padding:10px 12px; color:#2d045f; text-align:left; background:transparent; border:0; border-radius:8px; font-size:12px; font-weight:800; cursor:pointer; }
        .profile-dropdown-action:hover { color:#fff; background:var(--primary); }

        body.admin-institution-portal .profile-menu .profile {
            min-width:190px;
            padding:7px 12px 7px 8px;
            color:#fff;
            background:linear-gradient(135deg,rgba(255,255,255,.16),rgba(255,255,255,.08));
            border:1px solid rgba(255,255,255,.25);
            border-radius:13px;
            box-shadow:0 10px 28px rgba(24,3,48,.2),inset 0 1px 0 rgba(255,255,255,.12);
            backdrop-filter:blur(10px);
            transition:background .2s ease,border-color .2s ease,transform .2s ease;
        }
        body.admin-institution-portal .profile-menu .profile:hover,
        body.admin-institution-portal .profile-menu[open] .profile {
            background:linear-gradient(135deg,rgba(255,255,255,.22),rgba(255,255,255,.12));
            border-color:rgba(255,255,255,.38);
            transform:translateY(-1px);
        }
        body.admin-institution-portal .profile-menu .profile-avatar {
            color:#450693;
            background:rgba(255,255,255,.94);
            box-shadow:0 6px 16px rgba(26,2,45,.18);
            overflow:hidden;
        }
        body.admin-institution-portal .profile-menu .profile-avatar img { width:100%;height:100%;display:block;object-fit:cover; }
        body.admin-institution-portal .profile-menu .profile-name { color:#fff;font-size:12px; }
        body.admin-institution-portal .profile-menu .profile-role { color:rgba(255,255,255,.68);font-size:9px; }
        body.admin-institution-portal .profile-menu summary::after {
            width:7px;
            height:7px;
            margin:0 2px 4px auto;
            content:'';
            border-right:2px solid rgba(255,255,255,.82);
            border-bottom:2px solid rgba(255,255,255,.82);
            transform:rotate(45deg);
            transition:transform .2s ease;
        }
        body.admin-institution-portal .profile-menu[open] summary::after { margin-bottom:-2px;transform:rotate(225deg); }
        body.admin-institution-portal .profile-dropdown {
            background:linear-gradient(155deg,rgba(45,4,95,.98),rgba(69,6,147,.96));
            border-color:rgba(255,255,255,.2);
            box-shadow:0 18px 45px rgba(31,5,57,.3);
            backdrop-filter:blur(12px);
        }
        body.admin-institution-portal .profile-dropdown-action { color:rgba(255,255,255,.88);font-weight:750; }
        body.admin-institution-portal .profile-dropdown-action:hover { color:#fff;background:rgba(255,255,255,.14); }

        .admin-profile-modal[hidden] { display:none; }
        .admin-profile-modal { position:fixed;z-index:2100;inset:0;display:grid;place-items:center;padding:20px;background:rgba(31,5,57,.62);backdrop-filter:blur(5px); }
        .admin-profile-dialog { width:min(640px,100%);max-height:calc(100vh - 40px);padding:26px;overflow-y:auto;color:#24152f;background-color:#d9bfea;background-image:var(--portal-panel-image);background-position:center;background-repeat:no-repeat;background-size:cover;border:1px solid rgba(69,6,147,.2);border-radius:18px;box-shadow:0 28px 80px rgba(24,3,48,.3); }
        .admin-profile-header { display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:22px; }
        .admin-profile-header h2 { margin-bottom:5px;color:#2d045f;font-size:24px; }
        .admin-profile-header p { color:#55465f;font-size:13px;line-height:1.5; }
        .admin-profile-close { width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;color:#64748b;background:rgba(255,255,255,.78);border:1px solid rgba(69,6,147,.14);border-radius:9px;font-size:24px;cursor:pointer; }
        .admin-profile-form-grid { display:grid;grid-template-columns:1fr 1fr;gap:15px; }
        .admin-profile-photo { grid-column:1/-1;display:flex;align-items:center;gap:16px;padding:14px;background:rgba(255,255,255,.45);border:1px solid rgba(69,6,147,.12);border-radius:12px; }
        .admin-profile-photo-preview { width:78px;height:78px;display:grid;place-items:center;flex:0 0 78px;overflow:hidden;color:#fff;background:#450693;border:3px solid rgba(255,255,255,.8);border-radius:50%;box-shadow:0 8px 22px rgba(45,4,95,.18);font-size:25px;font-weight:850; }
        .admin-profile-photo-preview img { width:100%;height:100%;display:block;object-fit:cover; }
        .admin-profile-photo-copy { min-width:0; }
        .admin-profile-photo-copy strong { display:block;margin-bottom:4px;color:#302638;font-size:13px; }
        .admin-profile-photo-copy p { margin-bottom:9px;color:#675b70;font-size:11px;line-height:1.4; }
        .admin-profile-photo-input { position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap; }
        .admin-profile-photo-button { display:inline-flex;align-items:center;min-height:34px;padding:7px 11px;color:#450693;background:rgba(255,255,255,.8);border:1px solid rgba(69,6,147,.18);border-radius:8px;font-size:11px;font-weight:800;cursor:pointer; }
        .admin-profile-field.full { grid-column:1/-1; }
        .admin-profile-field label { display:block;margin-bottom:6px;color:#302638;font-size:11px;font-weight:800; }
        .admin-profile-field .input { width:100%;min-height:45px;padding:11px 12px;color:#271d2e;background:rgba(255,255,255,.82);border:1px solid rgba(69,6,147,.18);border-radius:9px;outline:none; }
        .admin-profile-field .input:focus { border-color:#7022b8;box-shadow:0 0 0 3px rgba(112,34,184,.1); }
        .admin-profile-field .input[readonly] { color:#675b70;background:rgba(243,238,246,.75);cursor:not-allowed; }
        .admin-profile-error { display:block;margin-top:5px;color:#b42318;font-size:11px; }
        .admin-profile-actions { display:flex;justify-content:flex-end;gap:10px;margin-top:22px;padding-top:18px;border-top:1px solid rgba(69,6,147,.12); }
        @media(max-width:600px){.admin-profile-modal{padding:12px}.admin-profile-dialog{padding:18px}.admin-profile-form-grid{grid-template-columns:1fr}.admin-profile-field.full{grid-column:auto}.admin-profile-actions{align-items:stretch;flex-direction:column-reverse}.admin-profile-actions .button{width:100%}}

        @media(max-width:950px) {
            body.admin-institution-portal .main::after {
                left:0;
                background-size:min(70vmin,680px) min(70vmin,680px);
            }
        }

        @media(max-width:600px) {
            body.admin-institution-portal .main::before,
            body.admin-institution-portal .main::after {
                top:110px;
            }

            body.admin-institution-portal .main::after {
                background-size:min(78vmin,440px) min(78vmin,440px);
            }
        }

        @media(prefers-reduced-motion:reduce) {
            body.admin-institution-portal .main::before {
                animation:none;
                transform:none;
            }
        }
    </style>
    @include('layouts.partials.scrollbar-styles')
</head>

<body class="admin-institution-portal" style="--admin-institution-logo:url('{{ asset('images/mcc-college-logo.png') }}');--admin-portal-background:url('{{ asset('images/admin-portal-background.png') }}')">
<div class="app">

    <aside id="portalSidebar" class="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            <span class="brand-icon brand-icon--scheduler"><img src="{{ asset('images/mcc-scheduler-logo.png') }}" alt="MCC Scheduler logo"></span>
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
           class="menu-link {{ request()->routeIs('admin.users.index', 'admin.users.create', 'admin.users.edit') ? 'active' : '' }}">
            <span class="menu-icon" aria-hidden="true">♙</span>
            User Accounts
        </a>

        <a href="{{ route('admin.users.deleted') }}"
           class="menu-link {{ request()->routeIs('admin.users.deleted') ? 'active' : '' }}">
            <span class="menu-icon" aria-hidden="true">♻</span>
            Deleted Accounts
        </a>

        <a href="{{ route('admin.ms365-accounts.index') }}" class="menu-link {{ request()->routeIs('admin.ms365-accounts.*') ? 'active' : '' }}">
            <span class="menu-icon" aria-hidden="true">@</span>
            MS365 Accounts
        </a>

    </aside>
    <button id="sidebarBackdrop" class="sidebar-backdrop" type="button" aria-label="Close navigation menu"></button>

    <main class="main">

        <header class="topbar">
            <div class="topbar-start">
                @include('layouts.partials.sidebar-toggle')
                <div><span class="topbar-label">Administrator workspace</span><h1>@yield('page-title', 'Admin Portal')</h1></div>
            </div>

            <details class="profile-menu">
                <summary class="profile">
                    <div class="profile-avatar">@if(auth()->user()->profile_photo_path)<img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }} profile photo">@else{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}@endif</div>
                    <div>
                        <div class="profile-name">{{ auth()->user()->name }}</div>
                        <div class="profile-role">Administrator</div>
                    </div>
                </summary>
                <div class="profile-dropdown">
                    <button id="openAdminProfileModal" type="button" class="profile-dropdown-action">Edit Profile</button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <input type="hidden" name="role" value="admin">
                        <button type="submit" class="profile-dropdown-action">Logout</button>
                    </form>
                </div>
            </details>
        </header>

        <section class="content">
            @yield('content')
        </section>
    </main>

</div>

<div id="adminProfileModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="adminProfileTitle">
        <div class="admin-profile-header">
            <div><h2 id="adminProfileTitle">Edit Profile</h2><p>Update your administrator account's basic information.</p></div>
            <button id="closeAdminProfileModal" type="button" class="admin-profile-close" aria-label="Close profile form">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <input type="hidden" name="profile_modal" value="1">

            <div class="admin-profile-form-grid">
                <div class="admin-profile-photo">
                    <div id="adminProfilePhotoPreview" class="admin-profile-photo-preview">@if(auth()->user()->profile_photo_path)<img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="Current profile photo">@else{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}@endif</div>
                    <div class="admin-profile-photo-copy"><strong>Profile picture</strong><p>Upload a JPG, PNG, or WebP image up to 50 MB.</p><label class="admin-profile-photo-button" for="admin_profile_photo">Choose Image</label><input id="admin_profile_photo" class="admin-profile-photo-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp">@error('profile_photo')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                </div>
                <div class="admin-profile-field"><label for="admin_first_name">First name</label><input id="admin_first_name" class="input" type="text" name="first_name" value="{{ old('first_name', auth()->user()->first_name) }}" required>@error('first_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="admin_middle_name">Middle name</label><input id="admin_middle_name" class="input" type="text" name="middle_name" value="{{ old('middle_name', auth()->user()->middle_name) }}">@error('middle_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="admin_last_name">Last name</label><input id="admin_last_name" class="input" type="text" name="last_name" value="{{ old('last_name', auth()->user()->last_name) }}" required>@error('last_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="admin_suffix">Suffix</label><input id="admin_suffix" class="input" type="text" name="suffix" value="{{ old('suffix', auth()->user()->suffix) }}" placeholder="e.g. Jr., Sr., III">@error('suffix')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="admin_email">Email address</label><input id="admin_email" class="input" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>@error('email')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="admin_role">Account role</label><input id="admin_role" class="input" type="text" value="Administrator" readonly></div>
            </div>

            <div class="admin-profile-actions">
                <button id="cancelAdminProfileModal" type="button" class="button button-secondary">Cancel</button>
                <button type="submit" class="button">Save Changes</button>
            </div>
        </form>
    </section>
</div>

@php
    $notificationIsSuccess = session()->has('success');
    $notificationIsError = session()->has('error') || ($errors->any() && ! old('profile_modal'));
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

<script>
    (() => {
        const modal = document.getElementById('adminProfileModal');
        const openButton = document.getElementById('openAdminProfileModal');
        const closeButton = document.getElementById('closeAdminProfileModal');
        const cancelButton = document.getElementById('cancelAdminProfileModal');
        const photoInput = document.getElementById('admin_profile_photo');
        const photoPreview = document.getElementById('adminProfilePhotoPreview');
        const profileMenu = openButton?.closest('details');

        if (!modal || !openButton || !closeButton || !cancelButton) return;

        function openModal() {
            profileMenu?.removeAttribute('open');
            modal.hidden = false;
            document.body.classList.add('modal-open');
            document.getElementById('admin_first_name')?.focus();
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            openButton.focus();
        }

        openButton.addEventListener('click', openModal);
        closeButton.addEventListener('click', closeModal);
        cancelButton.addEventListener('click', closeModal);
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
        photoInput?.addEventListener('change', () => {
            const file = photoInput.files?.[0];
            if (!file || !file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.addEventListener('load', () => {
                photoPreview.innerHTML = '';
                const image = document.createElement('img');
                image.src = String(reader.result);
                image.alt = 'Selected profile photo preview';
                photoPreview.appendChild(image);
            });
            reader.readAsDataURL(file);
        });

        @if(old('profile_modal'))
            openModal();
        @endif
    })();
</script>

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
