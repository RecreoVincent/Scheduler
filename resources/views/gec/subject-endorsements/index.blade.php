@extends('layouts.gec')
@section('title', 'Subject Endorsement')
@section('page-title', 'Subject Endorsement')

@push('styles')
<style>
    .endorsement-form-grid { grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; }
    .endorsement-form-grid .form-group { min-width:0; margin:0; }
    .endorsement-form-grid .input { width:100%; min-width:0; }
    .endorsement-form-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:20px; padding-top:18px; border-top:1px solid #eee8f1; }
    .endorsement-source { color:var(--muted); background:#faf8fb; cursor:default; }
    .endorsement-help { margin:0 0 18px; padding:12px 14px; font-size:11px; line-height:1.55; color:#5e5367; background:#f8f2fc; border:1px solid #e4d0f1; border-radius:10px; }
    .endorsement-history-header { margin:0 0 14px; }
    .endorsement-history-header h3 { margin:0 0 4px; color:var(--navy); }
    .endorsement-history-header p { font-size:11px; color:var(--muted); }
    .endorsement-class-list { display:grid; gap:5px; min-width:260px; }
    .endorsement-class-item { padding:7px 9px; font-size:10px; line-height:1.45; color:#51485a; background:#faf8fb; border:1px solid #eee7f1; border-radius:7px; }
    .endorsement-class-item strong { color:var(--navy); }
    @media(max-width:900px) { .endorsement-form-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media(max-width:600px) { .endorsement-form-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2>Subject Endorsement</h2>
        <p>Endorse a GEC-managed Minor subject from one department to another department.</p>
    </div>
</div>

<section class="card" style="margin-bottom:20px">
    <p class="endorsement-help">GEC submits this endorsement on behalf of the department that owns the Minor subject. The selected subject must already exist in GEC's Minor Subjects list with the same code, name, type, and units.</p>
    <form method="POST" action="{{ route('gec.subject-endorsements.store') }}">
        @csrf
        <div class="form-grid endorsement-form-grid">
            <div class="form-group">
                <label for="submittedBy">Submitting office</label>
                <input id="submittedBy" class="input endorsement-source" type="text" value="General Education Course (GEC)" readonly>
            </div>
            <div class="form-group">
                <label for="fromDepartment">Subject comes from</label>
                <select id="fromDepartment" class="input" name="from_department" required>
                    <option value="">Select the subject's department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->code }}" @selected(old('from_department') === $department->code)>{{ $department->code }} &mdash; {{ $department->program_name }}</option>
                    @endforeach
                </select>
                @error('from_department')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="toDepartment">Endorse to department</label>
                <select id="toDepartment" class="input" name="to_department" required>
                    <option value="">Select a department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->code }}" @selected(old('to_department') === $department->code)>{{ $department->code }} &mdash; {{ $department->program_name }}</option>
                    @endforeach
                </select>
                @error('to_department')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="subjectCode">Subject code</label>
                <input id="subjectCode" class="input" name="subject_code" value="{{ old('subject_code') }}" maxlength="30" placeholder="Example: GE 101" required>
                @error('subject_code')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="subjectName">Subject name</label>
                <input id="subjectName" class="input" name="subject_name" value="{{ old('subject_name') }}" maxlength="150" placeholder="Example: Understanding the Self" required>
                @error('subject_name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="subjectType">Type of subject</label>
                <select id="subjectType" class="input" name="subject_type" required>
                    <option value="">Select subject type</option>
                    @foreach(['Lecture', 'Laboratory'] as $type)
                        <option value="{{ $type }}" @selected(old('subject_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                @error('subject_type')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="subjectUnits">Units</label>
                <input id="subjectUnits" class="input" type="number" name="units" value="{{ old('units') }}" min="0.5" max="12" step="0.5" placeholder="Example: 3" required>
                @error('units')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="endorsement-form-actions">
            <button class="button" type="submit">Indorse</button>
        </div>
    </form>
</section>

<section class="card" style="margin-bottom:20px">
    <div class="endorsement-history-header">
        <h3>Endorsement History</h3>
        <p>Completed GEC endorsements remain here with every generated class schedule.</p>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Departments</th><th>Subject</th><th>Generated Class Schedules</th><th>Scheduled</th></tr></thead>
        <tbody>
        @forelse($endorsementHistory as $endorsement)
            <tr>
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
            <tr><td colspan="4">No completed GEC endorsements yet. Scheduled endorsements will appear here.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<section class="card">
    <div class="endorsement-history-header">
        <h3>Pending Endorsements</h3>
        <p>These GEC endorsements are waiting for the receiving Dean / Program Head to create the schedule.</p>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>From Department</th><th>Endorsed To</th><th>Subject</th><th>Subject Type</th><th>Units</th><th>Submitted</th></tr></thead>
        <tbody>
        @forelse($pendingEndorsements as $endorsement)
            <tr>
                <td><span class="badge">{{ $endorsement->from_department }}</span></td>
                <td><span class="badge">{{ $endorsement->to_department }}</span></td>
                <td><strong>{{ $endorsement->subject_code }}</strong><br><small>{{ $endorsement->subject_name ?? $endorsement->subject?->name ?? 'Subject name unavailable' }}</small></td>
                <td>{{ $endorsement->subject_type }}</td>
                <td>{{ rtrim(rtrim(number_format($endorsement->units, 1), '0'), '.') }}</td>
                <td>{{ $endorsement->created_at->format('M j, Y g:i A') }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No pending GEC endorsements submitted yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection

@push('scripts')
<script>
    (() => {
        const fromDepartment = document.getElementById('fromDepartment');
        const toDepartment = document.getElementById('toDepartment');

        const updateDestinationOptions = () => {
            [...toDepartment.options].forEach(option => {
                if (!option.value) return;
                option.disabled = option.value === fromDepartment.value;
            });
            if (toDepartment.value === fromDepartment.value) toDepartment.value = '';
        };

        fromDepartment.addEventListener('change', updateDestinationOptions);
        updateDestinationOptions();
    })();
</script>
@endpush
