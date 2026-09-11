@extends('layouts.dean')
@section('title', 'Subjects')
@section('page-title', 'Subjects')

@push('styles')
<style>
    .subject-year-list { display:grid; gap:22px; }
    .subject-year-card { padding:0; overflow:hidden; border:1px solid #e7e1ed; }
    .subject-year-header { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:20px 22px; background:linear-gradient(110deg,#fbf9fd 0%,#fffaf0 100%); border-bottom:1px solid #eee7f3; }
    .subject-year-title { display:flex; align-items:center; gap:13px; }
    .subject-year-number { width:42px; height:42px; display:grid; place-items:center; color:#fff; font-weight:800; background:var(--primary); border-radius:12px; box-shadow:0 7px 16px rgba(69,6,147,.18); }
    .subject-year-title h3 { margin:0 0 3px; color:var(--navy); }
    .subject-year-title p { margin:0; color:#64748b; font-size:13px; }
    .subject-empty { padding:28px !important; color:#64748b; text-align:center; }
    @media(max-width:780px) {
        .subject-year-header { align-items:flex-start; flex-direction:column; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div><h2>{{ $course }} Subjects by Year Level</h2><p>Each year level has a separate curriculum table.</p></div>
    <div class="actions">
        <button id="openSubjectImport" class="button button-secondary" type="button">Import Subjects</button>
        <button id="openSubjectCreate" class="button" type="button">Add Subject</button>
    </div>
</div>

<div class="card">
    <form class="filters" method="GET" data-auto-filter style="grid-template-columns:repeat(2,minmax(0,1fr));">
        <select class="input" name="year_level"><option value="">All years</option>@for($level = 1; $level <= 4; $level++)<option value="{{ $level }}" @selected((string) request('year_level') === (string) $level)>Year {{ $level }}</option>@endfor</select>
        <select class="input" name="subject_type"><option value="">All types</option>@foreach(['Lecture', 'Laboratory'] as $type)<option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ $type }}</option>@endforeach</select>
    </form>
</div>

@php($visibleYears = request()->filled('year_level') ? [(int) request('year_level')] : range(1, 4))
<div class="subject-year-list">
    @foreach($visibleYears as $yearLevel)
        @php($yearSubjects = $subjectsByYear->get($yearLevel, collect()))
        @php($yearLabel = match($yearLevel) { 1 => 'First Year', 2 => 'Second Year', 3 => 'Third Year', default => 'Fourth Year' })
        <section class="card subject-year-card">
            <header class="subject-year-header">
                <div class="subject-year-title">
                    <div class="subject-year-number">{{ $yearLevel }}</div>
                    <div><h3>{{ $yearLabel }} Subjects</h3><p>{{ $yearSubjects->count() }} {{ Str::plural('subject', $yearSubjects->count()) }}</p></div>
                </div>
            </header>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>Subject Code</th><th>Subject Description</th><th>Type</th><th>Classification</th><th>Semester</th><th>Curriculum</th><th>Unit</th><th>Instructors</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($yearSubjects as $subject)
                            <tr>
                                <td><strong>{{ $subject->code }}</strong></td>
                                <td>{{ $subject->name }}</td>
                                <td>{{ $subject->subject_type }}</td>
                                <td>{{ $subject->classification }}</td>
                                <td>{{ $subject->semester }}</td>
                                <td>{{ $subject->curriculum }} Curriculum</td>
                                <td>{{ number_format((float) $subject->units, 0) }}</td>
                                <td>{{ $subject->instructors->map(fn ($instructor) => $instructor->name.' ('.$instructor->course.')')->join(', ') ?: 'Unassigned' }}</td>
                                <td>
                                    <div class="actions">
                                        <a class="button button-secondary" href="{{ route('dean.subjects.index', array_merge(request()->query(), ['edit' => $subject->id])) }}#subjectCreateModal">Edit</a>
                                        <button type="button" class="button button-danger delete-confirmation-trigger" data-delete-url="{{ route('dean.subjects.destroy', $subject) }}" data-delete-name="{{ $subject->code }} — {{ $subject->name }}">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="subject-empty" colspan="9">No {{ strtolower($yearLabel) }} subjects match the current filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</div>

@include('dean.partials.delete-confirmation', [
    'title' => 'Delete Subject?',
    'message' => 'This subject and all of its existing class schedules will be permanently deleted.',
    'confirmLabel' => 'Delete Subject',
])
@endsection

@push('portal-profile-overlay')
<div id="subjectCreateModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="subjectCreateTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="subjectCreateTitle">{{ $editingSubject ? 'Edit Subject' : 'Add Subject' }}</h2>
                <p>{{ $editingSubject ? "Update this {$course} subject." : "Enter the curriculum information for {$course}." }}</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-subject-create aria-label="Close subject form">&times;</button>
        </header>

        <form id="subjectCreateForm" method="POST" action="{{ $editingSubject ? route('dean.subjects.update', $editingSubject) : route('dean.subjects.store') }}">
            @csrf
            @if($editingSubject) @method('PUT') @endif
            <input type="hidden" name="subject_modal" value="1">
            <input type="hidden" name="curriculum" value="{{ old('curriculum', $editingSubject?->curriculum ?? 'New') }}">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field">
                    <label for="subject_code">Subject code</label>
                    <input id="subject_code" class="input" name="code" value="{{ old('code', $editingSubject?->code) }}" required>
                    @error('code')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="subject_name">Subject name</label>
                    <input id="subject_name" class="input" name="name" value="{{ old('name', $editingSubject?->name) }}" required>
                    @error('name')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="subject_type">Subject type</label>
                    <select id="subject_type" class="input" name="subject_type" required>
                        @foreach(['Lecture','Laboratory'] as $type)<option @selected(old('subject_type', $editingSubject?->subject_type ?? 'Lecture') === $type)>{{ $type }}</option>@endforeach
                    </select>
                    @error('subject_type')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="subject_classification">Subject classification</label>
                    <select id="subject_classification" class="input" name="classification" required>
                        @foreach(['Major','Minor'] as $classification)<option @selected(old('classification', $editingSubject?->classification ?? 'Major') === $classification)>{{ $classification }}</option>@endforeach
                    </select>
                    @error('classification')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="subject_year_level">Year level</label>
                    <select id="subject_year_level" class="input" name="year_level" required>
                        @for($i=1;$i<=4;$i++)<option value="{{ $i }}" @selected((int) old('year_level', $editingSubject?->year_level ?? 1) === $i)>Year {{ $i }}</option>@endfor
                    </select>
                    @error('year_level')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="subject_semester">Semester</label>
                    <select id="subject_semester" class="input" name="semester" required>
                        @foreach(['1st','2nd','Summer'] as $semester)<option @selected(old('semester', $editingSubject?->semester ?? '1st') === $semester)>{{ $semester }}</option>@endforeach
                    </select>
                    @error('semester')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <label for="subject_units">Units</label>
                    <input id="subject_units" class="input" type="number" step="0.5" min="0.5" max="12" name="units" value="{{ old('units', $editingSubject?->units ?? 3) }}" required>
                    @error('units')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-subject-create>Cancel</button>
                <button class="button" type="submit">{{ $editingSubject ? 'Save Changes' : 'Add Subject' }}</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('portal-profile-overlay')
<div id="subjectImportModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="subjectImportTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="subjectImportTitle">Import Subjects</h2>
                <p>Bulk-create {{ $course }} subjects from a CSV file.</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-subject-import aria-label="Close subject import">&times;</button>
        </header>

        <form method="POST" action="{{ route('dean.subjects.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="subject_import_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field full">
                    <label for="subject_csv_file">CSV file</label>
                    <input id="subject_csv_file" class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
                    @error('csv_file')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field full">
                    <p style="margin:0;color:var(--muted);font-size:12px;line-height:1.6">
                        Required columns: <strong>code</strong>, <strong>name</strong>, <strong>subject_type</strong> (Lecture or Laboratory), <strong>year_level</strong> (1–4), <strong>semester</strong> (1st, 2nd, or Summer), <strong>units</strong> (0.5–12).
                        Optional columns: classification (Major/Minor, defaults to Major), curriculum (New/Old, defaults to New).
                        <a href="{{ route('dean.subjects.import-template') }}">Download a CSV template</a>.
                    </p>
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button class="button button-secondary" type="button" data-close-subject-import>Cancel</button>
                <button class="button" type="submit">Import Subjects</button>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('subjectCreateModal');
        const openButton = document.getElementById('openSubjectCreate');
        const closeButtons = [...modal.querySelectorAll('[data-close-subject-create]')];
        const firstInput = document.getElementById('subject_code');

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            @if($editingSubject)
                window.location = '{{ route('dean.subjects.index', request()->except('edit')) }}';
                return;
            @endif
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            openButton.focus();
        }

        openButton.addEventListener('click', openModal);
        closeButtons.forEach(button => button.addEventListener('click', closeModal));
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

        @if($errors->hasAny(['code', 'name', 'subject_type', 'classification', 'year_level', 'semester', 'curriculum', 'units']) || $editingSubject)
            openModal();
        @endif
    })();

    (() => {
        const modal = document.getElementById('subjectImportModal');
        const openButton = document.getElementById('openSubjectImport');
        const closeButtons = [...modal.querySelectorAll('[data-close-subject-import]')];
        const firstInput = document.getElementById('subject_csv_file');

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
</script>
@endpush
