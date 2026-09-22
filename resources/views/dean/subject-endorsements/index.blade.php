@extends('layouts.dean')
@section('title', 'Subject Endorsement')
@section('page-title', 'Subject Endorsement')

@push('styles')
<style>
    .endorsement-form-grid { grid-template-columns:repeat(3, minmax(0, 1fr)); }
    .endorsement-source { color:var(--muted); background:#faf8fb; cursor:default; }
    .endorsement-help { margin:0 0 18px; padding:12px 14px; font-size:11px; line-height:1.55; color:#5e5367; background:#f8f2fc; border:1px solid #e4d0f1; border-radius:10px; }
    .endorsement-history-header { margin:0 0 14px; }
    .endorsement-history-header h3 { margin:0 0 4px; color:var(--navy); }
    .endorsement-history-header p { font-size:11px; color:var(--muted); }
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
    <p class="endorsement-help">Your department is set automatically from your Dean account. The subject must already exist in your Subjects list with the code, type, and units entered below.</p>
    <form method="POST" action="{{ route('dean.subject-endorsements.store') }}">
        @csrf
        <div class="form-grid endorsement-form-grid">
            <div class="form-group">
                <label for="fromDepartment">Subject comes from</label>
                <input id="fromDepartment" class="input endorsement-source" type="text" value="{{ $course }} Department" readonly>
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
                <label for="subjectCode">Subject code</label>
                <input id="subjectCode" class="input" name="subject_code" value="{{ old('subject_code') }}" maxlength="30" placeholder="Example: ITE 201" required>
                @error('subject_code')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="subjectName">Subject name</label>
                <input id="subjectName" class="input" name="subject_name" value="{{ old('subject_name') }}" maxlength="150" placeholder="Example: Object-Oriented Programming" required>
                @error('subject_name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="subjectType">Type of subject</label>
                <select id="subjectType" class="input" name="subject_type" required>
                    <option value="">Select subject type</option>
                    @foreach(['Lecture', 'Laboratory', 'Internship'] as $type)
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
        <div class="form-actions">
            <button class="button" type="submit">Indorse</button>
        </div>
    </form>
</section>

@if($receivedEndorsements->isNotEmpty())
<section class="card" style="margin-bottom:20px">
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

<section class="card">
    <div class="endorsement-history-header">
        <h3>Endorsements You Sent</h3>
        <p>These are the subject endorsements submitted by the {{ $course }} department.</p>
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
            <tr><td colspan="6">No subject endorsements submitted yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
