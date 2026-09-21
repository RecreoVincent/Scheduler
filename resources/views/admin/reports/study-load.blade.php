@extends('layouts.admin')

@section('title', 'Student Study Load')
@section('page-title', 'Student Study Load')

@section('content')
<div class="page-header">
    <div><h2>Student Study Load</h2><p>Current students and their assigned programs.</p></div>
    <a class="button button-secondary" href="{{ route('admin.reports.index') }}">Back to Reports</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Student</th><th>Email</th><th>Course</th></tr></thead>
            <tbody>
            @forelse ($students as $student)
                <tr><td>{{ $student->name }}</td><td>{{ $student->email }}</td><td>{{ $student->course ?? 'Not assigned' }}</td></tr>
            @empty
                <tr><td colspan="3">No students found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
