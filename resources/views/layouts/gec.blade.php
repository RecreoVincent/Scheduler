<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.favicon')
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'GEC Portal') | Scheduler</title>

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

        .menu-icon { width: 20px; flex: 0 0 20px; display:inline-flex; align-items:center; justify-content:center; color: #64748b; font-size: 18px; line-height: 1; text-align: center; }
        .menu-icon svg { width:18px; height:18px; }
        .menu-link:hover .menu-icon, .menu-link.active .menu-icon { color: var(--primary-dark); }

        .main {
            width: calc(100% - 255px);
            margin-left: 255px;
        }

        .topbar-actions { display: flex; align-items: center; gap: 12px; }

        .topbar-settings-menu { position: relative; }
        .topbar-settings-menu summary { list-style: none; cursor: pointer; user-select: none; }
        .topbar-settings-menu summary::-webkit-details-marker { display: none; }
        .topbar-settings-trigger { width: 42px; height: 42px; display: grid; place-items: center; color: var(--primary); background: #eaf1ff; border: 1px solid #c9dcfb; border-radius: 11px; font-size: 18px; transition: .2s; }
        .topbar-settings-trigger svg { width: 19px; height: 19px; }
        .topbar-settings-trigger:hover, .topbar-settings-menu[open] .topbar-settings-trigger { background: var(--primary); color: #fff; }
        .topbar-settings-dropdown { position: absolute; z-index: 1300; top: calc(100% + 10px); right: 0; width: 280px; padding: 18px; background: white; border: 1px solid var(--border); border-radius: 14px; box-shadow: 0 18px 45px rgba(15, 23, 42, .18); }
        .topbar-settings-dropdown strong { display: block; margin-bottom: 5px; font-size: 13px; color: var(--navy); }
        .topbar-settings-dropdown p { margin: 0 0 12px; font-size: 11px; line-height: 1.5; color: var(--muted); }
        .switch-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 0; font-size: 12px; font-weight: 650; color: var(--text); border-top: 1px solid var(--border); }
        .switch-row:first-of-type { border-top: 0; }
        .switch { position: relative; display: inline-block; width: 38px; height: 22px; flex: 0 0 38px; }
        .switch input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; z-index: 1; }
        .switch-track { position: absolute; inset: 0; background: #d8dee9; border-radius: 999px; transition: .2s; }
        .switch-track::before { content: ''; position: absolute; width: 16px; height: 16px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: .2s; box-shadow: 0 1px 3px rgba(0, 0, 0, .25); }
        .switch input:checked + .switch-track { background: var(--primary); }
        .switch input:checked + .switch-track::before { transform: translateX(16px); }
        .switch input:focus-visible + .switch-track { box-shadow: 0 0 0 3px rgba(59, 130, 246, .25); }

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

        label { display: block; margin-bottom: 7px; }

        .form-grid, .filters {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .filters {
            grid-template-columns: repeat(4, 1fr);
            margin-bottom: 20px;
            padding: 15px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .filters label,
        .archive-filters label {
            display: block;
            margin-bottom: 6px;
        }

        .filters .input,
        .archive-filters .input {
            width: 100%;
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
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
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
            border-radius: 50%;
        }

        .notification-icon svg { width: 28px; height: 28px; }

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

        .notification-guidance {
            max-height: 220px;
            margin-top: 14px;
            padding: 12px 16px;
            overflow-y: auto;
            text-align: left;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .notification-guidance strong {
            display: block;
            margin-bottom: 4px;
            color: var(--navy);
            font-size: 12px;
        }

        .notification-guidance p {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
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

        @media (max-width: 950px) {
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

            .filters,
            .form-grid {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 600px) {
            .topbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .topbar-actions {
                width: 100%;
                justify-content: space-between;
            }

            .content {
                padding-top: 23px;
            }

            .page-header .button {
                width: 100%;
            }

            .card {
                padding: 18px;
            }
        }
    </style>

    @include('layouts.partials.sidebar-toggle-styles')
    @stack('styles')
    @include('layouts.partials.portal-unified-theme')
    @include('layouts.partials.portal-mobile-styles')
    @include('layouts.partials.schedule-notification-styles')
    <style>
        body.gec-institution-portal,
        body.gec-institution-portal .app,
        body.gec-institution-portal .main,
        body.gec-institution-portal .content {
            background-color:#fff;
        }

        body.gec-institution-portal .main {
            position:relative;
            isolation:isolate;
        }

        body.gec-institution-portal .main::before {
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
        }

        body.gec-institution-portal .main::after {
            content:none;
            display:none;
        }

        body.gec-institution-portal .topbar,
        body.gec-institution-portal .content {
            position:relative;
            z-index:2;
        }

        body.gec-institution-portal .content {
            background-color:transparent;
        }

        body.gec-institution-portal .content .card,
        body.gec-institution-portal .content .welcome,
        body.gec-institution-portal .content .welcome-card,
        body.gec-institution-portal .content .stat-card {
            color:#24152f;
            background-color:rgba(255,255,255,.46) !important;
            background-image:none !important;
            border-color:rgba(69,6,147,.25) !important;
        }

        body.gec-institution-portal .content .card :is(h1,h2,h3,h4,p,span,strong,label,td,th),
        body.gec-institution-portal .content .welcome :is(h1,h2,h3,h4,p,span,strong,label),
        body.gec-institution-portal .content .welcome-card :is(h1,h2,h3,h4,p,span,strong,label),
        body.gec-institution-portal .content .stat-card :is(h1,h2,h3,h4,p,span,strong,label) {
            color:#24152f !important;
        }

        body.gec-institution-portal .content .card .instructor-cell > .instructor-mark {
            display:grid;
            place-items:center;
            color:#fff !important;
            font-size:20px !important;
            font-weight:850;
            line-height:1;
        }

        body.gec-institution-portal .content .card .badge,
        body.gec-institution-portal .content .card .role-badge {
            color:#450693 !important;
        }

        body.gec-institution-portal .content .filters {
            background-color:#f8fafc;
            background-image:none;
            border-color:rgba(69,6,147,.22);
            box-shadow:0 6px 16px rgba(15,23,42,.05);
        }

        body.gec-institution-portal .content .filters .input {
            color:#271d2e;
        }

        body.gec-institution-portal .content .filters .input::placeholder {
            color:#55495e;
            opacity:1;
        }

        body.gec-institution-portal .content .card .table-wrap,
        body.gec-institution-portal .content .card .table-wrapper {
            background:rgba(255,255,255,.52);
        }

        body.gec-institution-portal .content .card table {
            background:transparent;
        }

        body.gec-institution-portal .content .card th {
            color:#302039;
            background:rgba(248,243,251,.68);
        }

        body.gec-institution-portal .content .card td,
        body.gec-institution-portal .content .card p,
        body.gec-institution-portal .content .card span {
            color:#302638;
        }

        body.gec-institution-portal .content .card .portal-page-button.is-active {
            color:#fff !important;
        }

        body.gec-institution-portal .content .report-card .report-symbol,
        body.gec-institution-portal .content .report-card .button span {
            color:#fff !important;
        }

        body.gec-institution-portal .content .card tbody tr:hover {
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

        body.gec-institution-portal .profile-menu .profile {
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
        body.gec-institution-portal .profile-menu .profile:hover,
        body.gec-institution-portal .profile-menu[open] .profile {
            background:linear-gradient(135deg,rgba(255,255,255,.22),rgba(255,255,255,.12));
            border-color:rgba(255,255,255,.38);
            transform:translateY(-1px);
        }
        body.gec-institution-portal .profile-menu .profile-avatar {
            color:#450693;
            background:rgba(255,255,255,.94);
            box-shadow:0 6px 16px rgba(26,2,45,.18);
            overflow:hidden;
        }
        body.gec-institution-portal .profile-menu .profile-avatar img { width:100%;height:100%;display:block;object-fit:cover; }
        body.gec-institution-portal .profile-menu .profile-name { color:#fff;font-size:12px; }
        body.gec-institution-portal .profile-menu .profile-role { color:rgba(255,255,255,.68);font-size:9px; }
        body.gec-institution-portal .profile-menu summary::after {
            width:7px;
            height:7px;
            margin:0 2px 4px auto;
            content:'';
            border-right:2px solid rgba(255,255,255,.82);
            border-bottom:2px solid rgba(255,255,255,.82);
            transform:rotate(45deg);
            transition:transform .2s ease;
        }
        body.gec-institution-portal .profile-menu[open] summary::after { margin-bottom:-2px;transform:rotate(225deg); }
        body.gec-institution-portal .profile-dropdown {
            background:linear-gradient(155deg,rgba(45,4,95,.98),rgba(69,6,147,.96));
            border-color:rgba(255,255,255,.2);
            box-shadow:0 18px 45px rgba(31,5,57,.3);
            backdrop-filter:blur(12px);
        }
        body.gec-institution-portal .profile-dropdown-action { color:rgba(255,255,255,.88);font-weight:750; }
        body.gec-institution-portal .profile-dropdown-action:hover { color:#fff;background:rgba(255,255,255,.14); }

        .admin-profile-modal[hidden] { display:none; }
        .admin-profile-modal { position:fixed;z-index:2100;inset:0;display:grid;place-items:center;padding:20px;background:rgba(31,5,57,.62);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px); }
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
        .admin-profile-field .input { width:100%;min-height:45px;padding:11px 12px;color:#271d2e;background:rgba(255,255,255,.82);border:1.5px solid rgba(69,6,147,.4);border-radius:9px;outline:none; }
        .admin-profile-field .input:focus { border-color:var(--primary);box-shadow:0 0 0 4px rgba(69,6,147,.22); }
        .admin-profile-field .input[readonly] { color:#675b70;background:rgba(243,238,246,.75);cursor:not-allowed; }
        .admin-profile-error { display:block;margin-top:5px;color:#b42318;font-size:11px; }
        .admin-profile-divider { padding-top:14px;border-top:1px solid rgba(69,6,147,.12); }
        .admin-profile-divider strong { display:block;margin-bottom:4px;color:#302638;font-size:13px; }
        .admin-profile-divider p { margin:0;color:#675b70;font-size:11px;line-height:1.4; }
        .admin-profile-password-wrap { position:relative; }
        .admin-profile-password-wrap .input { padding-right:42px; }
        .admin-profile-password-toggle { position:absolute;top:50%;right:6px;transform:translateY(-50%);width:32px;height:32px;display:grid;place-items:center;color:#675b70;background:transparent;border:0;border-radius:8px;cursor:pointer; }
        .admin-profile-password-toggle:hover { color:var(--primary);background:rgba(69,6,147,.08); }
        .admin-profile-password-toggle svg { width:18px;height:18px; }
        .admin-profile-actions { display:flex;justify-content:flex-end;gap:10px;margin-top:22px;padding-top:18px;border-top:1px solid rgba(69,6,147,.12); }
        @media(max-width:600px){.admin-profile-modal{padding:12px}.admin-profile-dialog{padding:18px}.admin-profile-form-grid{grid-template-columns:1fr}.admin-profile-field.full{grid-column:auto}.admin-profile-actions{align-items:stretch;flex-direction:column-reverse}.admin-profile-actions .button{width:100%}}
    </style>
    @include('layouts.partials.scrollbar-styles')
</head>

<body class="gec-institution-portal" style="--gec-institution-logo:url('{{ asset('images/mcc-college-logo.png') }}')">
<div class="app">

    <aside id="portalSidebar" class="sidebar portal-sidebar">
        <div class="portal-sidebar-navigation">
        <a href="{{ route('gec.dashboard') }}" class="brand">
            <span class="brand-icon brand-icon--scheduler"><img src="{{ asset('images/mcc-scheduler-logo.png') }}" alt="MCC Scheduler logo"></span>
            <span class="brand-copy"><strong>MCC | Scheduler</strong><small>GEC Portal</small></span>
        </a>
        <div class="portal-chip">General Education Course</div>

        <p class="menu-label">Overview</p>

        <a href="{{ route('gec.dashboard') }}"
           class="menu-link {{ request()->routeIs('gec.dashboard') ? 'active' : '' }}">
            <span class="menu-icon"><x-icon name="home" /></span>
            Dashboard
        </a>

        <p class="menu-label">Academic Management</p>
        <a class="menu-link {{ request()->routeIs('gec.subjects.*') ? 'active' : '' }}" href="{{ route('gec.subjects.index') }}"><span class="menu-icon"><x-icon name="book" /></span>Minor Subjects</a>
        <a class="menu-link {{ request()->routeIs('gec.subject-endorsements.*') ? 'active' : '' }}" href="{{ route('gec.subject-endorsements.index') }}"><span class="menu-icon"><x-icon name="send" /></span>Subject Endorsement</a>

        <p class="menu-label">Instructor Management</p>
        <a class="menu-link {{ request()->routeIs('gec.instructors.*') ? 'active' : '' }}" href="{{ route('gec.instructors.index') }}"><span class="menu-icon"><x-icon name="cap" /></span>Instructor List</a>
        <a class="menu-link {{ request()->routeIs('gec.instructor-units.*') ? 'active' : '' }}" href="{{ route('gec.instructor-units.index') }}"><span class="menu-icon"><x-icon name="clipboard" /></span>Instructor Units</a>
        <a class="menu-link {{ request()->routeIs('gec.subject-assignments.*') ? 'active' : '' }}" href="{{ route('gec.subject-assignments.index') }}"><span class="menu-icon"><x-icon name="link" /></span>Subject Assignment</a>

        <p class="menu-label">Scheduling</p>
        <a class="menu-link {{ request()->routeIs('gec.schedules.*') ? 'active' : '' }}" href="{{ route('gec.schedules.create') }}"><span class="menu-icon"><x-icon name="calendar-plus" /></span>Create Schedule</a>
        <a class="menu-link {{ request()->routeIs('gec.timetable.*') ? 'active' : '' }}" href="{{ route('gec.timetable.index') }}"><span class="menu-icon"><x-icon name="calendar-grid" /></span>Timetable</a>
        <a class="menu-link {{ request()->routeIs('gec.archive.*') ? 'active' : '' }}" href="{{ route('gec.archive.index') }}"><span class="menu-icon"><x-icon name="archive" /></span>Archive</a>
        <a class="menu-link {{ request()->routeIs('gec.print.*') ? 'active' : '' }}" href="{{ route('gec.print.index') }}"><span class="menu-icon"><x-icon name="printer" /></span>Print Reports</a>
        </div>
        @include('layouts.partials.portal-sidebar-logout')
    </aside>
    <button id="sidebarBackdrop" class="sidebar-backdrop" type="button" aria-label="Close navigation menu"></button>

    <main class="main">

        <header class="topbar">
            <div class="topbar-start">
                @include('layouts.partials.sidebar-toggle')
                <div><span class="topbar-label">GEC workspace</span><h1>@yield('page-title', 'GEC Portal')</h1></div>
            </div>

            <div class="topbar-actions">
                @php
                    $gecDepartment = auth()->user()->department;
                    $gecActiveSemester = session('gec.active_semester');
                    if (! in_array($gecActiveSemester, ['1st', '2nd', 'Summer'], true)) {
                        $gecActiveSemester = $gecDepartment?->enabledSemesterCodes()[0] ?? '1st';
                    }
                @endphp
                <details class="topbar-settings-menu" @if($errors->has('semester_availability')) open @endif>
                    <summary class="topbar-settings-trigger" aria-label="Semester settings" title="Semester settings"><x-icon name="gear" /></summary>
                    <div class="topbar-settings-dropdown">
                        <strong>Your Semester View</strong>
                        <p>Your selection applies only to this browser. It will not change the semester shown on another device.</p>
                        <form method="POST" action="{{ route('gec.settings.semesters') }}">
                            @csrf
                            @method('PATCH')
                            <label class="switch-row">
                                <span>1st Semester</span>
                                <span class="switch"><input type="radio" name="active_semester" value="first" @checked($gecActiveSemester === '1st')><span class="switch-track"></span></span>
                            </label>
                            <label class="switch-row">
                                <span>2nd Semester</span>
                                <span class="switch"><input type="radio" name="active_semester" value="second" @checked($gecActiveSemester === '2nd')><span class="switch-track"></span></span>
                            </label>
                            <label class="switch-row">
                                <span>Summer</span>
                                <span class="switch"><input type="radio" name="active_semester" value="summer" @checked($gecActiveSemester === 'Summer')><span class="switch-track"></span></span>
                            </label>
                            @error('semester_availability')<p class="error" style="margin:10px 0 0">{{ $message }}</p>@enderror
                            <button class="button" type="submit" style="width:100%;margin-top:12px">Save</button>
                        </form>
                    </div>
                </details>

                @include('layouts.partials.schedule-notifications')

                <details class="profile-menu">
                    <summary class="profile">
                        <div class="profile-avatar">@if(auth()->user()->profile_photo_path)<img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }} profile photo">@else{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}@endif</div>
                        <div>
                            <div class="profile-name">{{ auth()->user()->name }}</div>
                            <div class="profile-role">GEC</div>
                        </div>
                    </summary>
                    <div class="profile-dropdown">
                        <button id="openGecProfileModal" type="button" class="profile-dropdown-action">Edit Profile</button>
                    </div>
                </details>
            </div>
        </header>

        <section class="content">
            @yield('content')
        </section>
    </main>

</div>
@include('layouts.partials.portal-mobile-navigation')
@stack('portal-profile-overlay')

<div id="gecProfileModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="gecProfileTitle">
        <div class="admin-profile-header">
            <div><h2 id="gecProfileTitle">Edit Profile</h2><p>Update your GEC account's basic information.</p></div>
            <button id="closeGecProfileModal" type="button" class="admin-profile-close" aria-label="Close profile form">&times;</button>
        </div>

        <form method="POST" action="{{ route('gec.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <input type="hidden" name="profile_modal" value="1">

            <div class="admin-profile-form-grid">
                <div class="admin-profile-photo">
                    <div id="gecProfilePhotoPreview" class="admin-profile-photo-preview">@if(auth()->user()->profile_photo_path)<img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="Current profile photo">@else{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}@endif</div>
                    <div class="admin-profile-photo-copy"><strong>Profile picture</strong><p>Upload a JPG, PNG, or WebP image up to 50 MB.</p><label class="admin-profile-photo-button" for="gec_profile_photo">Choose Image</label><input id="gec_profile_photo" class="admin-profile-photo-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp">@error('profile_photo')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                </div>
                <div class="admin-profile-field"><label for="gec_first_name">First name</label><input id="gec_first_name" class="input" type="text" name="first_name" value="{{ old('first_name', auth()->user()->first_name) }}" required>@error('first_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="gec_middle_name">Middle name</label><input id="gec_middle_name" class="input" type="text" name="middle_name" value="{{ old('middle_name', auth()->user()->middle_name) }}">@error('middle_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="gec_last_name">Last name</label><input id="gec_last_name" class="input" type="text" name="last_name" value="{{ old('last_name', auth()->user()->last_name) }}" required>@error('last_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="gec_suffix">Suffix</label><input id="gec_suffix" class="input" type="text" name="suffix" value="{{ old('suffix', auth()->user()->suffix) }}" placeholder="e.g. Jr., Sr., III">@error('suffix')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="gec_email">Email address</label><input id="gec_email" class="input" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>@error('email')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="gec_role">Account role</label><input id="gec_role" class="input" type="text" value="GEC" readonly></div>
                <div class="admin-profile-field full admin-profile-divider"><strong>Change Password</strong><p>Leave these blank to keep your current password.</p></div>
                <div class="admin-profile-field full"><label for="gec_current_password">Current password</label><x-password-toggle id="gec_current_password" name="current_password" autocomplete="current-password" />@error('current_password')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="gec_new_password">New password</label><x-password-toggle id="gec_new_password" name="password" />@error('password')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="gec_new_password_confirmation">Confirm new password</label><x-password-toggle id="gec_new_password_confirmation" name="password_confirmation" /></div>
            </div>

            <div class="admin-profile-actions">
                <button id="cancelGecProfileModal" type="button" class="button button-secondary">Cancel</button>
                <button type="submit" class="button">Save Changes</button>
            </div>
        </form>
    </section>
</div>

@php
    $notificationIsSuccess = session()->has('success');
    $notificationIsError = session()->has('error') || ($errors->any() && ! old('profile_modal'));
    $isWorkloadCapacityNotice = str_starts_with((string) session('error'), 'Schedule was not created because these classes do not have an instructor');
@endphp

@if ($notificationIsSuccess || $notificationIsError)
    <div id="notificationModal" class="notification-modal" role="presentation">
        <section class="notification-dialog" role="dialog" aria-modal="true" aria-labelledby="notificationTitle">
            <div class="notification-icon {{ $notificationIsSuccess ? 'success' : 'error' }}">
                <x-icon :name="$notificationIsSuccess ? 'check' : 'warning'" />
            </div>

            <h2 id="notificationTitle">
                {{ $notificationIsSuccess ? 'Success' : ($isWorkloadCapacityNotice ? 'Not enough instructor hours' : 'Action unsuccessful') }}
            </h2>

            @if ($notificationIsSuccess)
                <p>{{ session('success') }}</p>
            @elseif (session('error'))
                <p>{{ session('error') }}</p>
            @endif

            @if (session('error_note'))
                <div class="notification-guidance">
                    <strong>{{ $isWorkloadCapacityNotice ? 'How to fix it' : 'What you can do' }}</strong>
                    <p>{{ session('error_note') }}</p>
                </div>
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
        const semesterSettings = document.querySelector('.topbar-settings-menu');

        if (!semesterSettings) return;

        document.addEventListener('click', event => {
            if (!semesterSettings.contains(event.target)) semesterSettings.removeAttribute('open');
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') semesterSettings.removeAttribute('open');
        });
    })();
</script>

<script>
    (() => {
        const modal = document.getElementById('gecProfileModal');
        const openButton = document.getElementById('openGecProfileModal');
        const closeButton = document.getElementById('closeGecProfileModal');
        const cancelButton = document.getElementById('cancelGecProfileModal');
        const photoInput = document.getElementById('gec_profile_photo');
        const photoPreview = document.getElementById('gecProfilePhotoPreview');
        const profileMenu = openButton?.closest('details');

        if (!modal || !openButton || !closeButton || !cancelButton) return;

        function openModal() {
            profileMenu?.removeAttribute('open');
            modal.hidden = false;
            document.body.classList.add('modal-open');
            document.getElementById('gec_first_name')?.focus();
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

        modal.querySelectorAll('[data-password-eye-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const input = button.previousElementSibling;
                const eyeIcon = button.querySelector('[data-eye-icon]');
                const eyeOffIcon = button.querySelector('[data-eye-off-icon]');
                if (!input) return;
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                eyeIcon.hidden = !showing;
                eyeOffIcon.hidden = showing;
                button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            });
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
