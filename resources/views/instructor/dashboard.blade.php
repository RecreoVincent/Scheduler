@extends('layouts.instructor')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@push('styles')
@include('layouts.partials.dashboard-analytics-card-styles')
<style>
    .welcome { margin-bottom:24px; } .welcome h2 { margin-bottom:6px; color:var(--navy); } .welcome p { color:var(--muted); }
    .dashboard-grid { display:grid; grid-template-columns:1.2fr .8fr; gap:20px; } .card h3 { margin-bottom:16px; color:var(--navy); }
    .schedule-row { display:grid; grid-template-columns:95px 1fr auto; gap:14px; align-items:center; padding:13px 0; border-bottom:1px solid #e2e8f0; font-size:13px; }
    .schedule-row:last-child { border:0; } .schedule-time { font-weight:700; color:#1d4ed8; } .schedule-details strong,.schedule-details span { display:block; } .schedule-details span { margin-top:3px; color:var(--muted); }
    .section-list { display:flex; flex-wrap:wrap; gap:9px; }
    @media(max-width:1050px){.dashboard-grid{grid-template-columns:1fr}} @media(max-width:520px){.schedule-row{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="welcome"><h2>Welcome, {{ auth()->user()->first_name }}</h2><p>Here is an overview of your assigned teaching workload.</p></div>
@php
    $instructorCards = [
        ['label'=>'Assigned Sections','key'=>'sections','color'=>'#3b82f6'], ['label'=>'Distinct Subjects','key'=>'subjects','color'=>'#8b5cf6'], ['label'=>'Weekly Hours','key'=>'hours','color'=>'#14b8a6'],
    ];
    $instructorMaximum = max(1, ...array_map('floatval', array_values($statistics)));
@endphp
<div class="stats portal-analytics-grid">
    @foreach($instructorCards as $card)
        @php($percentage = (int) round(((float) $statistics[$card['key']] / $instructorMaximum) * 100))
        <button type="button" class="card stat-card portal-analytics-card" data-stat="{{ $card['key'] }}" data-label="{{ $card['label'] }}" style="--analytics-accent:{{ $card['color'] }};--analytics-progress:{{ $percentage }}%">
            <span class="portal-analytics-header"><span class="portal-analytics-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19V8M10 19V4M16 19v-7M22 19H2"/></svg></span><span class="portal-analytics-title">{{ $card['label'] }}</span></span>
            <span class="portal-analytics-metric"><span class="portal-analytics-label">Current total</span><strong class="portal-analytics-value">{{ $statistics[$card['key']] }}</strong></span>
            <span class="portal-analytics-progress"><span class="portal-analytics-progress-fill"></span></span>
            <span class="portal-analytics-footer">{{ $percentage }}% relative to highest metric</span>
        </button>
    @endforeach
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

@include('layouts.partials.dashboard-analytics-modal', [
    'analyticsModalId' => 'instructorAnalyticsModal',
    'analyticsModalDescription' => 'Compare your current teaching workload.',
    'analyticsCardSelector' => '.portal-analytics-card[data-stat]',
    'analyticsModalData' => collect($instructorCards)->map(fn($card) => ['label'=>$card['label'], 'value'=>$statistics[$card['key']], 'color'=>$card['color']])->values()->all(),
])
@endsection
