@extends('layouts.admin')
@section('title','Student Roster')
@section('page-title','Student Roster')
@push('styles')
<style>
    .roster-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-bottom:20px; }
    @media(max-width:520px) { .roster-stats { grid-template-columns:1fr; } }
    .roster-stat-card {
        display:flex; align-items:center; gap:14px;
        padding:18px 20px;
        background:rgba(255,255,255,.92);
        border:1px solid var(--border);
        border-radius:17px;
        box-shadow:0 10px 28px rgba(15,23,42,.05);
    }
    .roster-stat-icon {
        display:grid; place-items:center; flex:0 0 44px; width:44px; height:44px;
        color:var(--primary); background:color-mix(in srgb, var(--primary) 12%, white); border-radius:12px;
    }
    .roster-stat-icon svg { width:22px; height:22px; }
    .roster-stat-body span { display:block; font-size:12px; font-weight:700; color:var(--muted); letter-spacing:.2px; }
    .roster-stat-body strong { display:block; margin-top:2px; font-size:26px; color:var(--navy); }

    .roster-import-form { display:flex; flex-wrap:wrap; align-items:end; gap:14px; }
    .roster-import-field { display:flex; flex-direction:column; gap:6px; width:100%; max-width:360px; }
    .roster-import-field label { display:block; }
    .roster-search-form { display:flex; flex-wrap:wrap; gap:10px; }
    .roster-search-form .input { flex:1; min-width:200px; }
    @media(max-width:600px) {
        .roster-import-field { max-width:none; }
        .roster-import-form .button { width:100%; }
        .roster-search-form .button { width:100%; }
    }

    .roster-table { width:100%; table-layout:fixed; }
    .roster-table th, .roster-table td { text-align:left; }
    .roster-table th:nth-child(1), .roster-table td:nth-child(1) { width:13%; }
    .roster-table th:nth-child(2), .roster-table td:nth-child(2) { width:24%; }
    .roster-table th:nth-child(3), .roster-table td:nth-child(3) { width:12%; }
    .roster-table th:nth-child(4), .roster-table td:nth-child(4) { width:15%; }
    .roster-table th:nth-child(5), .roster-table td:nth-child(5) { width:16%; }
    .roster-table th:nth-child(6), .roster-table td:nth-child(6) { width:20%; }
    .roster-table td { word-wrap:break-word; }
</style>
@endpush
@section('content')
<div class="page-header"><div><h2>Student ID Roster</h2><p>Students can sign in to the Student Portal when their student number and last name match this roster.</p></div></div>
<div class="roster-stats">
    <div class="roster-stat-card">
        <span class="roster-stat-icon"><x-icon name="users" /></span>
        <div class="roster-stat-body"><span>Total imported</span><strong>{{ $statistics['total'] }}</strong></div>
    </div>
    <div class="roster-stat-card">
        <span class="roster-stat-icon"><x-icon name="check" /></span>
        <div class="roster-stat-body"><span>Portal accounts created</span><strong>{{ $statistics['registered'] }}</strong></div>
    </div>
</div>
<div class="card" style="margin-bottom:20px">
    <h3 style="margin-bottom:7px">Import Student Roster CSV</h3>
    <p style="margin-bottom:15px">Upload a CSV with Student ID, Name, Section, and Department columns. Students are automatically assigned to the matching department; a department can be inferred only when the section belongs to one department.</p>
    <form method="POST" action="{{ route('admin.student-roster.import') }}" enctype="multipart/form-data" class="roster-import-form">
        @csrf
        <div class="roster-import-field">
            <label for="csv_file">CSV file</label>
            <input id="csv_file" class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
            @error('csv_file')<span class="error">{{ $message }}</span>@enderror
        </div>
        <button class="button" type="submit">Import CSV</button>
    </form>
    @if(session('error_note'))<p style="margin-top:12px;color:#b42318;font-size:12px">{{ session('error_note') }}</p>@endif
</div>
<div class="card"><form class="filters roster-search-form" method="GET"><input class="input" type="search" name="search" value="{{ $search }}" placeholder="Search student ID, name, department, or section"><button class="button" type="submit">Search</button></form><div class="table-wrap"><table class="roster-table"><thead><tr><th>Student ID</th><th>Name</th><th>Department</th><th>Section</th><th>Status</th><th>Last imported</th></tr></thead><tbody>@forelse($roster as $entry)<tr><td><strong>{{ $entry->student_id }}</strong></td><td>{{ $entry->full_name }}</td><td>{{ $entry->course ?: '—' }}</td><td>{{ $entry->section ?: '—' }}</td><td>@if(in_array($entry->student_id, $registeredStudentIds, true))<span class="badge">Portal account created</span>@else<span class="badge" style="color:#64748b">Not signed in yet</span>@endif</td><td>{{ $entry->imported_at?->format('M d, Y g:i A') ?: '—' }}</td></tr>@empty<tr><td colspan="6">No student roster records have been imported.</td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$roster" label="Student roster pages" /></div>
@endsection
