@extends('layouts.dean')
@section('title', 'Subject Endorsement')
@section('page-title', 'Subject Endorsement')

@push('styles')
<style>
    .endorsement-form-grid { grid-template-columns:repeat(3, minmax(0, 1fr)); }
    .endorsement-source { color:var(--muted); background:#faf8fb; cursor:default; }
    .endorsement-derived { color:var(--muted); background:#faf8fb; cursor:default; }
    .endorsement-help { margin:0 0 18px; padding:12px 14px; font-size:11px; line-height:1.55; color:#5e5367; background:#f8f2fc; border:1px solid #e4d0f1; border-radius:10px; }
    .endorsement-history-header { margin:0 0 14px; }
    .endorsement-history-header h3 { margin:0 0 4px; color:var(--navy); }
    .endorsement-history-header p { font-size:11px; color:var(--muted); }
    .endorsement-history-card .endorsement-history-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
    .endorsement-history-card .endorsement-history-header .button { flex:0 0 auto; white-space:nowrap; }
    .endorsement-history-card .button-danger:disabled { color:#9ca3af; background:#f8fafc; border-color:#e5e7eb; cursor:not-allowed; opacity:1; }
    .endorsement-class-list { display:grid; gap:5px; min-width:260px; }
    .endorsement-class-item { padding:7px 9px; font-size:10px; line-height:1.45; color:#51485a; background:#faf8fb; border:1px solid #eee7f1; border-radius:7px; }
    .endorsement-class-item strong { color:var(--navy); }
    .endorsement-lists { display:flex; flex-direction:column; gap:20px; }
    .endorsement-lists > .card { margin:0 !important; }
    .endorsement-received-card { order:1; }
    .endorsement-pending-card { order:2; }
    .endorsement-history-card { order:3; }
    @media(max-width:900px) { .endorsement-form-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media(max-width:600px) { .endorsement-form-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>Subject Endorsement</h2>
        <p>Record a subject endorsement from your department to another academic department.</p>
    </div>
</div>

<section class="card" style="margin-bottom:20px">
    <p class="endorsement-help">Your department is set automatically from your Dean account. Choose a year level, then select an existing subject from your Subjects list.</p>
    <form method="POST" action="{{ route('dean.subject-endorsements.store') }}">
        @csrf
        <div class="form-grid endorsement-form-grid">
            <div class="form-group">
                <label for="fromDepartment">Subject comes from</label>
                <input id="fromDepartment" class="input endorsement-source" type="text" value="{{ $course }} Department" data-department="{{ $course }}" readonly>
            </div>
            <div class="form-group">
                <label for="toDepartment">Endorse to department</label>
                <select id="toDepartment" class="input" name="to_department" required>
                    <option value="">Select a department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->code }}" @selected(old('to_department') === $department->code)>{{ $department->code }} — {{ $department->program_name }}</option>
                    @endforeach
                </select>
                @error('to_department')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="yearLevel">Year level</label>
                <select id="yearLevel" class="input" name="year_level" required>
                    <option value="">Select a year level</option>
                    @foreach([1 => 'First Year', 2 => 'Second Year', 3 => 'Third Year', 4 => 'Fourth Year'] as $level => $label)
                        <option value="{{ $level }}" @selected((string) old('year_level') === (string) $level)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('year_level')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="endorsementSubject">Subject code and name</label>
                <select id="endorsementSubject" class="input" name="subject_id" required disabled>
                    <option value="">Select the department and year level first</option>
                    @foreach($endorsementSubjects as $subject)
                        <option
                            value="{{ $subject->id }}"
                            data-department="{{ $subject->course }}"
                            data-year-level="{{ $subject->year_level }}"
                            data-subject-type="{{ $subject->subject_type }}"
                            data-units="{{ $subject->units }}"
                            @selected((string) old('subject_id') === (string) $subject->id)
                        >{{ $subject->code }} &mdash; {{ $subject->name }}</option>
                    @endforeach
                </select>
                @error('subject_id')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="subjectType">Type of subject</label>
                <input id="subjectType" class="input endorsement-derived" type="text" value="" placeholder="Selected automatically" readonly>
            </div>
            <div class="form-group">
                <label for="subjectUnits">Units</label>
                <input id="subjectUnits" class="input endorsement-derived" type="text" value="" placeholder="Selected automatically" readonly>
            </div>
        </div>
        <div class="form-actions">
            <button class="button" type="submit">Indorse</button>
        </div>
    </form>
</section>

<div class="endorsement-lists">
<section class="card endorsement-history-card" style="margin-bottom:20px">
    @php($canClearEndorsementHistory = $historyArchiveSupported && $endorsementHistory->isNotEmpty())
    <div class="endorsement-history-header">
        <div>
            <h3>Endorsement History</h3>
            <p>Completed endorsements are kept here, whether they were sent by your department or received from another department. Each generated class schedule is listed below its endorsement.</p>
        </div>
        <button
            type="button"
            class="button button-danger delete-confirmation-trigger"
            data-delete-url="{{ route('dean.subject-endorsements.history.destroy') }}"
            data-delete-name="{{ $endorsementHistory->count() }} completed {{ Str::plural('endorsement', $endorsementHistory->count()) }}"
            data-delete-title="Delete All Endorsement History?"
            data-delete-message="This removes completed endorsements only from your department's history. Generated schedules and the other department's history will remain unchanged."
            data-delete-confirm-label="Delete All History"
            title="{{ $historyArchiveSupported ? 'Remove completed endorsements from this department history.' : 'Run the latest database migration before clearing endorsement history.' }}"
            @disabled(! $canClearEndorsementHistory)
        >Delete All History</button>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Direction</th><th>Departments</th><th>Subject</th><th>Generated Class Schedules</th><th>Scheduled</th></tr></thead>
        <tbody>
        @forelse($endorsementHistory as $endorsement)
            <tr>
                <td><span class="badge">{{ $endorsement->from_department === $course ? 'Sent' : 'Received' }}</span></td>
                <td><strong>{{ $endorsement->from_department }}</strong> &rarr; <strong>{{ $endorsement->to_department }}</strong></td>
                <td>
                    <strong>{{ $endorsement->subject_code }}</strong><br>
                    <small>{{ $endorsement->subject_name ?? $endorsement->subject?->name ?? 'Subject name unavailable' }} &middot; {{ $endorsement->subject_type }} &middot; {{ rtrim(rtrim(number_format($endorsement->units, 1), '0'), '.') }} units</small>
                </td>
                <td>
                    <div class="endorsement-class-list">
                    @forelse($endorsement->scheduledClasses as $schedule)
                        <div class="endorsement-class-item">
                            <strong>{{ $schedule->section?->name ?? 'Section unavailable' }}</strong>
                            &middot; {{ $schedule->day }}
                            &middot; {{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('g:i A') }}&ndash;{{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('g:i A') }}<br>
                            {{ $schedule->instructor?->name ?? 'Instructor TBA' }} &middot; {{ $schedule->room?->name ?? 'Room TBA' }}
                        </div>
                    @empty
                        <span class="muted">The generated schedules are no longer available.</span>
                    @endforelse
                    </div>
                </td>
                <td>{{ $endorsement->scheduled_at?->format('M j, Y g:i A') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No completed endorsements yet. Scheduled endorsements will appear here.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

@if($receivedEndorsements->isNotEmpty())
<section class="card endorsement-received-card" style="margin-bottom:20px">
    <div class="endorsement-history-header">
        <h3>Endorsements Received</h3>
        <p>Use the schedule action to create a class schedule for the source department's sections using your department's instructors and rooms.</p>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>From Department</th><th>Subject</th><th>Subject Type</th><th>Units</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @foreach($receivedEndorsements as $endorsement)
            <tr>
                <td><span class="badge">{{ $endorsement->from_department }}</span></td>
                <td><strong>{{ $endorsement->subject_code }}</strong><br><small>{{ $endorsement->subject_name ?? $endorsement->subject?->name ?? 'Subject name unavailable' }}</small></td>
                <td>{{ $endorsement->subject_type }}</td>
                <td>{{ rtrim(rtrim(number_format($endorsement->units, 1), '0'), '.') }}</td>
                <td><span class="badge">{{ $endorsement->scheduled_at ? 'Scheduled' : 'Ready to schedule' }}</span></td>
                <td><a class="button button-secondary" href="{{ route('dean.subject-endorsements.schedule.create', $endorsement) }}">Schedule Subject</a></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endif

<section class="card endorsement-pending-card">
    <div class="endorsement-history-header">
        <h3>Pending Endorsements You Sent</h3>
        <p>These are the endorsements submitted by the {{ $course }} department that are still waiting to be scheduled by the receiving department.</p>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>From Department</th><th>Endorsed To</th><th>Subject</th><th>Subject Type</th><th>Units</th><th>Submitted</th></tr></thead>
        <tbody>
        @forelse($endorsements as $endorsement)
            <tr>
                <td><span class="badge">{{ $endorsement->from_department }}</span></td>
                <td><span class="badge">{{ $endorsement->to_department }}</span></td>
                <td><strong>{{ $endorsement->subject_code }}</strong><br><small>{{ $endorsement->subject_name ?? 'Subject name unavailable' }}</small></td>
                <td>{{ $endorsement->subject_type }}</td>
                <td>{{ rtrim(rtrim(number_format($endorsement->units, 1), '0'), '.') }}</td>
                <td>{{ $endorsement->created_at->format('M j, Y g:i A') }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No pending subject endorsements submitted yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
</div>
@if($canClearEndorsementHistory)
    @include('dean.partials.delete-confirmation', [
        'title' => 'Delete All Endorsement History?',
        'message' => 'This removes completed endorsements only from your department history. Generated schedules will remain unchanged.',
        'confirmLabel' => 'Delete All History',
    ])
@endif
@endsection

@push('scripts')
<script>
    (() => {
        const fromDepartment = document.getElementById('fromDepartment');
        const yearLevel = document.getElementById('yearLevel');
        const subject = document.getElementById('endorsementSubject');
        const subjectType = document.getElementById('subjectType');
        const subjectUnits = document.getElementById('subjectUnits');

        const selectedSource = () => fromDepartment.dataset.department || fromDepartment.value;
        const updateSubjectDetails = () => {
            const option = subject.options[subject.selectedIndex];
            subjectType.value = option?.dataset.subjectType || '';
            subjectUnits.value = option?.dataset.units || '';
        };

        const updateSubjectOptions = () => {
            const source = selectedSource();
            const level = yearLevel.value;
            let selectedOptionIsAvailable = false;

            [...subject.options].forEach(option => {
                if (! option.value) return;

                const available = Boolean(source && level)
                    && option.dataset.department === source
                    && option.dataset.yearLevel === level;
                option.hidden = ! available;
                option.disabled = ! available;
                selectedOptionIsAvailable ||= option.selected && available;
            });

            subject.disabled = ! source || ! level;
            if (! selectedOptionIsAvailable) subject.value = '';
            subject.options[0].text = source && level
                ? 'Select a subject'
                : 'Select the department and year level first';
            updateSubjectDetails();
        };

        yearLevel.addEventListener('change', updateSubjectOptions);
        subject.addEventListener('change', updateSubjectDetails);
        updateSubjectOptions();
    })();
</script>
@endpush
