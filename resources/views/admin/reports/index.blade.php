@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
<div class="page-header">
    <div>
        <h2>Academic Reports</h2>
        <p>Choose a report to review current account and workload information.</p>
    </div>
</div>

<div class="card" style="display:grid; gap:14px;">
    <a class="button" href="{{ route('admin.reports.instructor-workload') }}">Instructor Workload</a>
    <a class="button" href="{{ route('admin.reports.study-load') }}">Student Study Load</a>
    <a class="button button-secondary" href="{{ route('admin.reports.summary') }}">Account Summary</a>
</div>
@endsection
