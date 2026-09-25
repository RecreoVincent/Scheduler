@extends('layouts.admin')

@section('title', 'Student Roster')
@section('page-title', 'Student Roster')

@push('styles')
<style>
    .roster-table { width:100%; table-layout:fixed; }
    .roster-table th, .roster-table td { text-align:left; overflow-wrap:anywhere; }
    .roster-table col:nth-child(1) { width:14%; }
    .roster-table col:nth-child(2) { width:16%; }
    .roster-table col:nth-child(3) { width:13%; }
    .roster-table col:nth-child(4) { width:14%; }
    .roster-table col:nth-child(5) { width:15%; }
    .roster-table col:nth-child(6) { width:14%; }
    .roster-table col:nth-child(7) { width:14%; }
    body.admin-institution-portal .content .roster-table :is(th,td) { padding:14px 12px; }
    body.admin-institution-portal .content .roster-table :is(th,td):nth-child(5) { text-align:center; }
    .roster-page-actions, .roster-actions { display:flex; align-items:center; gap:7px; }
    .roster-actions { width:100%; min-width:0; flex-wrap:nowrap; justify-content:space-between; white-space:nowrap; }
    body.admin-institution-portal .content .roster-actions .button {
        min-width:0;
        min-height:38px;
        flex:1 1 0;
        padding:8px 10px;
        font-size:11px;
    }
    body.admin-institution-portal .content .roster-table .roster-status {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:fit-content;
        max-width:100%;
        text-align:center;
        white-space:nowrap;
    }
    body.admin-institution-portal .content .roster-filters {
        background:#fff;
        background-image:none;
        border-color:#e2e8f0;
        box-shadow:none;
    }
    body.admin-institution-portal .content .roster-filters .input {
        color:#334155;
        background:#fff;
        border-color:#cbd5e1;
        box-shadow:none;
    }
    body.admin-institution-portal .content .roster-filters .input::placeholder { color:#64748b; }
    body.admin-institution-portal .content .roster-filters .input:hover { background:#fff; border-color:#94a3b8; }
    body.admin-institution-portal .content .roster-filters .input:focus {
        background:#fff;
        border-color:#94a3b8;
        box-shadow:0 0 0 3px rgba(100,116,139,.16);
    }
    @media(max-width:760px) { .roster-table { min-width:680px; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>Student ID Roster</h2>
        <p>Students can sign in to the Student Portal when their student number and last name match this roster.</p>
    </div>
    <div class="roster-page-actions">
        <button id="openRosterImport" class="button button-secondary" type="button">Import Students</button>
        <a class="button button-secondary" href="{{ route('admin.student-roster.import-template') }}">Download CSV Template</a>
        <button id="openRosterCreate" class="button" type="button">Add Student</button>
    </div>
</div>

<form class="filters roster-filters" method="GET" data-auto-filter>
    <input class="input" type="search" name="search" value="{{ $search }}" placeholder="Search student ID, name, department, or section">
</form>

<div style="display:flex;justify-content:flex-end;margin:14px 0">
    <button type="button" class="button button-danger roster-delete-trigger" data-delete-all="true" data-delete-url="{{ route('admin.student-roster.destroy-all') }}" @disabled($statistics['total'] === 0)>Delete All Students</button>
</div>

<div class="card">
    <x-pagination :paginator="$roster" label="Student roster pages" mode="summary" />
    <div class="table-wrap">
        <table class="roster-table">
            <colgroup>
                <col><col><col><col><col><col><col>
            </colgroup>
            <thead><tr><th>Student ID</th><th>Name</th><th>Department</th><th>Section</th><th>Status</th><th>Last Imported</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($roster as $entry)
                    <tr>
                        <td><strong>{{ $entry->student_id }}</strong></td>
                        <td>{{ $entry->full_name }}</td>
                        <td>{{ $entry->course ?: '—' }}</td>
                        <td>{{ $entry->section ?: '—' }}</td>
                        <td>@if(in_array($entry->student_id, $registeredStudentIds, true))<span class="badge roster-status">Portal account created</span>@else<span class="badge roster-status" style="color:#64748b">Not signed in yet</span>@endif</td>
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
    <x-pagination :paginator="$roster" label="Student roster pages" mode="navigation" />
</div>

<div id="rosterImportModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="rosterImportTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="rosterImportTitle">Import Students</h2>
                <p>Upload the official Student Roster CSV to make its students eligible to create a Student Portal account.</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-roster-import aria-label="Close student import">&times;</button>
        </header>

        <form method="POST" action="{{ route('admin.student-roster.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field full">
                    <label for="csv_file">CSV file</label>
                    <input id="csv_file" class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
                    @error('csv_file')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <p style="margin:0;color:var(--muted);font-size:12px;line-height:1.6">
                        Required columns: <strong>Student ID</strong>, <strong>Name</strong>, and <strong>Department</strong>.
                        <strong>Section</strong> is optional. The Department must match an existing department code, such as BSIT or BSBA.
                        <a href="{{ route('admin.student-roster.import-template') }}">Download the CSV template</a>.
                    </p>
                    @if(session('error_note'))<p style="margin:12px 0 0;color:#b42318;font-size:12px;line-height:1.5">{{ session('error_note') }}</p>@endif
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-roster-import>Cancel</button>
                <button class="button" type="submit">Import Students</button>
            </footer>
        </form>
    </section>
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
        <header class="admin-profile-header"><div><h2 id="rosterDeleteTitle">Remove Roster Record?</h2><p id="rosterDeleteSingleMessage">Remove <strong id="rosterDeleteName"></strong> from the official roster. The student portal account will not be deleted.</p><p id="rosterDeleteAllMessage" hidden>Remove all {{ $statistics['total'] }} official student roster records. Existing student portal accounts will not be deleted.</p></div></header>
        <form id="rosterDeleteForm" method="POST">@csrf @method('DELETE')<footer class="admin-profile-actions"><button class="button button-secondary" type="button" id="cancelRosterDelete">Cancel</button><button class="button button-danger" type="submit" id="confirmRosterDelete">Remove Record</button></footer></form>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('rosterImportModal');
        const openButton = document.getElementById('openRosterImport');
        const closeButtons = [...modal.querySelectorAll('[data-close-roster-import]')];
        const firstInput = document.getElementById('csv_file');

        const open = () => {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        };
        const close = () => {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            openButton.focus();
        };

        openButton.addEventListener('click', open);
        closeButtons.forEach(button => button.addEventListener('click', close));
        modal.addEventListener('click', event => { if (event.target === modal) close(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) close(); });

        @if($errors->has('csv_file') || session('error_note')) open(); @endif
    })();

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
        const title = document.getElementById('rosterDeleteTitle');
        const name = document.getElementById('rosterDeleteName');
        const singleMessage = document.getElementById('rosterDeleteSingleMessage');
        const allMessage = document.getElementById('rosterDeleteAllMessage');
        const cancel = document.getElementById('cancelRosterDelete');
        const confirm = document.getElementById('confirmRosterDelete');
        let trigger;
        const close = () => { modal.hidden = true; document.body.classList.remove('modal-open'); form.removeAttribute('action'); trigger?.focus(); };
        document.querySelectorAll('.roster-delete-trigger').forEach(button => button.addEventListener('click', () => {
            trigger = button;
            const deletingAll = button.dataset.deleteAll === 'true';
            form.action = button.dataset.deleteUrl;
            title.textContent = deletingAll ? 'Remove All Roster Records?' : 'Remove Roster Record?';
            singleMessage.hidden = deletingAll;
            allMessage.hidden = !deletingAll;
            name.textContent = button.dataset.studentName;
            confirm.textContent = deletingAll ? 'Remove All Records' : 'Remove Record';
            modal.hidden = false; document.body.classList.add('modal-open'); cancel.focus();
        }));
        cancel.addEventListener('click', close);
        modal.addEventListener('click', event => { if (event.target === modal) close(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) close(); });
        form.addEventListener('submit', () => { confirm.disabled = true; confirm.textContent = 'Removing...'; });
    })();
</script>
@endpush
