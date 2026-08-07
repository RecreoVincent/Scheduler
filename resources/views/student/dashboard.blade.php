@extends('layouts.student')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
@include('layouts.partials.dashboard-analytics-card-styles')
<style>
    .welcome { display:flex; align-items:center; justify-content:space-between; gap:20px; margin-bottom:24px; }
    .welcome h2 { margin-bottom:6px; color:var(--navy); }
    .welcome p { color:var(--muted); }
    .card h3 { margin-bottom:16px; color:var(--navy); }
    .schedule-row { display:grid; grid-template-columns:95px 1fr auto; gap:14px; align-items:center; padding:13px 0; border-bottom:1px solid #e2e8f0; font-size:13px; }
    .schedule-row:last-child { border:0; }
    .schedule-time { font-weight:700; color:#1d4ed8; }
    .schedule-details strong,.schedule-details span { display:block; }
    .schedule-details span { margin-top:3px; color:var(--muted); }
    @media(max-width:650px) { .welcome { align-items:flex-start; flex-direction:column; } .schedule-row { grid-template-columns:1fr; } }
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

@php
    $studentCards = [
        ['label'=>'Distinct Subjects','key'=>'subjects','color'=>'#3b82f6'], ['label'=>'Total Units','key'=>'units','color'=>'#8b5cf6'], ['label'=>'Weekly Hours','key'=>'hours','color'=>'#14b8a6'],
    ];
    $studentMaximum = max(1, ...array_map('floatval', array_values($statistics)));
@endphp
<div class="stats portal-analytics-grid">
    @foreach($studentCards as $card)
        @php($percentage = (int) round(((float) $statistics[$card['key']] / $studentMaximum) * 100))
        <button type="button" class="card stat-card portal-analytics-card" data-stat="{{ $card['key'] }}" data-label="{{ $card['label'] }}" style="--analytics-accent:{{ $card['color'] }};--analytics-progress:{{ $percentage }}%">
            <span class="portal-analytics-header"><span class="portal-analytics-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19V8M10 19V4M16 19v-7M22 19H2"/></svg></span><span class="portal-analytics-title">{{ $card['label'] }}</span></span>
            <span class="portal-analytics-metric"><span class="portal-analytics-label">Current total</span><strong class="portal-analytics-value">{{ $statistics[$card['key']] }}</strong></span>
            <span class="portal-analytics-progress"><span class="portal-analytics-progress-fill"></span></span>
            <span class="portal-analytics-footer">{{ $percentage }}% relative to highest metric</span>
        </button>
    @endforeach
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

@include('layouts.partials.dashboard-analytics-modal', [
    'analyticsModalId' => 'studentAnalyticsModal',
    'analyticsModalDescription' => 'Compare your current study-load information.',
    'analyticsCardSelector' => '.portal-analytics-card[data-stat]',
    'analyticsModalData' => collect($studentCards)->map(fn($card) => ['label'=>$card['label'], 'value'=>$statistics[$card['key']], 'color'=>$card['color']])->values()->all(),
])
@endsection
