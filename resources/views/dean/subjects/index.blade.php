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
    .subject-year-controls { display:flex; gap:8px; }
    .subject-year-controls .button { min-height:38px; padding:8px 13px; }
    .subject-selection-notice { display:none; align-items:center; justify-content:space-between; gap:12px; padding:11px 22px; color:#4b3264; font-size:13px; font-weight:700; background:#f6efff; border-bottom:1px solid #e8daf8; }
    .subject-year-card[data-mode="edit"] .subject-selection-notice,
    .subject-year-card[data-mode="delete"] .subject-selection-notice { display:flex; }
    .subject-year-card[data-mode="delete"] .subject-selection-notice { color:#991b1b; background:#fff1f2; border-color:#fecdd3; }
    .subject-year-card .cancel-subject-selection { display:none; }
    .subject-year-card[data-mode] .subject-mode-button { display:none; }
    .subject-year-card[data-mode] .cancel-subject-selection { display:inline-flex; }
    .subject-selection-column { display:none; width:130px; text-align:right; }
    .subject-year-card[data-mode] .subject-selection-column { display:table-cell; }
    .subject-entry-action { display:none; min-width:108px; justify-content:center; }
    .subject-year-card[data-mode="edit"] .edit-subject-action,
    .subject-year-card[data-mode="delete"] .delete-subject-action { display:inline-flex; }
    .subject-empty { padding:28px !important; color:#64748b; text-align:center; }
    @media(max-width:780px) {
        .subject-year-header { align-items:flex-start; flex-direction:column; }
        .subject-year-controls { width:100%; flex-wrap:wrap; }
        .subject-year-controls .button { flex:1; }
        .subject-selection-notice { align-items:flex-start; flex-direction:column; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div><h2>{{ $course }} Subjects by Year Level</h2><p>Each year level has a separate curriculum table. Choose Edit or Delete, then select the exact subject.</p></div>
    <div class="actions">
        <a class="button button-secondary" href="{{ route('dean.subject-assignments.index') }}">Subject Assignment</a>
        <a class="button" href="{{ route('dean.subjects.create') }}">Add Subject</a>
    </div>
</div>

<div class="card">
    <form class="filters" method="GET" data-auto-filter style="grid-template-columns:repeat(4,minmax(0,1fr));">
        <select class="input" name="year_level"><option value="">All years</option>@for($level = 1; $level <= 4; $level++)<option value="{{ $level }}" @selected((string) request('year_level') === (string) $level)>Year {{ $level }}</option>@endfor</select>
        <select class="input" name="semester"><option value="">All semesters</option>@foreach(['1st', '2nd', 'Summer'] as $semester)<option value="{{ $semester }}" @selected(request('semester') === $semester)>{{ $semester }}</option>@endforeach</select>
        <select class="input" name="subject_type"><option value="">All types</option>@foreach(['Lecture', 'Laboratory'] as $type)<option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ $type }}</option>@endforeach</select>
        <select class="input" name="curriculum"><option value="">All curricula</option>@foreach(['New' => 'New Curriculum', 'Old' => 'Old Curriculum'] as $value => $label)<option value="{{ $value }}" @selected(request('curriculum') === $value)>{{ $label }}</option>@endforeach</select>
    </form>
</div>

@php($visibleYears = request()->filled('year_level') ? [(int) request('year_level')] : range(1, 4))
<div class="subject-year-list">
    @foreach($visibleYears as $yearLevel)
        @php($yearSubjects = $subjectsByYear->get($yearLevel, collect()))
        @php($yearLabel = match($yearLevel) { 1 => 'First Year', 2 => 'Second Year', 3 => 'Third Year', default => 'Fourth Year' })
        <section class="card subject-year-card" data-subject-year-card>
            <header class="subject-year-header">
                <div class="subject-year-title">
                    <div class="subject-year-number">{{ $yearLevel }}</div>
                    <div><h3>{{ $yearLabel }} Subjects</h3><p>{{ $yearSubjects->count() }} {{ Str::plural('subject', $yearSubjects->count()) }}</p></div>
                </div>
                @if($yearSubjects->isNotEmpty())
                    <div class="subject-year-controls">
                        <button type="button" class="button button-secondary subject-mode-button" data-subject-mode="edit">Edit Subjects</button>
                        <button type="button" class="button button-danger subject-mode-button" data-subject-mode="delete">Delete Subjects</button>
                        <button type="button" class="button button-secondary cancel-subject-selection">Cancel Selection</button>
                    </div>
                @endif
            </header>

            <div class="subject-selection-notice"><span data-subject-selection-message></span><span>Select one subject from the table below.</span></div>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>Subject Code</th><th>Subject Description</th><th>Type</th><th>Classification</th><th>Semester</th><th>Curriculum</th><th>Unit</th><th>Instructors</th><th class="subject-selection-column">Select Subject</th></tr></thead>
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
                                <td class="subject-selection-column">
                                    <a class="button button-secondary subject-entry-action edit-subject-action" href="{{ route('dean.subjects.edit', $subject) }}">Choose</a>
                                    <button type="button" class="button button-danger subject-entry-action delete-subject-action delete-confirmation-trigger" data-delete-url="{{ route('dean.subjects.destroy', $subject) }}" data-delete-name="{{ $subject->code }} — {{ $subject->name }}">Choose</button>
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

@push('scripts')
<script>
    (() => {
        document.querySelectorAll('[data-subject-year-card]').forEach(card => {
            const message = card.querySelector('[data-subject-selection-message]');
            card.querySelectorAll('[data-subject-mode]').forEach(button => {
                button.addEventListener('click', () => {
                    const mode = button.dataset.subjectMode;
                    card.dataset.mode = mode;
                    message.textContent = mode === 'edit'
                        ? 'Which subject do you want to edit?'
                        : 'Which subject do you want to delete?';
                });
            });
            card.querySelector('.cancel-subject-selection')?.addEventListener('click', () => {
                delete card.dataset.mode;
                message.textContent = '';
            });
        });
    })();
</script>
@endpush
