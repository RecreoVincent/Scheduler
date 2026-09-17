@php
    $deanDepartmentLogos=[
        'BSIT'=>'images/bsit-department-logo.jpg',
        'BSBA'=>'images/bsba-department-logo.jpg',
        'BSHM'=>'images/bshm-department-logo.jpg',
        'BSED'=>'images/education-department-logo.jpg',
        'BEED'=>'images/education-department-logo.jpg',
    ];
    $deanDepartmentLogo=$deanDepartmentLogos[strtoupper((string)auth()->user()->course)]??'images/mcc-college-logo.png';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dean Portal') | Scheduler</title>
    <style>
        :root {
            --primary:#450693;
            --primary-dark:#2d045f;
            --primary-light:#7022b8;
            --gold:#e8b84a;
            --gold-dark:#805000;
            --gold-soft:#fff6df;
            --navy:#2d045f;
            --text:#4d4456;
            --muted:#7c7285;
            --bg:#f7f4f9;
            --border:#e5dbea;
            --danger:#c62828;
            --success:#16835f;
        }

        * { box-sizing:border-box; margin:0; padding:0; }
        body { min-height:100vh; font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; color:var(--text); background:radial-gradient(circle at 90% 5%,rgba(112,34,184,.08),transparent 27%),var(--bg); }
        body.modal-open { overflow:hidden; }
        a { color:inherit; text-decoration:none; }
        button,input,select,textarea { font:inherit; }
        .app { display:flex; min-height:100vh; }

        .sidebar { position:fixed; z-index:100; inset:0 auto 0 0; width:270px; padding:25px 18px 22px; overflow-y:auto; color:white; background:linear-gradient(165deg,var(--primary-dark),var(--primary) 62%,#6115a3); box-shadow:8px 0 30px rgba(38,3,78,.12); }
        .sidebar::before { content:''; position:absolute; width:240px; height:240px; top:-155px; right:-150px; border:1px solid rgba(255,255,255,.11); border-radius:50%; box-shadow:0 0 0 50px rgba(255,255,255,.025),0 0 0 100px rgba(255,255,255,.018); pointer-events:none; }
        .brand { position:relative; display:flex; align-items:center; gap:12px; margin-bottom:21px; padding:0 10px; font-size:20px; font-weight:850; letter-spacing:-.4px; }
        .brand-icon { width:43px; height:43px; display:grid; place-items:center; flex:0 0 43px; color:#2e1d00; background:var(--gold); border:2px solid #f4d77e; border-radius:12px; box-shadow:0 9px 23px rgba(20,2,40,.22); }
        .brand-copy small { display:block; margin-top:2px; font-size:9px; font-weight:650; letter-spacing:.8px; color:rgba(255,255,255,.55); text-transform:uppercase; }
        .department-chip { position:relative; display:flex; align-items:center; gap:9px; margin:0 7px 23px; padding:11px 12px; font-size:11px; font-weight:750; color:#fff1c8; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.14); border-radius:10px; }
        .department-dot { width:8px; height:8px; flex:0 0 8px; background:var(--gold); border-radius:50%; box-shadow:0 0 0 4px rgba(232,184,74,.12); }
        .menu-label { margin:20px 12px 8px; font-size:9px; font-weight:800; letter-spacing:1.3px; color:rgba(255,255,255,.4); text-transform:uppercase; }
        .menu-link { position:relative; width:100%; display:flex; align-items:center; gap:11px; margin-bottom:5px; padding:11px 13px; overflow:hidden; font-size:12px; font-weight:650; color:rgba(255,255,255,.7); background:transparent; border:0; border-radius:9px; cursor:pointer; transition:.2s; }
        .menu-link::after { content:''; position:absolute; width:3px; height:0; right:0; top:50%; background:var(--gold); border-radius:3px 0 0 3px; transform:translateY(-50%); transition:.2s; }
        .menu-icon { width:20px; flex:0 0 20px; display:inline-flex; align-items:center; justify-content:center; color:rgba(255,255,255,.55); font-size:17px; line-height:1; text-align:center; }
        .menu-icon svg { width:18px; height:18px; }
        .menu-link:hover,.menu-link.active { color:white; background:rgba(255,255,255,.1); }
        .menu-link.active { font-weight:750; box-shadow:inset 0 0 0 1px rgba(255,255,255,.08); }
        .menu-link:hover .menu-icon,.menu-link.active .menu-icon { color:var(--gold); }
        .menu-link.active::after { height:58%; }

        .main { width:calc(100% - 270px); margin-left:270px; }
        .topbar { min-height:82px; display:flex; justify-content:space-between; align-items:center; gap:25px; padding:14px 34px; background:rgba(255,255,255,.92); border-bottom:1px solid var(--border); backdrop-filter:blur(10px); }
        .topbar-label { display:block; margin-bottom:3px; font-size:9px; font-weight:800; letter-spacing:1.1px; color:var(--gold-dark); text-transform:uppercase; }
        .topbar h1 { font-size:21px; letter-spacing:-.5px; color:var(--navy); }
        .profile { display:flex; align-items:center; gap:11px; padding:6px 9px 6px 7px; background:#faf8fb; border:1px solid var(--border); border-radius:11px; }
        .profile-avatar { width:36px; height:36px; display:grid; place-items:center; flex:0 0 36px; font-size:13px; font-weight:850; color:#2d1b00; background:var(--gold); border-radius:9px; }
        .profile-copy { text-align:left; } .profile-copy strong { display:block; max-width:190px; overflow:hidden; font-size:12px; white-space:nowrap; text-overflow:ellipsis; color:#302638; } .profile-copy span { display:block; margin-top:2px; font-size:9px; color:var(--muted); }

        .topbar-actions { display:flex; align-items:center; gap:12px; }
        .topbar-settings-menu { position:relative; }
        .topbar-settings-menu summary { list-style:none; cursor:pointer; user-select:none; }
        .topbar-settings-menu summary::-webkit-details-marker { display:none; }
        .topbar-settings-trigger { width:42px; height:42px; display:grid; place-items:center; color:var(--primary); background:#f6effb; border:1px solid #dbc6eb; border-radius:11px; font-size:18px; transition:.2s; }
        .topbar-settings-trigger svg { width:19px; height:19px; }
        .topbar-settings-trigger:hover,.topbar-settings-menu[open] .topbar-settings-trigger { background:var(--primary); color:#fff; }
        .topbar-settings-dropdown { position:absolute; z-index:1300; top:calc(100% + 10px); right:0; width:280px; padding:18px; background:white; border:1px solid var(--border); border-radius:14px; box-shadow:0 18px 45px rgba(31,5,57,.18); }
        .topbar-settings-dropdown strong { display:block; margin-bottom:5px; font-size:13px; color:var(--navy); }
        .topbar-settings-dropdown p { margin:0 0 12px; font-size:11px; line-height:1.5; color:var(--muted); }
        .switch-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:9px 0; font-size:12px; font-weight:650; color:#302638; border-top:1px solid #f1ebf5; }
        .switch-row:first-of-type { border-top:0; }
        .switch { position:relative; display:inline-block; width:38px; height:22px; flex:0 0 38px; }
        .switch input { position:absolute; inset:0; width:100%; height:100%; margin:0; opacity:0; cursor:pointer; z-index:1; }
        .switch-track { position:absolute; inset:0; background:#d8cedf; border-radius:999px; transition:.2s; }
        .switch-track::before { content:''; position:absolute; width:16px; height:16px; left:3px; top:3px; background:white; border-radius:50%; transition:.2s; box-shadow:0 1px 3px rgba(0,0,0,.25); }
        .switch input:checked + .switch-track { background:var(--primary); }
        .switch input:checked + .switch-track::before { transform:translateX(16px); }
        .switch input:focus-visible + .switch-track { box-shadow:0 0 0 3px rgba(69,6,147,.25); }

        .content { padding:31px 34px 42px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:24px; }
        .page-header h2 { margin-bottom:5px; font-size:23px; letter-spacing:-.6px; color:var(--navy); }
        .page-header p { font-size:12px; line-height:1.55; color:var(--muted); }
        .card { padding:24px; background:rgba(255,255,255,.96); border:1px solid var(--border); border-radius:14px; box-shadow:0 10px 30px rgba(48,16,69,.055); }
        .button { min-height:39px; display:inline-flex; justify-content:center; align-items:center; padding:9px 15px; font-size:11px; font-weight:800; color:white; background:var(--primary); border:1px solid var(--primary); border-radius:8px; box-shadow:0 6px 14px rgba(69,6,147,.12); cursor:pointer; transition:.2s; }
        .button:hover { background:var(--primary-light); border-color:var(--primary-light); transform:translateY(-1px); }
        .button-secondary { color:var(--primary); background:#f6effb; border-color:#dbc6eb; box-shadow:none; }
        .button-secondary:hover { color:white; background:var(--primary); border-color:var(--primary); }
        .button-danger { color:var(--danger); background:#fff5f4; border-color:#fecaca; box-shadow:none; }
        .button-danger:hover { color:white; background:var(--danger); border-color:var(--danger); }
        .input { width:100%; min-height:42px; padding:10px 12px; font-size:12px; color:#322939; background:white; border:1px solid #d8cedf; border-radius:8px; outline:none; transition:.2s; }
        .input:focus { border-color:var(--primary-light); box-shadow:0 0 0 3px rgba(69,6,147,.08); }
        label { display:block; margin-bottom:7px; font-size:11px; font-weight:750; color:#413649; }
        .form-grid,.filters { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
        .filters { grid-template-columns:repeat(4,1fr); margin-bottom:20px; padding:15px; background:#faf8fb; border:1px solid #eee7f1; border-radius:10px; }
        .form-group { margin-bottom:4px; }
        .form-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:20px; padding-top:18px; border-top:1px solid #eee8f1; }
        .error { margin-top:5px; font-size:10px; color:var(--danger); }
        .table-wrap { overflow-x:auto; border:1px solid #eee7f1; border-radius:10px; }
        table { width:100%; border-collapse:collapse; background:white; }
        th,td { padding:13px; text-align:left; border-bottom:1px solid #eee8f1; }
        th { font-size:9px; font-weight:850; letter-spacing:.65px; color:#786d80; background:#faf8fb; text-transform:uppercase; }
        td { font-size:11px; }
        tbody tr:last-child td { border-bottom:0; }
        tbody tr:hover { background:#fdfbfe; }
        .actions { display:flex; align-items:center; flex-wrap:wrap; gap:7px; }
        .badge { display:inline-block; padding:5px 9px; font-size:9px; font-weight:800; color:var(--primary); background:#f2e7fa; border:1px solid #e4d0f1; border-radius:20px; text-transform:capitalize; }

        .notice-modal { position:fixed; z-index:2000; inset:0; display:grid; place-items:center; padding:20px; background:rgba(31,5,57,.65); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); }
        .notice-dialog { width:min(430px,100%); padding:30px; text-align:center; background:white; border-top:4px solid var(--gold); border-radius:14px; box-shadow:0 28px 80px rgba(24,3,48,.3); }
        .notice-icon { width:50px; height:50px; display:grid; place-items:center; margin:0 auto 15px; color:#2d1b00; background:var(--gold-soft); border:1px solid #efd486; border-radius:50%; }
        .notice-icon svg { width:24px; height:24px; }
        .notice-dialog h2 { margin-bottom:9px; font-size:21px; color:var(--navy); }
        .notice-dialog p { font-size:12px; line-height:1.6; color:var(--muted); }
        .notice-dialog ul { margin:14px 0 0; padding-left:24px; font-size:11px; color:var(--danger); text-align:left; }
        .notice-guidance { margin-top:14px; padding:13px 14px; text-align:left; background:#f8f2fc; border:1px solid #e4d0f1; border-radius:10px; }
        .notice-guidance strong { display:block; margin-bottom:5px; font-size:11px; color:var(--primary); }
        .notice-guidance p { color:#5d5266; }

        @media(max-width:950px) {
            .sidebar { position:static; width:100%; padding-bottom:18px; }
            .sidebar::before { display:none; }
            .app { display:block; }
            .main { width:100%; margin:0; }
            .brand { margin-bottom:16px; }
            .department-chip { width:max-content; margin-bottom:15px; }
            .menu-label { margin-top:14px; }
            .topbar,.content { padding-left:20px; padding-right:20px; }
            .filters,.form-grid { grid-template-columns:1fr !important; }
        }
        @media(max-width:600px) {
            .topbar { align-items:flex-start; flex-direction:column; gap:12px; }
            .profile { width:100%; }
            .content { padding-top:23px; }
            .page-header { align-items:flex-start; flex-direction:column; }
            .page-header .button { width:100%; }
            .card { padding:18px; }
        }
    </style>
    @include('layouts.partials.sidebar-toggle-styles')
    @stack('styles')
    @include('layouts.partials.portal-unified-theme')
    @include('layouts.partials.schedule-notification-styles')
    <style>
        body.dean-department-portal,
        body.dean-department-portal .app,
        body.dean-department-portal .main,
        body.dean-department-portal .content {
            background-color:#fff;
        }

        body.dean-department-portal .main {
            position:relative;
            isolation:isolate;
            background-image:none;
        }

        body.dean-department-portal .main::before {
            content:'';
            position:fixed;
            z-index:0;
            top:82px;
            right:0;
            bottom:0;
            left:0;
            background-color:#fff;
            background-image:none;
            opacity:1;
            pointer-events:none;
        }

        body.dean-department-portal .main::after {
            content:none;
            display:none;
        }

        body.dean-department-portal .topbar,
        body.dean-department-portal .content {
            position:relative;
            z-index:2;
        }

        body.dean-department-portal .content {
            background-color:transparent;
        }

        body.dean-department-portal .content :is(.card,.welcome,.welcome-card,.stat-card,.stat) {
            color:#180d20;
            background-color:rgba(255,255,255,.68) !important;
            background-image:none !important;
            border-color:rgba(69,6,147,.28) !important;
        }

        body.dean-department-portal .content :is(.card,.welcome,.welcome-card,.stat-card,.stat) :is(h1,h2,h3,h4,p,span,strong,label,td,th) {
            color:#180d20 !important;
        }

        body.dean-department-portal .content .filters {
            background-color:rgba(255,255,255,.72);
            background-image:none;
        }

        body.dean-department-portal .content .filters .input {
            color:#180d20;
        }

        body.dean-department-portal .content .filters .input::placeholder {
            color:#3b2944;
            opacity:1;
        }

        body.dean-department-portal .content .card :is(.table-wrap,.table-wrapper) {
            background:rgba(255,255,255,.75);
        }

        body.dean-department-portal .content .card th {
            background:rgba(248,243,251,.85);
        }

        body.dean-department-portal .content .card :is(.badge,.role-badge) {
            color:#450693 !important;
        }

        body.dean-department-portal .content .card .portal-page-button.is-active {
            color:#fff !important;
        }

        body.dean-department-portal .content .report-card .report-symbol,
        body.dean-department-portal .content .report-card .button span {
            color:#fff !important;
        }

        body.dean-department-portal .content .card .instructor-mark {
            display:grid;
            place-items:center;
            color:#fff !important;
            font-size:18px;
            font-weight:850;
            line-height:1;
        }

        @media(max-width:950px) {
            body.dean-department-portal .main::after {
                left:0;
                background-size:min(66vmin,610px) min(66vmin,610px);
            }
        }

        @media(max-width:600px) {
            body.dean-department-portal .main::before,
            body.dean-department-portal .main::after {
                top:110px;
            }

            body.dean-department-portal .main::after {
                background-size:min(74vmin,420px) min(74vmin,420px);
            }
        }

    </style>
    @include('layouts.partials.scrollbar-styles')
</head>
<body class="dean-department-portal" style="--dean-department-logo:url('{{ asset($deanDepartmentLogo) }}')">
<div class="app">
    <aside id="portalSidebar" class="sidebar portal-sidebar">
        <div class="portal-sidebar-navigation">
        <a href="{{ route('dean.dashboard') }}" class="brand"><span class="brand-icon brand-icon--scheduler"><img src="{{ asset('images/mcc-scheduler-logo.png') }}" alt="MCC Scheduler logo"></span><span class="brand-copy"><strong>MCC | Scheduler</strong><small>Dean Portal</small></span></a>
        <div class="department-chip"><span class="department-dot"></span>{{ auth()->user()->course }} Department</div>

        <p class="menu-label">Overview</p>
        <a class="menu-link {{ request()->routeIs('dean.dashboard') ? 'active' : '' }}" href="{{ route('dean.dashboard') }}"><span class="menu-icon"><x-icon name="home" /></span>Dashboard</a>

        <p class="menu-label">Academic Management</p>
        <a class="menu-link {{ request()->routeIs('dean.students.*') ? 'active' : '' }}" href="{{ route('dean.students.index') }}"><span class="menu-icon"><x-icon name="users" /></span>Student List</a>
        <a class="menu-link {{ request()->routeIs('dean.sections.*') ? 'active' : '' }}" href="{{ route('dean.sections.index') }}"><span class="menu-icon"><x-icon name="grid" /></span>Sections</a>
        <a class="menu-link {{ request()->routeIs('dean.subjects.*') ? 'active' : '' }}" href="{{ route('dean.subjects.index') }}"><span class="menu-icon"><x-icon name="book" /></span>Subjects</a>
        <a class="menu-link {{ request()->routeIs('dean.rooms.*') ? 'active' : '' }}" href="{{ route('dean.rooms.index') }}"><span class="menu-icon"><x-icon name="building" /></span>Rooms</a>

        <p class="menu-label">Instructor Management</p>
        <a class="menu-link {{ request()->routeIs('dean.instructors.*') ? 'active' : '' }}" href="{{ route('dean.instructors.index') }}"><span class="menu-icon"><x-icon name="cap" /></span>Instructor List</a>
        <a class="menu-link {{ request()->routeIs('dean.instructor-units.*') ? 'active' : '' }}" href="{{ route('dean.instructor-units.index') }}"><span class="menu-icon"><x-icon name="clipboard" /></span>Instructor Units</a>
        <a class="menu-link {{ request()->routeIs('dean.subject-assignments.*') ? 'active' : '' }}" href="{{ route('dean.subject-assignments.index') }}"><span class="menu-icon"><x-icon name="link" /></span>Subject Assignment</a>
        <a class="menu-link {{ request()->routeIs('dean.instructor-requests.*') ? 'active' : '' }}" href="{{ route('dean.instructor-requests.index') }}"><span class="menu-icon"><x-icon name="users" /></span>Instructor Requests</a>

        <p class="menu-label">Scheduling</p>
        <a class="menu-link {{ request()->routeIs('dean.schedules.*') ? 'active' : '' }}" href="{{ route('dean.schedules.create') }}"><span class="menu-icon"><x-icon name="calendar-plus" /></span>Create Schedule</a>
        <a class="menu-link {{ request()->routeIs('dean.timetable.*') ? 'active' : '' }}" href="{{ route('dean.timetable.index') }}"><span class="menu-icon"><x-icon name="calendar-grid" /></span>Timetable</a>
        <a class="menu-link {{ request()->routeIs('dean.archive.*') ? 'active' : '' }}" href="{{ route('dean.archive.index') }}"><span class="menu-icon"><x-icon name="archive" /></span>Archive</a>
        <a class="menu-link {{ request()->routeIs('dean.print.*') ? 'active' : '' }}" href="{{ route('dean.print.index') }}"><span class="menu-icon"><x-icon name="printer" /></span>Print Reports</a>
        </div>
        @include('layouts.partials.portal-sidebar-logout')
    </aside>
    <button id="sidebarBackdrop" class="sidebar-backdrop" type="button" aria-label="Close navigation menu"></button>

    <main class="main">
        <header class="topbar">
            <div class="topbar-start">@include('layouts.partials.sidebar-toggle')<div><span class="topbar-label">Dean workspace</span><h1>@yield('page-title','Dean Portal')</h1></div></div>
            <div class="topbar-actions">
                @php $deanDepartment = auth()->user()->department; @endphp
                <details class="topbar-settings-menu" @if($errors->has('semester_availability')) open @endif>
                    <summary class="topbar-settings-trigger" aria-label="Semester settings" title="Semester settings"><x-icon name="gear" /></summary>
                    <div class="topbar-settings-dropdown">
                        <strong>Active Semester</strong>
                        <p>Choose exactly one semester. The other semesters are hidden from dean-portal filters and cannot generate new schedules.</p>
                        <form method="POST" action="{{ route('dean.settings.semesters') }}">
                            @csrf
                            @method('PATCH')
                            <label class="switch-row">
                                <span>1st Semester</span>
                                <span class="switch"><input type="checkbox" name="semester_first_enabled" value="1" @checked($deanDepartment?->semester_first_enabled ?? true)><span class="switch-track"></span></span>
                            </label>
                            <label class="switch-row">
                                <span>2nd Semester</span>
                                <span class="switch"><input type="checkbox" name="semester_second_enabled" value="1" @checked($deanDepartment?->semester_second_enabled ?? true)><span class="switch-track"></span></span>
                            </label>
                            <label class="switch-row">
                                <span>Summer</span>
                                <span class="switch"><input type="checkbox" name="semester_summer_enabled" value="1" @checked($deanDepartment?->semester_summer_enabled ?? true)><span class="switch-track"></span></span>
                            </label>
                            @error('semester_availability')<p class="error" style="margin:10px 0 0">{{ $message }}</p>@enderror
                            <button class="button" type="submit" style="width:100%;margin-top:12px">Save</button>
                        </form>
                    </div>
                </details>
                @include('layouts.partials.schedule-notifications')
                @include('layouts.partials.portal-profile-menu',['portalRoleLabel'=>'Dean'])
            </div>
        </header>
        <section class="content">@yield('content')</section>
    </main>
</div>
@stack('portal-profile-overlay')

@php $hasNotice=session()->has('success')||session()->has('error')||($errors->any()&&!old('profile_modal')&&!old('section_modal')&&!old('subject_modal')&&!old('assignment_modal')&&!old('room_modal')&&!old('instructor_modal')&&!old('instructor_import_modal')&&!old('student_modal')&&!old('student_import_modal')&&!old('section_import_modal')&&!old('subject_import_modal')&&!old('room_import_modal')); @endphp
@if($hasNotice)
<div id="deanNotice" class="notice-modal"><section class="notice-dialog" role="dialog" aria-modal="true" aria-labelledby="deanNoticeTitle">
    <div class="notice-icon"><x-icon :name="session()->has('success') ? 'check' : 'warning'" /></div>
    <h2 id="deanNoticeTitle">{{ session()->has('success') ? 'Success' : 'Action unsuccessful' }}</h2>
    @if(session('success'))<p>{{ session('success') }}</p>@endif
    @if(session('error'))<p>{{ session('error') }}</p>@endif
    @if(session('error_note'))
        <div class="notice-guidance">
            <strong>What you can do</strong>
            <p>{{ session('error_note') }}</p>
        </div>
    @endif
    @if($errors->any())<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <button id="closeDeanNotice" class="button" style="margin-top:20px" type="button">OK</button>
</section></div>
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
@if($hasNotice)<script>(()=>{const m=document.getElementById('deanNotice'),b=document.getElementById('closeDeanNotice');document.body.classList.add('modal-open');const close=()=>{m.remove();document.body.classList.remove('modal-open')};b.focus();b.onclick=close;m.onclick=e=>{if(e.target===m)close()};document.addEventListener('keydown',e=>{if(e.key==='Escape')close()})})();</script>@endif
</body>
</html>
