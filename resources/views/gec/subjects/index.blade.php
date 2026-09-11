@extends('layouts.gec')
@section('title', 'Minor Subjects')
@section('page-title', 'Minor Subjects')

@push('styles')
<style>
    .subjects-filters { grid-template-columns:repeat(4,minmax(0,1fr)); }
    @media(max-width:900px) { .subjects-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:560px) { .subjects-filters { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div><h2>General Education (Minor) Subjects</h2><p>Minor subjects across BSIT, BSBA, BSHM, BSED, and BEED, managed centrally by GEC.</p></div>
    <div class="actions">
        <button id="openSubjectCreate" class="button" type="button">Add Subject</button>
    </div>
</div>

<div class="card">
    <form class="filters subjects-filters" method="GET" data-auto-filter>
        <div>
            <label for="subjectSearch">Search subject</label>
            <input id="subjectSearch" class="input" name="search" value="{{ request('search') }}" placeholder="Search code or name">
        </div>
        <div>
            <label for="subjectDepartment">Department</label>
            <select id="subjectDepartment" class="input" name="department">
                <option value="">All departments</option>
                @foreach(\App\Http\Controllers\Gec\GecController::REAL_DEPARTMENTS as $department)
                    <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="subjectYearLevel">Year level</label>
            <select id="subjectYearLevel" class="input" name="year_level">
                <option value="">All years</option>
                @for($level = 1; $level <= 4; $level++)
                    <option value="{{ $level }}" @selected((string) request('year_level') === (string) $level)>Year {{ $level }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label for="subjectType">Subject type</label>
            <select id="subjectType" class="input" name="subject_type">
                <option value="">All types</option>
                @foreach(['Lecture', 'Laboratory'] as $type)
                    <option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Department</th><th>Code</th><th>Description</th><th>Type</th><th>Year</th><th>Semester</th><th>Curriculum</th><th>Units</th><th>Instructors</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($subjects as $subject)
                    <tr>
                        <td><span class="badge">{{ $subject->course }}</span></td>
                        <td><strong>{{ $subject->code }}</strong></td>
                        <td>{{ $subject->name }}</td>
                        <td>{{ $subject->subject_type }}</td>
                        <td>Year {{ $subject->year_level }}</td>
                        <td>{{ $subject->semester }}</td>
                        <td>{{ $subject->curriculum }} Curriculum</td>
                        <td>{{ number_format((float) $subject->units, 0) }}</td>
                        <td>{{ $subject->instructors->pluck('name')->join(', ') ?: 'Unassigned' }}</td>
                        <td>
                            <div class="actions">
                                <a class="button button-secondary" href="{{ route('gec.subjects.index', array_merge(request()->query(), ['edit' => $subject->id])) }}#subjectCreateModal">Edit</a>
                                <button type="button" class="button button-danger delete-confirmation-trigger" data-delete-url="{{ route('gec.subjects.destroy', $subject) }}" data-delete-name="{{ $subject->course }} {{ $subject->code }} — {{ $subject->name }}">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10">No minor subjects match the current filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$subjects" label="Minor subject pages" />
</div>

@include('dean.partials.delete-confirmation', [
    'title' => 'Delete Minor Subject?',
    'message' => 'This subject and all of its existing class schedules will be permanently deleted.',
    'confirmLabel' => 'Delete Subject',
])
@endsection

@push('portal-profile-overlay')
<div id="subjectCreateModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="subjectCreateTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="subjectCreateTitle">{{ $editingSubject ? 'Edit Minor Subject' : 'Add Minor Subject' }}</h2>
                <p>{{ $editingSubject ? 'Update this General Education subject.' : 'Add a General Education subject to a department\'s curriculum.' }}</p>
            </div>
            <button class="admin-profile-close" type="button" data-close-subject-create aria-label="Close subject form">&times;</button>
        </header>

        <form id="subjectCreateForm" method="POST" action="{{ $editingSubject ? route('gec.subjects.update', $editingSubject) : route('gec.subjects.store') }}">
            @csrf
            @if($editingSubject) @method('PUT') @endif
            <input type="hidden" name="subject_modal" value="1">
            <input type="hidden" name="curriculum" value="{{ old('curriculum', $editingSubject?->curriculum ?? 'New') }}">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field">
                    <label for="subject_course">Department</label>
                    <select id="subject_course" class="input" name="course" required>
                        @foreach(\App\Http\Controllers\Gec\GecController::REAL_DEPARTMENTS as $department)
                            <option value="{{ $department }}" @selected(old('course', $editingSubject?->course) === $department)>{{ $department }}</option>
                        @endforeach
                    </select>
                    @error('course')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
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

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('subjectCreateModal');
        const openButton = document.getElementById('openSubjectCreate');
        const closeButtons = [...modal.querySelectorAll('[data-close-subject-create]')];
        const firstInput = document.getElementById('subject_course');

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            @if($editingSubject)
                window.location = '{{ route('gec.subjects.index', request()->except('edit')) }}';
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

        @if($errors->hasAny(['course', 'code', 'name', 'subject_type', 'year_level', 'semester', 'curriculum', 'units']) || $editingSubject)
            openModal();
        @endif
    })();
</script>
@endpush
