@extends('layouts.admin')

@section('title', 'Instructor Workload')
@section('page-title', 'Instructor Workload')

@section('content')
<div class="page-header">
    <div><h2>Instructor Workload</h2><p>Current instructors grouped by assigned program.</p></div>
    <a class="button button-secondary" href="{{ route('admin.reports.index') }}">Back to Reports</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Instructor</th><th>Email</th><th>Course</th></tr></thead>
            <tbody>
            @forelse ($instructors as $instructor)
                <tr><td>{{ $instructor->name }}</td><td>{{ $instructor->email }}</td><td>{{ $instructor->course ?? 'Not assigned' }}</td></tr>
            @empty
                <tr><td colspan="3">No instructors found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
