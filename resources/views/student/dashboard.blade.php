@extends('layouts.student')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .welcome { display:flex; align-items:center; justify-content:space-between; gap:20px; margin-bottom:24px; }
    .welcome h2 { margin-bottom:6px; color:var(--navy); }
    .welcome p { color:var(--muted); }
    .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
    .stat-card { position:relative; overflow:hidden; }
    .stat-card span { display:block; margin-bottom:10px; font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; }
    .stat-card strong { font-size:30px; color:var(--navy); }
    .card h3 { margin-bottom:16px; color:var(--navy); }
    .schedule-row { display:grid; grid-template-columns:95px 1fr auto; gap:14px; align-items:center; padding:13px 0; border-bottom:1px solid #e2e8f0; font-size:13px; }
    .schedule-row:last-child { border:0; }
    .schedule-time { font-weight:700; color:#1d4ed8; }
    .schedule-details strong,.schedule-details span { display:block; }
    .schedule-details span { margin-top:3px; color:var(--muted); }
    @media(max-width:900px) { .stats { grid-template-columns:repeat(2,1fr); } }
    @media(max-width:650px) { .welcome { align-items:flex-start; flex-direction:column; } .stats { grid-template-columns:1fr; } .schedule-row { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="welcome">
    <div>
        <h2>Welcome, {{ $student->first_name }}</h2>
        <p>Here is an overview of your assigned class schedule and Study Load.</p>
    </div>
    <a class="button" href="{{ route('student.study-load.index') }}">View Full Study Load</a>
</div>

@unless($student->academicSection)
    <div class="assignment-notice"><strong>No section assigned.</strong> Please contact the administrator so your Study Load and class schedule can be displayed.</div>
@endunless

<div class="stats">
    <div class="card stat-card"><span>Distinct Subjects</span><strong>{{ $statistics['subjects'] }}</strong></div>
    <div class="card stat-card"><span>Total Units</span><strong>{{ $statistics['units'] }}</strong></div>
    <div class="card stat-card"><span>Weekly Hours</span><strong>{{ $statistics['hours'] }}</strong></div>
</div>

<div class="card">
    <h3>{{ $today }} Classes</h3>
    @forelse($todaySchedules as $schedule)
        <div class="schedule-row">
            <span class="schedule-time">{{ date('g:i A', strtotime($schedule->start_time)) }}</span>
            <div class="schedule-details"><strong>{{ $schedule->subject?->code }} · {{ $schedule->subject?->name }}</strong><span>{{ $schedule->instructor?->name }} · {{ $schedule->room?->name ?? 'TBA' }}</span></div>
            <span class="badge">{{ $schedule->semester }}</span>
        </div>
    @empty
        <p style="color:var(--muted)">You have no scheduled classes today.</p>
    @endforelse
</div>
@endsection
