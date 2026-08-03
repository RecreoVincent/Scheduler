@extends('layouts.instructor')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@push('styles')
<style>
    .welcome { margin-bottom:24px; } .welcome h2 { margin-bottom:6px; color:var(--navy); } .welcome p { color:var(--muted); }
    .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; } .stat-card { position:relative; overflow:hidden; }
    .stat-card span { display:block; margin-bottom:10px; font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; } .stat-card strong { font-size:30px; color:var(--navy); }
    .dashboard-grid { display:grid; grid-template-columns:1.2fr .8fr; gap:20px; } .card h3 { margin-bottom:16px; color:var(--navy); }
    .schedule-row { display:grid; grid-template-columns:95px 1fr auto; gap:14px; align-items:center; padding:13px 0; border-bottom:1px solid #e2e8f0; font-size:13px; }
    .schedule-row:last-child { border:0; } .schedule-time { font-weight:700; color:#1d4ed8; } .schedule-details strong,.schedule-details span { display:block; } .schedule-details span { margin-top:3px; color:var(--muted); }
    .section-list { display:flex; flex-wrap:wrap; gap:9px; }
    @media(max-width:1050px){.stats{grid-template-columns:repeat(2,1fr)}.dashboard-grid{grid-template-columns:1fr}} @media(max-width:520px){.stats{grid-template-columns:1fr}.schedule-row{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="welcome"><h2>Welcome, {{ auth()->user()->first_name }}</h2><p>Here is an overview of your assigned teaching workload.</p></div>
<div class="stats">
    <div class="card stat-card"><span>Assigned Sections</span><strong>{{ $statistics['sections'] }}</strong></div>
    <div class="card stat-card"><span>Distinct Subjects</span><strong>{{ $statistics['subjects'] }}</strong></div>
    <div class="card stat-card"><span>Weekly Hours</span><strong>{{ $statistics['hours'] }}</strong></div>
</div>
<div class="dashboard-grid">
    <div class="card"><h3>{{ $today }} Classes</h3>
        @forelse($todaySchedules as $schedule)
            <div class="schedule-row"><span class="schedule-time">{{ date('g:i A',strtotime($schedule->start_time)) }}</span><div class="schedule-details"><strong>{{ $schedule->subject?->code }} · {{ $schedule->subject?->name }}</strong><span>{{ $schedule->section?->name }} · {{ $schedule->room?->name ?? 'TBA' }}</span></div><span class="badge">{{ $schedule->semester }}</span></div>
        @empty<p style="color:var(--muted)">You have no scheduled classes today.</p>@endforelse
    </div>
    <div class="card"><h3>Assigned Sections</h3><div class="section-list">
        @forelse($schedules->pluck('section')->filter()->unique('id')->sortBy('name') as $section)<span class="badge">{{ $section->name }}</span>@empty<p style="color:var(--muted)">No sections assigned yet.</p>@endforelse
    </div><a class="button" style="margin-top:20px" href="{{ route('instructor.workload.index') }}">View Full Workload</a></div>
</div>
@endsection
