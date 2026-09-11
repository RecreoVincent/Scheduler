@extends('layouts.gec')
@section('title', 'Subject Assignment')
@section('page-title', 'Subject Assignment')

@push('styles')
<style>
    .assignment-table { table-layout:fixed; width:100%; }
    .assignment-table th, .assignment-table td { padding:16px 22px; }
    .assignment-table td { vertical-align:middle; word-wrap:break-word; }
    .assignment-table th:nth-child(1), .assignment-table td:nth-child(1) { width:10%; }
    .assignment-table th:nth-child(2), .assignment-table td:nth-child(2) { width:11%; }
    .assignment-table th:nth-child(3), .assignment-table td:nth-child(3) { width:18%; }
    .assignment-table th:nth-child(4), .assignment-table td:nth-child(4) { width:9%; }
    .assignment-table th:nth-child(5), .assignment-table td:nth-child(5) { width:10%; }
    .assignment-table th:nth-child(6), .assignment-table td:nth-child(6) { width:13%; }
    .assignment-table th:nth-child(7), .assignment-table td:nth-child(7) { width:17%; }
    .assignment-table th:nth-child(8), .assignment-table td:nth-child(8) { width:12%; }
    .assignment-instructors { display:flex; flex-wrap:wrap; gap:5px; }
    .assignment-instructors .badge { text-transform:none; }
    .assignment-actions { width:1%; white-space:nowrap; text-align:right; }
    .assignment-actions .actions { justify-content:flex-end; flex-wrap:nowrap; }
    .assignment-actions .button { min-width:100px; }
    .assignment-empty { padding:28px !important; color:var(--muted); text-align:center; }
    .assignment-search { min-width:min(290px,100%); }
    .assignment-filters { grid-template-columns:repeat(4,minmax(0,1fr)); }
    @media(max-width:900px) { .assignment-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:560px) { .assignment-filters { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@include('gec.subject-assignments.form')

<div class="page-header">
    <div class="actions">
        <button type="button" class="button assignment-form-trigger" aria-label="Assign Instructor to a Subject">Assign Instructor</button>
        <button
            type="button"
            class="button button-danger delete-confirmation-trigger"
            data-delete-url="{{ route('gec.subject-assignments.destroy-all') }}"
            data-delete-name="{{ $assignmentCount }} {{ str('instructor assignment')->plural($assignmentCount) }} across all departments"
            data-delete-title="Remove All Subject Assignments?"
            data-delete-message="This removes every instructor priority assignment from every minor subject. The subjects themselves will not be deleted."
            data-delete-confirm-label="Remove All Assignments"
            @disabled($assignmentCount === 0)
        >Remove All Subject Assignments</button>
    </div>
    <div style="order:-1">
        <h2>Existing Subject Assignments</h2>
        <p>Search, filter, and update GEC instructor priorities for a minor subject.</p>
    </div>
</div>

<section class="card">
    <form class="filters assignment-filters" method="GET" data-auto-filter>
        <div class="assignment-search">
            <label for="assignmentSearch">Search subject</label>
            <input
                id="assignmentSearch"
                class="input"
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search subject code or description"
            >
        </div>
        <div>
            <label for="assignmentDepartment">Department</label>
            <select id="assignmentDepartment" class="input" name="department">
                <option value="">All departments</option>
                @foreach(\App\Http\Controllers\Gec\GecController::REAL_DEPARTMENTS as $department)
                    <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="assignmentYearLevel">Year level</label>
            <select id="assignmentYearLevel" class="input" name="year_level">
                <option value="">All years</option>
                @for($level = 1; $level <= 4; $level++)
                    <option value="{{ $level }}" @selected((string) request('year_level') === (string) $level)>Year {{ $level }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label for="assignmentStatus">Assignment status</label>
            <select id="assignmentStatus" class="input" name="assignment_status">
                <option value="">All assignment statuses</option>
                <option value="assigned" @selected(request('assignment_status') === 'assigned')>Assigned</option>
                <option value="unassigned" @selected(request('assignment_status') === 'unassigned')>Unassigned</option>
            </select>
        </div>
    </form>

    <div class="table-wrap">
        <table class="assignment-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Subject Code</th>
                    <th>Subject Description</th>
                    <th>Year</th>
                    <th>Semester</th>
                    <th>Curriculum</th>
                    <th>Assigned Instructors</th>
                    <th class="assignment-actions">Assignment</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                    <tr>
                        <td><span class="badge">{{ $subject->course }}</span></td>
                        <td><strong>{{ $subject->code }}</strong></td>
                        <td>{{ $subject->name }}</td>
                        <td>Year {{ $subject->year_level }}</td>
                        <td>{{ $subject->semester }}</td>
                        <td>{{ $subject->curriculum }} Curriculum</td>
                        <td>
                            <div class="assignment-instructors">
                                @forelse($subject->instructors as $instructor)
                                    <span class="badge">Priority {{ $instructor->pivot->priority }} · {{ $instructor->name }}</span>
                                @empty
                                    <span class="badge">Unassigned</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="assignment-actions">
                            <div class="actions">
                                <a class="button button-secondary" href="{{ route('gec.subject-assignments.index', array_merge(request()->only(['search', 'department', 'year_level', 'semester', 'assignment_status']), ['subject_id' => $subject->id, 'open_assignment_modal' => 1])) }}#assignment-form">
                                    {{ $subject->instructors->isEmpty() ? 'Assign' : 'Update' }}
                                </a>
                                @if($subject->instructors->isNotEmpty())
                                    <button
                                        type="button"
                                        class="button button-danger delete-confirmation-trigger"
                                        data-delete-url="{{ route('gec.subject-assignments.destroy', $subject) }}"
                                        data-delete-name="{{ $subject->course }} {{ $subject->code }} — {{ $subject->name }}"
                                        data-delete-title="Remove Subject Assignments?"
                                        data-delete-message="This removes every instructor priority assignment from this subject only. The subject itself will not be deleted."
                                        data-delete-confirm-label="Remove Assignments"
                                    >Remove</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="assignment-empty" colspan="8">No minor subjects match the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('dean.partials.delete-confirmation', [
    'title' => 'Remove All Subject Assignments?',
    'message' => 'This removes every instructor priority assignment. The subjects themselves will not be deleted.',
    'confirmLabel' => 'Remove All Assignments',
])
@endsection
