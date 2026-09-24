@extends('layouts.dean')
@section('title', 'Schedule Endorsed Subject')
@section('page-title', 'Schedule Endorsed Subject')

@push('styles')
<style>
    #endorsedScheduleForm .form-grid { grid-template-columns:repeat(3, minmax(0, 1fr)); }
    .endorsement-schedule-summary { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:10px; margin-bottom:20px; }
    .endorsement-schedule-summary div { padding:12px; background:#f8f2fc; border:1px solid #e4d0f1; border-radius:10px; }
    .endorsement-schedule-summary span,.endorsement-schedule-summary strong { display:block; }
    .endorsement-schedule-summary span { margin-bottom:4px; font-size:9px; font-weight:800; letter-spacing:.65px; color:var(--muted); text-transform:uppercase; }
    .endorsement-schedule-summary strong { font-size:12px; color:var(--navy); }
    .endorsement-schedule-note { margin:0 0 20px; padding:13px 15px; font-size:11px; line-height:1.55; color:#5d5266; background:#fbf8fd; border:1px solid #e5dbea; border-radius:10px; }
    .endorsement-priorities { grid-column:1/-1; display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:10px; padding:15px; background:#faf8fb; border:1px solid #eee7f1; border-radius:10px; }
    .endorsement-priorities > div { min-width:0; }
    .endorsement-priorities h3 { grid-column:1/-1; margin:0; font-size:12px; color:var(--navy); }
    .endorsement-priorities p { grid-column:1/-1; margin:-5px 0 0; font-size:10px; color:var(--muted); }
    .section-preview { grid-column:1/-1; margin-top:4px; padding:18px; background:#faf8fb; border:1px solid var(--border); border-radius:11px; }
    .section-preview-head { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; margin-bottom:13px; }
    .section-preview-head h3 { margin-bottom:4px; font-size:12px; color:var(--navy); }
    .section-preview-head p,.section-count-status { font-size:10px; color:var(--muted); }
    .section-count-status { padding:6px 9px; font-weight:800; color:var(--primary); background:#f2e7fa; border-radius:20px; white-space:nowrap; }
    .section-list { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:9px; }
    .section-item { padding:11px; background:white; border:1px solid #e3d8e9; border-radius:9px; }
    .section-item.selected { color:var(--primary); background:#f3e9fa; border-color:var(--primary-light); box-shadow:inset 3px 0 var(--gold); }
    .section-item strong,.section-item small { display:block; }
    .section-item strong { font-size:11px; }
    .section-item small { margin-top:3px; font-size:9px; color:var(--muted); }
    .section-empty { grid-column:1/-1; padding:15px; font-size:11px; color:var(--muted); text-align:center; background:white; border:1px dashed #d8cce0; border-radius:9px; }
    @media(max-width:900px) { #endorsedScheduleForm .form-grid,.endorsement-schedule-summary { grid-template-columns:repeat(2, minmax(0, 1fr)); } .endorsement-priorities { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media(max-width:600px) { #endorsedScheduleForm .form-grid,.endorsement-schedule-summary,.endorsement-priorities,.section-list { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@php
    $academicYears = $sections->pluck('academic_year')->unique()->values();
    $defaultAcademicYear = old('academic_year', $academicYears->first());
    $defaultSectionCount = old('number_of_sections', 1);
@endphp

<div class="page-header">
    <div><h2>Schedule Endorsed Subject</h2><p>Set up the endorsed subject using the same cross-department scheduling approach as GEC.</p></div>
    <a class="button button-secondary" href="{{ route('dean.subject-endorsements.index') }}">Back to Endorsements</a>
</div>

<section class="endorsement-schedule-summary">
    <div><span>Source department</span><strong>{{ $sourceDepartment }}</strong></div>
    <div><span>Scheduling department</span><strong>{{ auth()->user()->course }}</strong></div>
    <div><span>Subject</span><strong>{{ $endorsement->subject_code }}<br>{{ $endorsement->subject_name ?? $endorsement->subject->name }}</strong></div>
    <div><span>Type / Units</span><strong>{{ $endorsement->subject_type }} &middot; {{ rtrim(rtrim(number_format($endorsement->units, 1), '0'), '.') }} units</strong></div>
</section>

<section class="card">
    <p class="endorsement-schedule-note">This creates schedules for {{ $sourceDepartment }} Year {{ $endorsement->subject->year_level }} sections. Your department's instructors and rooms are used, while existing schedules stay protected from conflicts. Only previous schedules for this endorsed subject are replaced.</p>
    <form id="endorsedScheduleForm" method="POST" action="{{ route('dean.subject-endorsements.schedule.store', $endorsement) }}">
        @csrf
        <div class="form-grid">
            <div><label>Program / Degree</label><input class="input" value="{{ $sourceDepartment }}" readonly></div>
            <div>
                <label for="academicYear">Academic Year</label>
                <select id="academicYear" class="input" name="academic_year" required>
                    @forelse($academicYears as $academicYear)<option value="{{ $academicYear }}" @selected($defaultAcademicYear === $academicYear)>{{ $academicYear }}</option>@empty<option value="">No academic years available</option>@endforelse
                </select>
                @error('academic_year')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="endorsementSemester">Semester</label>
                <select id="endorsementSemester" class="input" name="semester" required>
                    @foreach($enabledSemesters as $semester)<option value="{{ $semester }}" @selected(old('semester', $endorsement->subject->semester) === $semester)>{{ $semester }} Semester</option>@endforeach
                </select>
                @error('semester')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label>Year Level</label>
                <input class="input" value="Year {{ $endorsement->subject->year_level }}" readonly>
            </div>
            <div>
                <label for="sectionCount">Number of Existing Sections to Use</label>
                <input id="sectionCount" class="input" type="number" min="1" max="20" name="number_of_sections" value="{{ $defaultSectionCount }}" required>
                @error('number_of_sections')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="endorsement-priorities">
                <h3>Instructor Priorities</h3>
                <p>Select active {{ auth()->user()->course }} instructors. Priority 1 is required; the remaining priorities are backups.</p>
                @for($priority = 1; $priority <= 4; $priority++)
                    <div>
                        <label for="endorsementInstructor{{ $priority }}">Priority {{ $priority }}</label>
                        <select id="endorsementInstructor{{ $priority }}" class="input endorsement-instructor" name="instructor_ids[]" @required($priority === 1)>
                            <option value="">{{ $priority === 1 ? 'Select instructor' : 'Optional backup' }}</option>
                            @foreach($instructors as $instructor)
                                <option
                                    value="{{ $instructor->id }}"
                                    data-instructor-name="{{ $instructor->name }}"
                                    @selected(old('instructor_ids.'.($priority - 1)) == $instructor->id)
                                >{{ $instructor->name }} &middot; Current load</option>
                            @endforeach
                        </select>
                    </div>
                @endfor
                @error('instructor_ids')<p class="error" style="grid-column:1/-1">{{ $message }}</p>@enderror
                @error('instructor_ids.*')<p class="error" style="grid-column:1/-1">{{ $message }}</p>@enderror
            </div>

            <div class="section-preview">
                <div class="section-preview-head"><div><h3>Sections the Generator Will Use</h3><p>Automatically selected from {{ $sourceDepartment }}'s existing Year {{ $endorsement->subject->year_level }} sections.</p></div><span id="sectionCountStatus" class="section-count-status">0 sections available</span></div>
                <div id="sectionList" class="section-list">
                    @foreach($sections as $section)
                        <div class="section-item" data-academic-year="{{ $section->academic_year }}" data-name="{{ $section->name }}"><strong>{{ $section->name }}</strong><small>Year {{ $section->year_level }} &middot; {{ $section->academic_year }}</small></div>
                    @endforeach
                    <p id="sectionEmpty" class="section-empty" hidden>No existing sections match this academic year.</p>
                </div>
            </div>
        </div>
        <div class="form-actions"><button id="generateButton" class="button" type="submit" @disabled($sections->isEmpty() || $instructors->isEmpty())>Create Endorsed Schedule</button></div>
        @if($instructors->isEmpty())<p class="error" style="margin-top:10px">Add or approve an active {{ auth()->user()->course }} instructor before creating this schedule.</p>@endif
    </form>
</section>
@endsection

@push('scripts')
<script>
    (() => {
        const academicYear = document.getElementById('academicYear');
        const countInput = document.getElementById('sectionCount');
        const items = [...document.querySelectorAll('.section-item')];
        const empty = document.getElementById('sectionEmpty');
        const status = document.getElementById('sectionCountStatus');
        const generateButton = document.getElementById('generateButton');
        const instructors = [...document.querySelectorAll('.endorsement-instructor')];
        const semester = document.getElementById('endorsementSemester');
        const scheduledLoads = @json($scheduledInstructorLoads);
        const instructorLimits = @json($instructorLimits);

        const formatHours = hours => Number.isInteger(hours) ? String(hours) : hours.toFixed(1).replace(/\.0$/, '');
        const instructorLoad = instructorId => {
            const used = Number(scheduledLoads[instructorId]?.[academicYear.value]?.[semester.value] ?? 0);
            const limit = Number(instructorLimits[instructorId] ?? 0);

            return `${formatHours(used)}/${formatHours(limit)} hours`;
        };

        const updateInstructors = () => {
            const selected = instructors.map(input => input.value).filter(Boolean);
            instructors.forEach(input => {
                const ownValue = input.value;
                [...input.options].forEach(option => {
                    if (!option.value) return;
                    option.disabled = selected.includes(option.value) && option.value !== ownValue;
                    option.textContent = `${option.dataset.instructorName} · ${instructorLoad(option.value)}`;
                });
            });
        };
        const updatePreview = () => {
            const matching = items.filter(item => item.dataset.academicYear === academicYear.value)
                .sort((left, right) => left.dataset.name.localeCompare(right.dataset.name, undefined, {numeric:true, sensitivity:'base'}));
            const requested = Math.max(1, Number.parseInt(countInput.value || '1', 10));
            const selected = matching.slice(0, requested);
            items.forEach(item => { item.hidden = true; item.classList.remove('selected'); });
            matching.forEach(item => { item.hidden = false; item.classList.toggle('selected', selected.includes(item)); });
            countInput.max = Math.max(1, matching.length);
            empty.hidden = matching.length > 0;
            generateButton.disabled = matching.length === 0 || requested > matching.length || !instructors[0]?.value;
            status.textContent = matching.length === 0 ? '0 sections available' : `Using ${selected.length} of ${matching.length} sections`;
        };

        academicYear.addEventListener('change', () => { updateInstructors(); updatePreview(); });
        semester.addEventListener('change', updateInstructors);
        countInput.addEventListener('input', updatePreview);
        instructors.forEach(input => input.addEventListener('change', () => { updateInstructors(); updatePreview(); }));
        updateInstructors();
        updatePreview();
    })();
</script>
@endpush
