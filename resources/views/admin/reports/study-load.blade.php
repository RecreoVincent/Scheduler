@extends('layouts.admin')

@section('title', 'Student Study Load')
@section('page-title', 'Student Study Load')

@section('content')
<div class="page-header">
    <div><h2>Student Study Load</h2><p>Current students and their assigned programs.</p></div>
    <a class="button button-secondary" href="{{ route('admin.reports.index') }}">Back to Reports</a>
</div>

<div class="card">
    <table style="width:100%; border-collapse:collapse;">
        <thead><tr><th style="text-align:left;padding:12px;">Student</th><th style="text-align:left;padding:12px;">Email</th><th style="text-align:left;padding:12px;">Course</th></tr></thead>
        <tbody>
        @forelse ($students as $student)
            <tr style="border-top:1px solid #e2e8f0;"><td style="padding:12px;">{{ $student->name }}</td><td style="padding:12px;">{{ $student->email }}</td><td style="padding:12px;">{{ $student->course ?? 'Not assigned' }}</td></tr>
        @empty
            <tr><td colspan="3" style="padding:20px;text-align:center;">No students found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
