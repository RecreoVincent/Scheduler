@extends('layouts.dean')

@section('title', 'Students')
@section('page-title', 'Student List')

@push('styles')
<style>
    .input:disabled { color:#8b8092; background:rgba(243,238,246,.75); cursor:not-allowed; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $course }} Enrolled Students</h2>
        <p>Search, filter, and sort every student enrolled in the {{ $course }} department.</p>
    </div>
    <div class="actions">
        <button id="openStudentImport" class="button button-secondary" type="button">Import Students</button>
        <button id="openStudentCreate" class="button" type="button">Add Student</button>
    </div>
</div>

<form class="filters" style="grid-template-columns:2fr 1fr 1fr 1fr;" method="GET" data-auto-filter>
    <input class="input" name="search" value="{{ request('search') }}" placeholder="Search student name or email">
    <select class="input" name="year_level">
        <option value="">All year levels</option>
        @for($level = 1; $level <= 4; $level++)
            <option value="{{ $level }}" @selected((string) request('year_level') === (string) $level)>Year {{ $level }}</option>
        @endfor
    </select>
    <select class="input" name="academic_section_id">
        <option value="">All sections</option>
        @foreach($sections as $section)
            <option value="{{ $section->id }}" @selected((string) request('academic_section_id') === (string) $section->id)>Year {{ $section->year_level }} — {{ $section->name }}</option>
        @endforeach
    </select>
    <select class="input" name="sort">
        <option value="name" @selected(request('sort', 'name') === 'name')>Name A–Z</option>
        <option value="newest" @selected(request('sort') === 'newest')>Newest first</option>
        <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
        <option value="year_level" @selected(request('sort') === 'year_level')>Year level</option>
    </select>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Year Level</th><th>Section</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->email }}</td>
                    <td>{{ $student->year_level ? 'Year '.$student->year_level : '—' }}</td>
                    <td>{{ $student->academicSection?->name ?? '—' }}</td>
                    <td><span class="badge">{{ $student->account_status === 'active' ? 'Active' : str($student->account_status)->title() }}</span></td>
                    <td>
                        <div class="actions">
                            <a class="button button-secondary" href="{{ route('dean.students.index', array_merge(request()->query(), ['edit' => $student->id])) }}#studentCreateModal">Edit</a>
                            <button type="button" class="button button-danger delete-confirmation-trigger" data-delete-url="{{ route('dean.students.destroy', $student) }}" data-delete-name="{{ $student->name }}">Delete</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">{{ request()->hasAny(['search', 'year_level', 'academic_section_id']) ? 'No students match the current filters.' : 'No students enrolled yet.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$students" label="Student pages" />
</div>

@include('dean.partials.delete-confirmation', [
    'title' => 'Delete Student?',
    'message' => 'This student account will be permanently deleted.',
    'confirmLabel' => 'Delete Student',
])
@endsection

@push('portal-profile-overlay')
<div id="studentCreateModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="studentCreateTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="studentCreateTitle">{{ $editingStudent ? 'Edit Student' : 'Add Student' }}</h2>
                <p>{{ $editingStudent ? "Update this student's profile for the {$course} department." : "Manually create an active student account for the {$course} department." }}</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-student-create aria-label="Close student form">&times;</button>
        </header>

        <form id="studentCreateForm" method="POST" action="{{ $editingStudent ? route('dean.students.update', $editingStudent) : route('dean.students.store') }}">
            @csrf
            @if($editingStudent) @method('PUT') @endif
            <input type="hidden" name="student_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field">
                    <label for="modal_first_name">First Name</label>
                    <input id="modal_first_name" class="input" name="first_name" value="{{ old('first_name', $editingStudent?->first_name) }}" required>
                    @error('first_name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_last_name">Last Name</label>
                    <input id="modal_last_name" class="input" name="last_name" value="{{ old('last_name', $editingStudent?->last_name) }}" required>
                    @error('last_name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_middle_name">Middle Name</label>
                    <input id="modal_middle_name" class="input" name="middle_name" value="{{ old('middle_name', $editingStudent?->middle_name) }}">
                    @error('middle_name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_suffix">Suffix</label>
                    <input id="modal_suffix" class="input" name="suffix" value="{{ old('suffix', $editingStudent?->suffix) }}" placeholder="Jr., III, etc.">
                    @error('suffix')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_email">Email</label>
                    <input id="modal_email" type="email" class="input" name="email" value="{{ old('email', $editingStudent?->email) }}" required>
                    @error('email')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_year_level">Year Level</label>
                    <select id="modal_year_level" class="input" name="year_level" required>
                        @for($level = 1; $level <= 4; $level++)
                            <option value="{{ $level }}" @selected((int) old('year_level', $editingStudent?->year_level ?? 1) === $level)>Year {{ $level }}</option>
                        @endfor
                    </select>
                    @error('year_level')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <label for="modal_academic_section_id">Section</label>
                    <select id="modal_academic_section_id" class="input" name="academic_section_id">
                        <option value="">No section assigned</option>
                        @foreach($allSections as $section)
                            <option value="{{ $section->id }}" data-year-level="{{ $section->year_level }}" @selected((int) old('academic_section_id', $editingStudent?->academic_section_id) === $section->id)>Year {{ $section->year_level }} — {{ $section->name }}</option>
                        @endforeach
                    </select>
                    <small style="display:block;margin-top:5px;color:var(--muted)">Only sections matching the selected year level can be assigned.</small>
                    @error('academic_section_id')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_password">Password</label>
                    <input id="modal_password" type="password" class="input" name="password" placeholder="{{ $editingStudent ? 'Leave blank to keep current password' : '' }}" @if(!$editingStudent) required @endif>
                    @error('password')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_password_confirmation">Confirm Password</label>
                    <input id="modal_password_confirmation" type="password" class="input" name="password_confirmation" @if(!$editingStudent) required @endif>
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-student-create>Cancel</button>
                <button class="button" type="submit">{{ $editingStudent ? 'Save Changes' : 'Add Student' }}</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('portal-profile-overlay')
<div id="studentImportModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="studentImportTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="studentImportTitle">Import Students</h2>
                <p>Bulk-create active {{ $course }} student accounts from a CSV file.</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-student-import aria-label="Close student import">&times;</button>
        </header>

        <form method="POST" action="{{ route('dean.students.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="student_import_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field full">
                    <label for="student_csv_file">CSV file</label>
                    <input id="student_csv_file" class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
                    @error('csv_file')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <p style="margin:0;color:var(--muted);font-size:12px;line-height:1.6">
                        Required columns: <strong>first_name</strong>, <strong>last_name</strong>, <strong>email</strong>, <strong>year_level</strong> (1–4).
                        Optional columns: middle_name, suffix, section (must match an existing section name for that year level), password (a temporary one is generated if left blank).
                        <a href="{{ route('dean.students.import-template') }}">Download a CSV template</a>.
                    </p>
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-student-import>Cancel</button>
                <button class="button" type="submit">Import Students</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('studentImportModal');
        const openButton = document.getElementById('openStudentImport');
        const closeButtons = [...modal.querySelectorAll('[data-close-student-import]')];
        const firstInput = document.getElementById('student_csv_file');

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            openButton.focus();
        }

        openButton.addEventListener('click', openModal);
        closeButtons.forEach(button => button.addEventListener('click', closeModal));
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

        @if($errors->has('csv_file'))
            openModal();
        @endif
    })();

    (() => {
        const yearLevelSelect = document.querySelector('form[data-auto-filter] select[name="year_level"]');
        const sectionSelect = document.querySelector('form[data-auto-filter] select[name="academic_section_id"]');

        yearLevelSelect?.addEventListener('change', () => {
            sectionSelect.value = '';
        });
    })();

    (() => {
        const modal = document.getElementById('studentCreateModal');
        const openButton = document.getElementById('openStudentCreate');
        const closeButtons = [...modal.querySelectorAll('[data-close-student-create]')];
        const firstInput = document.getElementById('modal_first_name');
        const yearLevelSelect = document.getElementById('modal_year_level');
        const sectionSelect = document.getElementById('modal_academic_section_id');
        const sectionOptions = [...sectionSelect.options];

        function syncSectionOptions() {
            const currentYearLevel = yearLevelSelect.value;
            const currentSelection = sectionSelect.value;
            let stillValid = false;

            sectionOptions.forEach((option) => {
                const optionYearLevel = option.dataset.yearLevel;
                const visible = !optionYearLevel || optionYearLevel === currentYearLevel;
                option.hidden = !visible;
                if (visible && option.value === currentSelection) stillValid = true;
            });

            if (!stillValid) sectionSelect.value = '';
        }

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            @if($editingStudent)
                window.location = '{{ route('dean.students.index', request()->except('edit')) }}';
                return;
            @endif
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            openButton.focus();
        }

        yearLevelSelect.addEventListener('change', syncSectionOptions);
        syncSectionOptions();

        openButton.addEventListener('click', openModal);
        closeButtons.forEach(button => button.addEventListener('click', closeModal));
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

        @if($errors->hasAny(['first_name', 'last_name', 'middle_name', 'suffix', 'email', 'year_level', 'academic_section_id', 'password']) || $editingStudent)
            openModal();
        @endif
    })();
</script>
@endpush
