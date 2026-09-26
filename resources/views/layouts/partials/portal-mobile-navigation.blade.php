@php
    $mobilePortalRole = auth()->user()?->role;
    $mobileNavItems = match ($mobilePortalRole) {
        'admin' => [
            ['label' => 'Home', 'icon' => 'home', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Accounts', 'icon' => 'users', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.index', 'admin.users.create', 'admin.users.edit')],
            ['label' => 'Roster', 'icon' => 'clipboard', 'route' => 'admin.student-roster.index', 'active' => request()->routeIs('admin.student-roster.*')],
            ['label' => 'Deleted', 'icon' => 'trash', 'route' => 'admin.users.deleted', 'active' => request()->routeIs('admin.users.deleted')],
        ],
        'dean' => [
            ['label' => 'Home', 'icon' => 'home', 'route' => 'dean.dashboard', 'active' => request()->routeIs('dean.dashboard')],
            ['label' => 'Create', 'icon' => 'calendar-plus', 'route' => 'dean.schedules.create', 'active' => request()->routeIs('dean.schedules.*')],
            ['label' => 'Timetable', 'icon' => 'calendar-grid', 'route' => 'dean.timetable.index', 'active' => request()->routeIs('dean.timetable.*')],
            ['label' => 'Subjects', 'icon' => 'book', 'route' => 'dean.subjects.index', 'active' => request()->routeIs('dean.subjects.*')],
        ],
        'gec' => [
            ['label' => 'Home', 'icon' => 'home', 'route' => 'gec.dashboard', 'active' => request()->routeIs('gec.dashboard')],
            ['label' => 'Create', 'icon' => 'calendar-plus', 'route' => 'gec.schedules.create', 'active' => request()->routeIs('gec.schedules.*')],
            ['label' => 'Timetable', 'icon' => 'calendar-grid', 'route' => 'gec.timetable.index', 'active' => request()->routeIs('gec.timetable.*')],
            ['label' => 'Subjects', 'icon' => 'book', 'route' => 'gec.subjects.index', 'active' => request()->routeIs('gec.subjects.*')],
        ],
        'instructor' => [
            ['label' => 'Home', 'icon' => 'home', 'route' => 'instructor.dashboard', 'active' => request()->routeIs('instructor.dashboard')],
            ['label' => 'Workload', 'icon' => 'clipboard', 'route' => 'instructor.workload.index', 'active' => request()->routeIs('instructor.workload.*')],
            ['label' => 'Scan', 'icon' => 'qrcode', 'route' => 'instructor.scanner.index', 'active' => request()->routeIs('instructor.scanner.*')],
        ],
        'student' => [
            ['label' => 'Home', 'icon' => 'home', 'route' => 'student.dashboard', 'active' => request()->routeIs('student.dashboard')],
            ['label' => 'Study Load', 'icon' => 'clipboard', 'route' => 'student.study-load.index', 'active' => request()->routeIs('student.study-load.*')],
            ['label' => 'Scan', 'icon' => 'qrcode', 'route' => 'student.scanner.index', 'active' => request()->routeIs('student.scanner.*')],
        ],
        default => [],
    };
@endphp

@if($mobileNavItems !== [])
<nav class="mobile-bottom-nav" aria-label="Mobile portal navigation" style="--mobile-nav-count:{{ count($mobileNavItems) + 1 }}">
    @foreach($mobileNavItems as $item)
        <a class="mobile-bottom-nav-item {{ $item['active'] ? 'is-active' : '' }}" href="{{ route($item['route']) }}" @if($item['active']) aria-current="page" @endif>
            <x-icon :name="$item['icon']" />
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
    <button id="mobileMoreNavigation" class="mobile-bottom-nav-item {{ collect($mobileNavItems)->contains('active', true) ? '' : 'is-active' }}" type="button" aria-controls="portalSidebar" aria-expanded="false" aria-label="More navigation options">
        <x-icon name="grid" />
        <span>More</span>
    </button>
</nav>
@endif
