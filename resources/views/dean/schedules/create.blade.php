@extends('layouts.dean')

@section('title', 'Create Schedule')
@section('page-title', 'Automatic Schedule Generator')

@push('styles')
<style>
    .generator-note { margin-bottom:20px; padding:16px; color:var(--gold-dark); background:var(--gold-soft); border:1px solid #efd486; border-radius:11px; line-height:1.6; }
    .section-preview { grid-column:1/-1; margin-top:4px; padding:18px; background:#faf8fb; border:1px solid var(--border); border-radius:11px; }
    .section-preview-head { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; margin-bottom:13px; }
    .section-preview-head h3 { margin-bottom:4px; font-size:12px; color:var(--navy); }
    .section-preview-head p,.section-count-status { font-size:10px; color:var(--muted); }
    .section-count-status { padding:6px 9px; font-weight:800; color:var(--primary); background:#f2e7fa; border-radius:20px; white-space:nowrap; }
    .section-list { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:9px; }
    .section-item { padding:11px; background:white; border:1px solid #e3d8e9; border-radius:9px; }
    .section-item.selected { color:var(--primary); background:#f3e9fa; border-color:var(--primary-light); box-shadow:inset 3px 0 var(--gold); }
    .section-item strong,.section-item small { display:block; }
    .section-item strong { font-size:11px; }
    .section-item small { margin-top:3px; font-size:9px; color:var(--muted); }
    .section-empty { grid-column:1/-1; padding:15px; font-size:11px; color:var(--muted); text-align:center; background:white; border:1px dashed #d8cce0; border-radius:9px; }
    #number_of_sections { appearance:textfield; -moz-appearance:textfield; }
    #number_of_sections::-webkit-inner-spin-button,
    #number_of_sections::-webkit-outer-spin-button { margin:0; -webkit-appearance:none; }
    @media(max-width:700px) { .section-list { grid-template-columns:1fr; } .section-preview-head { flex-direction:column; } }
</style>
@endpush

@section('content')
@php
    $academicYears = $sections->pluck('academic_year')->unique()->values();
    $defaultAcademicYear = old('academic_year', $academicYears->first());
    $defaultYearLevel = (string) old('year_level', $sections->firstWhere('academic_year', $defaultAcademicYear)?->year_level ?? 1);
@endphp

<div class="page-header">
    <div><h2>Generate {{ $course }} Class Schedules</h2><p>Use the first existing sections for the selected year level and academic year.</p></div>
</div>

<div class="card" style="max-width:850px">
    <div class="generator-note"><strong>Scheduling rules:</strong> Classes run from 7:30 AM to 7:30 PM, with 12:00 PM to 1:00 PM reserved for lunch. Major subjects use 2 hours 30 minutes and may be scheduled on M–W, T–Th, or F–S so instructors with Friday–Saturday availability can receive classes. Minor subjects use 1 hour 30 minutes and may fill lightly loaded M–W or T–Th days before using F–S. First Year must cover all three day pairs. Each instructor may handle no more than three schedules per day pair, instructor priorities and unit limits are followed, Years 1–2 receive laboratory priority, and all section, instructor, room, day, and time conflicts are checked before saving.</div>

    <form id="scheduleForm" method="POST" action="{{ route('dean.schedules.store') }}">
        @csrf
        <div class="form-grid">
            <div><label>Program / Degree</label><input class="input" value="{{ $course }}" disabled></div>
            <div>
                <label for="academic_year">Academic Year</label>
                <select id="academic_year" class="input" name="academic_year" required>
                    @forelse($academicYears as $academicYear)<option value="{{ $academicYear }}" @selected($defaultAcademicYear === $academicYear)>{{ $academicYear }}</option>@empty<option value="">No academic years available</option>@endforelse
                </select>
                @error('academic_year')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="semester">Semester</label>
                <select id="semester" class="input" name="semester">@foreach(['1st','2nd','Summer'] as $semester)<option @selected(old('semester') === $semester)>{{ $semester }}</option>@endforeach</select>
                @error('semester')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="curriculum">Curriculum</label>
                <select id="curriculum" class="input" name="curriculum" required>
                    @foreach(['New' => 'New Curriculum', 'Old' => 'Old Curriculum'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('curriculum', 'New') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('curriculum')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="year_level">Year Level</label>
                <select id="year_level" class="input" name="year_level">
                    <option value="all" @selected($defaultYearLevel === 'all')>All Year Levels</option>
                    @for($level=1;$level<=4;$level++)<option value="{{ $level }}" @selected($defaultYearLevel === (string) $level)>Year {{ $level }}</option>@endfor
                </select>
                @error('year_level')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div id="sectionCountField">
                <label id="sectionCountLabel" for="number_of_sections">Number of Existing Sections to Use</label>
                <input id="number_of_sections" class="input" type="number" min="1" max="20" name="number_of_sections" value="{{ old('number_of_sections', 1) }}" required>
                @error('number_of_sections')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="section-preview">
                <div class="section-preview-head"><div><h3>Sections the Generator Will Use</h3><p>Automatically selected from the existing Sections list.</p></div><span id="sectionCountStatus" class="section-count-status">0 sections available</span></div>
                <div id="sectionList" class="section-list">
                    @foreach($sections as $section)
                        <div class="section-item" data-academic-year="{{ $section->academic_year }}" data-year-level="{{ $section->year_level }}" data-name="{{ $section->name }}"><strong>{{ $section->name }}</strong><small>Year {{ $section->year_level }} · {{ $section->academic_year }}</small></div>
                    @endforeach
                    <p id="sectionEmpty" class="section-empty" hidden>No existing sections match this academic year and year level. <a href="{{ route('dean.sections.create') }}" style="font-weight:800;color:var(--primary)">Add sections first.</a></p>
                </div>
            </div>
        </div>

        <div class="form-actions"><button id="generateButton" class="button" type="submit" @disabled($sections->isEmpty())>Create Schedule</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const academicYear = document.getElementById('academic_year');
        const yearLevel = document.getElementById('year_level');
        const countInput = document.getElementById('number_of_sections');
        const countField = document.getElementById('sectionCountField');
        const items = [...document.querySelectorAll('.section-item')];
        const empty = document.getElementById('sectionEmpty');
        const status = document.getElementById('sectionCountStatus');
        const generateButton = document.getElementById('generateButton');

        function updatePreview() {
            const matching = items
                .filter(item => item.dataset.academicYear === academicYear.value
                    && (yearLevel.value === 'all' || item.dataset.yearLevel === yearLevel.value))
                .sort((left, right) => {
                    const yearComparison = Number(left.dataset.yearLevel) - Number(right.dataset.yearLevel);
                    return yearComparison !== 0
                        ? yearComparison
                        : left.dataset.name.localeCompare(right.dataset.name, undefined, { numeric:true, sensitivity:'base' });
                });
            const requested = Math.max(1, Number.parseInt(countInput.value || '1', 10));
            const allYearLevels = yearLevel.value === 'all';
            const selected = allYearLevels ? matching : matching.slice(0, requested);

            items.forEach(item => { item.hidden = true; item.classList.remove('selected'); });
            matching.forEach(item => {
                item.hidden = false;
                item.classList.toggle('selected', selected.includes(item));
            });

            countField.hidden = allYearLevels;
            countInput.disabled = allYearLevels;
            countInput.required = !allYearLevels;
            countInput.max = Math.max(1, matching.length);
            empty.hidden = matching.length > 0;
            generateButton.disabled = matching.length === 0 || (!allYearLevels && requested > matching.length);
            status.textContent = matching.length === 0
                ? '0 sections available'
                : `Using ${selected.length} of ${matching.length} sections`;
        }

        academicYear.addEventListener('change', updatePreview);
        yearLevel.addEventListener('change', updatePreview);
        countInput.addEventListener('input', updatePreview);
        updatePreview();
    })();
</script>
@endpush
