@extends('layouts.dean')
@section('title', 'Timetable')
@section('page-title', 'Class Timetable')

@push('styles')
<style>
    .timetable-list { display:grid; gap:22px; }
    .section-schedule { padding:0; overflow:hidden; border:1px solid #e7e1ed; }
    .section-schedule-header { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:20px 22px; background:linear-gradient(110deg, #fbf9fd 0%, #fffaf0 100%); border-bottom:1px solid #eee7f3; }
    .section-schedule-title { display:flex; align-items:center; gap:13px; }
    .section-schedule-mark { width:42px; height:42px; display:grid; place-items:center; color:#fff; font-weight:800; background:var(--primary); border-radius:12px; box-shadow:0 7px 16px rgba(69,6,147,.18); }
    .section-schedule-title h3 { margin:0 0 3px; color:var(--navy); }
    .section-schedule-title p { margin:0; color:#64748b; font-size:13px; }
    .section-schedule-controls { display:flex; align-items:center; gap:8px; }
    .section-schedule-controls .button { min-height:38px; padding:8px 13px; }
    .selection-notice { display:none; align-items:center; justify-content:space-between; gap:14px; padding:11px 22px; color:#4b3264; font-size:13px; font-weight:700; background:#f6efff; border-bottom:1px solid #e8daf8; }
    .section-schedule[data-mode="edit"] .selection-notice { display:flex; }
    .section-schedule .cancel-selection { display:none; }
    .section-schedule[data-mode] .cancel-selection { display:inline-flex; }
    .section-schedule[data-mode] .mode-button { display:none; }
    .schedule-table { width:100%; min-width:1050px; table-layout:fixed; }
    .schedule-table col.time-column { width:16%; }
    .schedule-table col.days-column { width:10%; }
    .schedule-table col.code-column { width:12%; }
    .schedule-table col.description-column { width:22%; }
    .schedule-table col.unit-column { width:7%; }
    .schedule-table col.room-column { width:12%; }
    .schedule-table col.instructor-column { width:21%; }
    .schedule-table col.entry-selection-column { width:0; }
    .section-schedule[data-mode="edit"] .schedule-table col.entry-selection-column { width:130px; }
    .schedule-table th,.schedule-table td { overflow-wrap:anywhere; word-break:normal; vertical-align:middle; }
    .schedule-table th:nth-child(2),.schedule-table td:nth-child(2),
    .schedule-table th:nth-child(5),.schedule-table td:nth-child(5) { text-align:center; }
    .schedule-table tbody tr { transition:background .18s ease; }
    .section-schedule[data-mode] .schedule-table tbody tr:hover { background:#faf7ff; }
    .selection-column { display:none; width:130px; text-align:right; }
    .section-schedule[data-mode="edit"] .selection-column { display:table-cell; }
    .entry-action { display:none; min-width:108px; justify-content:center; }
    .section-schedule[data-mode="edit"] .edit-entry-action { display:inline-flex; }
    .timetable-pagination { display:flex; justify-content:flex-end; align-items:center; gap:10px; margin-top:22px; }
    .timetable-pagination .button[aria-disabled="true"] { opacity:.45; pointer-events:none; }
    .timetable-page-count { color:#64748b; font-size:13px; font-weight:700; }
    .empty-timetable { padding:46px 24px; text-align:center; }
    .empty-timetable h3 { margin:0 0 7px; color:var(--navy); }
    .empty-timetable p { margin:0 0 19px; color:#64748b; }
    .timetable-header-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .timetable-header-actions .button[disabled] { opacity:.5; cursor:not-allowed; }
    .timetable-filters { grid-template-columns:repeat(5,minmax(0,1fr)) !important; }
    @media (max-width:1100px) {
        .timetable-filters { grid-template-columns:repeat(2,minmax(0,1fr)) !important; }
    }
    @media (max-width:780px) {
        .timetable-filters { grid-template-columns:1fr !important; }
        .section-schedule-header { align-items:flex-start; flex-direction:column; }
        .section-schedule-controls { width:100%; flex-wrap:wrap; }
        .section-schedule-controls .button { flex:1; }
        .selection-notice { align-items:flex-start; flex-direction:column; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $course }} Section Timetables</h2>
        <p>Each section has its own complete schedule table. Edit a specific class entry or delete the section's entire schedule.</p>
    </div>
    <div class="timetable-header-actions">
        <a class="button" href="{{ route('dean.schedules.create') }}">Generate Schedule</a>
        <button
            type="button"
            class="button button-danger delete-confirmation-trigger"
            data-delete-url="{{ route('dean.timetable.destroy-all', request()->only(['section_id', 'year_level', 'academic_year', 'semester', 'day'])) }}"
            data-delete-name="{{ $filteredScheduleCount }} matching class {{ Str::plural('entry', $filteredScheduleCount) }} in {{ $course }}"
            data-delete-title="Delete All Schedules?"
            data-delete-message="All schedules matching the current timetable filters will be moved to Archive. They can still be restored later."
            data-delete-confirm-label="Delete All Schedules"
            @disabled($filteredScheduleCount === 0)
        >Delete All Schedules</button>
    </div>
</div>

<div class="card">
    <form class="filters timetable-filters" method="GET" data-auto-filter>
        <select class="input" name="section_id">
            <option value="">All sections</option>
            @foreach($sections as $section)
                <option value="{{ $section->id }}" @selected((string) request('section_id') === (string) $section->id)>{{ $section->name }}</option>
            @endforeach
        </select>
        <select class="input" name="year_level">
            <option value="">All year levels</option>
            @for($yearLevel = 1; $yearLevel <= 4; $yearLevel++)
                <option value="{{ $yearLevel }}" @selected((string) request('year_level') === (string) $yearLevel)>Year {{ $yearLevel }}</option>
            @endfor
        </select>
        <input class="input" name="academic_year" value="{{ request('academic_year') }}" placeholder="Academic year">
        <select class="input" name="semester">
            <option value="">All semesters</option>
            @foreach(['1st', '2nd', 'Summer'] as $semester)
                <option value="{{ $semester }}" @selected(request('semester') === $semester)>{{ $semester }}</option>
            @endforeach
        </select>
        <select class="input" name="day">
            <option value="">All days</option>
            @foreach(['M - W', 'T - Th', 'F - S'] as $day)
                <option value="{{ $day }}" @selected(request('day') === $day)>{{ $day }}</option>
            @endforeach
        </select>
    </form>
</div>

@if($sectionPages->isEmpty())
    <div class="card empty-timetable">
        <h3>No schedules found</h3>
        <p>Generate a class schedule or change the current timetable filters.</p>
        <a class="button" href="{{ route('dean.schedules.create') }}">Generate Schedule</a>
    </div>
@else
    <div class="timetable-list">
        @foreach($sectionPages as $section)
            @php($sectionSchedules = $schedulesBySection->get($section->id, collect()))
            <section class="card section-schedule" data-section-card>
                <header class="section-schedule-header">
                    <div class="section-schedule-title">
                        <div class="section-schedule-mark">{{ strtoupper(substr($section->name, 0, 1)) }}</div>
                        <div>
                            <h3>{{ $section->name }}</h3>
                            <p>{{ $section->year_level }} Year &middot; {{ $section->academic_year }} &middot; {{ $sectionSchedules->count() }} class {{ Str::plural('entry', $sectionSchedules->count()) }}</p>
                        </div>
                    </div>
                    <div class="section-schedule-controls">
                        <button type="button" class="button button-secondary mode-button" data-selection-mode="edit">Edit Schedule</button>
                        <button
                            type="button"
                            class="button button-danger delete-confirmation-trigger"
                            data-delete-url="{{ route('dean.timetable.sections.destroy', $section) }}"
                            data-delete-name="{{ $section->name }} — entire section schedule"
                        >Delete Schedule</button>
                        <button type="button" class="button button-secondary cancel-selection">Cancel Selection</button>
                    </div>
                </header>

                <div class="selection-notice">
                    <span data-selection-message></span>
                    <span>Select one row from the table below.</span>
                </div>

                <div class="table-wrap">
                    <table class="schedule-table">
                        <colgroup>
                            <col class="time-column">
                            <col class="days-column">
                            <col class="code-column">
                            <col class="description-column">
                            <col class="unit-column">
                            <col class="room-column">
                            <col class="instructor-column">
                            <col class="entry-selection-column">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Days</th>
                                <th>Subject Code</th>
                                <th>Subject Description</th>
                                <th>Unit</th>
                                <th>Room</th>
                                <th>Instructors</th>
                                <th class="selection-column">Select Entry</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sectionSchedules as $schedule)
                                <tr>
                                    <td>{{ date('g:i A', strtotime($schedule->start_time)) }} &ndash; {{ date('g:i A', strtotime($schedule->end_time)) }}</td>
                                    <td><strong>{{ $schedule->day }}</strong></td>
                                    <td><strong>{{ $schedule->subject?->code }}</strong></td>
                                    <td>{{ $schedule->subject?->name }}</td>
                                    <td>{{ number_format((float) $schedule->subject?->units, 0) }}</td>
                                    <td>{{ $schedule->room?->name ?? 'TBA' }}</td>
                                    <td>{{ $schedule->instructor?->name }}</td>
                                    <td class="selection-column">
                                        <a class="button button-secondary entry-action edit-entry-action" href="{{ route('dean.timetable.edit', $schedule) }}">Choose</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>

    <x-pagination :paginator="$sectionPages" label="Timetable section pages" />
@endif

@include('dean.partials.delete-confirmation', [
    'title' => 'Delete Section Schedule?',
    'message' => 'Every class schedule entry for this section will be moved to the archive and can be restored later.',
    'confirmLabel' => 'Delete Schedule',
])
@endsection

@push('scripts')
<script>
    (() => {
        document.querySelectorAll('[data-section-card]').forEach(card => {
            const message = card.querySelector('[data-selection-message]');

            card.querySelectorAll('[data-selection-mode]').forEach(button => {
                button.addEventListener('click', () => {
                    card.dataset.mode = 'edit';
                    message.textContent = 'Which class entry do you want to edit?';
                });
            });

            card.querySelector('.cancel-selection')?.addEventListener('click', () => {
                delete card.dataset.mode;
                message.textContent = '';
            });
        });
    })();
</script>
@endpush
