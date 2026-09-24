@extends('layouts.gec')
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
    .schedule-table { width:100%; min-width:0; table-layout:fixed; }
    .section-schedule .table-wrap { overflow-x:hidden; }
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
    .timetable-header-actions { display:flex; align-items:center; gap:10px; flex:0 0 auto; flex-wrap:nowrap; }
    .timetable-header-actions .button { width:auto !important; white-space:nowrap; }
    .timetable-header-actions .button[disabled] { opacity:.5; cursor:not-allowed; }
    .timetable-filters { grid-template-columns:repeat(5,minmax(0,1fr)) !important; }
    @media (max-width:1200px) {
        .timetable-filters { grid-template-columns:repeat(3,minmax(0,1fr)) !important; }
    }
    @media (max-width:780px) {
        .timetable-filters { grid-template-columns:1fr !important; }
        .timetable-header-actions { flex-wrap:wrap; width:100%; }
        .timetable-header-actions form,
        .timetable-header-actions .button { width:100% !important; }
        .section-schedule-header { align-items:flex-start; flex-direction:column; }
        .section-schedule-controls { width:100%; flex-wrap:wrap; }
        .section-schedule-controls .button { flex:1; }
        .selection-notice { align-items:flex-start; flex-direction:column; }
        .schedule-table { table-layout:auto; min-width:640px; }
        .section-schedule .table-wrap { overflow-x:auto; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>Minor Subject Timetables</h2>
        <p>Every section's General Education class schedule, across all departments. Edit a specific class entry or delete a section's entire minor-subject schedule.</p>
    </div>
    <div class="timetable-header-actions">
        <a class="button" href="{{ route('gec.schedules.create') }}">Generate Schedule</a>
        @if(request()->filled('department'))
            <form method="POST" action="{{ route('gec.timetable.send-to-dean') }}" onsubmit="return confirm('Send the {{ request('department') }} Minor-subject schedules back to the Dean?\n\nThis notifies the department\'s Dean that their Minor-subject schedules are ready.');">
                @csrf
                <input type="hidden" name="department" value="{{ request('department') }}">
                <button type="submit" class="button button-secondary">Send Back to Dean</button>
            </form>
        @endif
        <button
            type="button"
            class="button button-danger delete-confirmation-trigger"
            data-delete-url="{{ route('gec.timetable.destroy-all', request()->only(['department', 'section_id', 'year_level', 'academic_year', 'semester', 'day'])) }}"
            data-delete-name="{{ $filteredScheduleCount }} matching class {{ Str::plural('entry', $filteredScheduleCount) }}"
            data-delete-title="Delete All Schedules?"
            data-delete-message="All schedules matching the current timetable filters will be moved to Archive. They can still be restored later."
            data-delete-confirm-label="Delete All Schedules"
            @disabled($filteredScheduleCount === 0)
        >Delete All Schedules</button>
    </div>
</div>

@if($scheduleHandoffs->isNotEmpty())
    <div class="card" style="margin-bottom:20px">
        <p style="margin:0 0 10px;font-size:12px;font-weight:800;color:var(--navy)">{{ request('department') }} Handoff Status</p>
        <div style="display:flex;flex-direction:column;gap:6px">
            @foreach($scheduleHandoffs as $handoff)
                <p style="margin:0;font-size:11px;color:var(--muted)">
                    <strong>{{ $handoff->semester }} Semester {{ $handoff->academic_year }}:</strong>
                    @if($handoff->majors_sent_at)
                        Major schedules received {{ $handoff->majors_sent_at->format('M j, Y g:i A') }}.
                    @else
                        Major schedules not sent yet.
                    @endif
                    @if($handoff->minors_sent_back_at)
                        Minor schedules sent back {{ $handoff->minors_sent_back_at->format('M j, Y g:i A') }}.
                    @endif
                </p>
            @endforeach
        </div>
    </div>
@endif

<div class="card">
    <form class="filters timetable-filters" method="GET" data-auto-filter>
        <div>
            <label for="timetableDepartment">Department</label>
            <select id="timetableDepartment" class="input" name="department">
                <option value="">All departments</option>
                @foreach(\App\Http\Controllers\Gec\GecController::REAL_DEPARTMENTS as $department)
                    <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="timetableSection">Section</label>
            <select id="timetableSection" class="input" name="section_id">
                <option value="">All sections</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" @selected((string) request('section_id') === (string) $section->id)>{{ $section->course }} · {{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="timetableYearLevel">Year level</label>
            <select id="timetableYearLevel" class="input" name="year_level">
                <option value="">All year levels</option>
                @for($yearLevel = 1; $yearLevel <= 4; $yearLevel++)
                    <option value="{{ $yearLevel }}" @selected((string) request('year_level') === (string) $yearLevel)>Year {{ $yearLevel }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label for="timetableAcademicYear">Academic year</label>
            <input id="timetableAcademicYear" class="input" name="academic_year" value="{{ request('academic_year') }}" placeholder="Academic year">
        </div>
        <div>
            <label for="timetableDay">Days</label>
            <select id="timetableDay" class="input" name="day">
                <option value="">All days</option>
                @foreach(['M - W', 'T - Th', 'F - S'] as $day)
                    <option value="{{ $day }}" @selected(request('day') === $day)>{{ $day }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

@if($sectionPages->isEmpty())
    <div class="card empty-timetable">
        <h3>No schedules found</h3>
        <p>Generate a minor-subject schedule or change the current timetable filters.</p>
        <a class="button" href="{{ route('gec.schedules.create') }}">Generate Schedule</a>
    </div>
@else
    <x-pagination :paginator="$sectionPages" label="Timetable section pages" />
    <div class="timetable-list">
        @foreach($sectionPages as $section)
            @php($sectionSchedules = $schedulesBySection->get($section->id, collect()))
            <section class="card section-schedule" data-section-card>
                <header class="section-schedule-header">
                    <div class="section-schedule-title">
                        <div class="section-schedule-mark">{{ strtoupper(substr($section->name, 0, 1)) }}</div>
                        <div>
                            <h3>{{ $section->course }} · {{ $section->name }}</h3>
                            <p>{{ $section->year_level }} Year &middot; {{ $section->academic_year }} &middot; {{ $sectionSchedules->count() }} class {{ Str::plural('entry', $sectionSchedules->count()) }}</p>
                        </div>
                    </div>
                    <div class="section-schedule-controls">
                        <button type="button" class="button button-secondary mode-button" data-selection-mode="edit">Edit Schedule</button>
                        <button
                            type="button"
                            class="button button-danger delete-confirmation-trigger"
                            data-delete-url="{{ route('gec.timetable.sections.destroy', $section) }}"
                            data-delete-name="{{ $section->course }} {{ $section->name }} — entire minor-subject schedule"
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
                                        <a class="button button-secondary entry-action edit-entry-action" href="{{ route('gec.timetable.index', array_merge(request()->query(), ['edit' => $schedule->id])) }}#scheduleEditModal">Choose</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>

@endif

@include('dean.partials.delete-confirmation', [
    'title' => 'Delete Section Schedule?',
    'message' => 'Every minor-subject class entry for this section will be moved to the archive and can be restored later.',
    'confirmLabel' => 'Delete Schedule',
])
@endsection

@push('portal-profile-overlay')
<div id="scheduleEditModal" class="admin-profile-modal" hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="scheduleEditTitle">
        <header class="admin-profile-header">
            <div>
                <h2 id="scheduleEditTitle">{{ $editingSchedule ? "{$editingSchedule->section?->name} · {$editingSchedule->subject?->code}" : 'Edit Class Schedule' }}</h2>
                <p>Edit the selected class entry. The system will check the section, instructor, and room for conflicts.</p>
            </div>
            <button id="closeScheduleEdit" class="admin-profile-close" type="button" aria-label="Close schedule edit form">&times;</button>
        </header>

        @if($editingSchedule)
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:22px">
                <span class="badge">{{ $editingSchedule->subject?->name }}</span>
                <span class="badge">{{ $editingSchedule->academic_year }}</span>
                <span class="badge">{{ $editingSchedule->semester }} Semester</span>
            </div>
        @endif

        <form id="scheduleEditForm" method="POST" action="{{ $editingSchedule ? route('gec.timetable.update', $editingSchedule) : '' }}">
            @csrf
            @method('PUT')
            <div class="admin-profile-form-grid">
                <div class="admin-profile-field">
                    <label for="modal_instructor_id">Instructor</label>
                    <select id="modal_instructor_id" class="input" name="instructor_id" required>
                        @foreach($instructors as $instructor)
                            <option value="{{ $instructor->id }}" @selected((string) old('instructor_id', $editingSchedule?->instructor_id) === (string) $instructor->id)>{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                    @error('instructor_id')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_room_id">Room</label>
                    <select id="modal_room_id" class="input" name="room_id">
                        <option value="">TBA (no room)</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @selected((string) old('room_id', $editingSchedule?->room_id) === (string) $room->id)>{{ $room->course }} · {{ $room->name }}</option>
                        @endforeach
                    </select>
                    @error('room_id')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_day">Day</label>
                    <select id="modal_day" class="input" name="day" required>
                        @foreach(['M - W', 'T - Th', 'F - S'] as $day)
                            <option value="{{ $day }}" @selected(old('day', $editingSchedule?->day) === $day)>{{ $day }}</option>
                        @endforeach
                    </select>
                    @error('day')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_start_time">Start Time</label>
                    <input id="modal_start_time" class="input" type="time" name="start_time" value="{{ old('start_time', $editingSchedule ? substr($editingSchedule->start_time, 0, 5) : null) }}" required>
                    @error('start_time')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
                <div class="admin-profile-field">
                    <label for="modal_end_time">End Time</label>
                    <input id="modal_end_time" class="input" type="time" name="end_time" value="{{ old('end_time', $editingSchedule ? substr($editingSchedule->end_time, 0, 5) : null) }}" required>
                    @error('end_time')<span class="admin-profile-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <footer class="admin-profile-actions">
                <button id="cancelScheduleEdit" type="button" class="button button-secondary">Cancel</button>
                <button class="button" type="submit">Save Schedule Changes</button>
            </footer>
        </form>
    </section>
</div>
@endpush

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

    (() => {
        const modal = document.getElementById('scheduleEditModal');
        const closeButton = document.getElementById('closeScheduleEdit');
        const cancelButton = document.getElementById('cancelScheduleEdit');
        const firstInput = document.getElementById('modal_instructor_id');

        function openModal() {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            window.setTimeout(() => firstInput.focus(), 0);
        }

        function closeModal() {
            window.location = '{{ route('gec.timetable.index', request()->except('edit')) }}';
        }

        closeButton.addEventListener('click', closeModal);
        cancelButton.addEventListener('click', closeModal);
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

        @if($editingSchedule || $errors->hasAny(['instructor_id', 'room_id', 'day', 'start_time', 'end_time']))
            openModal();
        @endif
    })();
</script>
@endpush
