@extends('layouts.dean')
@section('title', 'Subject Assignment')
@section('page-title', 'Subject Assignment')

@push('styles')
<style>
    .assignment-table { min-width:820px; width:100%; }
    .assignment-table th, .assignment-table td { padding:16px 24px; }
    .assignment-table td { vertical-align:middle; word-wrap:break-word; }
    .assignment-table th:nth-child(6), .assignment-table td:nth-child(6) { min-width:220px; }
    .assignment-instructors { display:flex; flex-wrap:wrap; gap:5px; }
    .assignment-instructors .badge { text-transform:none; }
    .assignment-actions { white-space:nowrap; text-align:center; }
    .assignment-actions .actions { justify-content:center; flex-wrap:nowrap; }
    .assignment-actions .button { min-width:100px; }
    .assignment-empty { padding:28px !important; color:var(--muted); text-align:center; }
    .assignment-search { min-width:min(290px,100%); }
    .assignment-filters { grid-template-columns:repeat(3,minmax(0,1fr)) auto; }
    .assignment-filter-submit { align-self:end; white-space:nowrap; }
    @media(max-width:760px) { .assignment-filters { grid-template-columns:1fr; } .assignment-filter-submit { width:100%; } }
</style>
@endpush

@section('content')
@include('dean.subject-assignments.form')

<div class="page-header">
    <div class="actions">
        <button type="button" class="button assignment-form-trigger" aria-label="Assign Instructor to a Subject">Assign Instructor</button>
        <button
            type="button"
            class="button button-danger delete-confirmation-trigger"
            data-delete-url="{{ route('dean.subject-assignments.destroy-all') }}"
            data-delete-name="{{ $assignmentCount }} {{ str('instructor assignment')->plural($assignmentCount) }} in {{ $course }}"
            data-delete-title="Remove All Subject Assignments?"
            data-delete-message="This removes every instructor priority assignment from all {{ $course }} subjects. The subjects themselves will not be deleted."
            data-delete-confirm-label="Remove All Assignments"
            @disabled($assignmentCount === 0)
        >Remove All Subject Assignments</button>
    </div>
    <div style="order:-1">
        <h2>Existing Subject Assignments</h2>
        <p>Search, filter, and update instructor priorities for a subject.</p>
    </div>
</div>

<section class="card">
    <form class="filters assignment-filters" method="GET">
        <input
            class="input assignment-search"
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search subject code or description"
            aria-label="Search subjects"
        >
        <select class="input" name="year_level">
            <option value="">All years</option>
            @for($level = 1; $level <= 4; $level++)
                <option value="{{ $level }}" @selected((string) request('year_level') === (string) $level)>Year {{ $level }}</option>
            @endfor
        </select>
        <select class="input" name="assignment_status">
            <option value="">All assignment statuses</option>
            <option value="assigned" @selected(request('assignment_status') === 'assigned')>Assigned</option>
            <option value="unassigned" @selected(request('assignment_status') === 'unassigned')>Unassigned</option>
        </select>
        <button class="button assignment-filter-submit" type="submit">Search</button>
    </form>

    <div class="table-wrap">
        <table class="assignment-table">
            <thead>
                <tr>
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
                        <td><strong>{{ $subject->code }}</strong></td>
                        <td>{{ $subject->name }}</td>
                        <td>Year {{ $subject->year_level }}</td>
                        <td>{{ $subject->semester }}</td>
                        <td>{{ $subject->curriculum }} Curriculum</td>
                        <td>
                            <div class="assignment-instructors">
                                @forelse($subject->instructors as $instructor)
                                    <span class="badge">Priority {{ $instructor->pivot->priority }} · {{ $instructor->name }} · {{ $instructor->course }}</span>
                                @empty
                                    <span class="badge">Unassigned</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="assignment-actions">
                            <div class="actions">
                                <a class="button button-secondary" href="{{ route('dean.subject-assignments.index', array_merge(request()->only(['search', 'year_level', 'semester', 'assignment_status']), ['subject_id' => $subject->id, 'open_assignment_modal' => 1])) }}#assignment-form">
                                    {{ $subject->instructors->isEmpty() ? 'Assign' : 'Update' }}
                                </a>
                                @if($subject->instructors->isNotEmpty())
                                    <button
                                        type="button"
                                        class="button button-danger delete-confirmation-trigger"
                                        data-delete-url="{{ route('dean.subject-assignments.destroy', $subject) }}"
                                        data-delete-name="{{ $subject->code }} — {{ $subject->name }}"
                                        data-delete-title="Remove Subject Assignments?"
                                        data-delete-message="This removes every instructor priority assignment from this subject only. The subject itself will not be deleted."
                                        data-delete-confirm-label="Remove Assignments"
                                    >Remove</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="assignment-empty" colspan="7">No subjects match the selected filters.</td></tr>
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
