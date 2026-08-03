@extends('layouts.admin')

@section('title', 'Instructor Workload')
@section('page-title', 'Instructor Workload')

@section('content')
<div class="page-header">
    <div><h2>Instructor Workload</h2><p>Current instructors grouped by assigned program.</p></div>
    <a class="button button-secondary" href="{{ route('admin.reports.index') }}">Back to Reports</a>
</div>

<div class="card">
    <table style="width:100%; border-collapse:collapse;">
        <thead><tr><th style="text-align:left;padding:12px;">Instructor</th><th style="text-align:left;padding:12px;">Email</th><th style="text-align:left;padding:12px;">Course</th></tr></thead>
        <tbody>
        @forelse ($instructors as $instructor)
            <tr style="border-top:1px solid #e2e8f0;"><td style="padding:12px;">{{ $instructor->name }}</td><td style="padding:12px;">{{ $instructor->email }}</td><td style="padding:12px;">{{ $instructor->course ?? 'Not assigned' }}</td></tr>
        @empty
            <tr><td colspan="3" style="padding:20px;text-align:center;">No instructors found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
