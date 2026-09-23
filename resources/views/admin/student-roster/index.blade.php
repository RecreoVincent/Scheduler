@extends('layouts.admin')

@section('title', 'Student Roster')
@section('page-title', 'Student Roster')

@push('styles')
<style>
    .roster-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-bottom:20px; }
    .roster-stat-card { display:flex; align-items:center; gap:14px; padding:18px 20px; background:rgba(255,255,255,.92); border:1px solid var(--border); border-radius:17px; box-shadow:0 10px 28px rgba(15,23,42,.05); }
    .roster-stat-icon { display:grid; place-items:center; flex:0 0 44px; width:44px; height:44px; color:var(--primary); background:color-mix(in srgb,var(--primary) 12%,white); border-radius:12px; }
    .roster-stat-icon svg { width:22px; height:22px; }
    .roster-stat-body span { display:block; font-size:12px; font-weight:700; color:var(--muted); letter-spacing:.2px; }
    .roster-stat-body strong { display:block; margin-top:2px; font-size:26px; color:var(--navy); }
    .roster-import-form { display:flex; flex-wrap:wrap; align-items:end; gap:14px; }
    .roster-import-field { display:flex; flex-direction:column; gap:6px; width:100%; max-width:360px; }
    .roster-search-form { display:flex; flex-wrap:wrap; gap:10px; }
    .roster-search-form .input { flex:1; min-width:200px; }
    .roster-table { width:100%; table-layout:fixed; }
    .roster-table th, .roster-table td { text-align:left; overflow-wrap:anywhere; }
    .roster-table th:nth-child(1), .roster-table td:nth-child(1) { width:12%; }
    .roster-table th:nth-child(2), .roster-table td:nth-child(2) { width:22%; }
    .roster-table th:nth-child(3), .roster-table td:nth-child(3) { width:11%; }
    .roster-table th:nth-child(4), .roster-table td:nth-child(4) { width:14%; }
    .roster-table th:nth-child(5), .roster-table td:nth-child(5) { width:15%; }
    .roster-table th:nth-child(6), .roster-table td:nth-child(6) { width:14%; }
    .roster-table th:nth-child(7), .roster-table td:nth-child(7) { width:12%; }
    .roster-actions { display:flex; flex-wrap:wrap; gap:7px; }
    .roster-actions .button { min-width:0; padding:8px 11px; font-size:12px; }
    @media(max-width:760px) { .roster-stats { grid-template-columns:1fr; } .roster-import-field { max-width:none; } .roster-import-form .button { width:100%; } .roster-table { min-width:900px; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>Student ID Roster</h2>
        <p>Students can sign in to the Student Portal when their student number and last name match this roster.</p>
    </div>
    <button id="openRosterCreate" class="button" type="button">+ Add Roster Record</button>
</div>

<div class="roster-stats">
    <div class="roster-stat-card"><span class="roster-stat-icon"><x-icon name="users" /></span><div class="roster-stat-body"><span>Total imported</span><strong>{{ $statistics['total'] }}</strong></div></div>
    <div class="roster-stat-card"><span class="roster-stat-icon"><x-icon name="check" /></span><div class="roster-stat-body"><span>Portal accounts created</span><strong>{{ $statistics['registered'] }}</strong></div></div>
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

<div class="card">
    <form class="filters roster-search-form" method="GET">
        <input class="input" type="search" name="search" value="{{ $search }}" placeholder="Search student ID, name, department, or section">
        <button class="button" type="submit">Search</button>
    </form>
    <div class="table-wrap">
        <table class="roster-table">
            <thead><tr><th>Student ID</th><th>Name</th><th>Department</th><th>Section</th><th>Status</th><th>Last Imported</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($roster as $entry)
                    <tr>
                        <td><strong>{{ $entry->student_id }}</strong></td>
                        <td>{{ $entry->full_name }}</td>
                        <td>{{ $entry->course ?: '—' }}</td>
                        <td>{{ $entry->section ?: '—' }}</td>
                        <td>@if(in_array($entry->student_id, $registeredStudentIds, true))<span class="badge">Portal account created</span>@else<span class="badge" style="color:#64748b">Not signed in yet</span>@endif</td>
                        <td>{{ $entry->imported_at?->format('M d, Y g:i A') ?: '—' }}</td>
                        <td><div class="roster-actions">
                            <a class="button button-secondary" href="{{ route('admin.student-roster.index', array_merge(request()->query(), ['edit' => $entry->id])) }}#rosterFormModal">Edit</a>
                            <button type="button" class="button button-danger roster-delete-trigger" data-delete-url="{{ route('admin.student-roster.destroy', $entry) }}" data-student-name="{{ $entry->full_name }}">Delete</button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No student roster records have been imported.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$roster" label="Student roster pages" />
</div>

<div id="rosterFormModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="rosterFormTitle">
        <header class="admin-profile-header">
            <div><h2 id="rosterFormTitle">{{ $editingRoster ? 'Edit Roster Record' : 'Add Roster Record' }}</h2><p>{{ $editingRoster ? 'Update the official student roster information.' : 'Create an official student roster record.' }}</p></div>
            <button class="admin-profile-close" type="button" data-close-roster-form aria-label="Close roster form">&times;</button>
        </header>
        <form id="rosterForm" method="POST" action="{{ $editingRoster ? route('admin.student-roster.update', $editingRoster) : route('admin.student-roster.store') }}">
            @csrf
            @if($editingRoster) @method('PATCH') @endif
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field"><label for="roster_student_id">Student ID</label><input id="roster_student_id" class="input" name="student_id" value="{{ old('student_id', $editingRoster?->student_id) }}" required>@error('student_id')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="roster_full_name">Full Name</label><input id="roster_full_name" class="input" name="full_name" value="{{ old('full_name', $editingRoster?->full_name) }}" required>@error('full_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="roster_course">Department</label><select id="roster_course" class="input" name="course" required><option value="">Select department</option>@foreach($courses as $course)<option value="{{ $course }}" @selected(old('course', $editingRoster?->course) === $course)>{{ $course }}</option>@endforeach</select>@error('course')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label for="roster_section">Section</label><input id="roster_section" class="input" name="section" value="{{ old('section', $editingRoster?->section) }}" placeholder="Example: 1 - East">@error('section')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
            </div>
            <footer class="admin-profile-actions"><button class="button button-secondary" type="button" data-close-roster-form>Cancel</button><button class="button" type="submit">{{ $editingRoster ? 'Save Changes' : 'Create Record' }}</button></footer>
        </form>
    </section>
</div>

<div id="rosterDeleteModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="rosterDeleteTitle">
        <header class="admin-profile-header"><div><h2 id="rosterDeleteTitle">Remove Roster Record?</h2><p>Remove <strong id="rosterDeleteName"></strong> from the official roster. The student portal account will not be deleted.</p></div></header>
        <form id="rosterDeleteForm" method="POST">@csrf @method('DELETE')<footer class="admin-profile-actions"><button class="button button-secondary" type="button" id="cancelRosterDelete">Cancel</button><button class="button button-danger" type="submit" id="confirmRosterDelete">Remove Record</button></footer></form>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('rosterFormModal');
        const openButton = document.getElementById('openRosterCreate');
        const closeButtons = [...modal.querySelectorAll('[data-close-roster-form]')];
        const firstInput = document.getElementById('roster_student_id');
        const open = () => { modal.hidden = false; document.body.classList.add('modal-open'); window.setTimeout(() => firstInput.focus(), 0); };
        const close = () => {
            @if($editingRoster) window.location = '{{ route('admin.student-roster.index', request()->except('edit')) }}'; return; @endif
            modal.hidden = true; document.body.classList.remove('modal-open'); openButton.focus();
        };
        openButton.addEventListener('click', open);
        closeButtons.forEach(button => button.addEventListener('click', close));
        modal.addEventListener('click', event => { if (event.target === modal) close(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) close(); });
        @if($errors->hasAny(['student_id', 'full_name', 'course', 'section']) || $editingRoster) open(); @endif
    })();

    (() => {
        const modal = document.getElementById('rosterDeleteModal');
        const form = document.getElementById('rosterDeleteForm');
        const name = document.getElementById('rosterDeleteName');
        const cancel = document.getElementById('cancelRosterDelete');
        const confirm = document.getElementById('confirmRosterDelete');
        let trigger;
        const close = () => { modal.hidden = true; document.body.classList.remove('modal-open'); form.removeAttribute('action'); trigger?.focus(); };
        document.querySelectorAll('.roster-delete-trigger').forEach(button => button.addEventListener('click', () => { trigger = button; form.action = button.dataset.deleteUrl; name.textContent = button.dataset.studentName; modal.hidden = false; document.body.classList.add('modal-open'); cancel.focus(); }));
        cancel.addEventListener('click', close);
        modal.addEventListener('click', event => { if (event.target === modal) close(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) close(); });
        form.addEventListener('submit', () => { confirm.disabled = true; confirm.textContent = 'Removing...'; });
    })();
</script>
@endpush
